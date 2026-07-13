<?php

namespace Tests\Feature\Comments;

use App\Jobs\SendCommentNotification;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class CommentTest extends FeatureTestCase
{
    public function test_authenticated_user_can_create_comment(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'This is a comment.',
        ])->assertStatus(201)
            ->assertJsonPath('data.body', 'This is a comment.');
    }

    public function test_create_comment_rejects_empty_body(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => '',
        ])->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_create_comment(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Unauthorized.',
        ])->assertStatus(401);
    }

    public function test_author_can_update_own_comment(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->putJson("/api/v1/comments/{$comment->id}", [
            'body' => 'Updated comment body.',
        ])->assertOk()
            ->assertJsonPath('data.body', 'Updated comment body.');
    }

    public function test_non_author_cannot_update_comment(): void
    {
        $author = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $author->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $author->id]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->actingAsUser();

        $this->putJson("/api/v1/comments/{$comment->id}", [
            'body' => 'Hacked body.',
        ])->assertStatus(403);
    }

    public function test_author_can_delete_own_comment(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $this->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertOk();
    }

    public function test_project_owner_can_delete_any_comment(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $other = User::factory()->create();
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $other->id,
        ]);

        $this->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertOk();
    }

    public function test_non_author_non_owner_cannot_delete_comment(): void
    {
        $author = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $author->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $author->id]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
        ]);

        $this->actingAsUser();

        $this->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertStatus(403);
    }

    public function test_list_comments_for_task(): void
    {
        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        Comment::factory()->count(3)->create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}/comments");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_assignee_comment_notifies_owner(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($assignee->id, ['role' => 'member']);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        Sanctum::actingAs($assignee);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Comment from assignee',
        ])->assertStatus(201);

        Queue::assertPushed(SendCommentNotification::class, function ($job) use ($owner) {
            return $job->recipient->id === $owner->id;
        });
    }

    public function test_owner_comment_notifies_assignee(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Comment from owner',
        ])->assertStatus(201);

        Queue::assertPushed(SendCommentNotification::class, function ($job) use ($assignee) {
            return $job->recipient->id === $assignee->id;
        });
    }

    public function test_other_member_comment_notifies_both_owner_and_assignee(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $assignee = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'member']);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Comment from member',
        ])->assertStatus(201);

        Queue::assertPushed(SendCommentNotification::class, 2);
        Queue::assertPushed(SendCommentNotification::class, fn ($job) => $job->recipient->id === $owner->id);
        Queue::assertPushed(SendCommentNotification::class, fn ($job) => $job->recipient->id === $assignee->id);
    }

    public function test_no_self_notification_when_owner_is_also_assignee(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $owner->id,
        ]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Self comment',
        ])->assertStatus(201);

        Queue::assertNotPushed(SendCommentNotification::class);
    }

    public function test_no_notification_when_no_assignee(): void
    {
        Queue::fake();

        $owner = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => null,
        ]);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'Comment on unassigned task',
        ])->assertStatus(201);

        Queue::assertNotPushed(SendCommentNotification::class);
    }
}
