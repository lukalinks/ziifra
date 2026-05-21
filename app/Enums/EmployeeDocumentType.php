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
        return match ($this) {
            self::Contract => 'Employment contract',
            self::IdDocument => 'ID / passport',
            self::Certificate => 'Certificate / diploma',
            self::Other => 'Other',
        };
    }
}
