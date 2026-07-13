<?php

namespace Tests\Feature\Tasks;

use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Jobs\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class TaskTest extends FeatureTestCase
{
    public function test_owner_can_view_single_task(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.title', $task->title);
    }

    public function test_owner_can_update_task(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated title',
            'priority' => 'urgent',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated title');
    }

    public function test_updating_task_status_fires_event(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'status' => 'open']);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'status' => 'in_progress',
        ])->assertOk();

        Event::assertDispatched(TaskStatusChanged::class);
    }

    public function test_create_task_rejects_empty_title(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => '',
            'priority' => 'medium',
        ])->assertStatus(422);
    }

    public function test_creating_task_dispatches_task_created_event(): void
    {
        Event::fake([TaskCreated::class]);

        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Fix login bug',
            'priority' => 'high',
        ])->assertStatus(201);

        Event::assertDispatched(TaskCreated::class, function ($event) {
            return $event->task->title === 'Fix login bug';
        });
    }

    public function test_creating_task_with_assignee_dispatches_notification_job(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Deploy feature',
            'priority' => 'urgent',
            'assigned_to' => $assignee->id,
        ])->assertStatus(201);

        Queue::assertPushedOn('notifications', SendTaskAssignedNotification::class);
    }

    public function test_task_list_is_filterable_by_status(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'status' => 'open']);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'status' => 'done']);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?status=open");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('open', $data[0]['status']['value']);
    }

    public function test_non_project_member_cannot_view_tasks(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->actingAsUser();

        $this->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertStatus(403);
    }

    public function test_owner_can_delete_task(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted.');

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_updating_task_assignee_fires_notification(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'assigned_to' => null]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'assigned_to' => $assignee->id,
        ])->assertOk();

        Queue::assertPushed(SendTaskAssignedNotification::class);
    }

    public function test_non_owner_cannot_delete_task(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->actingAsUser();

        $this->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(403);
    }

    public function test_task_list_is_filterable_by_priority(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'priority' => 'high']);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'priority' => 'low']);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?priority=high");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('high', $response->json('data.0.priority'));
    }

    public function test_task_list_is_filterable_by_overdue(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'due_date' => now()->subDay(), 'status' => 'open']);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'due_date' => now()->subDay(), 'status' => 'done']);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?overdue=1");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_assigning_task_adds_assignee_as_project_member(): void
    {
        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Collaborative task',
            'priority' => 'medium',
            'assigned_to' => $assignee->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $assignee->id,
            'role' => 'member',
        ]);
    }

    public function test_assignee_can_view_project_after_being_assigned(): void
    {
        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'assigned_to' => $assignee->id,
        ])->assertOk();

        Sanctum::actingAs($assignee);

        $this->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $project->name);
    }

    public function test_assignee_can_comment_on_task_after_being_assigned(): void
    {
        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'assigned_to' => $assignee->id,
        ])->assertOk();

        Sanctum::actingAs($assignee);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Assignee comment',
        ])->assertStatus(201)
            ->assertJsonPath('data.body', 'Assignee comment');
    }

    public function test_assignee_sees_project_in_their_list(): void
    {
        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'assigned_to' => $assignee->id,
        ])->assertOk();

        Sanctum::actingAs($assignee);

        $projectIds = collect($this->getJson('/api/v1/projects')->json('data'))->pluck('id');

        $this->assertContains($project->id, $projectIds);
    }
}
