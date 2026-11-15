<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use App\Modules\Booking\BookingService;
use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\ItemCatalogService;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-11-20';

    private BookingService $bookings;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookings = new BookingService(new ItemCatalogService());
        $this->member = User::factory()->create();
    }

    private function existing(Item $item, string $start, string $end, string $status = 'pending', string $date = self::DATE): Booking
    {
        return Booking::create([
            'user_id' => $this->member->id,
            'item_id' => $item->id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    private function assertConflicting(Item $item, string $start, string $end): void
    {
        try {
            $this->bookings->createRequest($this->member->id, $item->id, self::DATE, $start, $end);
            $this->fail("The request {$start}-{$end} was accepted but overlaps an existing booking.");
        } catch (BookingConflictException) {
            // Expected: FR-04 rejects the request.
        }
    }

    public function test_a_request_starting_when_an_existing_booking_ends_is_accepted(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $booking = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '17:00', '18:00');

        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_request_ending_when_an_existing_booking_starts_is_accepted(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '17:00:00', '18:00:00');

        $booking = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:00');

        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_one_minute_overlap_is_rejected_and_nothing_is_created(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $this->assertConflicting($item, '16:59', '18:00');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseMissing('bookings', ['start_time' => '16:59:00']);
    }

    public function test_an_identical_interval_is_rejected(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $this->assertConflicting($item, '16:00', '17:00');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_request_containing_an_existing_booking_is_rejected(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $this->assertConflicting($item, '15:00', '18:00');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_request_contained_in_an_existing_booking_is_rejected(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '15:00:00', '18:00');

        $this->assertConflicting($item, '16:00', '17:00');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_the_same_interval_on_a_different_item_is_accepted(): void
    {
        $bookedItem = Item::factory()->create();
        $otherItem = Item::factory()->create();
        $this->existing($bookedItem, '16:00:00', '17:00:00');

        $booking = $this->bookings->createRequest($this->member->id, $otherItem->id, self::DATE, '16:00', '17:00');

        $this->assertSame($otherItem->id, $booking->item_id);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_the_same_interval_on_a_different_date_is_accepted(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $booking = $this->bookings->createRequest($this->member->id, $item->id, '2026-11-21', '16:00', '17:00');

        $this->assertSame('2026-11-21', $booking->date);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_rejected_booking_does_not_block(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00', 'rejected');

        $booking = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:00');

        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_cancelled_booking_does_not_block(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00', 'cancelled');

        $booking = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:00');

        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_an_approved_booking_blocks(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00', 'approved');

        $this->assertConflicting($item, '16:30', '17:30');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_deactivated_item_is_rejected_and_nothing_is_created(): void
    {
        $item = Item::factory()->create(['is_active' => false]);

        try {
            $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:00');
            $this->fail('The request for a deactivated item was accepted.');
        } catch (ItemInactiveException) {
            // Expected: FR-14 rejects the request.
        }

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_locks', 0);
    }

    public function test_an_unknown_item_is_rejected_and_nothing_is_created(): void
    {
        try {
            $this->bookings->createRequest($this->member->id, 9999, self::DATE, '16:00', '17:00');
            $this->fail('The request for an unknown item was accepted.');
        } catch (ItemInactiveException) {
            // Expected: Catalog reports no active item.
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_created_request_is_pending_and_stores_times_with_seconds(): void
    {
        $item = Item::factory()->create();

        $booking = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:30');

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame($this->member->id, $booking->fresh()->user_id);
        $this->assertSame($item->id, $booking->fresh()->item_id);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'date' => self::DATE,
            'start_time' => '16:00:00',
            'end_time' => '17:30:00',
            'status' => 'pending',
        ]);
    }

    public function test_one_lock_row_serves_two_bookings_on_the_same_item_and_date(): void
    {
        $item = Item::factory()->create();

        $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '16:00', '17:00');
        $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '17:00', '18:00');

        $this->assertDatabaseCount('bookings', 2);
        $this->assertDatabaseCount('booking_locks', 1);
        $this->assertDatabaseHas('booking_locks', [
            'item_id' => $item->id,
            'date' => self::DATE,
        ]);
    }

    public function test_a_conflict_leaves_the_single_lock_row_in_place(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $this->assertConflicting($item, '16:30', '17:30');

        $this->assertDatabaseCount('booking_locks', 1);
    }

    public function test_booked_ranges_lists_pending_and_approved_ranges_ordered_by_start(): void
    {
        $item = Item::factory()->create();
        $otherItem = Item::factory()->create();
        $otherMember = User::factory()->create();

        $this->existing($item, '18:00:00', '19:00:00', 'pending');
        $this->existing($item, '08:00:00', '09:00:00', 'approved');
        $this->existing($item, '10:00:00', '11:00:00', 'rejected');
        $this->existing($item, '12:00:00', '13:00:00', 'cancelled');
        Booking::create([
            'user_id' => $otherMember->id,
            'item_id' => $otherItem->id,
            'date' => self::DATE,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'pending',
        ]);

        $this->assertSame(
            [
                ['start' => '08:00', 'end' => '09:00'],
                ['start' => '18:00', 'end' => '19:00'],
            ],
            $this->bookings->bookedRangesFor($item->id, self::DATE)
        );
    }

    public function test_booked_ranges_returns_start_and_end_only(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $ranges = $this->bookings->bookedRangesFor($item->id, self::DATE);

        $this->assertCount(1, $ranges);
        $this->assertSame(['start', 'end'], array_keys($ranges[0]));
    }

    public function test_booked_ranges_is_empty_for_another_date(): void
    {
        $item = Item::factory()->create();
        $this->existing($item, '16:00:00', '17:00:00');

        $this->assertSame([], $this->bookings->bookedRangesFor($item->id, '2026-11-21'));
    }
}
