<?php

namespace App\Exceptions;

/**
 * Exception yang dilempar ketika kapasitas kelas sudah penuh.
 */
class ClassFullException extends BusinessException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Kapasitas kelas sudah penuh')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorCode(): string
    {
        return 'CLASS_FULL';
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpStatus(): int
    {
        return 400;
    }
}
