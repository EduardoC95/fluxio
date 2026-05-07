<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsFluxioActivity;
use App\Models\CalendarAction;
use App\Models\CalendarEvent;
use App\Models\CalendarType;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanningController extends Controller
{
    use LogsFluxioActivity;

    public function index(Request $request): Response
    {
        $filters = [
            'user_id' => $request->integer('user_id') ?: null,
            'entity_id' => $request->integer('entity_id') ?: null,
        ];

        $events = CalendarEvent::query()
            ->with(['user', 'entity', 'type', 'action'])
            ->when($filters['user_id'], fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['entity_id'], fn ($query, $entityId) => $query->where('entity_id', $entityId))
            ->orderBy('scheduled_for')
            ->get();

        return Inertia::render('Calendar/Index', [
            'records' => $events->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'user_id' => $event->user_id,
                'user_name' => $event->user?->name,
                'entity_id' => $event->entity_id,
                'entity_name' => $event->entity?->name,
                'calendar_type_id' => $event->calendar_type_id,
                'calendar_action_id' => $event->calendar_action_id,
                'scheduled_for' => optional($event->scheduled_for)->format('Y-m-d\TH:i'),
                'duration_minutes' => $event->duration_minutes,
                'shared' => $event->shared,
                'knowledge' => $event->knowledge,
                'description' => $event->description,
                'status' => $event->status,
                'title' => trim(($event->type?->name ?? 'Atividade').' - '.($event->entity?->name ?? $event->user?->name ?? 'Fluxio')),
                'start' => optional($event->scheduled_for)->toIso8601String(),
                'end' => optional($event->scheduled_for?->clone()->addMinutes($event->duration_minutes))->toIso8601String(),
                'backgroundColor' => $event->type?->color ?? '#b08968',
                'borderColor' => $event->action?->color ?? '#7c6f64',
            ])->values(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'entities' => Entity::query()->orderBy('number')->get()->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'label' => sprintf('%s - %s', $entity->number, $entity->name),
            ])->values(),
            'types' => CalendarType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'actions' => CalendarAction::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
            'filters' => $filters,
            'defaults' => [
                'user_id' => $request->user()?->id,
                'scheduled_for' => now()->format('Y-m-d\TH:i'),
                'duration_minutes' => 60,
                'shared' => false,
                'knowledge' => false,
                'status' => 'scheduled',
            ],
            'endpoints' => [
                'store' => '/calendario',
                'update' => '/calendario',
                'delete' => '/calendario',
                'filter' => '/calendario',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = CalendarEvent::query()->create($this->validatedPayload($request));

        $this->logFluxioActivity($request, 'Calendário', 'create', $event);

        return back()->with($this->flashToast('success', 'Atividade agendada com sucesso.'));
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->update($this->validatedPayload($request));

        $this->logFluxioActivity($request, 'Calendário', 'update', $calendarEvent);

        return back()->with($this->flashToast('success', 'Atividade atualizada com sucesso.'));
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->delete();

        $this->logFluxioActivity($request, 'Calendário', 'delete');

        return back()->with($this->flashToast('success', 'Atividade removida com sucesso.'));
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'entity_id' => ['nullable', 'exists:entities,id'],
            'calendar_type_id' => ['nullable', 'exists:calendar_types,id'],
            'calendar_action_id' => ['nullable', 'exists:calendar_actions,id'],
            'scheduled_for' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'shared' => ['boolean'],
            'knowledge' => ['boolean'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);

        return [
            ...$validated,
            'shared' => (bool) ($validated['shared'] ?? false),
            'knowledge' => (bool) ($validated['knowledge'] ?? false),
        ];
    }
}
