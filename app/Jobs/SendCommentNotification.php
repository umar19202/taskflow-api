<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly Comment $comment,
        public readonly User   $recipient,
        public readonly string $requestId = '',
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        app()->instance('request_id', $this->requestId ?: 'queue-job');

        Log::info('Dispatching CommentPostedNotification', [
            'comment_id' => $this->comment->id,
            'task_id'    => $this->comment->task_id,
            'recipient'  => $this->recipient->id,
        ]);

        $this->recipient->notify(
            new CommentPostedNotification($this->comment, $this->requestId)
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCommentNotification failed', [
            'comment_id' => $this->comment->id,
            'recipient'  => $this->recipient->id,
            'error'      => $e->getMessage(),
            'request_id' => $this->requestId,
        ]);
    }
}
