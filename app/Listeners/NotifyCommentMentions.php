<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Jobs\SendCommentNotification;

class NotifyCommentMentions
{
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $assignee = $comment->task->assignee;
        $projectOwner = $comment->task->project->owner;
        $authorId = (int) $comment->user_id;

        if ($assignee && $authorId !== (int) $assignee->id) {
            SendCommentNotification::dispatch(
                comment: $comment,
                recipient: $assignee,
                requestId: $event->requestId,
            )->onQueue('notifications');
        }

        if ($projectOwner && $authorId !== (int) $projectOwner->id
            && (! $assignee || (int) $projectOwner->id !== (int) $assignee->id)) {
            SendCommentNotification::dispatch(
                comment: $comment,
                recipient: $projectOwner,
                requestId: $event->requestId,
            )->onQueue('notifications');
        }
    }
}
