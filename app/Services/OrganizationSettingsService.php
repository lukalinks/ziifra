<?php

namespace App\Services;

use App\Models\Organization;
use App\Support\OrganizationLogo;
use Illuminate\Http\UploadedFile;

class OrganizationSettingsService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Organization $organization, array $attributes, ?UploadedFile $logo = null, bool $removeLogo = false): Organization
    {
        if ($removeLogo) {
            OrganizationLogo::delete($organization->logo_path);
            $attributes['logo_path'] = null;
        } elseif ($logo !== null) {
            $attributes['logo_path'] = OrganizationLogo::store($organization, $logo);
        }

        unset($attributes['logo'], $attributes['remove_logo']);

        $organization->fill($attributes);
        $organization->save();

        return $organization->fresh();
    }
}
