<?php
return [
    'sys' => [
        'auth' => 'COOKIE',
        'default_tpl_dir' => 'default',
        'display' => 'JSON'
    ],
    'encrypt' => [
        'uri' => 'crossphp',
        'auth' => ''
    ],
    'url' => [
        '*' => 'Main:index',
        'type' => 1,
        'rewrite' => false,
        'dot' => '/',
        'ext' => '',
    ],
    'router' => [
        'hi' => 'main:index',
    ]
];


