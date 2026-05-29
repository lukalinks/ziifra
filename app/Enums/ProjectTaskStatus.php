<?php

namespace App\Enums;

enum ProjectTaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return __('projects.task_statuses.'.$this->value);
    }
}
