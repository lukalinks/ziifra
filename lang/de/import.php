<?php

return [
    'title' => 'Mitarbeiter importieren',
    'subtitle' => 'Laden Sie eine CSV-Datei hoch, um mehrere Mitarbeiter auf einmal hinzuzufügen.',
    'download_template' => 'CSV-Vorlage herunterladen',
    'upload' => 'Hochladen und importieren',
    'file_label' => 'CSV-Datei',
    'file_hint' => 'Max. 2 MB. UTF-8-CSV mit Kopfzeile.',
    'columns_title' => 'Unterstützte Spalten',
    'limit_reached' => 'Mitarbeiter-Tariflimit erreicht — verbleibende Zeilen wurden nicht importiert.',
    'result_imported' => ':count Mitarbeiter erfolgreich importiert.',
    'result_skipped' => ':count Zeile(n) übersprungen.',
    'errors_title' => 'Zeilenfehler',

    'columns' => [
        'first_name' => 'Vorname (Pflichtfeld)',
        'last_name' => 'Nachname (Pflichtfeld)',
        'email' => 'E-Mail (optional, muss eindeutig sein)',
        'phone' => 'Telefon',
        'department' => 'Abteilungsname (muss bereits existieren)',
        'position' => 'Positionsbezeichnung (muss bereits existieren)',
        'manager_email' => 'E-Mail des Vorgesetzten (bestehender Mitarbeiter)',
        'employment_type' => 'full_time, part_time, contract, intern, temporary',
        'employment_status' => 'active, on_leave, terminated',
        'start_date' => 'Eintrittsdatum (JJJJ-MM-TT)',
    ],

    'errors' => [
        'missing_columns' => 'CSV muss die Spalten first_name und last_name enthalten.',
        'name_required' => 'Vor- und Nachname sind Pflichtfelder.',
        'invalid_email' => 'Ungültige E-Mail-Adresse.',
        'duplicate_email_in_file' => 'Doppelte E-Mail in dieser Datei.',
        'email_exists' => 'Ein Mitarbeiter mit dieser E-Mail existiert bereits.',
        'unknown_department' => 'Unbekannte Abteilung: :name.',
        'unknown_position' => 'Unbekannte Position: :name.',
        'unknown_manager' => 'Kein Mitarbeiter mit Vorgesetzten-E-Mail gefunden: :email.',
        'invalid_type' => 'Ungültiger employment_type.',
        'invalid_status' => 'Ungültiger employment_status.',
        'invalid_date' => 'Ungültiges start_date — verwenden Sie JJJJ-MM-TT.',
    ],
];
