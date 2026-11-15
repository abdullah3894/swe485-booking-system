<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Booking\Models\Booking;

/**
 * A-POL: the owner check for cancelling a booking (FR-08, NFR-02).
 */
class BookingPolicy
{
    /**
     * Only the member who made the booking may cancel it.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id;
    }
}
