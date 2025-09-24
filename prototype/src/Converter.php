<?php
declare(strict_types=1);

namespace App;

class Converter implements ConverterInterface
{
    use BitwiseOperationsTrait;

    private int $number;

    public function __construct(int $number)
    {
        $this->number = $number;
    }

    public function toDecimal(): string
    {
        return (string)$this->number;
    }

    public function toBinary(): string
    {
        return decbin($this->number);
    }

    public function toHex(): string
    {
        return strtoupper(dechex($this->number));
    }

    public static function fromBinary(string $bin): self
    {
        $num = bindec($bin);
        return new self((int)$num);
    }

    public static function fromHex(string $hex): self
    {
        $num = hexdec($hex);
        return new self((int)$num);
    }

    // Expose the raw int when needed
    public function toInt(): int
    {
        return $this->number;
    }
}