<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Generations\CreateGenerationAction;
use App\DTOs\Generations\CreateGenerationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGenerationRequest;
use App\Http\Resources\GenerationCreatedResource;
use App\Http\Resources\GenerationTaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

final class GenerationController extends Controller
{
    public function __construct(
        private readonly CreateGenerationAction $action,
    ) {}

    #[OA\Post(
        path: '/api/v1/generations',
        summary: 'Create a generation task',
        security: [['bearerAuth' => []]],
        tags: ['Generations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'prompt', 'variations'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['image'], example: 'image'),
                    new OA\Property(property: 'prompt', type: 'string', maxLength: 2000, example: 'neon city at night, cinematic'),
                    new OA\Property(property: 'variations', type: 'integer', minimum: 1, maximum: 4, example: 2),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Task accepted',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string', example: 'pending'),
                        new OA\Property(property: 'quota_remaining', type: 'integer'),
                    ]),
                ]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error, or quota exceeded with error.code=QUOTA_EXCEEDED',
            ),
        ],
    )]
    public function store(CreateGenerationRequest $request): GenerationCreatedResource
    {
        $task = $this->action->execute($request->user(), CreateGenerationData::fromRequest($request));

        $subscription = $request->user()->subscription()->firstOrFail();
        $remaining = $subscription->monthly_limit - $subscription->used_quota;

        return new GenerationCreatedResource($task, $remaining);
    }

    #[OA\Get(
        path: '/api/v1/generations/{id}',
        summary: 'Get a generation task',
        security: [['bearerAuth' => []]],
        tags: ['Generations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found (also returned for another user\'s task)'),
        ],
    )]
    public function show(Request $request, int $id): GenerationTaskResource
    {
        $task = $request->user()->generationTasks()->with('assets')->findOrFail($id);

        return new GenerationTaskResource($task);
    }

    #[OA\Get(
        path: '/api/v1/generations',
        summary: 'List the authenticated user\'s generation tasks',
        security: [['bearerAuth' => []]],
        tags: ['Generations'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'completed', 'failed']),
            ),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of tasks'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->generationTasks()
            ->with('assets')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate();

        return GenerationTaskResource::collection($tasks);
    }
}
