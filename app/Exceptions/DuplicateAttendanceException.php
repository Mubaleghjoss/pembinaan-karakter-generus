<?php

namespace App\Exceptions;

/**
 * Exception yang dilempar ketika presensi sudah tercatat untuk hari yang sama.
 */
class DuplicateAttendanceException extends BusinessException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Presensi sudah tercatat untuk hari ini')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorCode(): string
    {
        return 'DUPLICATE_ATTENDANCE';
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpStatus(): int
    {
        return 400;
    }
}
