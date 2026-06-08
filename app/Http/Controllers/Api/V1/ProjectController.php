<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Project\CreateProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->listForUser(
            $request->user(),
            page: $request->integer('page', 1)
        );

        return ApiResponse::paginated(
            $projects->through(fn($p) => new ProjectResource($p)),
            'Projects retrieved.'
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create(
            $request->user(),
            CreateProjectDTO::fromRequest($request)
        );

        return ApiResponse::success(new ProjectResource($project), 'Project created.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->findById($id);

        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->authorize('view', $project);

        return ApiResponse::success(new ProjectResource($project), 'Project retrieved.');
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projectService->findById($id);

        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->authorize('update', $project);

        $project = $this->projectService->update(
            $project,
            UpdateProjectDTO::fromRequest($request)
        );

        return ApiResponse::success(new ProjectResource($project), 'Project updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectService->findById($id);

        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return ApiResponse::success(null, 'Project deleted.');
    }
}
