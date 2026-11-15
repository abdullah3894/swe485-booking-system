<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Exceptions\BookingAlreadyDecidedException;
use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\BookingNotCancellableException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLock;
use App\Modules\Catalog\ItemCatalogService;
use App\Modules\Identity\IdentityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Booking rules: availability, the conflict-checked request and the supervisor
 * decision (A-BK).
 *
 * Touches only the bookings and booking_locks tables; Catalog and Identity are
 * reached through their services, never through their tables.
 */
class BookingService
{
    /**
     * The operating hours a day's free ranges are calculated inside.
     */
    private const OPENS_AT = '06:00';

    private const CLOSES_AT = '23:00';

    public function __construct(
        private readonly ItemCatalogService $catalog,
        private readonly IdentityService $identity,
    ) {
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
     * The Supervisor dashboard data: the pending requests, the chosen day's
     * bookings and each active item's booked and free ranges (FR-11, UC-03).
     *
     * Built from separate queries, never a join: read bookings, then ask Catalog
     * for the item names and Identity for the member names (A-BK, §2.4).
     *
     * @return array{
     *     pending: list<array<string, mixed>>,
     *     day: list<array<string, mixed>>,
     *     availability: list<array<string, mixed>>
     * }
     */
    public function supervisorSchedule(string $date): array
    {
        $pending = Booking::query()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $day = Booking::query()
            ->where('date', $date)
            ->orderBy('item_id')
            ->orderBy('start_time')
            ->get();

        $items = $this->catalog->getByIds(
            $pending->pluck('item_id')
                ->merge($day->pluck('item_id'))
                ->unique()
                ->values()
                ->all()
        );

        $memberNames = $this->identity->namesFor(
            $pending->pluck('user_id')
                ->merge($day->pluck('user_id'))
                ->unique()
                ->values()
                ->all()
        );

        $bookedByItem = [];

        foreach ($day as $booking) {
            if (! in_array($booking->status, ['pending', 'approved'], true)) {
                continue;
            }

            $bookedByItem[(int) $booking->item_id][] = [
                'start' => substr((string) $booking->start_time, 0, 5),
                'end' => substr((string) $booking->end_time, 0, 5),
            ];
        }

        $availability = [];

        foreach ($this->catalog->listActive() as $item) {
            $booked = $bookedByItem[(int) $item->id] ?? [];

            $availability[] = [
                'item_id' => (int) $item->id,
                'item_name' => $item->name,
                'booked' => $booked,
                'free' => $this->freeRanges($booked),
            ];
        }

        return [
            'pending' => $this->scheduleRows($pending, $items, $memberNames),
            'day' => $this->scheduleRows($day, $items, $memberNames),
            'availability' => $availability,
        ];
    }

    /**
     * Approve a pending booking, unless its item has been deactivated (FR-06, FR-14).
     *
     * The single conditional update means a simultaneous member cancel and this
     * decision cannot both win; the loser sees zero affected rows (UC-03).
     */
    public function approve(int $bookingId): void
    {
        $booking = Booking::query()->findOrFail($bookingId);

        if (! $this->catalog->isActive((int) $booking->item_id)) {
            throw new ItemInactiveException();
        }

        $this->decide($bookingId, 'approved');
    }

    /**
     * Reject a pending booking, even when its item has been deactivated (FR-07, FR-14).
     */
    public function reject(int $bookingId): void
    {
        $this->decide($bookingId, 'rejected');
    }

    /**
     * The conditional update that only a still-pending booking can pass.
     */
    private function decide(int $bookingId, string $status): void
    {
        $affected = Booking::query()
            ->whereKey($bookingId)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'decided_at' => now(),
            ]);

        if ($affected === 0) {
            throw new BookingAlreadyDecidedException();
        }
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

    /**
     * The dashboard row shape shared by the pending list and the day's schedule.
     *
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, \App\Modules\Catalog\Models\Item>  $items
     * @param  array<int, string>  $memberNames
     * @return list<array{id: int, item_name: string, member_name: string, date: string, start: string, end: string, status: string, decided_at: string|null}>
     */
    private function scheduleRows(Collection $bookings, array $items, array $memberNames): array
    {
        return $bookings
            ->map(fn (Booking $booking): array => [
                'id' => (int) $booking->id,
                'item_name' => isset($items[$booking->item_id]) ? $items[$booking->item_id]->name : '',
                'member_name' => $memberNames[$booking->user_id] ?? '',
                'date' => (string) $booking->date,
                'start' => substr((string) $booking->start_time, 0, 5),
                'end' => substr((string) $booking->end_time, 0, 5),
                'status' => $booking->status,
                'decided_at' => $booking->decided_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    /**
     * The gaps between the booked ranges inside the operating hours: an item with
     * one 16:00-17:00 booking is free 06:00-16:00 and 17:00-23:00.
     *
     * @param  list<array{start: string, end: string}>  $booked
     * @return list<array{start: string, end: string}>
     */
    private function freeRanges(array $booked): array
    {
        $free = [];
        $cursor = self::OPENS_AT;

        foreach ($booked as $range) {
            if ($range['start'] >= self::CLOSES_AT) {
                continue;
            }

            if ($range['start'] > $cursor) {
                $free[] = ['start' => $cursor, 'end' => $range['start']];
            }

            if ($range['end'] > $cursor) {
                $cursor = $range['end'];
            }
        }

        if ($cursor < self::CLOSES_AT) {
            $free[] = ['start' => $cursor, 'end' => self::CLOSES_AT];
        }

        return $free;
    }
}
