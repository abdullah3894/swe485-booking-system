<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestBookingTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-11-20';

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

    private function booking(Item $item, User $owner, string $start, string $end, string $status = 'pending'): Booking
    {
        return Booking::create([
            'user_id' => $owner->id,
            'item_id' => $item->id,
            'date' => self::DATE,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    private function request(Item $item, string $start, string $end): array
    {
        return [
            'item_id' => $item->id,
            'date' => self::DATE,
            'start_time' => $start,
            'end_time' => $end,
        ];
    }

    public function test_guest_is_redirected_to_login_from_both_booking_routes(): void
    {
        $item = Item::factory()->create();

        $this->get('/bookings/create')->assertRedirect('/login');
        $this->post('/bookings', $this->request($item, '16:00', '17:00'))->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_supervisor_gets_403_on_both_booking_routes_and_nothing_is_created(): void
    {
        $supervisor = $this->supervisor();
        $item = Item::factory()->create();

        $this->actingAs($supervisor)->get('/bookings/create')->assertForbidden();
        $this->actingAs($supervisor)
            ->post('/bookings', $this->request($item, '16:00', '17:00'))
            ->assertForbidden();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_member_sees_the_request_form_with_active_items_only(): void
    {
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        Item::factory()->create(['name' => 'Old Racket', 'type' => 'equipment', 'is_active' => false]);

        $this->actingAs($this->member())
            ->get('/bookings/create')
            ->assertOk()
            ->assertSee('Pitch A')
            ->assertDontSee('Old Racket')
            ->assertSee('Show availability')
            ->assertSee('Request booking')
            ->assertSee('My bookings')
            ->assertSee('Log out');
    }

    public function test_the_request_screen_shows_booked_ranges_without_names_or_emails(): void
    {
        $owner = $this->member('Aisha Owner', 'aisha@example.com');
        $viewer = $this->member('Bilal Viewer', 'bilal@example.com');
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->booking($item, $owner, '16:00:00', '17:00:00');

        $this->actingAs($viewer)
            ->get("/bookings/create?item={$item->id}&date=".self::DATE)
            ->assertOk()
            ->assertSee('16:00')
            ->assertSee('17:00')
            ->assertDontSee('Aisha Owner')
            ->assertDontSee('aisha@example.com');
    }

    public function test_the_request_screen_does_not_show_rejected_or_cancelled_ranges(): void
    {
        $owner = $this->member('Aisha Owner', 'aisha@example.com');
        $viewer = $this->member('Bilal Viewer', 'bilal@example.com');
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->booking($item, $owner, '08:00:00', '09:00:00', 'rejected');
        $this->booking($item, $owner, '10:00:00', '11:00:00', 'cancelled');

        $this->actingAs($viewer)
            ->get("/bookings/create?item={$item->id}&date=".self::DATE)
            ->assertOk()
            ->assertSee('No bookings for this item on this date.')
            ->assertDontSee('08:00')
            ->assertDontSee('10:00');
    }

    public function test_the_request_screen_without_item_or_date_shows_no_ranges(): void
    {
        $this->actingAs($this->member())
            ->get('/bookings/create')
            ->assertOk()
            ->assertDontSee('No bookings for this item on this date.');
    }

    public function test_member_creates_a_pending_booking_and_is_redirected_with_the_confirmation(): void
    {
        $member = $this->member();
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->actingAs($member)
            ->post('/bookings', $this->request($item, '16:00', '17:00'))
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking requested (Pending).');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $member->id,
            'item_id' => $item->id,
            'date' => self::DATE,
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('booking_locks', 1);
    }

    public function test_an_overlapping_request_is_rejected_with_the_conflict_message(): void
    {
        $member = $this->member();
        $owner = $this->member('Aisha Owner', 'aisha@example.com');
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $this->booking($item, $owner, '16:00:00', '17:00:00');

        $this->actingAs($member)
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '16:59', '18:00'))
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors(['start_time' => 'This time overlaps an existing booking.'])
            ->assertSessionHasInput('start_time', '16:59');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_request_touching_an_existing_booking_is_accepted(): void
    {
        $member = $this->member();
        $owner = $this->member('Aisha Owner', 'aisha@example.com');
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $this->booking($item, $owner, '16:00:00', '17:00:00');

        $this->actingAs($member)
            ->post('/bookings', $this->request($item, '17:00', '18:00'))
            ->assertRedirect('/bookings')
            ->assertSessionHas('status', 'Booking requested (Pending).');

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_deactivated_item_is_rejected_with_the_deactivation_message(): void
    {
        $member = $this->member();
        $item = Item::factory()->create(['name' => 'Old Racket', 'type' => 'equipment', 'is_active' => false]);

        $this->actingAs($member)
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '16:00', '17:00'))
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors(['item_id' => 'This item is deactivated and cannot be booked.']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_all_fields_are_required(): void
    {
        $this->actingAs($this->member())
            ->from('/bookings/create')
            ->post('/bookings', [])
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors(['item_id', 'date', 'start_time', 'end_time']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_date_and_time_formats_are_validated(): void
    {
        $item = Item::factory()->create();

        $this->actingAs($this->member())
            ->from('/bookings/create')
            ->post('/bookings', [
                'item_id' => $item->id,
                'date' => '20/11/2026',
                'start_time' => '8:00',
                'end_time' => '9:00',
            ])
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors(['date', 'start_time', 'end_time']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $member = $this->member();
        $item = Item::factory()->create();

        $this->actingAs($member)
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '17:00', '17:00'))
            ->assertSessionHasErrors('end_time');

        $this->actingAs($member)
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '17:00', '16:00'))
            ->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_start_before_operating_hours_is_rejected(): void
    {
        $item = Item::factory()->create();

        $this->actingAs($this->member())
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '05:30', '07:00'))
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_an_end_after_operating_hours_is_rejected(): void
    {
        $item = Item::factory()->create();

        $this->actingAs($this->member())
            ->from('/bookings/create')
            ->post('/bookings', $this->request($item, '22:00', '23:30'))
            ->assertRedirect('/bookings/create')
            ->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_operating_hours_boundaries_are_accepted(): void
    {
        $member = $this->member();
        $item = Item::factory()->create();

        $this->actingAs($member)
            ->post('/bookings', $this->request($item, '06:00', '07:00'))
            ->assertRedirect('/bookings');

        $this->actingAs($member)
            ->post('/bookings', $this->request($item, '22:00', '23:00'))
            ->assertRedirect('/bookings');

        $this->assertDatabaseHas('bookings', [
            'item_id' => $item->id,
            'start_time' => '06:00:00',
            'end_time' => '07:00:00',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('bookings', [
            'item_id' => $item->id,
            'start_time' => '22:00:00',
            'end_time' => '23:00:00',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('bookings', 2);
    }
}
