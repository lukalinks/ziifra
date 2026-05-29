<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\OrganizationLegalForm;
use App\Enums\SubscriptionPlan;
use App\Enums\WorkWeekDay;
use App\Support\OrganizationLogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'legal_form',
        'registration_number',
        'fiscal_number',
        'vat_number',
        'country_code',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'phone',
        'email',
        'website',
        'timezone',
        'currency',
        'locale',
        'logo_path',
        'primary_color',
        'accent_color',
        'brand_tagline',
        'hr_email',
        'reply_to_email',
        'signatory_name',
        'signatory_title',
        'work_week_days',
        'fiscal_year_start_month',
        'date_format',
        'observe_kosovo_holidays',
        'default_employment_type',
        'probation_days',
        'employee_id_prefix',
        'handbook_url',
        'vat_registered',
        'bank_name',
        'bank_iban',
        'hr_can_invite',
        'owner_id',
        'plan',
        'trial_ends_at',
        'suspended_at',
        'stripe_id',
        'stripe_subscription_id',
        'stripe_subscription_status',
        'stripe_subscription_ends_at',
        'paypal_subscription_id',
        'paypal_subscription_status',
        'paypal_subscription_ends_at',
        'billing_payment_provider',
        'billing_reminders_sent',
        'payslip_template',
        'payroll_settings',
        'invoice_settings',
        'chat_settings',
        'mail_settings',
    ];

    protected function casts(): array
    {
        return [
            'legal_form' => OrganizationLegalForm::class,
            'work_week_days' => 'array',
            'observe_kosovo_holidays' => 'boolean',
            'vat_registered' => 'boolean',
            'hr_can_invite' => 'boolean',
            'fiscal_year_start_month' => 'integer',
            'probation_days' => 'integer',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'stripe_subscription_ends_at' => 'datetime',
            'paypal_subscription_ends_at' => 'datetime',
            'billing_reminders_sent' => 'array',
            'payslip_template' => 'array',
            'payroll_settings' => 'array',
            'invoice_settings' => 'array',
            'chat_settings' => 'array',
            'mail_settings' => 'array',
            'plan' => SubscriptionPlan::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (empty($organization->slug)) {
                $organization->slug = static::uniqueSlug($organization->name);
            }
        });

        static::deleting(function (Organization $organization): void {
            OrganizationLogo::delete($organization->logo_path);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'company';
        }

        $slug = $base;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function hrCanInvite(): bool
    {
        return $this->hr_can_invite ?? true;
    }

    /**
     * @return list<string>
     */
    public function workWeekDayValues(): array
    {
        $days = $this->work_week_days;

        if (! is_array($days) || $days === []) {
            return array_map(fn (WorkWeekDay $day) => $day->value, WorkWeekDay::defaultWorkWeek());
        }

        return $days;
    }

    public function worksOn(WorkWeekDay $day): bool
    {
        return in_array($day->value, $this->workWeekDayValues(), true);
    }

    public function notificationReplyTo(): ?string
    {
        return $this->reply_to_email ?: $this->hr_email ?: $this->email;
    }

    /**
     * @return list<string>
     */
    public function payslipLegalLines(): array
    {
        $lines = array_filter([
            $this->displayName(),
            $this->formattedAddress(),
            $this->fiscal_number ? 'NUI: '.$this->fiscal_number : null,
            $this->registration_number ? 'Reg. no.: '.$this->registration_number : null,
            $this->vat_registered && $this->vat_number ? 'VAT: '.$this->vat_number : null,
            $this->phone,
            $this->hr_email ?: $this->email,
            $this->bank_iban ? trim(($this->bank_name ? $this->bank_name.' — ' : '').$this->bank_iban) : null,
        ]);

        if ($this->signatory_name) {
            $line = $this->signatory_name;
            if ($this->signatory_title) {
                $line .= ', '.$this->signatory_title;
            }
            $lines[] = $line;
        }

        return array_values($lines);
    }

    /**
     * Payslip PDF/HTML appearance (merged with defaults).
     *
     * @return array{
     *     layout: string,
     *     show_logo: bool,
     *     show_legal_block: bool,
     *     footer_note: ?string,
     *     show_employer_pension: bool
     * }
     */
    public function resolvedPayslipTemplate(): array
    {
        $defaults = [
            'layout' => 'standard',
            'show_logo' => true,
            'show_legal_block' => true,
            'footer_note' => null,
            'show_employer_pension' => false,
        ];

        $stored = $this->payslip_template;
        if (! is_array($stored)) {
            return $defaults;
        }

        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));

        if (! in_array($merged['layout'], ['standard', 'compact', 'detailed'], true)) {
            $merged['layout'] = 'standard';
        }

        return $merged;
    }

    /**
     * Inline image for DomPDF / print-friendly payslips.
     */
    public function payslipLogoDataUri(): ?string
    {
        if (! OrganizationLogo::exists($this->logo_path)) {
            return null;
        }

        try {
            $binary = Storage::disk('local')->get($this->logo_path);
            if ($binary === null || $binary === '') {
                return null;
            }

            $ext = strtolower(pathinfo((string) $this->logo_path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    public static function supportedDateFormats(): array
    {
        return [
            'd/m/Y' => 'DD/MM/YYYY (31/12/2026)',
            'm/d/Y' => 'MM/DD/YYYY (12/31/2026)',
            'Y-m-d' => 'YYYY-MM-DD (2026-12-31)',
        ];
    }

    /**
     * @return list<int>
     */
    public static function supportedFiscalYearMonths(): array
    {
        return range(1, 12);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function employeeFieldDefinitions(): HasMany
    {
        return $this->hasMany(EmployeeFieldDefinition::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    public function contractTemplates(): HasMany
    {
        return $this->hasMany(OrganizationContractTemplate::class);
    }

    public function adminAuditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class);
    }

    public function displayName(): string
    {
        return $this->legal_name ?: $this->name;
    }

    public function hasLogo(): bool
    {
        return filled($this->logo_path);
    }

    public function isProfileComplete(): bool
    {
        return filled($this->fiscal_number)
            && filled($this->address_line_1)
            && filled($this->city)
            && filled($this->email);
    }

    /**
     * @return list<string>
     */
    public static function supportedTimezones(): array
    {
        return [
            'Europe/Zurich',
            'Europe/Berlin',
            'Europe/London',
            'UTC',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedPayrollSettings(): array
    {
        $defaults = [
            'trust_employee_percent' => 5.0,
            'trust_employer_percent' => 5.0,
            'vat_percent' => 8.0,
            'show_logo' => true,
            'show_vat' => true,
            'footer_note' => null,
        ];

        return array_merge($defaults, $this->payroll_settings ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedInvoiceSettings(): array
    {
        $defaults = [
            'footer_text' => null,
            'vat_percent' => 8.0,
            'vat_manual' => false,
            'bank_name' => $this->bank_name,
            'bank_iban' => $this->bank_iban,
        ];

        return array_merge($defaults, $this->invoice_settings ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedChatSettings(): array
    {
        $defaults = [
            'enabled' => true,
            'employees_can_write' => true,
            'private_chat_enabled' => true,
        ];

        return array_merge($defaults, $this->chat_settings ?? []);
    }

    /**
     * SMTP and sender identity for outbound email (invites, payslips, leave, etc.).
     *
     * @return array{
     *     enabled: bool,
     *     host: string,
     *     port: int,
     *     encryption: string,
     *     username: string,
     *     password: ?string,
     *     from_address: string,
     *     from_name: string
     * }
     */
    public function resolvedMailSettings(): array
    {
        $defaults = [
            'enabled' => false,
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => null,
            'from_address' => $this->hr_email ?: $this->email ?: '',
            'from_name' => $this->name ?: '',
            'last_tested_at' => null,
            'last_test_ok' => null,
        ];

        $stored = $this->mail_settings;
        if (! is_array($stored)) {
            return $defaults;
        }

        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));

        $encryption = strtolower((string) ($merged['encryption'] ?? 'tls'));
        if (! in_array($encryption, ['tls', 'ssl', ''], true)) {
            $encryption = 'tls';
        }
        $merged['encryption'] = $encryption;
        $merged['port'] = (int) ($merged['port'] ?? 587);
        $merged['enabled'] = (bool) ($merged['enabled'] ?? false);
        return $merged;
    }

    /**
     * @return list<string>
     */
    public static function supportedCurrencies(): array
    {
        return ['EUR', 'USD', 'CHF', 'GBP'];
    }

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return app(\App\Services\LocaleConfigurationService::class)->enabledCodes();
    }

    /**
     * @return array<string, string>
     */
    public static function supportedCountries(): array
    {
        return [
            'XK' => 'Kosovo',
            'AL' => 'Albania',
            'MK' => 'North Macedonia',
            'RS' => 'Serbia',
            'ME' => 'Montenegro',
            'DE' => 'Germany',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
        ];
    }

    public function formattedAddress(): ?string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            trim(implode(' ', array_filter([$this->postal_code, $this->city]))),
        ]);

        if ($parts === []) {
            return null;
        }

        $address = implode(', ', $parts);

        if ($country = self::supportedCountries()[$this->country_code] ?? null) {
            $address .= ', '.$country;
        }

        return $address;
    }
}
