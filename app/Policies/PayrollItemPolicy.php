<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\User;

class PayrollItemPolicy
{
    public function viewPayslip(User $user, PayrollItem $item): bool
    {
        return $user->can('view', $item->payrollRun);
    }

    public function sendPayslipEmail(User $user, PayrollItem $item): bool
    {
        return $this->viewPayslip($user, $item);
    }
}
