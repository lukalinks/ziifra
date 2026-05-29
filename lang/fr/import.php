<?php

return [
    'title' => 'Import d\'employés',
    'subtitle' => 'Téléversez un fichier CSV pour ajouter plusieurs employés en une fois.',
    'download_template' => 'Télécharger le modèle CSV',
    'upload' => 'Téléverser et importer',
    'file_label' => 'Fichier CSV',
    'file_hint' => 'Max. 2 Mo. CSV UTF-8 avec une ligne d\'en-tête.',
    'columns_title' => 'Colonnes prises en charge',
    'limit_reached' => 'Limite du plan employés atteinte — les lignes restantes n\'ont pas été importées.',
    'result_imported' => ':count employé(s) importé(s) avec succès.',
    'result_skipped' => ':count ligne(s) ignorée(s).',
    'errors_title' => 'Erreurs par ligne',

    'columns' => [
        'first_name' => 'Prénom (obligatoire)',
        'last_name' => 'Nom (obligatoire)',
        'email' => 'E-mail (facultatif, doit être unique)',
        'phone' => 'Téléphone',
        'department' => 'Nom du département (doit déjà exister)',
        'position' => 'Intitulé du poste (doit déjà exister)',
        'manager_email' => 'E-mail du manager (employé existant)',
        'employment_type' => 'full_time, part_time, contract, intern, temporary',
        'employment_status' => 'active, on_leave, terminated',
        'start_date' => 'Date de début (AAAA-MM-JJ)',
    ],

    'errors' => [
        'missing_columns' => 'Le CSV doit inclure les colonnes first_name et last_name.',
        'name_required' => 'Le prénom et le nom sont obligatoires.',
        'invalid_email' => 'Adresse e-mail invalide.',
        'duplicate_email_in_file' => 'E-mail en double dans ce fichier.',
        'email_exists' => 'Un employé avec cet e-mail existe déjà.',
        'unknown_department' => 'Département inconnu : :name.',
        'unknown_position' => 'Poste inconnu : :name.',
        'unknown_manager' => 'Aucun employé trouvé avec l\'e-mail manager : :email.',
        'invalid_type' => 'employment_type invalide.',
        'invalid_status' => 'employment_status invalide.',
        'invalid_date' => 'start_date invalide — utilisez AAAA-MM-JJ.',
    ],
];
