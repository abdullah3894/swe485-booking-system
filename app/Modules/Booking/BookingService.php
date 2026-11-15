<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\BookingNotCancellableException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLock;
use App\Modules\Catalog\ItemCatalogService;
use Illuminate\Support\Carbon;
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
     * The member's own bookings, newest date first (FR-10).
     *
     * Item names come from Catalog's service, so a deactivated item still shows
     * its name; the items table is never joined or queried here.
     *
     * @return list<array{id: int, item_name: string, date: string, start: string, end: string, status: string, can_cancel: bool}>
     */
    public function listForMember(int $userId): array
    {
        $bookings = Booking::query()
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        $items = $this->catalog->getByIds(
            $bookings->pluck('item_id')->unique()->values()->all()
        );

        return $bookings
            ->map(fn (Booking $booking): array => [
                'id' => (int) $booking->id,
                'item_name' => isset($items[$booking->item_id]) ? $items[$booking->item_id]->name : '',
                'date' => (string) $booking->date,
                'start' => substr((string) $booking->start_time, 0, 5),
                'end' => substr((string) $booking->end_time, 0, 5),
                'status' => $booking->status,
                'can_cancel' => $this->canCancel($booking),
            ])
            ->all();
    }

    /**
     * Cancel a pending or approved booking strictly before it starts (FR-08).
     *
     * The owner check lives in BookingPolicy (A-POL); this only enforces the state
     * and the deadline. decided_at is left untouched.
     */
    public function cancel(int $bookingId): void
    {
        $booking = Booking::query()->findOrFail($bookingId);

        if (! $this->canCancel($booking)) {
            throw new BookingNotCancellableException();
        }

        $booking->update(['status' => 'cancelled']);
    }

    /**
     * Pending or approved, and now() strictly before date + start_time: cancelling
     * a 19:00 booking at 18:59 is allowed, at 19:00 it is denied (FR-08).
     */
    private function canCancel(Booking $booking): bool
    {
        if (! in_array($booking->status, ['pending', 'approved'], true)) {
            return false;
        }

        $startsAt = Carbon::parse(
            $booking->date.' '.$booking->start_time,
            config('app.timezone'),
        );

        return now()->lt($startsAt);
    }

    /**
     * Store times as H:i:s.
     */
    private function withSeconds(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
