<?php

namespace App\DTOs\Task;

use App\Http\Requests\Task\UpdateTaskRequest;

final readonly class UpdateTaskDTO
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?int $assignedTo,
        public ?string $status,
        public ?string $priority,
        public ?string $dueDate,
    ) {}

    public static function fromRequest(UpdateTaskRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title: $validated['title'] ?? null,
            description: $validated['description'] ?? null,
            assignedTo: $validated['assigned_to'] ?? null,
            status: $validated['status'] ?? null,
            priority: $validated['priority'] ?? null,
            dueDate: $validated['due_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'assigned_to' => $this->assignedTo,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate,
        ], fn ($value) => $value !== null);
    }
}
