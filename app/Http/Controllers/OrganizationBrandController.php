<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\OrganizationLogo;
use App\Support\SocialMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationBrandController extends Controller
{
    public function logo(Organization $organization): StreamedResponse|RedirectResponse
    {
        if (! OrganizationLogo::exists($organization->logo_path)) {
            return redirect()->away(SocialMeta::defaultImageUrl());
        }

        return Storage::disk('local')->response(
            $organization->logo_path,
            'logo',
            [
                'Cache-Control' => 'public, max-age=86400',
            ],
        );
    }
}
