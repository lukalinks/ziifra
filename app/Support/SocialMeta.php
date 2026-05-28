<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SocialMeta
{
    public function __construct(
        public string $title,
        public string $description,
        public string $imageUrl,
        public string $canonicalUrl,
        public string $siteName,
        public string $type = 'website',
        public string $imageAlt = '',
    ) {}

    public static function fromPage(?string $pageTitle = null, ?string $description = null, ?Organization $organization = null): self
    {
        $appName = (string) config('app.name');
        $pageTitle = trim((string) ($pageTitle ?? ''));
        $description = trim((string) ($description ?? ''));

        if ($pageTitle === '') {
            $pageTitle = (string) __('common.default_header');
        }

        $organization ??= self::resolveOrganization();

        if ($organization !== null) {
            $siteName = $organization->name;
            $title = $pageTitle.' — '.$siteName;
            $description = $description !== ''
                ? $description
                : (filled($organization->brand_tagline)
                    ? (string) $organization->brand_tagline
                    : (string) __('social.org_description', ['org' => $organization->name, 'app' => $appName]));
            $imageUrl = self::organizationImageUrl($organization);
            $imageAlt = $organization->name.' logo';
        } else {
            $siteName = $appName;
            $title = Str::contains($pageTitle, $appName)
                ? $pageTitle
                : $pageTitle.' — '.$appName;
            $description = $description !== ''
                ? $description
                : (string) (config('ziifra.social.default_description') ?: __('landing.meta_description'));
            $imageUrl = self::defaultImageUrl();
            $imageAlt = $appName;
        }

        return new self(
            title: $title,
            description: Str::limit($description, 200, ''),
            imageUrl: $imageUrl,
            canonicalUrl: url()->current(),
            siteName: $siteName,
            imageAlt: $imageAlt,
        );
    }

    public static function defaultImageUrl(): string
    {
        $configured = config('ziifra.social.default_image');

        if (filled($configured)) {
            return Str::startsWith($configured, ['http://', 'https://'])
                ? $configured
                : url($configured);
        }

        if (is_file(public_path('og/ziifra-share.png'))) {
            return asset('og/ziifra-share.png');
        }

        return asset('og/ziifra-share.svg');
    }

    public static function organizationImageUrl(Organization $organization): string
    {
        if ($organization->hasLogo()) {
            return route('org.brand.logo', $organization, absolute: true)
                .'?v='.($organization->updated_at?->getTimestamp() ?? '1');
        }

        return self::defaultImageUrl();
    }

    protected static function resolveOrganization(): ?Organization
    {
        $shared = View::shared('socialOrganization');

        if ($shared instanceof Organization) {
            return $shared;
        }

        $current = CurrentOrganization::get();

        if ($current !== null) {
            return $current;
        }

        $routeOrganization = request()->route('organization');

        return $routeOrganization instanceof Organization ? $routeOrganization : null;
    }
}
