<?php

namespace App\DTOs\Project;

use App\Http\Requests\Project\UpdateProjectRequest;

final readonly class UpdateProjectDTO
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public ?string $status,
    ) {}

    public static function fromRequest(UpdateProjectRequest $request): self
    {
        $validated = $request->validated();
        return new self(
            name:        $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            status:      $validated['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
        ], fn($value) => $value !== null);
    }
}
