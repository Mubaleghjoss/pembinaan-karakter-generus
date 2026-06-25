<?php

namespace App\Exceptions;

/**
 * Exception yang dilempar ketika QR token sudah expired.
 */
class QrTokenExpiredException extends BusinessException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'QR token telah kadaluarsa')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorCode(): string
    {
        return 'QR_EXPIRED';
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpStatus(): int
    {
        return 400;
    }
}
