<?php

namespace App\Contracts\Repositories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

interface CommentRepositoryInterface
{
    public function findById(int $id): ?Comment;

    public function create(array $data): Comment;

    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): void;

    public function queryByTask(int $taskId): Builder;
}
