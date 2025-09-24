<?php
declare(strict_types=1);

namespace App;

interface ConverterInterface
{
    public function toDecimal(): string;
    public function toBinary(): string;
    public function toHex(): string;

    // factory
    public static function fromBinary(string $bin): self;
    public static function fromHex(string $hex): self;
}