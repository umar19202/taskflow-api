<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\Feature\FeatureTestCase;

class UserListTest extends FeatureTestCase
{
    public function test_authenticated_user_can_list_users(): void
    {
        User::factory(3)->create();

        $this->actingAsUser();

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized();
    }
}
