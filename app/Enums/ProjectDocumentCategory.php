<?php

namespace App\Enums;

enum ProjectDocumentCategory: string
{
    case Travel = 'travel';
    case Materials = 'materials';
    case Equipment = 'equipment';
    case Subcontractor = 'subcontractor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Travel => __('project_documents.categories.travel'),
            self::Materials => __('project_documents.categories.materials'),
            self::Equipment => __('project_documents.categories.equipment'),
            self::Subcontractor => __('project_documents.categories.subcontractor'),
            self::Other => __('project_documents.categories.other'),
        };
    }
}
