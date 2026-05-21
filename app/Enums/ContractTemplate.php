<?php

namespace App\Enums;

enum ContractTemplate: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case FixedTerm = 'fixed_term';
    case Internship = 'internship';
    case Nda = 'nda';

    public function label(): string
    {
        return __('documents.templates.types.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('documents.templates.types.'.$this->value.'.description');
    }

    public function documentTitle(?string $employeeName = null): string
    {
        $base = __('documents.templates.types.'.$this->value.'.document_title');

        if ($employeeName === null) {
            return $base;
        }

        return $base.' — '.$employeeName;
    }
}
