@extends('adminlte::page')

@section('title', 'Task Pipeline')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <h1 class="mb-2">Task Pipeline</h1>
        <form method="GET" action="{{ route('tasks.index') }}" class="form-inline mb-2">
            <label for="client_id" class="mr-2">Client</label>
            <select name="client_id" id="client_id" class="form-control mr-2">
                <option value="">All clients</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected($clientId == $client->id)>
                        {{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="task-board d-flex flex-nowrap overflow-auto pb-3">
        @forelse ($taskTemplates as $taskTemplate)
            @php
                $currentUser = auth()->user();
                $canModifyTemplate = $currentUser
                    && (
                        $currentUser->can('task.edit_any')
                        || $taskTemplate->responsiblePositions
                            ->pluck('id')
                            ->intersect($currentUser->positions->pluck('id'))
                            ->isNotEmpty()
                    );
            @endphp
            <div class="card task-template-card mr-3">
                <div class="card-header bg-white">
                    <strong>{{ strtoupper($taskTemplate->name) }}</strong>
                </div>
                <div class="card-body">
                    @forelse ($taskTemplate->tasks as $task)
                        @php
                            $activeSubTasks = $task->subTasks
                                ->where('status', 'pending')
                                ->sortBy('order');
                            $completedSubTasks = $task->subTasks
                                ->where('status', 'completed')
                                ->sortBy('order');
                            $hasSubTasks = $activeSubTasks->count() + $completedSubTasks->count() > 0;
                            $responsibleUserIds = $taskTemplate->responsiblePositions
                                ->flatMap(fn ($position) => $position->users)
                                ->pluck('id')
                                ->unique();
                            $assignedUser = $task->assignedUser;
                            $showAssignedUser = $assignedUser && ! $responsibleUserIds->contains($assignedUser->id);
                        @endphp
                        <div class="task-item mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-grow-1">
                                    @if ($hasSubTasks)
                                        <button class="btn btn-link p-0 mr-2 task-toggle" type="button" data-target="#task-{{ $task->id }}-subtasks">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    @else
                                        <span class="task-toggle-placeholder mr-2"></span>
                                    @endif
                                    <button
                                        type="button"
                                        class="btn btn-link p-0 mr-2 task-check {{ $activeSubTasks->isNotEmpty() || ! $canModifyTemplate ? 'disabled' : '' }}"
                                        data-action="{{ route('tasks.complete', $task) }}"
                                        data-name="{{ $task->subject_name }}"
                                        {{ $activeSubTasks->isNotEmpty() || ! $canModifyTemplate ? 'disabled' : '' }}
                                    >
                                        <span class="task-circle"></span>
                                    </button>
                                    <div>
                                        <div class="task-client-name">{{ $task->subject_name }}</div>
                                        @if (! $task->client && $task->dropbox)
                                            <div class="text-muted small">Prospective client</div>
                                        @endif
                                        <div class="text-muted small">Created {{ $task->created_at->format('m/d/Y g:i A') }}</div>
                                        @if ($showAssignedUser)
                                            <div class="text-muted small">Assigned to {{ $assignedUser->name }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-link p-0 mr-2 text-secondary" title="View task">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-link text-danger p-0 task-cancel {{ $canModifyTemplate ? '' : 'disabled' }}"
                                        data-action="{{ route('tasks.cancel', $task) }}"
                                        data-name="{{ $task->subject_name }}"
                                        {{ $canModifyTemplate ? '' : 'disabled' }}
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @if ($hasSubTasks)
                                <div id="task-{{ $task->id }}-subtasks" class="subtask-list collapse show">
                                    @foreach ($activeSubTasks as $subTask)
                                        <div class="d-flex align-items-center justify-content-between subtask-item">
                                            <div class="d-flex align-items-center">
                                                <form action="{{ route('sub-tasks.complete', $subTask) }}" method="POST" class="mr-2">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link p-0" {{ $canModifyTemplate ? '' : 'disabled' }}>
                                                        <span class="task-circle task-circle-sm"></span>
                                                    </button>
                                                </form>
                                                <div class="subtask-name">{{ $subTask->subTaskTemplate?->name }}</div>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-link text-danger p-0 subtask-cancel {{ $canModifyTemplate ? '' : 'disabled' }}"
                                                data-action="{{ route('sub-tasks.cancel', $subTask) }}"
                                                data-name="{{ $subTask->subTaskTemplate?->name }}"
                                                {{ $canModifyTemplate ? '' : 'disabled' }}
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                    @foreach ($completedSubTasks as $subTask)
                                        <div class="d-flex align-items-center justify-content-between subtask-item completed">
                                            <div class="d-flex align-items-center">
                                                <span class="task-circle task-circle-sm task-circle-complete mr-2"></span>
                                                <div class="subtask-name">{{ $subTask->subTaskTemplate?->name }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No tasks yet.</p>
                    @endforelse
                </div>
                <div class="card-footer bg-white">
                    <div class="raci-list">
                        @php
                            $raciGroups = [
                                'R' => $taskTemplate->responsiblePositions,
                                'A' => $taskTemplate->accountablePositions,
                                'C' => $taskTemplate->consultedPositions,
                                'I' => $taskTemplate->informedPositions,
                            ];
                        @endphp
                        @foreach ($raciGroups as $label => $positions)
                            <div class="raci-item">
                                <span class="raci-label">{{ $label }}</span>
                                <span class="text-muted">
                                    @php
                                        $raciNames = $positions
                                            ->flatMap(function ($position) {
                                                $userShortNames = $position->users->pluck('short_name')->filter();

                                                return $userShortNames->isNotEmpty()
                                                    ? $userShortNames
                                                    : collect([$position->name]);
                                            })
                                            ->unique()
                                            ->values();
                                    @endphp
                                    {{ $raciNames->implode(', ') ?: '—' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No task templates found.</p>
        @endforelse
    </div>

    <div class="modal fade" id="taskCompleteModal" tabindex="-1" role="dialog" aria-labelledby="taskCompleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" class="modal-content" id="taskCompleteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="taskCompleteModalLabel">Complete Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Mark task for <strong id="taskCompleteName"></strong> as complete?</p>
                    <div class="form-group">
                        <label for="taskCompleteRemarks">Remarks (optional)</label>
                        <textarea class="form-control" name="remarks" id="taskCompleteRemarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="taskCancelModal" tabindex="-1" role="dialog" aria-labelledby="taskCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" class="modal-content" id="taskCancelForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="taskCancelModalLabel">Cancel Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Cancel <strong id="taskCancelName"></strong>?</p>
                    <div class="form-group">
                        <label for="taskCancelRemarks">Remarks (required)</label>
                        <textarea class="form-control" name="remarks" id="taskCancelRemarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-danger">Confirm</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        .task-board {
            gap: 1rem;
            align-items: stretch;
        }

        .task-template-card {
            min-width: 320px;
            max-width: 320px;
            background-color: #f4f6f9;
            border: 1px solid #e1e5ea;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 220px);
        }

        .task-template-card .card-body {
            overflow-y: auto;
            flex: 1 1 auto;
        }

        .task-template-card .card-footer {
            border-top: 1px solid #e1e5ea;
        }

        .task-item {
            background: #fff;
            border: 1px solid #e1e5ea;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .task-client-name {
            font-weight: 600;
        }

        .task-toggle-placeholder {
            width: 1rem;
            height: 1rem;
        }

        .task-toggle i {
            transition: transform 0.2s ease;
        }

        .task-toggle.is-open i {
            transform: rotate(90deg);
        }

        .task-circle {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #6c757d;
            display: inline-block;
        }

        .task-circle-sm {
            width: 14px;
            height: 14px;
        }

        .task-circle-complete {
            border-color: #28a745;
            background-color: #28a745;
        }

        .task-check.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .task-cancel.disabled,
        .subtask-cancel.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .subtask-list {
            margin-top: 0.75rem;
            padding-left: 2rem;
        }

        .subtask-item {
            padding: 0.4rem 0;
            border-top: 1px solid #f1f3f5;
        }

        .subtask-item:first-child {
            border-top: none;
        }

        .subtask-item.completed .subtask-name {
            text-decoration: line-through;
            color: #6c757d;
        }

        .raci-list {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.75rem;
        }

        .raci-item {
            display: flex;
            gap: 0.5rem;
        }

        .raci-label {
            font-weight: 700;
            min-width: 1rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('.task-toggle').each(function () {
                const target = $(this).data('target');
                if ($(target).hasClass('show')) {
                    $(this).addClass('is-open');
                }
            });

            $('.task-toggle').on('click', function () {
                const target = $(this).data('target');
                $(target).collapse('toggle');
                $(this).toggleClass('is-open');
            });

            $('#taskCompleteModal').on('show.bs.modal', function () {
                $('#taskCompleteRemarks').val('');
            });

            $('.task-check').on('click', function () {
                const action = $(this).data('action');
                const name = $(this).data('name');
                $('#taskCompleteForm').attr('action', action);
                $('#taskCompleteName').text(name);
                $('#taskCompleteModal').modal('show');
            });

            $('.task-cancel, .subtask-cancel').on('click', function () {
                const action = $(this).data('action');
                const name = $(this).data('name');
                $('#taskCancelForm').attr('action', action);
                $('#taskCancelName').text(name);
                $('#taskCancelRemarks').val('');
                $('#taskCancelModal').modal('show');
            });
        });
    </script>
@stop
