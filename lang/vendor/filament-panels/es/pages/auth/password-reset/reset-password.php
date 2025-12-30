<?php

return [

    'title' => 'Restablecer contraseña',

    'heading' => 'Restablecer contraseña',

    'form' => [

        'email' => [
            'label' => 'Correo electrónico',
        ],

        'password' => [
            'label' => 'Contraseña',
            'validation_attribute' => 'contraseña',
        ],

        'password_confirmation' => [
            'label' => 'Confirmar contraseña',
        ],

        'actions' => [

            'reset' => [
                'label' => 'Restablecer contraseña',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Demasiados intentos',
            'body' => 'Por favor, intente de nuevo en :seconds segundos.',
        ],

    ],

];
