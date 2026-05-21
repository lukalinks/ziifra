<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'start_date' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
}
