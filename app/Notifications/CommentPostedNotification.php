<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;

class CommentPostedNotification extends Notification
{
    public function __construct(
        private readonly Comment $comment,
        private readonly string $requestId = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $task = $this->comment->task;

        return [
            'type' => 'comment_posted',
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'task_title' => $task?->title,
            'project_id' => $task?->project_id,
            'author' => $this->comment->author?->name,
            'message' => $task?->title ? "{$this->comment->author?->name} commented on: {$task->title}" : null,
            'request_id' => $this->requestId,
        ];
    }
}
