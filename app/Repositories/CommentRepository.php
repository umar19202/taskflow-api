<?php

namespace App\Repositories;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

class CommentRepository implements CommentRepositoryInterface
{
    public function findById(int $id): ?Comment
    {
        return Comment::with(['author:id,name,email'])->find($id);
    }

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);

        return $comment->fresh(['author:id,name,email']);
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }

    public function queryByTask(int $taskId): Builder
    {
        return Comment::where('task_id', $taskId);
    }
}
