<?php

namespace App\Exceptions;

use App\Models\Clinic;
use RuntimeException;

/**
 * Thrown while booking when the doctor's clinic has issued all of its tokens
 * for the requested date. Throwing inside the booking transaction also rolls
 * back anything consumed alongside it, such as a free-slot offer.
 */
class ClinicTokensUnavailableException extends RuntimeException
{
    public function __construct(public readonly Clinic $clinic)
    {
        parent::__construct('No tokens are available at ' . $clinic->name . ' on the selected date.');
    }
}
