<?php

namespace App\Console\Commands;

use App\Enums\EmployeeDocumentType;
use App\Enums\EmploymentType;
use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Enums\WorkWeekDay;
use App\Mail\DocumentExpiringMail;
use App\Mail\EmployeeLimitApproachingMail;
use App\Mail\LeaveRequestReviewedMail;
use App\Mail\LeaveRequestSubmittedMail;
use App\Mail\ResetPasswordMail;
use App\Mail\SubscriptionPaymentFailedMail;
use App\Mail\TeamInvitationMail;
use App\Mail\TrialEndingSoonMail;
use App\Mail\TrialExpiredMail;
use App\Mail\WelcomeMail;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Invitation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeaveRequestService;
use App\Support\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendAllTestEmailsCommand extends Command
{
    protected $signature = 'mail:test-all
                            {email : Recipient address for every template}
                            {--cleanup : Delete the temporary organization after sending}';

    protected $description = 'Send every application email template to one address (SMTP smoke test)';

    public function handle(): int
    {
        $to = strtolower(trim($this->argument('email')));

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address [{$to}].");

            return self::FAILURE;
        }

        $this->info("Sending all email templates to {$to} via ".config('mail.default').'…');
        $this->newLine();

        $fixtures = $this->createFixtures();
        $billingUrl = Workspace::route('settings.billing', $fixtures['organization'], [], true);

        $approvedLeave = $fixtures['leaveRequest'];
        $approvedLeave->status = LeaveRequestStatus::Approved;

        $cases = [
            'WelcomeMail' => new WelcomeMail($fixtures['user'], $fixtures['organization']),
            'ResetPasswordMail' => new ResetPasswordMail(
                $fixtures['user'],
                URL::route('password.reset', ['token' => 'test-token', 'email' => $to]),
            ),
            'TeamInvitationMail' => new TeamInvitationMail($fixtures['invitation']),
            'LeaveRequestSubmittedMail' => new LeaveRequestSubmittedMail($fixtures['leaveRequest']),
            'LeaveRequestReviewedMail' => new LeaveRequestReviewedMail($approvedLeave, $fixtures['user']),
            'DocumentExpiringMail' => new DocumentExpiringMail($fixtures['document']),
            'TrialEndingSoonMail' => new TrialEndingSoonMail($fixtures['organization'], 3, $billingUrl),
            'TrialExpiredMail' => new TrialExpiredMail($fixtures['organization'], $billingUrl),
            'EmployeeLimitApproachingMail' => new EmployeeLimitApproachingMail($fixtures['organization'], 9, 10),
            'SubscriptionPaymentFailedMail' => new SubscriptionPaymentFailedMail($fixtures['organization']),
        ];

        $passed = 0;
        $failed = 0;

        foreach ($cases as $name => $mailable) {
            try {
                Mail::to($to)->send($mailable);
                $this->line("  <fg=green>✓</> {$name}");
                $passed++;
            } catch (\Throwable $e) {
                $this->line("  <fg=red>✗</> {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done: {$passed} sent, {$failed} failed.");

        if ($this->option('cleanup')) {
            $fixtures['organization']->delete();
            $fixtures['user']->delete();
            $this->comment('Temporary mail-test user and organization removed.');
        } else {
            $this->comment('Fixture org: '.$fixtures['organization']->name.' (id '.$fixtures['organization']->id.'). Use --cleanup to remove.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{user: User, organization: Organization, invitation: Invitation, leaveRequest: LeaveRequest, document: EmployeeDocument}
     */
    protected function createFixtures(): array
    {
        $suffix = now()->format('YmdHis');

        return DB::transaction(function () use ($suffix) {
            $user = User::query()->create([
                'name' => 'Mail Test Owner',
                'email' => "mail-test-owner-{$suffix}@ziifra.local",
                'password' => 'password',
            ]);

            $organization = Organization::query()->create([
                'name' => "Mail Test Org {$suffix}",
                'country_code' => 'XK',
                'timezone' => config('app.timezone', 'Europe/Belgrade'),
                'currency' => 'EUR',
                'locale' => config('app.locale', 'en'),
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
                'trial_ends_at' => now()->addDays(14),
            ]);

            $organization->users()->attach($user->id, [
                'role' => OrganizationRole::Owner->value,
                'joined_at' => now(),
            ]);

            LeaveRequestService::seedDefaultTypes($organization);

            $invitation = $organization->invitations()->create([
                'email' => 'invitee@example.test',
                'role' => OrganizationRole::Hr,
                'token' => Invitation::generateToken(),
                'invited_by' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);
            $invitation->load('organization');

            $employee = Employee::factory()->forOrganization($organization)->create([
                'first_name' => 'Test',
                'last_name' => 'Employee',
                'email' => 'employee@example.test',
            ]);

            $leaveType = LeaveType::query()
                ->where('organization_id', $organization->id)
                ->where('name', 'Annual leave')
                ->firstOrFail();

            $leaveRequest = LeaveRequest::factory()
                ->forEmployee($employee, $leaveType, $user)
                ->create([
                    'start_date' => now()->addWeek(),
                    'end_date' => now()->addWeeks(2),
                    'days' => 5,
                    'status' => LeaveRequestStatus::Pending,
                ]);
            $leaveRequest->load(['employee', 'leaveType', 'submittedBy', 'organization']);

            $document = EmployeeDocument::query()->create([
                'organization_id' => $organization->id,
                'employee_id' => $employee->id,
                'uploaded_by_user_id' => $user->id,
                'type' => EmployeeDocumentType::IdDocument,
                'title' => 'Passport',
                'file_path' => "organizations/{$organization->id}/mail-test.pdf",
                'original_filename' => 'passport.pdf',
                'expires_at' => now()->addDays(14),
            ]);
            $document->load(['employee', 'organization']);

            return compact('user', 'organization', 'invitation', 'leaveRequest', 'document');
        });
    }
}
