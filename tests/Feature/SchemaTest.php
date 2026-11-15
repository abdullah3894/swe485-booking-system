<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLock;
use App\Modules\Catalog\Models\Item;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_supervisor_exists_with_role_supervisor(): void
    {
        $this->seed();

        $supervisor = User::where('email', 'supervisor@example.com')->first();

        $this->assertNotNull($supervisor);
        $this->assertSame('supervisor', $supervisor->role);
        $this->assertTrue($supervisor->isSupervisor());
        $this->assertSame(1, User::where('role', 'supervisor')->count());
    }

    public function test_user_created_without_role_gets_member(): void
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'No Role',
            'email' => 'norole@example.com',
            'password' => Hash::make('Password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('member', User::findOrFail($id)->role);
        $this->assertSame('member', User::factory()->create()->role);
    }

    public function test_duplicate_booking_lock_for_same_item_and_date_throws(): void
    {
        $item = Item::factory()->create();
        $date = '2026-11-20';

        BookingLock::create(['item_id' => $item->id, 'date' => $date]);

        $this->expectException(QueryException::class);

        BookingLock::create(['item_id' => $item->id, 'date' => $date]);
    }

    public function test_booking_defaults_to_pending_status(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'date' => '2026-11-20',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending',
        ]);
    }
}
