<?php

namespace Tests\Feature\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class DashboardTest extends FeatureTestCase
{
    public function test_authenticated_user_can_view_dashboard_stats(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        Task::factory(3)->create(['project_id' => $project->id]);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_projects',
                    'total_tasks',
                    'active_tasks',
                    'overdue_tasks',
                    'recent_tasks',
                    'tasks_by_project',
                    'tasks_by_status',
                    'tasks_by_priority',
                ],
            ])
            ->assertJsonPath('data.total_projects', 1)
            ->assertJsonPath('data.total_tasks', 3);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/dashboard/stats')
            ->assertUnauthorized();
    }

    public function test_user_only_sees_their_own_stats(): void
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->create();

        $userProject = Project::factory()->create(['owner_id' => $user->id]);
        Task::factory(2)->create(['project_id' => $userProject->id]);

        $otherProject = Project::factory()->create(['owner_id' => $otherUser->id]);
        Task::factory(5)->create(['project_id' => $otherProject->id]);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_projects', 1)
            ->assertJsonPath('data.total_tasks', 2);
    }

    public function test_member_sees_project_in_dashboard(): void
    {
        $member = $this->actingAsUser();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id, ['role' => 'member']);
        Task::factory(2)->create(['project_id' => $project->id]);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_projects', 1)
            ->assertJsonPath('data.total_tasks', 2);
    }

    public function test_dashboard_includes_overdue_count(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->subDay(),
            'status' => 'open',
        ]);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.overdue_tasks', 1);
    }
}
