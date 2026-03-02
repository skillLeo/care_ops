<?php

namespace App\Http\Controllers;

use App\Models\SubTaskTemplate;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubTaskTemplateController extends Controller
{
    public function index()
    {
        $subTaskTemplates = SubTaskTemplate::with('taskTemplate')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('sub_task_templates.index', compact('subTaskTemplates'));
    }

    public function create()
    {
        $taskTemplates = TaskTemplate::orderBy('name')->get();
        $nextOrder = max(1, (SubTaskTemplate::max('order') ?? 0) + 1);

        return view('sub_task_templates.create', compact('taskTemplates', 'nextOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_template_id' => ['required', 'exists:task_templates,id'],
            'order' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('sub_task_templates', 'order')->where(
                    fn ($query) => $query->where('task_template_id', $request->input('task_template_id'))
                ),
            ],
        ]);

        $order = $validated['order'] ?? max(1, (SubTaskTemplate::max('order') ?? 0) + 1);

        SubTaskTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'task_template_id' => $validated['task_template_id'],
            'order' => $order,
        ]);

        return redirect()->route('sub-task-templates.index')
            ->with('success', 'Sub-task template created successfully.');
    }

    public function edit(SubTaskTemplate $subTaskTemplate)
    {
        $taskTemplates = TaskTemplate::orderBy('name')->get();

        return view('sub_task_templates.edit', compact('subTaskTemplate', 'taskTemplates'));
    }

    public function update(Request $request, SubTaskTemplate $subTaskTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_template_id' => ['required', 'exists:task_templates,id'],
            'order' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('sub_task_templates', 'order')
                    ->where(fn ($query) => $query->where('task_template_id', $request->input('task_template_id')))
                    ->ignore($subTaskTemplate->id),
            ],
        ]);

        $order = $validated['order'] ?? $subTaskTemplate->order;

        $subTaskTemplate->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'task_template_id' => $validated['task_template_id'],
            'order' => $order,
        ]);

        return redirect()->route('sub-task-templates.index')
            ->with('success', 'Sub-task template updated successfully.');
    }

    public function destroy(SubTaskTemplate $subTaskTemplate)
    {
        $subTaskTemplate->delete();

        return redirect()->route('sub-task-templates.index')
            ->with('success', 'Sub-task template deleted successfully.');
    }
}
