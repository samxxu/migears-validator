<?php

declare(strict_types=1);

namespace MiGears\Validator;

interface ValidatorInterface
{
    /** Validate a value, returns true if valid. */
    public function validate(mixed $value): bool;

    /**
     * Get the error code (rule name) for this validator.
     *
     * Used as the i18n translation key, e.g. "required", "minLength", "email".
     */
    public function getErrorCode(): string;

    /**
     * Get parameters associated with the validation failure.
     *
     * These parameters are used for i18n message interpolation,
     * e.g. ['min' => 3, 'max' => 20].
     *
     * @return array<string, mixed>
     */
    public function getErrorParams(): array;
}
