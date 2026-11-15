<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MyBookingsTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-11-20';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function member(string $name = 'Mia Member', string $email = 'member@example.com'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'member',
        ]);
    }

    private function supervisor(): User
    {
        return User::factory()->create([
            'name' => 'Sam Supervisor',
            'email' => 'supervisor@example.com',
            'role' => 'supervisor',
        ]);
    }

    private function booking(
        Item $item,
        User $owner,
        string $start,
        string $end,
        string $status = 'pending',
        string $date = self::DATE,
    ): Booking {
        return Booking::create([
            'user_id' => $owner->id,
            'item_id' => $item->id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_my_bookings_and_cancel(): void
    {
        $booking = $this->booking(Item::factory()->create(), $this->member(), '19:00:00', '21:00:00');

        $this->get('/bookings')->assertRedirect('/login');
        $this->post("/bookings/{$booking->id}/cancel")->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_supervisor_gets_403_on_my_bookings_and_cancel_and_nothing_changes(): void
    {
        $supervisor = $this->supervisor();
        $booking = $this->booking(Item::factory()->create(), $this->member(), '19:00:00', '21:00:00');

        $this->actingAs($supervisor)->get('/bookings')->assertForbidden();
        $this->actingAs($supervisor)->post("/bookings/{$booking->id}/cancel")->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_member_sees_their_own_bookings_with_item_date_times_and_status(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $other = $this->member('Aisha Owner', 'aisha@example.com');
        $pitch = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->booking($pitch, $member, '19:00:00', '21:00:00', 'approved');
        $this->booking($pitch, $other, '16:00:00', '17:00:00');

        $this->actingAs($member)
            ->get('/bookings')
            ->assertOk()
            ->assertSee('My bookings')
            ->assertSee('Pitch A')
            ->assertSee(self::DATE)
            ->assertSee('19:00')
            ->assertSee('21:00')
            ->assertSee('Approved')
            ->assertDontSee('16:00')
            ->assertDontSee('Aisha Owner');
    }

    public function test_a_deactivated_items_name_still_shows_on_my_bookings(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $item = Item::factory()->create(['name' => 'Old Racket', 'type' => 'equipment', 'is_active' => false]);

        $this->booking($item, $member, '19:00:00', '21:00:00');

        $this->actingAs($member)
            ->get('/bookings')
            ->assertOk()
            ->assertSee('Old Racket');
    }

    public function test_my_bookings_without_bookings_shows_the_empty_state_and_a_link_to_request_one(): void
    {
        $this->actingAs($this->member())
            ->get('/bookings')
            ->assertOk()
            ->assertSee('You have no bookings yet.')
            ->assertSee('href="/bookings/create"', false)
            ->assertDontSee('Cancel');
    }

    public function test_the_flash_message_of_a_new_request_is_shown(): void
    {
        $this->actingAs($this->member())
            ->withSession(['status' => 'Booking requested (Pending).'])
            ->get('/bookings')
            ->assertOk()
            ->assertSee('Booking requested (Pending).');
    }

    public function test_the_cancel_button_is_shown_only_for_bookings_that_can_be_cancelled(): void
    {
        Carbon::setTestNow('2026-11-20 18:00:00');

        $member = $this->member();
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $cancellable = $this->booking($item, $member, '19:00:00', '21:00:00');
        $started = $this->booking($item, $member, '16:00:00', '17:00:00');
        $rejected = $this->booking($item, $member, '08:00:00', '09:00:00', 'rejected');

        $this->actingAs($member)
            ->get('/bookings')
            ->assertOk()
            ->assertSee("/bookings/{$cancellable->id}/cancel", false)
            ->assertDontSee("/bookings/{$started->id}/cancel", false)
            ->assertDontSee("/bookings/{$rejected->id}/cancel", false);
    }

    public function test_a_member_cancels_their_pending_booking(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '19:00:00', '21:00:00');

        $this->actingAs($member)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking cancelled.');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_a_member_cancels_their_approved_booking(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '19:00:00', '21:00:00', 'approved');

        $this->actingAs($member)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking cancelled.');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_cancelling_at_18_59_is_allowed_and_at_19_00_is_denied(): void
    {
        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '19:00:00', '21:00:00');

        Carbon::setTestNow('2026-11-20 19:00:00');

        $this->actingAs($member)
            ->from('/bookings')
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('error', 'This booking can no longer be cancelled.');

        $this->assertSame('pending', $booking->fresh()->status);

        Carbon::setTestNow('2026-11-20 18:59:00');

        $this->actingAs($member)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking cancelled.');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_an_already_cancelled_booking_shows_the_message_and_stays_cancelled(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '19:00:00', '21:00:00', 'cancelled');

        $this->actingAs($member)
            ->from('/bookings')
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('error', 'This booking can no longer be cancelled.');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_a_rejected_booking_shows_the_message_and_stays_rejected(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '19:00:00', '21:00:00', 'rejected');

        $this->actingAs($member)
            ->from('/bookings')
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect('/bookings')
            ->assertSessionHas('error', 'This booking can no longer be cancelled.');

        $this->assertSame('rejected', $booking->fresh()->status);
    }

    public function test_another_members_booking_returns_403_and_is_unchanged(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $owner = $this->member('Aisha Owner', 'aisha@example.com');
        $intruder = $this->member();
        $booking = $this->booking(Item::factory()->create(), $owner, '19:00:00', '21:00:00');

        $this->actingAs($intruder)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertForbidden();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_the_slot_is_free_for_a_new_request_after_a_cancel(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $saad = $this->member('Saad Owner', 'saad@example.com');
        $khalid = $this->member('Khalid Member', 'khalid@example.com');
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $booking = $this->booking($item, $saad, '19:00:00', '21:00:00');

        $this->actingAs($khalid)
            ->from('/bookings/create')
            ->post('/bookings', [
                'item_id' => $item->id,
                'date' => self::DATE,
                'start_time' => '19:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHasErrors('start_time');

        $this->actingAs($saad)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertSessionHas('status', 'Booking cancelled.');

        $this->actingAs($khalid)
            ->post('/bookings', [
                'item_id' => $item->id,
                'date' => self::DATE,
                'start_time' => '19:00',
                'end_time' => '21:00',
            ])
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking requested (Pending).');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $khalid->id,
            'item_id' => $item->id,
            'date' => self::DATE,
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'status' => 'pending',
        ]);
    }
}
