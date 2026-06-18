<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventAttendeeRequest;
use App\Http\Resources\EventVisualResource;
use App\Jobs\SendAttendeeConfirmation;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Support\LocationResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventVisualController extends Controller
{
    public function visualOne(Request $request): Response
    {
        return $this->renderVisualPage('Events/VisualOne', $request);
    }

    public function visualTwo(Request $request): Response
    {
        return $this->renderVisualPage('Events/VisualTwo', $request);
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadVisualListing($request);

        return response()->json([
            'data' => EventVisualResource::collection($events->items())->resolve(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'per_page' => $events->perPage(),
            'stats' => $stats,
        ]);
    }

    public function storeAttendee(StoreEventAttendeeRequest $request, Event $event): JsonResponse
    {
        $attendee = EventAttendee::create([
            'event_id' => $event->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        SendAttendeeConfirmation::dispatch($attendee);

        return response()->json([
            'message' => 'You are on the list! A confirmation email has been sent.',
            'attendee' => [
                'id' => $attendee->id,
                'name' => $attendee->name,
                'email' => $attendee->email,
            ],
        ], 201);
    }

    private function renderVisualPage(string $component, Request $request): Response
    {
        return Inertia::render($component, [
            'cities' => LocationResolver::cityOptions(),
            'filters' => [
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'city' => $request->input('city'),
                'status' => $request->input('status', 'published'),
            ],
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: array{ms: int, bytes: int}}
     */
    private function loadVisualListing(Request $request): array
    {
        $start = microtime(true);
        $perPage = min((int) $request->input('per_page', 24), 60);

        $events = Event::query()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('attendees')
            ->when(
                $request->input('status', 'published'),
                fn ($q, $status) => $q->where('status', $status),
            )
            ->when($request->filled('city'), fn ($q) => $q->where('city_slug', $request->input('city')))
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $from = strtotime($request->input('date_from').' 00:00:00 UTC');
                if ($from !== false) {
                    $q->where('created_time', '>=', $from);
                }
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $to = strtotime($request->input('date_to').' 23:59:59 UTC');
                if ($to !== false) {
                    $q->where('created_time', '<=', $to);
                }
            })
            ->orderBy('created_time')
            ->paginate($perPage)
            ->withQueryString();

        $resource = EventVisualResource::collection($events->items())->resolve();
        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($resource)),
        ];

        return [$events, $stats];
    }
}
