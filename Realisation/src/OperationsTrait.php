<?php 
declare(strict_types=1);

namespace App;

trait OperationsTrait
{
    public function andWith(int $other): int 
    {
        return $this ->number & $other;
    }

    public function orWith(int $other): int 
    {
        return $this->number | $other;
    }

    public function xorWith(int $other): int
    {
        return $this->number ^ $other;
    }

    public function not(): int
    {
        return ~$this->number;
    }

    public function shiftLeft(int $bits): int 
    {
        return $this->number << $bits; 
    }

    public function shiftRight(int $bits): int
    {
        return $this->number >> $bits;
    }

        /**
     * Apply mask and return masked value.
     */
    public function applyMask(int $mask): int
    {
        return $this->number & $mask;
    }

    /**
     * Given flags map (name => bit), return array of active flag names.
     * $flagsMap example: ['read' => 0b100, 'write' => 0b010, 'exec' => 0b001]
     */
    public function flagsFromMap(array $flagsMap): array
    {
        $active = [];
        foreach ($flagsMap as $name => $bit) {
            if (($this->number & (int)$bit) !== 0) {
                $active[] = $name;
            }
        }
        return $active;
    }    
}
?>