<?php

return [
    'title' => 'Importo punonjësit',
    'subtitle' => 'Ngarkoni një skedar CSV për të shtuar shumë punonjës njëherësh.',
    'download_template' => 'Shkarko shabllonin CSV',
    'upload' => 'Ngarko dhe importo',
    'file_label' => 'Skedari CSV',
    'file_hint' => 'Maks. 2 MB. CSV UTF-8 me rresht titulli.',
    'columns_title' => 'Kolonat e mbështetura',
    'limit_reached' => 'U arrit kufiri i planit për punonjës — rreshtat e mbetur nuk u importuan.',
    'result_imported' => ':count punonjës u importuan me sukses.',
    'result_skipped' => ':count rresht u anashkaluan.',
    'errors_title' => 'Gabimet e rreshtave',

    'columns' => [
        'first_name' => 'Emri (i detyrueshëm)',
        'last_name' => 'Mbiemri (i detyrueshëm)',
        'email' => 'Email (opsionale, duhet të jetë unik)',
        'phone' => 'Telefoni',
        'department' => 'Emri i departamentit (duhet të ekzistojë)',
        'position' => 'Titulli i pozicionit (duhet të ekzistojë)',
        'manager_email' => 'Email i menaxherit (punonjës ekzistues)',
        'employment_type' => 'full_time, part_time, contract, intern, temporary',
        'employment_status' => 'active, on_leave, terminated',
        'start_date' => 'Data e fillimit (VVVV-MM-DD)',
    ],

    'errors' => [
        'missing_columns' => 'CSV duhet të përfshijë kolonat first_name dhe last_name.',
        'name_required' => 'Emri dhe mbiemri janë të detyrueshëm.',
        'invalid_email' => 'Adresë email e pavlefshme.',
        'duplicate_email_in_file' => 'Email i dyfishtë në këtë skedar.',
        'email_exists' => 'Ekziston tashmë një punonjës me këtë email.',
        'unknown_department' => 'Departament i panjohur: :name.',
        'unknown_position' => 'Pozicion i panjohur: :name.',
        'unknown_manager' => 'Nuk u gjet punonjës me email menaxheri: :email.',
        'invalid_type' => 'employment_type i pavlefshëm.',
        'invalid_status' => 'employment_status i pavlefshëm.',
        'invalid_date' => 'start_date e pavlefshme — përdorni VVVV-MM-DD.',
    ],
];
