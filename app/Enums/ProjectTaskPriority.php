<?php

namespace App\Enums;

enum ProjectTaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return __('projects.task_priorities.'.$this->value);
    }
}
