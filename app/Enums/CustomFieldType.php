<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Boolean => 'Yes / No',
            self::Select => 'Dropdown',
            self::File => 'File upload',
        };
    }

    public function htmlInputType(): string
    {
        return match ($this) {
            self::Text => 'text',
            self::Number => 'number',
            self::Date => 'date',
            self::Boolean => 'checkbox',
            self::Select => 'select',
            self::File => 'file',
        };
    }

    public function acceptsFileUpload(): bool
    {
        return $this === self::File;
    }
}
