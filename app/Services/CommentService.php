<?php

namespace App\Services;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\DTOs\Comment\CreateCommentDTO;
use App\DTOs\Comment\UpdateCommentDTO;
use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
    ) {}

    public function listForTask(Task $task): LengthAwarePaginator
    {
        return $this->commentRepository->queryByTask($task->id)
            ->with(['author:id,name,email'])
            ->oldest()
            ->paginate(25);
    }

    public function create(Task $task, User $author, CreateCommentDTO $dto): Comment
    {
        return DB::transaction(function () use ($task, $author, $dto) {
            $comment = $this->commentRepository->create([
                'task_id' => $task->id,
                'user_id' => $author->id,
                'body'    => $dto->body,
            ]);

            Log::info('Comment created', [
                'comment_id' => $comment->id,
                'task_id'    => $task->id,
                'author_id'  => $author->id,
            ]);

            $task->project->touch();

            event(new CommentPosted(
                comment:   $comment->load(['author', 'task.project']),
                requestId: app()->has('request_id') ? app('request_id') : '',
            ));

            return $comment;
        });
    }

    public function update(Comment $comment, UpdateCommentDTO $dto): Comment
    {
        return $this->commentRepository->update($comment, $dto->toArray());
    }

    public function delete(Comment $comment): void
    {
        $comment->task->project->touch();

        $this->commentRepository->delete($comment);
    }
}
