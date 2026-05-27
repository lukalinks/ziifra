<?php

return [
    'title' => 'Uvoz zaposlenih',
    'subtitle' => 'Otpremite CSV fajl da biste dodali više zaposlenih odjednom.',
    'download_template' => 'Preuzmi CSV šablon',
    'upload' => 'Otpremi i uvezi',
    'file_label' => 'CSV fajl',
    'file_hint' => 'Maks. 2 MB. UTF-8 CSV sa redom zaglavlja.',
    'columns_title' => 'Podržane kolone',
    'limit_reached' => 'Dostignut limit zaposlenih plana — preostali redovi nisu uvezeni.',
    'result_imported' => ':count zaposlenih uspešno uvezeno.',
    'result_skipped' => ':count red(ova) preskočeno.',
    'errors_title' => 'Greške u redovima',

    'columns' => [
        'first_name' => 'Ime (obavezno)',
        'last_name' => 'Prezime (obavezno)',
        'email' => 'Email (opciono, mora biti jedinstven)',
        'phone' => 'Telefon',
        'department' => 'Naziv odeljenja (mora već postojati)',
        'position' => 'Naziv pozicije (mora već postojati)',
        'manager_email' => 'Email menadžera (postojeći zaposleni)',
        'employment_type' => 'full_time, part_time, contract, intern, temporary',
        'employment_status' => 'active, on_leave, terminated',
        'start_date' => 'Datum početka (YYYY-MM-DD)',
    ],

    'errors' => [
        'missing_columns' => 'CSV mora sadržati kolone first_name i last_name.',
        'name_required' => 'Ime i prezime su obavezni.',
        'invalid_email' => 'Neispravna email adresa.',
        'duplicate_email_in_file' => 'Duplikat email-a u ovom fajlu.',
        'email_exists' => 'Zaposleni sa ovim email-om već postoji.',
        'unknown_department' => 'Nepoznato odeljenje: :name.',
        'unknown_position' => 'Nepoznata pozicija: :name.',
        'unknown_manager' => 'Nije pronađen zaposleni sa email-om menadžera: :email.',
        'invalid_type' => 'Neispravan employment_type.',
        'invalid_status' => 'Neispravan employment_status.',
        'invalid_date' => 'Neispravan start_date — koristite YYYY-MM-DD.',
    ],
];
