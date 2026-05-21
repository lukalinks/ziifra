<?php

namespace Database\Factories;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'start_date' => $start,
            'end_date' => $start,
            'days' => 1,
            'status' => LeaveRequestStatus::Pending,
        ];
    }

    public function forEmployee(Employee $employee, LeaveType $leaveType, User $submittedBy): static
    {
        return $this->state(fn () => [
            'organization_id' => $employee->organization_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'submitted_by_user_id' => $submittedBy->id,
        ]);
    }
}
