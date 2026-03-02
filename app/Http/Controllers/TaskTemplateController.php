<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\TaskCategory;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskTemplateController extends Controller
{
    public function index()
    {
        $taskTemplates = TaskTemplate::with(['category', 'subTaskTemplates'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('task_templates.index', compact('taskTemplates'));
    }

    public function create()
    {
        $categories = TaskCategory::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $nextOrder = max(1, (TaskTemplate::max('order') ?? 0) + 1);

        return view('task_templates.create', compact('categories', 'positions', 'nextOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_category_id' => ['required', 'exists:task_categories,id'],
            'order' => ['nullable', 'integer', 'min:1', Rule::unique('task_templates', 'order')],
            'responsible_position_ids' => ['nullable', 'array'],
            'responsible_position_ids.*' => ['integer', 'exists:positions,id'],
            'accountable_position_ids' => ['nullable', 'array'],
            'accountable_position_ids.*' => ['integer', 'exists:positions,id'],
            'consulted_position_ids' => ['nullable', 'array'],
            'consulted_position_ids.*' => ['integer', 'exists:positions,id'],
            'informed_position_ids' => ['nullable', 'array'],
            'informed_position_ids.*' => ['integer', 'exists:positions,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $order = $validated['order'] ?? max(1, (TaskTemplate::max('order') ?? 0) + 1);
            $taskTemplate = TaskTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'task_category_id' => $validated['task_category_id'],
                'order' => $order,
            ]);

            $taskTemplate->responsiblePositions()->sync($validated['responsible_position_ids'] ?? []);
            $taskTemplate->accountablePositions()->sync($validated['accountable_position_ids'] ?? []);
            $taskTemplate->consultedPositions()->sync($validated['consulted_position_ids'] ?? []);
            $taskTemplate->informedPositions()->sync($validated['informed_position_ids'] ?? []);
        });

        return redirect()->route('task-templates.index')
            ->with('success', 'Task template created successfully.');
    }

    public function edit(TaskTemplate $taskTemplate)
    {
        $taskTemplate->load([
            'responsiblePositions',
            'accountablePositions',
            'consultedPositions',
            'informedPositions',
        ]);
        $categories = TaskCategory::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('task_templates.edit', compact('taskTemplate', 'categories', 'positions'));
    }

    public function update(Request $request, TaskTemplate $taskTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_category_id' => ['required', 'exists:task_categories,id'],
            'order' => ['nullable', 'integer', 'min:1', Rule::unique('task_templates', 'order')->ignore($taskTemplate->id)],
            'responsible_position_ids' => ['nullable', 'array'],
            'responsible_position_ids.*' => ['integer', 'exists:positions,id'],
            'accountable_position_ids' => ['nullable', 'array'],
            'accountable_position_ids.*' => ['integer', 'exists:positions,id'],
            'consulted_position_ids' => ['nullable', 'array'],
            'consulted_position_ids.*' => ['integer', 'exists:positions,id'],
            'informed_position_ids' => ['nullable', 'array'],
            'informed_position_ids.*' => ['integer', 'exists:positions,id'],
        ]);

        DB::transaction(function () use ($validated, $taskTemplate) {
            $order = $validated['order'] ?? $taskTemplate->order;
            $taskTemplate->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'task_category_id' => $validated['task_category_id'],
                'order' => $order,
            ]);

            $taskTemplate->responsiblePositions()->sync($validated['responsible_position_ids'] ?? []);
            $taskTemplate->accountablePositions()->sync($validated['accountable_position_ids'] ?? []);
            $taskTemplate->consultedPositions()->sync($validated['consulted_position_ids'] ?? []);
            $taskTemplate->informedPositions()->sync($validated['informed_position_ids'] ?? []);
        });

        return redirect()->route('task-templates.index')
            ->with('success', 'Task template updated successfully.');
    }

    public function destroy(TaskTemplate $taskTemplate)
    {
        $taskTemplate->delete();

        return redirect()->route('task-templates.index')
            ->with('success', 'Task template deleted successfully.');
    }
}
