<?php

namespace App\Services;

use App\Enums\ContractTemplate;
use App\Models\Organization;
use App\Models\OrganizationContractTemplate;
use Illuminate\Support\Str;

class OrganizationContractTemplateService
{
    public function seedDefaults(Organization $organization): void
    {
        if ($organization->contractTemplates()->exists()) {
            return;
        }

        foreach (ContractTemplate::cases() as $index => $template) {
            $definition = ContractTemplateDefaults::definition($template);

            $organization->contractTemplates()->create([
                ...$definition,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    public function ensureDefaults(Organization $organization): void
    {
        if ($organization->contractTemplates()->exists()) {
            return;
        }

        $this->seedDefaults($organization);
    }

    /**
     * @return list<string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            ':company_name',
            ':company_address',
            ':company_registration',
            ':company_fiscal',
            ':signatory_name',
            ':signatory_title',
            ':employee_name',
            ':employee_email',
            ':employee_phone',
            ':employee_position',
            ':employee_department',
            ':employment_type',
            ':start_date',
            ':gross_salary',
            ':contract_date',
            ':probation_days',
            ':end_date',
            ':template_title',
        ];
    }

    public function uniqueSlug(Organization $organization, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'template';
        $suffix = 2;

        while ($this->slugExists($organization, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(Organization $organization, string $slug, ?int $ignoreId = null): bool
    {
        return OrganizationContractTemplate::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
