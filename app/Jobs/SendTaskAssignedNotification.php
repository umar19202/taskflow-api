<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly Task   $task,
        public readonly User   $assignee,
        public readonly string $requestId = '',
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        app()->instance('request_id', $this->requestId ?: 'queue-job');

        Log::info('Dispatching TaskAssignedNotification', [
            'task_id'  => $this->task->id,
            'assignee' => $this->assignee->id,
        ]);

        $this->assignee->notify(
            new TaskAssignedNotification($this->task, $this->requestId)
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendTaskAssignedNotification failed', [
            'task_id'    => $this->task->id,
            'assignee'   => $this->assignee->id,
            'error'      => $e->getMessage(),
            'request_id' => $this->requestId,
        ]);
    }
}
