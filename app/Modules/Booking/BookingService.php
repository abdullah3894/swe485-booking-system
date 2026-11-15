<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLock;
use App\Modules\Catalog\ItemCatalogService;
use Illuminate\Support\Facades\DB;

/**
 * Booking rules: availability and the conflict-checked request (A-BK).
 *
 * Touches only the bookings and booking_locks tables; Catalog is reached
 * through ItemCatalogService, never through its table.
 */
class BookingService
{
    public function __construct(private readonly ItemCatalogService $catalog)
    {
    }

    /**
     * The booked ranges for an item and date: start and end only, no user data (FR-16).
     *
     * @return list<array{start: string, end: string}>
     */
    public function bookedRangesFor(int $itemId, string $date): array
    {
        return Booking::query()
            ->where('item_id', $itemId)
            ->where('date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time'])
            ->map(fn (Booking $booking): array => [
                'start' => substr((string) $booking->start_time, 0, 5),
                'end' => substr((string) $booking->end_time, 0, 5),
            ])
            ->all();
    }

    /**
     * Create a pending booking request, or reject it when the item is inactive
     * (FR-14) or the interval overlaps an existing booking (FR-04).
     *
     * The lock row is inserted before the transaction and locked with FOR UPDATE
     * before the overlap query, so simultaneous requests for the same item and day
     * are serialised (ADR-003).
     */
    public function createRequest(int $userId, int $itemId, string $date, string $start, string $end): Booking
    {
        if (! $this->catalog->isActive($itemId)) {
            throw new ItemInactiveException();
        }

        DB::table('booking_locks')->insertOrIgnore([
            'item_id' => $itemId,
            'date' => $date,
        ]);

        $start = $this->withSeconds($start);
        $end = $this->withSeconds($end);

        return DB::transaction(function () use ($userId, $itemId, $date, $start, $end): Booking {
            BookingLock::query()
                ->where('item_id', $itemId)
                ->where('date', $date)
                ->lockForUpdate()
                ->first();

            $overlaps = Booking::query()
                ->where('item_id', $itemId)
                ->where('date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($overlaps) {
                throw new BookingConflictException();
            }

            return Booking::create([
                'user_id' => $userId,
                'item_id' => $itemId,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Store times as H:i:s.
     */
    private function withSeconds(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
