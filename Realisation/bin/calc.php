#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Calculator;

$shortopts = "f:o:";
$longopts = [
    "help",
];
$options = getopt($shortopts, $longopts);

$stderr = fn(string $s) => fwrite(STDERR, $s . PHP_EOL);

// Debugging: Output parsed options and arguments (remove after testing)
$stderr("DEBUG: argc=$argc, argv=" . json_encode($argv) . ", options=" . json_encode($options));

// Manually check for -o if getopt fails to parse it
if (empty($options['o'])) {
    $o_index = array_search('-o', $argv);
    if ($o_index !== false && isset($argv[$o_index + 1])) {
        $options['o'] = $argv[$o_index + 1];
    }
}

if (isset($options['help']) || (php_sapi_name() !== 'cli')) {
    echo "Usage: php bin/calc.php <numberA> <numberB> [-o output.json]\n";
    echo "or php bin/calc.php -f <input.json|input.txt> [-o output.json]\n";
    echo "Options:\n";
    echo "  -f <file>                 read JSON input {\"a\":number, \"b\":number} or text input (two integers, space-separated or one per line)\n";
    echo "  -o <file>                 write JSON output\n";
    echo "  --help                    show this help\n";
    echo "Performs bitwise AND, OR, XOR on two positive integers and NOT on first.\n";
    exit(0);
}

try {
    $a = null;
    $b = null;
    if (isset($options['f'])) {
        $file = $options['f'];
        if (!file_exists($file)) {
            throw new \RuntimeException("Input file '$file' does not exist");
        }
        $content = @file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file '$file'");
        }
        if (empty(trim($content))) {
            throw new \RuntimeException("Input file '$file' is empty");
        }

        // Check file extension to determine parsing method
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($extension === 'json') {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['a']) || !isset($data['b'])) {
                throw new \InvalidArgumentException("Invalid JSON in '$file': must contain 'a' and 'b' keys with integer values");
            }
            if (!is_int($data['a']) && (!is_numeric($data['a']) || (int)$data['a'] != $data['a'])) {
                throw new \InvalidArgumentException("Invalid JSON in '$file': 'a' must be an integer");
            }
            if (!is_int($data['b']) && (!is_numeric($data['b']) || (int)$data['b'] != $data['b'])) {
                throw new \InvalidArgumentException("Invalid JSON in '$file': 'b' must be an integer");
            }
            $a = (int)$data['a'];
            $b = (int)$data['b'];
        } elseif ($extension === 'txt') {
            // Parse text file: assume two integers, space-separated or one per line
            $numbers = preg_split('/[\s\n]+/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
            if (count($numbers) !== 2) {
                throw new \InvalidArgumentException("Text file '$file' must contain exactly two integers, space-separated or one per line");
            }
            if (!is_numeric($numbers[0]) || !is_numeric($numbers[1]) || (int)$numbers[0] != $numbers[0] || (int)$numbers[1] != $numbers[1]) {
                throw new \InvalidArgumentException("Text file '$file' must contain valid integers");
            }
            $a = (int)$numbers[0];
            $b = (int)$numbers[1];
        } else {
            throw new \InvalidArgumentException("Unsupported file extension for '$file'. Use .json or .txt");
        }
    } else {
// Remove the script name (first element of $argv)
array_shift($argv);
$args = array_filter(
    $argv,
    function ($arg) use ($options) {
        // skip option flags
        if (in_array($arg, ['-o', '--o', '-f', '--f'])) return false;
        // skip values of -o
        if (isset($options['o']) && $arg === $options['o']) return false;
        // skip values of -f
        if (isset($options['f']) && $arg === $options['f']) return false;
        // otherwise keep it
        return true;
    }
);
$args = array_values($args); // reindex 0,1

        if (count($args) !== 2) {
            throw new \InvalidArgumentException("Require exactly two positive integers as arguments (e.g., 'php bin/calc.php 5 3 [-o output.json]')");
        }
        if (!is_numeric($args[0]) || !is_numeric($args[1]) || (int)$args[0] != $args[0] || (int)$args[1] != $args[1]) {
            throw new \InvalidArgumentException("Arguments must be valid integers");
        }
        $a = (int)$args[0];
        $b = (int)$args[1];
    }

    $calculator = new Calculator($a, $b);
    $result = $calculator->getResults();

    $output = json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    if (isset($options['o'])) {
        $file = $options['o'];
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException("Cannot create directory '$dir'");
            }
        }
        if (@file_put_contents($file, $output) === false) {
            throw new \RuntimeException("Cannot write to '$file'");
        }
        echo "Wrote $file\n";
    } else {
        echo "Input A: {$result['a']} ({$result['a_bin']})\n";
        echo "Input B: {$result['b']} ({$result['b_bin']})\n";
        echo "A AND B: {$result['and']} ({$result['and_bin']})\n";
        echo "A OR B: {$result['or']} ({$result['or_bin']})\n";
        echo "A XOR B: {$result['xor']} ({$result['xor_bin']})\n";
        echo "NOT A: {$result['not_a']} ({$result['not_a_bin']})\n";
    }

} catch (\JsonException $e) {
    $stderr("Error: Invalid JSON in input file: " . $e->getMessage());
    exit(1);
} catch (\Throwable $e) {
    $stderr("Error: " . $e->getMessage());
    exit(1);
}