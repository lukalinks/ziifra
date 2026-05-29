<?php

namespace App\Enums;

enum EmployeeDocumentType: string
{
    case Contract = 'contract';
    case IdDocument = 'id_document';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return __('documents.document_types.'.$this->value);
    }
}
