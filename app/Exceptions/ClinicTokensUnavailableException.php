<?php

namespace App\Exceptions;

use App\Models\Clinic;
use RuntimeException;

/**
 * Thrown while booking when the clinic can't give out the token: all of the doctor's
 * tokens for the date are used, or the token the patient picked is taken or invalid.
 * Throwing inside the booking transaction also rolls back anything consumed
 * alongside it, such as a free-slot offer.
 */
class ClinicTokensUnavailableException extends RuntimeException
{
    public function __construct(public readonly Clinic $clinic, ?string $message = null)
    {
        parent::__construct($message ?? 'No tokens are available with this doctor at ' . $clinic->name . ' on the selected date.');
    }
}
