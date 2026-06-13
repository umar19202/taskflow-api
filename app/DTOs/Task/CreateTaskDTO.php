<?php

namespace App\DTOs\Task;

use App\Http\Requests\Task\StoreTaskRequest;

final readonly class CreateTaskDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?int $assignedTo,
        public string $priority,
        public ?string $dueDate,
    ) {}

    public static function fromRequest(StoreTaskRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            assignedTo: $request->validated('assigned_to'),
            priority: $request->validated('priority', 'medium'),
            dueDate: $request->validated('due_date'),
        );
    }
}
