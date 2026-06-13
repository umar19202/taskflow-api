<?php

namespace App\Actions\Task;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AssignTaskAction
{
    public function handle(Task $task, User $assignee): Task
    {
        $previousAssigneeId = $task->assigned_to;

        $task->update(['assigned_to' => $assignee->id]);

        Log::info('Task assigned', [
            'task_id' => $task->id,
            'new_assignee_id' => $assignee->id,
            'previous_assignee_id' => $previousAssigneeId,
        ]);

        event(new TaskCreated($task->fresh(['assignee', 'project'])));

        return $task->fresh('assignee');
    }
}
