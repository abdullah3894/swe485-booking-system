<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * The booking is not pending or approved, or its start time has been reached (FR-08).
 */
class BookingNotCancellableException extends RuntimeException
{
    //
}
