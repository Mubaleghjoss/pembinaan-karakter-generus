<?php

namespace App\Exceptions;

use Exception;

/**
 * Abstract base class untuk semua business exceptions.
 *
 * Business exceptions adalah exceptions yang terjadi karena
 * business logic violations, bukan karena system errors.
 */
abstract class BusinessException extends Exception
{
    /**
     * Mendapatkan error code untuk response API.
     */
    abstract public function getErrorCode(): string;

    /**
     * Mendapatkan HTTP status code untuk response.
     */
    abstract public function getHttpStatus(): int;

    /**
     * Mendapatkan data tambahan untuk response.
     *
     * @return array<string, mixed>
     */
    public function getAdditionalData(): array
    {
        return [];
    }
}
