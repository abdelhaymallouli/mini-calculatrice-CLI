<?php
declare(strict_types=1);

namespace App;

class Calculator
{
    private int $a;
    private int $b;

    public function __construct(int $a, int $b)
    {
        if ($a < 0 || $b < 0) {
            throw new \InvalidArgumentException('Inputs must be positive integers.');
        }
        $this->a = $a;
        $this->b = $b;
    }

    public function getResults(): array
    {
        $converterA = new Converter($this->a);
        $converterB = new Converter($this->b);

        $and = $converterA->andWith($this->b);
        $or = $converterA->orWith($this->b);
        $xor = $converterA->xorWith($this->b);
        $not = $converterA->not();

        // Compute max_bits for positive values
        $positives = [$this->a, $this->b, $and, $or, $xor];
        $max_bits = 0;
        foreach ($positives as $v) {
            $len = strlen(decbin($v));
            if ($len > $max_bits) {
                $max_bits = $len;
            }
        }

        $larger_bits = max(8, $max_bits);

        $a_bin = str_pad(decbin($this->a), $max_bits, '0', STR_PAD_LEFT);
        $b_bin = str_pad(decbin($this->b), $max_bits, '0', STR_PAD_LEFT);
        $and_bin = str_pad(decbin($and), $max_bits, '0', STR_PAD_LEFT);
        $or_bin = str_pad(decbin($or), $max_bits, '0', STR_PAD_LEFT);
        $xor_bin = str_pad(decbin($xor), $max_bits, '0', STR_PAD_LEFT);
        $not_bin = '…' . str_pad(decbin($not & ((1 << $larger_bits) - 1)), $larger_bits, '1', STR_PAD_LEFT);

        return [
            'a' => $this->a,
            'b' => $this->b,
            'and' => $and,
            'or' => $or,
            'xor' => $xor,
            'not_a' => $not,
            'a_bin' => $a_bin,
            'b_bin' => $b_bin,
            'and_bin' => $and_bin,
            'or_bin' => $or_bin,
            'xor_bin' => $xor_bin,
            'not_a_bin' => $not_bin,
            'a_hex' => $converterA->toHex(),
            'b_hex' => $converterB->toHex(),
        ];
    }
}