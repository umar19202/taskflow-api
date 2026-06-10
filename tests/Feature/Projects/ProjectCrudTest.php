<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class ProjectCrudTest extends FeatureTestCase
{
    public function test_owner_can_view_single_project(): void
    {
        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $project->name);
    }

    public function test_owner_can_update_project(): void
    {
        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->putJson("/api/v1/projects/{$project->id}", [
            'name'        => 'Updated Name',
            'description' => 'Updated description',
        ])->assertOk()
          ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_non_owner_cannot_update_project(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->actingAsUser();

        $this->putJson("/api/v1/projects/{$project->id}", [
            'name' => 'Hacked Name',
        ])->assertStatus(403);
    }

    public function test_create_project_rejects_empty_name(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/projects', [
            'name' => '',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_create_project(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/projects', [
            'name'        => 'Alpha Project',
            'description' => 'First project',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Alpha Project')
          ->assertJsonPath('data.total_tasks', 0)
          ->assertJsonPath('data.active_tasks', 0);

        $this->assertDatabaseHas('projects', ['name' => 'Alpha Project']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/v1/projects', ['name' => 'Test'])
            ->assertStatus(401);
    }

    public function test_user_only_sees_their_own_projects(): void
    {
        $owner  = $this->actingAsUser();
        $other  = User::factory()->create();
        $myProj = Project::factory()->create(['owner_id' => $owner->id]);
        $theirP = Project::factory()->create(['owner_id' => $other->id]);

        $ids = collect($this->getJson('/api/v1/projects')->json('data'))->pluck('id');

        $this->assertContains($myProj->id, $ids);
        $this->assertNotContains($theirP->id, $ids);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->actingAsUser();

        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_soft_delete_project(): void
    {
        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
