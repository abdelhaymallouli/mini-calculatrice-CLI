#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../src/ConverterInterface.php';
require __DIR__ . '/../src/BitwiseOperationsTrait.php';
require __DIR__ . '/../src/Converter.php';
require __DIR__ . '/../vendor/autoload.php';


use App\Converter;

$shortopts  = "n:f:o:"; // -n number, -f input json file, -o output json file
$longopts  = [
    "op:",       // convert | and | or | xor | not | shl | shr | mask | flags
    "help",
    "mask:",
    "flags:",    // JSON string or file
    "num:",      // alternate to -n
];
$options = getopt($shortopts, $longopts);

// helper for printing
$stderr = fn(string $s) => fwrite(STDERR, $s . PHP_EOL);

// help
if (isset($options['help']) || (php_sapi_name() !== 'cli')) {
    echo "Usage: php bin/convert.php -n <number> --op <convert|and|or|xor|not|shl|shr|mask|flags> [options]\n";
    echo "Options:\n";
    echo "  -n, --num <number>        number to operate on\n";
    echo "  -f <file>                 read JSON input (overrides -n if contains 'number')\n";
    echo "  -o <file>                 write JSON output\n";
    echo "  --op <operation>          operation to run (convert, and, or, xor, not, shl, shr, mask, flags)\n";
    echo "  --mask <int>              mask value for mask operation\n";
    echo "  --flags <json|file>       flags mapping as JSON object name=>bit (e.g. '{\"r\":4,\"w\":2}') or path to file\n";
    exit(0);
}

try {
    // read input number: from -f JSON or -n
    $number = null;
    if (isset($options['f'])) {
        $json = file_get_contents($options['f']);
        if ($json === false) throw new RuntimeException("Cannot read file {$options['f']}");
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (isset($data['number'])) $number = (int)$data['number'];
    }

    if ($number === null) {
        if (isset($options['n'])) $number = (int)$options['n'];
        if (isset($options['num'])) $number = (int)$options['num'];
    }

    if ($number === null) throw new InvalidArgumentException("No number provided. Use -n or -f with {\"number\":...}");

    $op = $options['op'] ?? 'convert';
    $converter = new Converter($number);
    $result = [];

    switch ($op) {
        case 'convert':
            $result = [
                'decimal' => $converter->toDecimal(),
                'binary'  => $converter->toBinary(),
                'hex'     => $converter->toHex(),
                'int'     => $converter->toInt(),
            ];
            break;

        case 'and':
        case 'or':
        case 'xor':
            if (!isset($options['mask'])) throw new InvalidArgumentException("Operation {$op} requires --mask");
            $other = (int)$options['mask'];
            $value = match ($op) {
                'and' => $converter->andWith($other),
                'or'  => $converter->orWith($other),
                'xor' => $converter->xorWith($other),
            };
            $result = [
                'op' => $op,
                'left' => $converter->toInt(),
                'right' => $other,
                'result' => $value,
                'binary' => decbin($value),
                'hex' => strtoupper(dechex($value)),
            ];
            break;

        case 'not':
            $val = $converter->not();
            $result = [
                'op' => 'not',
                'input' => $converter->toInt(),
                'result' => $val,
            ];
            break;

        case 'shl':
        case 'shr':
            if (!isset($options['mask'])) throw new InvalidArgumentException("Operation {$op} requires --mask (number of bits)");
            $bits = (int)$options['mask'];
            $res = $op === 'shl' ? $converter->shiftLeft($bits) : $converter->shiftRight($bits);
            $result = [
                'op' => $op,
                'input' => $converter->toInt(),
                'bits' => $bits,
                'result' => $res,
                'binary' => decbin($res),
            ];
            break;

        case 'mask':
            if (!isset($options['mask'])) throw new InvalidArgumentException("mask requires --mask");
            $maskVal = (int)$options['mask'];
            $res = $converter->applyMask($maskVal);
            $result = [
                'input' => $converter->toInt(),
                'mask' => $maskVal,
                'result' => $res,
                'binary' => decbin($res),
            ];
            break;

        case 'flags':
            if (!isset($options['flags'])) throw new InvalidArgumentException("flags requires --flags (json or file)");
            $flagsArg = $options['flags'];
            // if file exists, read, else parse JSON
            if (is_file($flagsArg)) {
                $flagsJson = file_get_contents($flagsArg);
                if ($flagsJson === false) throw new RuntimeException("Cannot read flags file");
                $flagsMap = json_decode($flagsJson, true, 512, JSON_THROW_ON_ERROR);
            } else {
                $flagsMap = json_decode($flagsArg, true, 512, JSON_THROW_ON_ERROR);
            }
            if (!is_array($flagsMap)) throw new InvalidArgumentException("flags must be a JSON object name=>bit");
            $active = $converter->flagsFromMap($flagsMap);
            $result = [
                'input' => $converter->toInt(),
                'active_flags' => $active,
            ];
            break;

        default:
            throw new InvalidArgumentException("Unknown operation: {$op}");
    }

    $output = json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    if (isset($options['o'])) {
        $ok = file_put_contents($options['o'], $output);
        if ($ok === false) throw new RuntimeException("Cannot write to {$options['o']}");
        echo "Wrote {$options['o']}\n";
    } else {
        echo $output . PHP_EOL;
    }

} catch (Throwable $e) {
    $stderr("Error: " . $e->getMessage());
    exit(1);
}