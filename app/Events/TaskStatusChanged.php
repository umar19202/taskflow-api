<?php

namespace App\Events;

use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task       $task,
        public readonly TaskStatus $previousStatus,
        public readonly TaskStatus $newStatus,
        public readonly string     $requestId = '',
    ) {}
}
