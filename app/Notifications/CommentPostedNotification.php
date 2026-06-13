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
        return [
            'type' => 'comment_posted',
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'task_title' => $this->comment->task->title,
            'project_id' => $this->comment->task->project_id,
            'author' => $this->comment->author->name,
            'message' => "{$this->comment->author->name} commented on: {$this->comment->task->title}",
            'request_id' => $this->requestId,
        ];
    }
}
