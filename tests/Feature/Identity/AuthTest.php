<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sara Member',
            'email' => 'sara@example.com',
            'password' => 'Abc12345',
            'password_confirmation' => 'Abc12345',
        ], $overrides);
    }

    private function probeRoute(): void
    {
        Route::middleware(['web', 'auth', 'role:supervisor'])->get('/_probe', fn () => 'ok');
    }

    public function test_register_creates_a_member_and_redirects_to_bookings(): void
    {
        $this->post('/register', $this->payload())
            ->assertRedirect('/bookings');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Sara Member',
            'email' => 'sara@example.com',
            'role' => 'member',
        ]);
    }

    public function test_register_ignores_a_supervisor_role_in_the_request(): void
    {
        $this->post('/register', $this->payload(['role' => 'supervisor']))
            ->assertRedirect('/bookings');

        $this->assertSame('member', User::where('email', 'sara@example.com')->value('role'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'seven characters' => ['Abc1234'],
            'no number' => ['abcdefgh'],
            'no letter' => ['12345678'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_register_rejects_invalid_passwords(string $password): void
    {
        $this->from('/register')
            ->post('/register', $this->payload([
                'password' => $password,
                'password_confirmation' => $password,
            ]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_register_accepts_an_eight_character_password(): void
    {
        $this->post('/register', $this->payload([
            'password' => 'Abc12345',
            'password_confirmation' => 'Abc12345',
        ]))->assertRedirect('/bookings');

        $this->assertDatabaseHas('users', ['email' => 'sara@example.com']);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'sara@example.com']);

        $this->from('/register')
            ->post('/register', $this->payload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_member_login_redirects_to_bookings(): void
    {
        $member = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'Abc12345',
            'role' => 'member',
        ]);

        $this->post('/login', ['email' => 'member@example.com', 'password' => 'Abc12345'])
            ->assertRedirect('/bookings');

        $this->assertAuthenticatedAs($member);
    }

    public function test_supervisor_login_redirects_to_dashboard(): void
    {
        $supervisor = User::factory()->create([
            'email' => 'supervisor@example.com',
            'password' => 'Abc12345',
            'role' => 'supervisor',
        ]);

        $this->post('/login', ['email' => 'supervisor@example.com', 'password' => 'Abc12345'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($supervisor);
    }

    public function test_login_with_a_wrong_password_fails_and_stays_guest(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'Abc12345',
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'member@example.com', 'password' => 'WrongPass1'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_returns_the_user_to_a_guest_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_an_auth_route(): void
    {
        $this->probeRoute();

        $this->get('/_probe')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_member_gets_403_on_a_supervisor_route(): void
    {
        $this->probeRoute();

        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->get('/_probe')
            ->assertForbidden();
    }
}
