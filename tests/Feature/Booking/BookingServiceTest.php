<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use App\Modules\Booking\BookingService;
use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\BookingNotCancellableException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\ItemCatalogService;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function existing(Item $item, string $start, string $end, string $status = 'pending', string $date = self::DATE, ?User $owner = null): Booking
    {
        return Booking::create([
            'user_id' => $owner->id ?? $this->member->id,
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

    private function assertCancelRejected(Booking $booking): void
    {
        try {
            $this->bookings->cancel($booking->id);
            $this->fail('The booking was cancelled but should not be cancellable.');
        } catch (BookingNotCancellableException) {
            // Expected: FR-08 denies the cancel.
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

    public function test_list_for_member_returns_only_that_members_bookings(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create(['name' => 'Pitch A']);
        $otherMember = User::factory()->create();
        $mine = $this->existing($item, '19:00:00', '21:00:00');
        $this->existing($item, '16:00:00', '17:00:00', 'pending', self::DATE, $otherMember);

        $list = $this->bookings->listForMember($this->member->id);

        $this->assertCount(1, $list);
        $this->assertSame($mine->id, $list[0]['id']);
    }

    public function test_list_for_member_returns_item_name_date_times_status_and_can_cancel(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create(['name' => 'Pitch A']);
        $booking = $this->existing($item, '19:00:00', '21:30:00');

        $this->assertSame(
            [[
                'id' => $booking->id,
                'item_name' => 'Pitch A',
                'date' => self::DATE,
                'start' => '19:00',
                'end' => '21:30',
                'status' => 'pending',
                'can_cancel' => true,
            ]],
            $this->bookings->listForMember($this->member->id)
        );
    }

    public function test_list_for_member_shows_a_deactivated_items_name(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create(['name' => 'Old Racket', 'type' => 'equipment']);
        $this->existing($item, '19:00:00', '21:00:00');
        $item->update(['is_active' => false]);

        $list = $this->bookings->listForMember($this->member->id);

        $this->assertSame('Old Racket', $list[0]['item_name']);
    }

    public function test_list_for_member_orders_newest_date_first(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create();
        $later = $this->existing($item, '19:00:00', '21:00:00', 'pending', '2026-11-22');
        $earlier = $this->existing($item, '19:00:00', '21:00:00', 'pending', '2026-11-21');

        $list = $this->bookings->listForMember($this->member->id);

        $this->assertSame([$later->id, $earlier->id], array_column($list, 'id'));
    }

    public function test_list_for_member_marks_started_cancelled_and_rejected_bookings_as_not_cancellable(): void
    {
        Carbon::setTestNow('2026-11-20 19:00:00');

        $item = Item::factory()->create();
        $this->existing($item, '19:00:00', '21:00:00');
        $this->existing($item, '21:00:00', '22:00:00', 'cancelled');
        $this->existing($item, '06:00:00', '07:00:00', 'rejected');

        $list = $this->bookings->listForMember($this->member->id);

        $this->assertCount(3, $list);

        foreach ($list as $row) {
            $this->assertFalse($row['can_cancel']);
        }
    }

    public function test_cancel_sets_a_pending_booking_to_cancelled(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00');

        $this->bookings->cancel($booking->id);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->decided_at);
    }

    public function test_cancel_sets_an_approved_booking_to_cancelled_and_keeps_decided_at(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $decidedAt = Carbon::parse('2026-11-14 10:00:00');
        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00', 'approved');
        $booking->update(['decided_at' => $decidedAt]);

        $this->bookings->cancel($booking->id);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertTrue($decidedAt->equalTo($booking->fresh()->decided_at));
    }

    public function test_cancel_rejects_an_already_cancelled_booking(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00', 'cancelled');

        $this->assertCancelRejected($booking);
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_cancel_rejects_a_rejected_booking(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00', 'rejected');

        $this->assertCancelRejected($booking);
        $this->assertSame('rejected', $booking->fresh()->status);
    }

    public function test_cancel_at_18_59_is_allowed_for_a_booking_starting_at_19_00(): void
    {
        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00');

        Carbon::setTestNow('2026-11-20 18:59:00');

        $this->bookings->cancel($booking->id);

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_cancel_at_19_00_is_denied_for_a_booking_starting_at_19_00(): void
    {
        $booking = $this->existing(Item::factory()->create(), '19:00:00', '21:00:00');

        Carbon::setTestNow('2026-11-20 19:00:00');

        $this->assertCancelRejected($booking);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_a_cancelled_booking_no_longer_blocks_the_slot(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create();
        $booking = $this->existing($item, '19:00:00', '21:00:00');

        $this->bookings->cancel($booking->id);

        $new = $this->bookings->createRequest($this->member->id, $item->id, self::DATE, '19:00', '21:00');

        $this->assertSame('pending', $new->status);
        $this->assertDatabaseCount('bookings', 2);
    }
}
