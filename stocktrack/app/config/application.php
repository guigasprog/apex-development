<?php
return [
    'general' =>  [
        'timezone'      => 'America/Sao_Paulo',
        'language'      => 'pt',
        'application'   => 'stocktrack',
        'title'         => 'StockTrack VV',
        'theme'         => 'adminbs5',
        'seed'          => 'colocarQualquerCoisaAqui',
        'debug'         => '1',
    ],
    
    'permission' =>  [
        'public_classes' => [
            'LoginForm',
            'PublicView',
        ],
    ],
    
    'highlight' => [ 'comment' => '#808080', 'default' => '#FFFFFF', 'html' => '#C0C0C0', 'keyword' => '#62d3ea', 'string' => '#FFC472', ],
    'login' => [ 'logo' => '', 'background' => '' ],
    'template' => [ 'navbar' => [ 'has_program_search' => '1', ], 'dialogs' => [ 'use_swal' => '1' ], ],
];