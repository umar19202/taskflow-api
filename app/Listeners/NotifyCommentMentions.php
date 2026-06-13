<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Jobs\SendCommentNotification;

class NotifyCommentMentions
{
    public function handle(CommentPosted $event): void
    {
        $assignee = $event->comment->task->assignee;

        if ($assignee === null) {
            return;
        }

        if ((int) $assignee->id === (int) $event->comment->user_id) {
            return;
        }

        SendCommentNotification::dispatch(
            comment: $event->comment,
            recipient: $assignee,
            requestId: $event->requestId,
        )->onQueue('notifications');
    }
}
