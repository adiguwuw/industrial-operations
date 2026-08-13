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

    public function storePhoto(
        StoreIncidentPhotoRequest $request,
        Incident $incident,
        IncidentPhotoService $photoService
    ): IncidentPhotoResource {
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
}