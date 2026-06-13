<?php

namespace Tests\Unit\Services;

use App\DTOs\Task\CreateTaskDTO;
use App\Events\TaskCreated;
use App\Models\Project;
use App\Models\User;
use App\Services\TaskService;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskService::class);
    }

    public function test_create_dispatches_task_created_event(): void
    {
        Event::fake([TaskCreated::class]);

        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->service->create($project, $user, new CreateTaskDTO(
            title: 'Test Task',
            description: null,
            assignedTo: null,
            priority: 'high',
            dueDate: null,
        ));

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_new_task_has_open_status_by_default(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $task = $this->service->create($project, $user, new CreateTaskDTO(
            title: 'New Task',
            description: null,
            assignedTo: null,
            priority: 'medium',
            dueDate: null,
        ));

        $this->assertEquals(TaskStatus::Open, $task->status);
    }

    public function test_creating_task_touches_project_to_invalidate_cache(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $originalTimestamp = $project->updated_at;

        sleep(1);

        $this->service->create($project, $user, new CreateTaskDTO(
            title: 'Cache Test', description: null,
            assignedTo: null, priority: 'low', dueDate: null,
        ));

        $this->assertGreaterThan(
            $originalTimestamp,
            $project->fresh()->updated_at
        );
    }
}
