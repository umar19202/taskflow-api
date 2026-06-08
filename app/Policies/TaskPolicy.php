<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function update(User $user, Task $task): bool
    {
        return $task->project->hasAccess($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->created_by || $user->id === $task->project->owner_id;
    }
}
