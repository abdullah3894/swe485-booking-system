<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function supervisor(string $name = 'Sam Supervisor', string $email = 'supervisor@example.com'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
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

    public function test_guest_is_redirected_to_login_from_the_dashboard_and_decision(): void
    {
        $booking = $this->booking(Item::factory()->create(), $this->member(), '16:00:00', '17:00:00');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->post("/dashboard/bookings/{$booking->id}/decision", ['decision' => 'approve'])
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_member_gets_403_on_the_dashboard_and_decision_and_nothing_changes(): void
    {
        $member = $this->member();
        $booking = $this->booking(Item::factory()->create(), $member, '16:00:00', '17:00:00');

        $this->actingAs($member)->get('/dashboard')->assertForbidden();
        $this->actingAs($member)
            ->post("/dashboard/bookings/{$booking->id}/decision", ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_the_dashboard_shows_pending_requests_the_day_schedule_and_availability(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $khalid = $this->member('Khalid Member', 'khalid@example.com');
        $pitch = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $pending = $this->booking($pitch, $khalid, '16:00:00', '17:00:00');
        $this->booking($pitch, $khalid, '18:00:00', '19:00:00', 'approved');

        $this->actingAs($this->supervisor())
            ->get('/dashboard?date='.self::DATE)
            ->assertOk()
            ->assertSee('Pending requests')
            ->assertSee('Khalid Member')
            ->assertSee('Pitch A')
            ->assertSee("/dashboard/bookings/{$pending->id}/decision", false)
            ->assertSee('Schedule for '.self::DATE)
            ->assertSee('Approved')
            ->assertSee('Availability for '.self::DATE)
            ->assertSee('06:00-16:00')
            ->assertSee('17:00-18:00')
            ->assertSee('19:00-23:00');
    }

    public function test_the_dashboard_defaults_to_today(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $this->actingAs($this->supervisor())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Schedule for 2026-11-15')
            ->assertSee('Availability for 2026-11-15');
    }

    public function test_the_dashboard_shows_empty_states_when_there_are_no_bookings(): void
    {
        Item::factory()->create(['name' => 'Pitch A']);

        $this->actingAs($this->supervisor())
            ->get('/dashboard?date='.self::DATE)
            ->assertOk()
            ->assertSee('No pending requests.')
            ->assertSee('No bookings on this day.')
            ->assertSee('06:00-23:00');
    }

    public function test_the_dashboard_shows_an_empty_state_when_there_are_no_active_items(): void
    {
        Item::factory()->create(['is_active' => false]);

        $this->actingAs($this->supervisor())
            ->get('/dashboard?date='.self::DATE)
            ->assertOk()
            ->assertSee('No active items.');
    }

    public function test_the_dashboard_rejects_an_invalid_date(): void
    {
        $this->actingAs($this->supervisor())
            ->get('/dashboard?date=20-11-2026')
            ->assertSessionHasErrors('date');
    }

    public function test_a_supervisor_approves_a_pending_booking_and_keeps_the_date(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $booking = $this->booking(Item::factory()->create(), $this->member(), '16:00:00', '17:00:00');

        $this->actingAs($this->supervisor())
            ->post("/dashboard/bookings/{$booking->id}/decision", [
                'decision' => 'approve',
                'date' => self::DATE,
            ])
            ->assertRedirect('/dashboard?date='.self::DATE)
            ->assertSessionHas('status', 'Booking approved.');

        $this->assertSame('approved', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->decided_at);
    }

    public function test_a_supervisor_rejects_a_pending_booking_and_keeps_the_date(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $booking = $this->booking(Item::factory()->create(), $this->member(), '16:00:00', '17:00:00');

        $this->actingAs($this->supervisor())
            ->post("/dashboard/bookings/{$booking->id}/decision", [
                'decision' => 'reject',
                'date' => self::DATE,
            ])
            ->assertRedirect('/dashboard?date='.self::DATE)
            ->assertSessionHas('status', 'Booking rejected.');

        $this->assertSame('rejected', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->decided_at);
    }

    public function test_a_decision_on_an_already_decided_booking_shows_the_message_and_changes_nothing(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $member = $this->member();
        $supervisor = $this->supervisor();

        foreach (['approved', 'rejected', 'cancelled'] as $status) {
            $booking = $this->booking(Item::factory()->create(), $member, '16:00:00', '17:00:00', $status);

            foreach (['approve', 'reject'] as $decision) {
                $this->actingAs($supervisor)
                    ->post("/dashboard/bookings/{$booking->id}/decision", [
                        'decision' => $decision,
                        'date' => self::DATE,
                    ])
                    ->assertRedirect('/dashboard?date='.self::DATE)
                    ->assertSessionHas('error', 'This booking was already handled.');
            }

            $this->assertSame($status, $booking->fresh()->status);
            $this->assertNull($booking->fresh()->decided_at);
        }
    }

    public function test_approving_a_booking_on_a_deactivated_item_shows_the_uc_03_message_and_keeps_it_pending(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create(['is_active' => false]);
        $booking = $this->booking($item, $this->member(), '16:00:00', '17:00:00');

        $this->actingAs($this->supervisor())
            ->post("/dashboard/bookings/{$booking->id}/decision", [
                'decision' => 'approve',
                'date' => self::DATE,
            ])
            ->assertRedirect('/dashboard?date='.self::DATE)
            ->assertSessionHas('error', 'This item is deactivated and cannot be booked');

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->decided_at);
    }

    public function test_rejecting_a_booking_on_a_deactivated_item_is_allowed(): void
    {
        Carbon::setTestNow('2026-11-15 09:00:00');

        $item = Item::factory()->create(['is_active' => false]);
        $booking = $this->booking($item, $this->member(), '16:00:00', '17:00:00');

        $this->actingAs($this->supervisor())
            ->post("/dashboard/bookings/{$booking->id}/decision", [
                'decision' => 'reject',
                'date' => self::DATE,
            ])
            ->assertRedirect('/dashboard?date='.self::DATE)
            ->assertSessionHas('status', 'Booking rejected.');

        $this->assertSame('rejected', $booking->fresh()->status);
    }

    public function test_an_unknown_decision_is_rejected(): void
    {
        $booking = $this->booking(Item::factory()->create(), $this->member(), '16:00:00', '17:00:00');

        $this->actingAs($this->supervisor())
            ->from('/dashboard')
            ->post("/dashboard/bookings/{$booking->id}/decision", ['decision' => 'maybe'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_the_flash_messages_are_shown_on_the_dashboard(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->withSession(['status' => 'Booking approved.'])
            ->get('/dashboard?date='.self::DATE)
            ->assertOk()
            ->assertSee('Booking approved.');

        $this->actingAs($supervisor)
            ->withSession(['error' => 'This booking was already handled.'])
            ->get('/dashboard?date='.self::DATE)
            ->assertOk()
            ->assertSee('This booking was already handled.');
    }
}
