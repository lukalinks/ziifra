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
     *     has_password: bool
     * }
     */
    public function settingsForForm(Organization $organization): array
    {
        $resolved = $organization->resolvedMailSettings();

        return [
            'enabled' => $resolved['enabled'],
            'host' => $resolved['host'],
            'port' => $resolved['port'],
            'encryption' => $resolved['encryption'],
            'username' => $resolved['username'],
            'password' => null,
            'from_address' => $resolved['from_address'],
            'from_name' => $resolved['from_name'],
            'has_password' => filled($resolved['password']),
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

        return [
            'enabled' => $enabled,
            'host' => trim((string) ($input['host'] ?? '')),
            'port' => (int) ($input['port'] ?? 587),
            'encryption' => $encryption,
            'username' => trim((string) ($input['username'] ?? '')),
            'password' => $password,
            'from_address' => strtolower(trim((string) ($input['from_address'] ?? ''))),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
        ];
    }

    public function usesCustomSmtp(Organization $organization): bool
    {
        $settings = $organization->resolvedMailSettings();

        return $settings['enabled']
            && $settings['host'] !== ''
            && $settings['from_address'] !== '';
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
                'timeout' => null,
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

        Mail::mailer($mailer)->raw(
            __('settings.mail.test_body', ['name' => $organization->name]),
            function ($message) use ($to, $settings, $organization): void {
                $message->to($to)
                    ->subject(__('settings.mail.test_subject', ['name' => $organization->name]))
                    ->from(
                        $settings['from_address'],
                        $settings['from_name'] !== '' ? $settings['from_name'] : $organization->name,
                    );
            },
        );
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
