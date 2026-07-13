<?php

namespace Tests\Feature\Notifications;

use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

class NotificationTest extends FeatureTestCase
{
    public function test_authenticated_user_can_list_notifications(): void
    {
        $user = $this->actingAsUser();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => json_encode(['message' => 'Test']),
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => json_encode(['message' => 'Test 2']),
        ]);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = $this->actingAsUser();
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => json_encode(['message' => 'Test']),
            'read_at' => null,
        ]);

        $this->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->actingAsUser();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => json_encode(['message' => 'Test']),
            'read_at' => null,
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TaskAssignedNotification',
            'data' => json_encode(['message' => 'Test 2']),
            'read_at' => null,
        ]);

        $this->patchJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/notifications')
            ->assertUnauthorized();
    }
}
