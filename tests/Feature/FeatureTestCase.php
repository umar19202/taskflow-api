<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user);
        
        return $user;
    }
}
