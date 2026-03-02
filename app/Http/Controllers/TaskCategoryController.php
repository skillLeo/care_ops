<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use Illuminate\Http\Request;

class TaskCategoryController extends Controller
{
    public function index()
    {
        $categories = TaskCategory::orderBy('name')->get();

        return view('task_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        TaskCategory::create($validated);

        return redirect()->route('task-categories.index')
            ->with('success', 'Task category created successfully.');
    }

    public function edit(TaskCategory $taskCategory)
    {
        return view('task_categories.edit', compact('taskCategory'));
    }

    public function update(Request $request, TaskCategory $taskCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $taskCategory->update($validated);

        return redirect()->route('task-categories.index')
            ->with('success', 'Task category updated successfully.');
    }

    public function destroy(TaskCategory $taskCategory)
    {
        $taskCategory->delete();

        return redirect()->route('task-categories.index')
            ->with('success', 'Task category deleted successfully.');
    }
}
