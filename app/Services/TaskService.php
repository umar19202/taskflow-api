<?php

namespace App\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\DTOs\Task\CreateTaskDTO;
use App\DTOs\Task\UpdateTaskDTO;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Filters\TaskQueryFilter;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function listForProject(Project $project, array $filters = []): LengthAwarePaginator
    {
        $version    = $project->updated_at->timestamp;
        $filterHash = md5(serialize($filters) . request('page', 1));
        $cacheKey   = "project:{$project->id}:v{$version}:tasks:{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($project, $filters) {
            return TaskQueryFilter::apply(array_merge($filters, ['project_id' => $project->id]))
                ->paginate(15);
        });
    }

    public function create(Project $project, User $creator, CreateTaskDTO $dto): Task
    {
        $task = $this->taskRepository->create([
            'project_id'  => $project->id,
            'created_by'  => $creator->id,
            'assigned_to' => $dto->assignedTo,
            'title'       => $dto->title,
            'description' => $dto->description,
            'status'      => TaskStatus::Open,
            'priority'    => $dto->priority,
            'due_date'    => $dto->dueDate,
        ]);

        $project->touch();

        Cache::tags(["user:{$project->owner_id}:projects"])->flush();
        Cache::forget("project:{$project->id}");

        event(new TaskCreated(
            task:      $task->load(['creator', 'assignee', 'project']),
            requestId: app()->has('request_id') ? app('request_id') : '',
        ));

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $previousStatus = $task->status;

        $task = $this->taskRepository->update($task, array_filter($data, fn ($v) => $v !== null));

        $task->project->touch();

        Cache::tags(["user:{$task->project->owner_id}:projects"])->flush();
        Cache::forget("project:{$task->project->id}");

        if (isset($data['status']) && $data['status'] !== $previousStatus->value) {
            event(new TaskStatusChanged(
                task:           $task,
                previousStatus: $previousStatus,
                newStatus:      TaskStatus::from($data['status']),
                requestId:      app()->has('request_id') ? app('request_id') : '',
            ));
        }

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->project->touch();

        Cache::tags(["user:{$task->project->owner_id}:projects"])->flush();
        Cache::forget("project:{$task->project->id}");

        $this->taskRepository->delete($task);
    }
}
