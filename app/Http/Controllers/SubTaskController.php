<?php

namespace App\Http\Controllers;

use App\Models\SubTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubTaskController extends Controller
{
    public function complete(SubTask $subTask): RedirectResponse
    {
        if (! $this->canModifySubTask(request(), $subTask)) {
            return back()->with('error', 'You do not have permission to complete this subtask.');
        }

        if ($subTask->status !== 'pending') {
            return back()->with('error', 'This subtask is already closed.');
        }

        $subTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Subtask completed successfully.');
    }

    public function cancel(Request $request, SubTask $subTask): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string'],
        ]);

        if (! $this->canModifySubTask($request, $subTask)) {
            return back()->with('error', 'You do not have permission to cancel this subtask.');
        }

        if ($subTask->status !== 'pending') {
            return back()->with('error', 'This subtask is already closed.');
        }

        $subTask->update([
            'status' => 'cancelled',
            'remarks' => $validated['remarks'],
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Subtask cancelled successfully.');
    }

    private function canModifySubTask(Request $request, SubTask $subTask): bool
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(403);
        }

        if ($user->can('task.edit_any')) {
            return true;
        }

        $subTask->loadMissing('task');

        if ((int) $subTask->task?->assigned_user_id === (int) $user->id) {
            return true;
        }

        $user->loadMissing('positions');
        $subTask->loadMissing('task.taskTemplate.responsiblePositions');

        $responsiblePositionIds = $subTask->task?->taskTemplate?->responsiblePositions?->pluck('id') ?? collect();

        return $responsiblePositionIds->intersect($user->positions->pluck('id'))->isNotEmpty();
    }
}
