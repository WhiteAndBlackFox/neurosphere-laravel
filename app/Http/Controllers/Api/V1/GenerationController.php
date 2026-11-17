<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Generations\CreateGenerationAction;
use App\DTOs\Generations\CreateGenerationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGenerationRequest;
use App\Http\Resources\GenerationTaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GenerationController extends Controller
{
    public function __construct(
        private readonly CreateGenerationAction $action,
    ) {}

    public function store(CreateGenerationRequest $request): JsonResponse
    {
        $task = $this->action->execute($request->user(), CreateGenerationData::fromRequest($request));

        $subscription = $request->user()->subscription()->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $task->id,
                'status' => $task->status->value,
                'quota_remaining' => $subscription->monthly_limit - $subscription->used_quota,
            ],
        ], 202);
    }

    public function show(Request $request, int $id): GenerationTaskResource
    {
        $task = $request->user()->generationTasks()->with('assets')->findOrFail($id);

        return new GenerationTaskResource($task);
    }

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
