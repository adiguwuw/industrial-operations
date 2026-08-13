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

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Incident::query()
            ->with([
                'category',
                'reporter',
            ])
            ->latest('reported_at');

        if (!$user->can('incident.view-all')) {
            $query->where('user_id', $user->id);
        }

        return IncidentResource::collection(
            $query->paginate(15)
        );
    }

    public function store(
        StoreIncidentRequest $request,
        IncidentNumberService $numberService
    ): IncidentResource {
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
}