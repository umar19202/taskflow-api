<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository implements TaskRepositoryInterface
{
    public function findById(int $id): ?Task
    {
        return Task::with(['creator:id,name', 'assignee:id,name,email'])->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['creator:id,name', 'assignee:id,name,email']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function queryByProject(int $projectId): Builder
    {
        return Task::where('project_id', $projectId);
    }
}
