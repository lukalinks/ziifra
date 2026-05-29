<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class OrganizationMailService
{
    public const MAILER_PREFIX = 'org_mail_';

    public const STATUS_PLATFORM = 'platform';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INCOMPLETE = 'incomplete';

    /**
     * @return array<string, array{host: string, port: int, encryption: string}>
     */
    public function providerPresets(): array
    {
        return [
            'hostinger' => [
                'host' => 'smtp.hostinger.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'google' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'microsoft' => [
                'host' => 'smtp.office365.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
        ];
    }

    public function status(Organization $organization): string
    {
        $settings = $organization->resolvedMailSettings();

        if (! $settings['enabled']) {
            return self::STATUS_PLATFORM;
        }

        return $this->usesCustomSmtp($organization)
            ? self::STATUS_ACTIVE
            : self::STATUS_INCOMPLETE;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     host: string,
     *     port: int,
     *     encryption: string,
     *     username: string,
     *     password: ?string,
     *     from_address: string,
     *     from_name: string,
     *     has_password: bool,
     *     last_tested_at: ?string,
     *     last_test_ok: ?bool
     * }
     */
    public function settingsForForm(Organization $organization): array
    {
        $resolved = $organization->resolvedMailSettings();

        return [
            'enabled' => $resolved['enabled'],
            'host' => $resolved['host'],
            'port' => $resolved['port'],
            'encryption' => $resolved['encryption'] !== '' ? $resolved['encryption'] : 'tls',
            'username' => $resolved['username'],
            'password' => null,
            'from_address' => $resolved['from_address'],
            'from_name' => $resolved['from_name'],
            'has_password' => filled($resolved['password']),
            'last_tested_at' => $resolved['last_tested_at'],
            'last_test_ok' => $resolved['last_test_ok'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeInput(Organization $organization, array $input): array
    {
        $current = $organization->resolvedMailSettings();
        $enabled = (bool) ($input['enabled'] ?? false);

        $password = $current['password'];
        $rawPassword = $input['password'] ?? null;

        if (is_string($rawPassword) && trim($rawPassword) !== '') {
            $password = Crypt::encryptString(trim($rawPassword));
        } elseif (! $enabled) {
            $password = null;
        }

        $encryption = strtolower(trim((string) ($input['encryption'] ?? 'tls')));
        if (! in_array($encryption, ['tls', 'ssl', 'none', ''], true)) {
            $encryption = 'tls';
        }

        if ($encryption === 'none') {
            $encryption = '';
        }

        $port = (int) ($input['port'] ?? $this->defaultPortForEncryption($encryption));

        $stored = is_array($organization->mail_settings) ? $organization->mail_settings : [];

        $normalized = [
            'enabled' => $enabled,
            'host' => trim((string) ($input['host'] ?? '')),
            'port' => $port > 0 ? $port : $this->defaultPortForEncryption($encryption),
            'encryption' => $encryption,
            'username' => trim((string) ($input['username'] ?? '')),
            'password' => $password,
            'from_address' => strtolower(trim((string) ($input['from_address'] ?? ''))),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
            'last_tested_at' => $stored['last_tested_at'] ?? null,
            'last_test_ok' => $stored['last_test_ok'] ?? null,
        ];

        if (! $enabled) {
            $normalized['last_test_ok'] = null;
        }

        return $normalized;
    }

    public function usesCustomSmtp(Organization $organization): bool
    {
        $settings = $organization->resolvedMailSettings();

        if (! $settings['enabled'] || $settings['host'] === '' || $settings['from_address'] === '') {
            return false;
        }

        if ($settings['username'] !== '' && ! filled($settings['password'])) {
            return false;
        }

        return true;
    }

    public function mailerName(Organization $organization): string
    {
        return self::MAILER_PREFIX.$organization->id;
    }

    public function registerMailerForOrganizationId(int $organizationId): ?string
    {
        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            return null;
        }

        return $this->registerMailer($organization);
    }

    public function registerMailer(Organization $organization): string
    {
        if (! $this->usesCustomSmtp($organization)) {
            return (string) config('mail.default', 'smtp');
        }

        $settings = $organization->resolvedMailSettings();
        $name = $this->mailerName($organization);
        $scheme = $settings['encryption'] === 'ssl' ? 'smtps' : null;

        config([
            'mail.mailers.'.$name => array_filter([
                'transport' => 'smtp',
                'scheme' => $scheme,
                'host' => $settings['host'],
                'port' => $settings['port'],
                'encryption' => $settings['encryption'] !== '' ? $settings['encryption'] : null,
                'username' => $settings['username'] !== '' ? $settings['username'] : null,
                'password' => $this->decryptPassword($settings['password']),
                'timeout' => 30,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
            ]),
        ]);

        return $name;
    }

    public function prepareMailable(Mailable $mailable, Organization $organization): Mailable
    {
        $settings = $organization->resolvedMailSettings();

        if ($this->usesCustomSmtp($organization)) {
            $mailable->from(
                $settings['from_address'],
                $settings['from_name'] !== '' ? $settings['from_name'] : $organization->name,
            );
        }

        $replyTo = $organization->notificationReplyTo();

        if ($replyTo) {
            $mailable->replyTo($replyTo);
        }

        return $mailable;
    }

    /**
     * @param  string|list<string>  $to
     */
    public function sendNow(Organization $organization, string|array $to, Mailable $mailable): void
    {
        $mailer = $this->registerMailer($organization);
        $mailable = $this->prepareMailable($mailable, $organization);

        Mail::mailer($mailer)->to($to)->sendNow($mailable);
    }

    /**
     * @param  string|list<string>  $to
     */
    public function queue(Organization $organization, string|array $to, Mailable $mailable): void
    {
        $mailer = $this->registerMailer($organization);
        $mailable = $this->prepareMailable($mailable, $organization);
        $mailable->mailer($mailer);

        Mail::to($to)->queue($mailable);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendTest(Organization $organization, string $to): void
    {
        $mailer = $this->registerMailer($organization);

        if (! $this->usesCustomSmtp($organization)) {
            throw new \InvalidArgumentException('Custom SMTP is not configured.');
        }

        $settings = $organization->resolvedMailSettings();
        $replyTo = $organization->notificationReplyTo();

        try {
            Mail::mailer($mailer)->raw(
                __('settings.mail.test_body', ['name' => $organization->name]),
                function ($message) use ($to, $settings, $organization, $replyTo): void {
                    $message->to($to)
                        ->subject(__('settings.mail.test_subject', ['name' => $organization->name]))
                        ->from(
                            $settings['from_address'],
                            $settings['from_name'] !== '' ? $settings['from_name'] : $organization->name,
                        );

                    if ($replyTo) {
                        $message->replyTo($replyTo);
                    }
                },
            );

            $this->recordTestResult($organization, true);
        } catch (TransportExceptionInterface $exception) {
            $this->recordTestResult($organization, false);

            throw $exception;
        }
    }

    public function registerFromQueuedJob(JobProcessing $event): void
    {
        try {
            $payload = $event->job->payload();
            $command = unserialize($payload['data']['command'] ?? '', ['allowed_classes' => true]);
        } catch (Throwable) {
            return;
        }

        if (! $command instanceof SendQueuedMailable) {
            return;
        }

        $mailer = $command->mailable->mailer ?? null;

        if (! is_string($mailer) || ! str_starts_with($mailer, self::MAILER_PREFIX)) {
            return;
        }

        $organizationId = (int) Str::after($mailer, self::MAILER_PREFIX);

        if ($organizationId > 0) {
            $this->registerMailerForOrganizationId($organizationId);
        }
    }

    protected function recordTestResult(Organization $organization, bool $success): void
    {
        $stored = is_array($organization->mail_settings) ? $organization->mail_settings : [];
        $stored['last_tested_at'] = now()->toIso8601String();
        $stored['last_test_ok'] = $success;

        $organization->update(['mail_settings' => $stored]);
    }

    protected function defaultPortForEncryption(string $encryption): int
    {
        return match ($encryption) {
            'ssl' => 465,
            'tls' => 587,
            default => 25,
        };
    }

    protected function decryptPassword(?string $encrypted): ?string
    {
        if (! filled($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }
}
