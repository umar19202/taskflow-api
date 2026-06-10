<?php

namespace App\Actions\Task;

use App\Events\TaskStatusChanged;
use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Facades\Log;

class ChangeTaskStatusAction
{
    public function handle(Task $task, TaskStatus $newStatus): Task
    {
        $previousStatus = $task->status;

        if ($previousStatus === $newStatus) {
            return $task;
        }

        $task->update(['status' => $newStatus->value]);

        Log::info('Task status changed', [
            'task_id'         => $task->id,
            'previous_status' => $previousStatus->value,
            'new_status'      => $newStatus->value,
        ]);

        event(new TaskStatusChanged(
            task:           $task->fresh(['creator', 'assignee', 'project']),
            previousStatus: $previousStatus,
            newStatus:      $newStatus,
            requestId:      app()->has('request_id') ? app('request_id') : '',
        ));

        return $task;
    }
}
