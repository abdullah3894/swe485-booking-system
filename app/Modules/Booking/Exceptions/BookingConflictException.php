<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * The requested interval overlaps a pending or approved booking (FR-04).
 */
class BookingConflictException extends RuntimeException
{
    //
}
