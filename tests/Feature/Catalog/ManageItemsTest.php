<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageItemsTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::factory()->create([
            'name' => 'Sam Supervisor',
            'email' => 'supervisor@example.com',
            'role' => 'supervisor',
        ]);
    }

    private function member(): User
    {
        return User::factory()->create([
            'name' => 'Mia Member',
            'email' => 'member@example.com',
            'role' => 'member',
        ]);
    }

    public function test_supervisor_sees_all_items_active_and_deactivated(): void
    {
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        Item::factory()->create(['name' => 'Old Racket', 'type' => 'equipment', 'is_active' => false]);

        $this->actingAs($this->supervisor())
            ->get('/items')
            ->assertOk()
            ->assertSee('Pitch A')
            ->assertSee('Old Racket')
            ->assertSee('Active')
            ->assertSee('Deactivated')
            ->assertSee('Sam Supervisor')
            ->assertSee('Log out');
    }

    public function test_supervisor_adds_a_field_and_it_is_active(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->post('/items', ['name' => 'Pitch C', 'type' => 'field'])
            ->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'name' => 'Pitch C',
            'type' => 'field',
            'is_active' => true,
        ]);

        $this->actingAs($supervisor)
            ->get('/items')
            ->assertOk()
            ->assertSee('Pitch C');
    }

    public function test_adding_a_duplicate_name_is_rejected(): void
    {
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->from('/items')
            ->actingAs($this->supervisor())
            ->post('/items', ['name' => 'Pitch A', 'type' => 'equipment'])
            ->assertRedirect('/items')
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('items', 1);
    }

    public function test_supervisor_edits_an_items_name_and_type(): void
    {
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);

        $this->actingAs($this->supervisor())
            ->post("/items/{$item->id}", ['action' => 'update', 'name' => 'Pitch B', 'type' => 'equipment'])
            ->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Pitch B',
            'type' => 'equipment',
        ]);
    }

    public function test_renaming_an_item_to_another_items_name_is_rejected(): void
    {
        Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $item = Item::factory()->create(['name' => 'Pitch B', 'type' => 'field']);

        $this->from('/items')
            ->actingAs($this->supervisor())
            ->post("/items/{$item->id}", ['action' => 'update', 'name' => 'Pitch A', 'type' => 'equipment'])
            ->assertRedirect('/items')
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Pitch B',
            'type' => 'field',
        ]);
    }

    public function test_supervisor_deactivates_an_item_that_has_a_booking(): void
    {
        $member = $this->member();
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field']);
        $booking = Booking::create([
            'user_id' => $member->id,
            'item_id' => $item->id,
            'date' => '2026-11-20',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->supervisor())
            ->post("/items/{$item->id}", ['action' => 'deactivate'])
            ->assertRedirect('/items');

        $this->assertFalse($item->fresh()->is_active);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'is_active' => false]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'item_id' => $item->id]);
    }

    public function test_member_gets_403_on_each_item_route_and_nothing_changes(): void
    {
        $item = Item::factory()->create(['name' => 'Pitch A', 'type' => 'field', 'is_active' => true]);
        $member = $this->member();

        $this->actingAs($member)->get('/items')->assertForbidden();
        $this->actingAs($member)
            ->post('/items', ['name' => 'Pitch C', 'type' => 'field'])
            ->assertForbidden();
        $this->actingAs($member)
            ->post("/items/{$item->id}", ['action' => 'update', 'name' => 'Pitch Z', 'type' => 'equipment'])
            ->assertForbidden();
        $this->actingAs($member)
            ->post("/items/{$item->id}", ['action' => 'deactivate'])
            ->assertForbidden();

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Pitch A',
            'type' => 'field',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_manage_items(): void
    {
        $this->get('/items')->assertRedirect('/login');

        $this->assertGuest();
    }
}
