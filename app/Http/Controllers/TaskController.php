<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $clientId = $request->query('client_id');

        $taskTemplates = TaskTemplate::query()
            ->with([
                'responsiblePositions.users',
                'accountablePositions.users',
                'consultedPositions.users',
                'informedPositions.users',
                'tasks' => function ($query) use ($clientId) {
                    $query->with(['client', 'dropbox', 'subTasks.subTaskTemplate', 'assignedUser'])
                    ->where('status', 'pending')
                    ->when($clientId, fn ($taskQuery) => $taskQuery->where('client_id', $clientId))
                    ->orderBy('created_at');
                },
            ])
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $clients = Client::orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('tasks.index', [
            'taskTemplates' => $taskTemplates,
            'clients' => $clients,
            'clientId' => $clientId,
        ]);
    }

    public function list(): View
    {
        $tasks = Task::with(['taskTemplate', 'client', 'dropbox', 'assignedUser'])
            ->orderByDesc('created_at')
            ->get();

        return view('tasks.list', [
            'tasks' => $tasks,
        ]);
    }

    public function assigned(Request $request): View
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(403);
        }

        $tasks = Task::with(['taskTemplate', 'client', 'dropbox', 'assignedUser'])
            ->where('assigned_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('tasks.assigned', [
            'tasks' => $tasks,
        ]);
    }

    public function show(Request $request, Task $task): View
    {
        $task->load(['taskTemplate', 'client', 'dropbox', 'subTasks.subTaskTemplate', 'assignedUser']);

        $users = User::orderBy('name')->get();
        $canReassign = $this->canReassignTask($request, $task);
        $canModifyTask = $this->canModifyTask($request, $task);
        $hasPendingSubTasks = $task->subTasks->where('status', 'pending')->isNotEmpty();

        return view('tasks.show', [
            'task' => $task,
            'users' => $users,
            'canReassign' => $canReassign,
            'canModifyTask' => $canModifyTask,
            'hasPendingSubTasks' => $hasPendingSubTasks,
        ]);
    }

    public function complete(Request $request, Task $task, TaskWorkflowService $workflow): RedirectResponse
    {
        $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        if (! $this->canModifyTask($request, $task)) {
            return back()->with('error', 'You do not have permission to complete this task.');
        }

        if ($task->status !== 'pending') {
            return back()->with('error', 'This task is already closed.');
        }

        $hasPendingSubTasks = $task->subTasks()->where('status', 'pending')->exists();

        if ($hasPendingSubTasks) {
            return back()->with('error', 'Complete or cancel all subtasks before finishing this task.');
        }

        $task->update([
            'status' => 'completed',
            'remarks' => $request->input('remarks'),
            'completed_at' => now(),
        ]);

        $workflow->triggerNextTask($task);

        return back()->with('success', 'Task completed successfully.');
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string'],
        ]);

        if (! $this->canModifyTask($request, $task)) {
            return back()->with('error', 'You do not have permission to cancel this task.');
        }

        if ($task->status !== 'pending') {
            return back()->with('error', 'This task is already closed.');
        }

        $task->update([
            'status' => 'cancelled',
            'remarks' => $validated['remarks'],
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Task cancelled successfully.');
    }

    public function reassign(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (! $this->canReassignTask($request, $task)) {
            return back()->with('error', 'You do not have permission to reassign this task.');
        }

        $task->update([
            'assigned_user_id' => $validated['assigned_user_id'],
        ]);

        return back()->with('success', 'Task reassigned successfully.');
    }

    private function canModifyTask(Request $request, Task $task): bool
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(403);
        }

        if ($user->can('task.edit_any')) {
            return true;
        }

        if ((int) $task->assigned_user_id === (int) $user->id) {
            return true;
        }

        $user->loadMissing('positions');
        $task->loadMissing('taskTemplate.responsiblePositions');

        $responsiblePositionIds = $task->taskTemplate
            ? $task->taskTemplate->responsiblePositions->pluck('id')
            : collect();

        return $responsiblePositionIds->intersect($user->positions->pluck('id'))->isNotEmpty();
    }

    private function canReassignTask(Request $request, Task $task): bool
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(403);
        }

        if ($user->can('task.edit_any')) {
            return true;
        }

        return (int) $task->assigned_user_id === (int) $user->id;
    }
}
