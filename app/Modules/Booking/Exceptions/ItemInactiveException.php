<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * The item does not exist or has been deactivated (FR-14).
 */
class ItemInactiveException extends RuntimeException
{
    //
}
