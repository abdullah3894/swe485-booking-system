<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * The booking is no longer pending, so a decision cannot change it (FR-05, UC-03).
 */
class BookingAlreadyDecidedException extends RuntimeException
{
    //
}
