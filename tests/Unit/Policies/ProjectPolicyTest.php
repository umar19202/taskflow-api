<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy();
    }

    public function test_owner_can_update_project(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $project));
    }

    public function test_non_owner_cannot_update_project(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->policy->update($other, $project));
    }

    public function test_member_can_view_project(): void
    {
        $owner   = User::factory()->create();
        $member  = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id);

        $this->assertTrue($this->policy->view($member, $project));
    }
}
