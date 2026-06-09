<?php

namespace App\DTOs\Comment;

use App\Http\Requests\Comment\StoreCommentRequest;

final readonly class CreateCommentDTO
{
    public function __construct(
        public string $body,
    ) {}

    public static function fromRequest(StoreCommentRequest $request): self
    {
        return new self(
            body: $request->validated('body'),
        );
    }
}
