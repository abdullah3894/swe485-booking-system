<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Modules\Identity\IdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_always_creates_a_member(): void
    {
        $user = (new IdentityService)->register('Amina Member', 'amina@example.com', 'Abc12345');

        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('Abc12345', $user->password));
        $this->assertDatabaseHas('users', [
            'email' => 'amina@example.com',
            'role' => 'member',
        ]);
    }

    public function test_names_for_returns_names_keyed_by_id(): void
    {
        $first = User::factory()->create(['name' => 'Amina Member']);
        $second = User::factory()->create(['name' => 'Bilal Member']);

        $names = (new IdentityService)->namesFor([$first->id, $second->id]);

        $this->assertSame([
            $first->id => 'Amina Member',
            $second->id => 'Bilal Member',
        ], $names);
    }
}
