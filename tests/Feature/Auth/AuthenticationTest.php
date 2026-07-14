<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\FeatureTestCase;

class AuthenticationTest extends FeatureTestCase
{
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Umar Khan',
            'email' => 'umartest@gmail.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['user' => ['id', 'name', 'email'], 'token'],
                'meta' => ['request_id', 'timestamp', 'version'],
            ])
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['email' => 'umartest@gmail.com']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'umartest@gmail.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Umar Khan',
            'email' => 'umartest@gmail.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecurePass123',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_me_endpoint_returns_current_user(): void
    {
        $user = $this->actingAsUser();

        $this->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_authenticated_user_can_update_name_and_email(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@gmail.com',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@gmail.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@gmail.com',
        ]);
    }

    public function test_update_profile_rejects_duplicate_email(): void
    {
        $this->actingAsUser();
        User::factory()->create(['email' => 'taken@gmail.com']);

        $this->putJson('/api/v1/auth/profile', [
            'email' => 'taken@gmail.com',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Hacker',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/v1/auth/profile', [
            'current_password' => 'password',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.');

        $this->assertTrue(Hash::check('NewSecurePass123', $user->fresh()->password));
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/v1/auth/profile', [
            'current_password' => 'wrong-password',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.current_password.0', 'The current password is incorrect.');
    }
}
