<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\StoreIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Incident\InvestigateIncidentRequest;
use App\Http\Requests\Incident\ResolveIncidentRequest;
use App\Services\Incident\IncidentWorkflowService;
use App\Http\Requests\Incident\StoreIncidentCommentRequest;
use App\Http\Resources\IncidentCommentResource;
use App\Services\Incident\IncidentCommentService;
use App\Http\Requests\Incident\StoreIncidentPhotoRequest;
use App\Http\Resources\IncidentPhotoResource;
use App\Models\IncidentPhoto;
use App\Services\Incident\IncidentPhotoService;
use App\Http\Resources\IncidentStatusHistoryResource;
use App\Http\Requests\Incident\IndexIncidentRequest;
use App\Services\Incident\IncidentStatisticsService;
use App\Http\Requests\Incident\IncidentTrendRequest;

class IncidentController extends Controller
{
    public function index(IndexIncidentRequest $request)
    {
        $query = Incident::query()
            ->with([
                'reporter:id,name',
                'category:id,name',
            ]);

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')
            );
        }

        if ($request->filled('severity')) {
            $query->where(
                'severity',
                $request->string('severity')
            );
        }

        if ($request->filled('category_id')) {
            $query->where(
                'category_id',
                $request->string('category_id')
            );
        }

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($query) use ($search) {
                $query
                    ->where('incident_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', '-reported_at');

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $column = ltrim($sort, '-');

        if ($column === 'severity') {
            $query->orderByRaw(
                "CASE severity
                    WHEN 'critical' THEN 4
                    WHEN 'high' THEN 3
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 1
                END {$direction}"
            );
        } else {
            $query->orderBy($column, $direction);
        }

        $perPage = $request->integer('per_page', 15);

        return IncidentResource::collection(
            $query->paginate($perPage)
        );
    }
    public function store(
        StoreIncidentRequest $request,
        IncidentNumberService $numberService
    ): IncidentResource {
        Gate::authorize('create', Incident::class);

        $incident = Incident::create([
            'incident_number' => $numberService->generate(),

            'user_id' => $request->user()->id,

            'category_id' => $request->category_id,

            'title' => $request->title,

            'description' => $request->description,

            'location_address' => $request->location_address,

            'latitude' => $request->latitude,

            'longitude' => $request->longitude,

            'severity' => $request->severity,

            'status' => 'pending',

            'reported_at' => now(),
        ]);

        $incident->load([
            'category',
            'reporter',
        ]);

        return new IncidentResource($incident);
    }

    public function show(
        Request $request,
        Incident $incident
    ): IncidentResource {
        Gate::authorize('view', $incident);

        $incident->load([
            'category',
            'reporter',
            'photos',
            'comments',
            'statusHistories',
        ]);

        return new IncidentResource($incident);
    }

    public function investigate(
        InvestigateIncidentRequest $request,
        Incident $incident,
        IncidentWorkflowService $workflow
    ): IncidentResource {
        Gate::authorize('investigate', $incident);

        $incident = $workflow->investigate(
            $incident,
            $request->user(),
            $request->validated('notes')
        );

        return new IncidentResource($incident);
    }

    public function resolve(
        ResolveIncidentRequest $request,
        Incident $incident,
        IncidentWorkflowService $workflow
    ): IncidentResource {
        Gate::authorize('resolve', $incident);

        $incident = $workflow->resolve(
            $incident,
            $request->user(),
            $request->validated('notes')
        );

        return new IncidentResource($incident);
    }

    public function storeComment(
        StoreIncidentCommentRequest $request,
        Incident $incident,
        IncidentCommentService $commentService
    ): IncidentCommentResource {
        $comment = $commentService->create(
            $incident,
            $request->user(),
            $request->validated('comment')
        );

        return new IncidentCommentResource($comment);
    }

    public function comments(
        Request $request,
        Incident $incident
    ) {
        Gate::authorize('view', $incident);

        $comments = $incident
            ->comments()
            ->with('user')
            ->latest()
            ->get();

        return IncidentCommentResource::collection($comments);
    }

    public function storePhoto(
        StoreIncidentPhotoRequest $request,
        Incident $incident,
        IncidentPhotoService $photoService
    ): IncidentPhotoResource {
        Gate::authorize('comment', $incident);
        
        $photo = $photoService->upload(
            $incident,
            $request->user(),
            $request->file('photo')
        );

        return new IncidentPhotoResource($photo);
    }

    public function photos(
        Request $request,
        Incident $incident
    ) {
        Gate::authorize('view', $incident);

        return IncidentPhotoResource::collection(
            $incident->photos()->latest()->get()
        );
    }

    public function destroyPhoto(
        Request $request,
        Incident $incident,
        IncidentPhoto $photo,
        IncidentPhotoService $photoService
    ) {
        Gate::authorize('comment', $incident);

        abort_unless(
            $photo->incident_id === $incident->id,
            404
        );

        $photoService->delete($photo);

        return response()->json([
            'message' => 'Incident photo deleted successfully.',
        ]);
    }

    public function history(Incident $incident)
    {
        Gate::authorize('view', $incident);

        $histories = $incident->statusHistories()
            ->with('user')
            ->get();

        return IncidentStatusHistoryResource::collection($histories);
    }

    public function statistics(
        IncidentStatisticsService $statisticsService
    ) {
        return response()->json([
            'data' => $statisticsService->getStatistics(),
        ]);
    }

    public function statisticsByCategory(
        IncidentStatisticsService $statisticsService
    ) {
        return response()->json([
            'data' => $statisticsService->getByCategory(),
        ]);
    }

    public function statisticsTrends(
        IncidentTrendRequest $request,
        IncidentStatisticsService $statisticsService
    ) {
        return response()->json([
            'data' => [
                'period' => $request->period(),
                'items' => $statisticsService->getTrends(
                    $request->period()
                ),
            ],
        ]);
    }
}