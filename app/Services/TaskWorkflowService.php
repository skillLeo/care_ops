<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Dropbox;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskTemplate;
use Illuminate\Support\Facades\DB;

class TaskWorkflowService
{
    public function triggerFirstTaskForCategory(string $categoryName, Client $client): ?Task
    {
        $category = TaskCategory::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
            ->first();

        if (! $category) {
            return null;
        }

        $template = $category->taskTemplates()
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (! $template) {
            return null;
        }

        if ($this->taskExistsForClient($template, $client)) {
            return null;
        }

        return $this->createTaskFromTemplate($template, $client);
    }

    public function triggerFirstTaskForDropbox(string $categoryName, Dropbox $dropbox): ?Task
    {
        $category = TaskCategory::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
            ->first();

        if (! $category) {
            return null;
        }

        $template = $category->taskTemplates()
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (! $template) {
            return null;
        }

        if ($this->taskExistsForDropbox($template, $dropbox)) {
            return null;
        }

        return $this->createTaskFromTemplateForDropbox($template, $dropbox);
    }

    public function triggerNextTask(Task $task): ?Task
    {
        $task->loadMissing('taskTemplate.category');
        $template = $task->taskTemplate;
        $category = $template?->category;

        if (! $category) {
            return null;
        }

        if (! $task->client) {
            return null;
        }

        $nextTemplate = $category->taskTemplates()
            ->where('order', '>', $template->order)
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (! $nextTemplate) {
            return null;
        }

        if ($this->taskExistsForClient($nextTemplate, $task->client)) {
            return null;
        }

        return $this->createTaskFromTemplate($nextTemplate, $task->client);
    }

    public function createTaskFromTemplate(TaskTemplate $template, Client $client): Task
    {
        return DB::transaction(function () use ($template, $client) {
            $assignedUserId = $this->resolveAssignedUserId($template);
            $task = Task::create([
                'task_template_id' => $template->id,
                'client_id' => $client->id,
                'assigned_user_id' => $assignedUserId,
                'status' => 'pending',
            ]);

            $subTaskTemplates = $template->subTaskTemplates()
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            foreach ($subTaskTemplates as $subTaskTemplate) {
                SubTask::create([
                    'task_id' => $task->id,
                    'sub_task_template_id' => $subTaskTemplate->id,
                    'order' => $subTaskTemplate->order,
                    'status' => 'pending',
                ]);
            }

            return $task;
        });
    }

    public function createTaskFromTemplateForDropbox(TaskTemplate $template, Dropbox $dropbox): Task
    {
        return DB::transaction(function () use ($template, $dropbox) {
            $assignedUserId = $this->resolveAssignedUserId($template);
            $task = Task::create([
                'task_template_id' => $template->id,
                'dropbox_id' => $dropbox->id,
                'assigned_user_id' => $assignedUserId,
                'status' => 'pending',
            ]);

            $subTaskTemplates = $template->subTaskTemplates()
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            foreach ($subTaskTemplates as $subTaskTemplate) {
                SubTask::create([
                    'task_id' => $task->id,
                    'sub_task_template_id' => $subTaskTemplate->id,
                    'order' => $subTaskTemplate->order,
                    'status' => 'pending',
                ]);
            }

            return $task;
        });
    }

    public function assignDropboxTasksToClient(Dropbox $dropbox, Client $client): int
    {
        return Task::query()
            ->where('dropbox_id', $dropbox->id)
            ->whereNull('client_id')
            ->update(['client_id' => $client->id]);
    }

    private function taskExistsForClient(TaskTemplate $template, Client $client): bool
    {
        return Task::query()
            ->where('task_template_id', $template->id)
            ->where('client_id', $client->id)
            ->exists();
    }

    private function taskExistsForDropbox(TaskTemplate $template, Dropbox $dropbox): bool
    {
        return Task::query()
            ->where('task_template_id', $template->id)
            ->where('dropbox_id', $dropbox->id)
            ->exists();
    }

    private function resolveAssignedUserId(TaskTemplate $template): ?int
    {
        $template->loadMissing('responsiblePositions.users');

        return $template->responsiblePositions
            ->flatMap(fn ($position) => $position->users)
            ->pluck('id')
            ->first();
    }
}
