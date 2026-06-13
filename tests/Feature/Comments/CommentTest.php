<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
}
