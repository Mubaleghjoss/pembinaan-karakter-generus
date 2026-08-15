<?php

namespace App\Services;

class PamongAssignmentVersionConflict extends \RuntimeException
{
    public function __construct(public readonly string $currentVersion)
    {
        parent::__construct('Data Binaan Pamong telah berubah sejak papan dibuka.');
    }
}
