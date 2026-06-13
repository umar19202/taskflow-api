<?php

namespace App\DTOs\Project;

use App\Http\Requests\Project\StoreProjectRequest;

final readonly class CreateProjectDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProjectRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
        );
    }
}
