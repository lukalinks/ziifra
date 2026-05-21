<?php

namespace App\Services;

use App\Enums\ContractTemplate;

class ContractTemplateDefaults
{
    /**
     * @return array{name: string, slug: string, description: string, body: string, is_system: bool}
     */
    public static function definition(ContractTemplate $template): array
    {
        return [
            'name' => __('documents.templates.types.'.$template->value.'.label'),
            'slug' => $template->value,
            'description' => __('documents.templates.types.'.$template->value.'.description'),
            'body' => self::bodyFor($template),
            'is_system' => true,
        ];
    }

    public static function bodyFor(ContractTemplate $template): string
    {
        $section = match ($template) {
            ContractTemplate::FullTime, ContractTemplate::PartTime => __('documents.templates.sections.employment_terms'),
            ContractTemplate::FixedTerm => __('documents.templates.sections.fixed_term'),
            ContractTemplate::Internship => __('documents.templates.sections.internship'),
            ContractTemplate::Nda => __('documents.templates.sections.nda'),
        };

        $paragraphKeys = match ($template) {
            ContractTemplate::FullTime => [
                'documents.templates.clauses.full_time.intro',
                'documents.templates.clauses.full_time.role',
                'documents.templates.clauses.full_time.compensation',
                'documents.templates.clauses.full_time.probation',
                'documents.templates.clauses.full_time.working_hours',
                'documents.templates.clauses.common.confidentiality',
                'documents.templates.clauses.common.termination',
                'documents.templates.clauses.common.governing_law',
            ],
            ContractTemplate::PartTime => [
                'documents.templates.clauses.part_time.intro',
                'documents.templates.clauses.part_time.role',
                'documents.templates.clauses.part_time.compensation',
                'documents.templates.clauses.part_time.hours',
                'documents.templates.clauses.part_time.probation',
                'documents.templates.clauses.common.confidentiality',
                'documents.templates.clauses.common.termination',
                'documents.templates.clauses.common.governing_law',
            ],
            ContractTemplate::FixedTerm => [
                'documents.templates.clauses.fixed_term.intro',
                'documents.templates.clauses.fixed_term.duration',
                'documents.templates.clauses.fixed_term.role',
                'documents.templates.clauses.fixed_term.compensation',
                'documents.templates.clauses.common.confidentiality',
                'documents.templates.clauses.common.termination',
                'documents.templates.clauses.common.governing_law',
            ],
            ContractTemplate::Internship => [
                'documents.templates.clauses.internship.intro',
                'documents.templates.clauses.internship.role',
                'documents.templates.clauses.internship.supervision',
                'documents.templates.clauses.internship.compensation',
                'documents.templates.clauses.common.confidentiality',
                'documents.templates.clauses.common.governing_law',
            ],
            ContractTemplate::Nda => [
                'documents.templates.clauses.nda.intro',
                'documents.templates.clauses.nda.scope',
                'documents.templates.clauses.nda.obligations',
                'documents.templates.clauses.nda.duration',
                'documents.templates.clauses.nda.return',
                'documents.templates.clauses.common.governing_law',
            ],
        };

        $html = '<h2>'.e($section).'</h2>';

        foreach ($paragraphKeys as $key) {
            $html .= '<p>'.e(__($key)).'</p>';
        }

        return $html;
    }
}
