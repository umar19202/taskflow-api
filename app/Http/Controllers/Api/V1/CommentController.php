<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Comment\CreateCommentDTO;
use App\DTOs\Comment\UpdateCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Services\CommentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        $comments = $this->commentService->listForTask($task);

        return ApiResponse::paginated(
            $comments->through(fn ($c) => new CommentResource($c)),
            'Comments retrieved.'
        );
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        $comment = $this->commentService->create(
            $task,
            $request->user(),
            CreateCommentDTO::fromRequest($request)
        );

        return ApiResponse::success(new CommentResource($comment), 'Comment created.', 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment = $this->commentService->update(
            $comment,
            UpdateCommentDTO::fromRequest($request)
        );

        return ApiResponse::success(new CommentResource($comment), 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->delete($comment);

        return ApiResponse::success(null, 'Comment deleted.');
    }
}
