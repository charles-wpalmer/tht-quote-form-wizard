<?php

namespace App\Domains\Journey;

final class ValidationResult
{
    /**
     * @param  string[]  $errors
     */
    private function __construct(
        public readonly bool $passed,
        public readonly array $errors,
    ) {}

    public static function passed(): self
    {
        return new self(true, []);
    }

    /**
     * @param  string[]  $errors
     */
    public static function failed(array $errors): self
    {
        return new self(false, $errors);
    }
}
