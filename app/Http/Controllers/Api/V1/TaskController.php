<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Task\CreateTaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $this->taskService->listForProject(
            $project,
            $request->only(['status', 'priority', 'assigned_to', 'overdue', 'sort_by', 'sort_dir'])
        );

        return ApiResponse::paginated(
            $tasks->through(fn ($t) => new TaskResource($t)),
            'Tasks retrieved.'
        );
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $task = $this->taskService->create(
            $project,
            $request->user(),
            CreateTaskDTO::fromRequest($request)
        );

        return ApiResponse::success(new TaskResource($task), 'Task created.', 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        return ApiResponse::success(
            new TaskResource($task->load(['creator', 'assignee'])),
            'Task retrieved.'
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task updated.');
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return ApiResponse::success(null, 'Task deleted.');
    }
}
