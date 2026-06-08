<?php

namespace App\Contracts\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): void;

    public function queryByProject(int $projectId): Builder;
}
