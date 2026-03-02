@extends('adminlte::page')

@section('title', 'Task Details')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Task Details</h1>
        <a href="{{ route('tasks.list') }}" class="btn btn-secondary">Back to Tasks</a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-header bg-white">
            <strong>{{ $task->taskTemplate?->name ?? 'Task' }}</strong>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <button
                    type="button"
                    class="btn btn-primary mr-2 mb-2 task-complete {{ $canModifyTask && ! $hasPendingSubTasks && $task->status === 'pending' ? '' : 'disabled' }}"
                    data-action="{{ route('tasks.complete', $task) }}"
                    data-name="{{ $task->subject_name }}"
                    {{ $canModifyTask && ! $hasPendingSubTasks && $task->status === 'pending' ? '' : 'disabled' }}
                >
                    <i class="fas fa-check"></i> Complete Task
                </button>
                <button
                    type="button"
                    class="btn btn-danger mb-2 task-cancel {{ $canModifyTask && $task->status === 'pending' ? '' : 'disabled' }}"
                    data-action="{{ route('tasks.cancel', $task) }}"
                    data-name="{{ $task->subject_name }}"
                    {{ $canModifyTask && $task->status === 'pending' ? '' : 'disabled' }}
                >
                    <i class="fas fa-times"></i> Cancel Task
                </button>
                @if ($hasPendingSubTasks)
                    <span class="text-muted small ml-2">Complete or cancel all subtasks to finish this task.</span>
                @endif
            </div>

            <dl class="row">
                <dt class="col-sm-3">Client</dt>
                <dd class="col-sm-9">
                    {{ $task->subject_name }}
                    @if (! $task->client && $task->dropbox)
                        <span class="badge badge-secondary ml-2">Prospective</span>
                    @endif
                </dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ ucfirst($task->status) }}</dd>

                <dt class="col-sm-3">Assigned To</dt>
                <dd class="col-sm-9">{{ $task->assignedUser?->name ?? 'Unassigned' }}</dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $task->created_at->format('m/d/Y g:i A') }}</dd>

                <dt class="col-sm-3">Completed</dt>
                <dd class="col-sm-9">{{ $task->completed_at?->format('m/d/Y g:i A') ?? '—' }}</dd>

                <dt class="col-sm-3">Cancelled</dt>
                <dd class="col-sm-9">{{ $task->cancelled_at?->format('m/d/Y g:i A') ?? '—' }}</dd>

                <dt class="col-sm-3">Remarks</dt>
                <dd class="col-sm-9">{{ $task->remarks ?? '—' }}</dd>
            </dl>

            @if ($canReassign)
                <h5 class="mt-4">Reassign Task</h5>
                <form action="{{ route('tasks.reassign', $task) }}" method="POST" class="form-inline flex-wrap">
                    @csrf
                    <label for="assigned_user_id" class="mr-2">Assign to</label>
                    <select name="assigned_user_id" id="assigned_user_id" class="form-control mr-2 mb-2">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($task->assigned_user_id === $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary mb-2">Reassign</button>
                </form>
            @endif

            <h5 class="mt-4">Subtasks</h5>
            @if ($task->subTasks->isEmpty())
                <p class="text-muted">No subtasks available.</p>
            @else
                <ul class="list-group">
                    @foreach ($task->subTasks as $subTask)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                @if ($subTask->status === 'pending')
                                    <form action="{{ route('sub-tasks.complete', $subTask) }}" method="POST" class="mr-2">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0" {{ $canModifyTask ? '' : 'disabled' }}>
                                            <span class="task-circle task-circle-sm"></span>
                                        </button>
                                    </form>
                                @else
                                    <span class="task-circle task-circle-sm task-circle-complete mr-2"></span>
                                @endif
                                <span>{{ $subTask->subTaskTemplate?->name ?? 'Subtask' }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-{{ $subTask->status === 'completed' ? 'success' : ($subTask->status === 'cancelled' ? 'danger' : 'secondary') }} mr-2">
                                    {{ ucfirst($subTask->status) }}
                                </span>
                                @if ($subTask->status === 'pending')
                                    <button
                                        type="button"
                                        class="btn btn-link text-danger p-0 subtask-cancel {{ $canModifyTask ? '' : 'disabled' }}"
                                        data-action="{{ route('sub-tasks.cancel', $subTask) }}"
                                        data-name="{{ $subTask->subTaskTemplate?->name }}"
                                        {{ $canModifyTask ? '' : 'disabled' }}
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
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

        .task-complete.disabled,
        .task-cancel.disabled,
        .subtask-cancel.disabled {
            opacity: 0.4;
            pointer-events: none;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('.task-complete').on('click', function () {
                const action = $(this).data('action');
                const name = $(this).data('name');
                $('#taskCompleteForm').attr('action', action);
                $('#taskCompleteName').text(name);
                $('#taskCompleteRemarks').val('');
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
