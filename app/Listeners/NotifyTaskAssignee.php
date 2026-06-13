<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Jobs\SendTaskAssignedNotification;

class NotifyTaskAssignee
{
    public function handle(TaskCreated $event): void
    {
        if ($event->task->assignee === null) {
            return;
        }

        SendTaskAssignedNotification::dispatch(
            task: $event->task,
            assignee: $event->task->assignee,
            requestId: $event->requestId,
        )->onQueue('notifications');
    }
}
