<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function viewAny(User $user, Employee $employee): bool
    {
        return $user->can('view', $employee);
    }

    public function create(User $user, Employee $employee): bool
    {
        return $user->can('update', $employee);
    }

    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->can('view', $document->employee);
    }

    public function delete(User $user, EmployeeDocument $document): bool
    {
        return $user->can('update', $document->employee);
    }
}
