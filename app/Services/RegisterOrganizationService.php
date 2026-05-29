<?php

namespace App\Services;

use App\Enums\EmploymentType;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Enums\WorkWeekDay;
use App\Mail\WelcomeMail;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeaveRequestService;
use App\Services\OrganizationContractTemplateService;
use App\Services\BillingConfigurationService;
use Illuminate\Support\Facades\DB;

class RegisterOrganizationService
{
    /**
     * @return array{user: User, organization: Organization}
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $companyName,
    ): array {
        $result = DB::transaction(function () use ($name, $email, $password, $companyName) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $organization = Organization::query()->create([
                'name' => $companyName,
                'country_code' => 'XK',
                'timezone' => config('app.timezone', 'Europe/Zurich'),
                'currency' => 'EUR',
                'locale' => app(LocaleConfigurationService::class)->defaultCode(),
                'work_week_days' => array_map(
                    fn (WorkWeekDay $day) => $day->value,
                    WorkWeekDay::defaultWorkWeek(),
                ),
                'fiscal_year_start_month' => 1,
                'date_format' => 'd/m/Y',
                'observe_kosovo_holidays' => true,
                'default_employment_type' => EmploymentType::FullTime->value,
                'hr_can_invite' => true,
                'owner_id' => $user->id,
                'plan' => SubscriptionPlan::Trial->value,
                'trial_ends_at' => now()->addDays(app(BillingConfigurationService::class)->trialDays()),
            ]);

            $organization->users()->attach($user->id, [
                'role' => OrganizationRole::Owner->value,
                'joined_at' => now(),
            ]);

            LeaveRequestService::seedDefaultTypes($organization);
            app(OrganizationContractTemplateService::class)->seedDefaults($organization);

            return [
                'user' => $user,
                'organization' => $organization,
            ];
        });

        app(OrganizationMailService::class)->queue(
            $result['organization'],
            $result['user']->email,
            new WelcomeMail($result['user'], $result['organization']),
        );

        return $result;
    }
}
