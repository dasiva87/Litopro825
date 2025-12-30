<?php

return [

    'title' => 'Recuperar contraseña',

    'heading' => 'Recuperar contraseña',

    'actions' => [

        'login' => [
            'label' => 'Volver al inicio de sesión',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Correo electrónico',
        ],

        'actions' => [

            'request' => [
                'label' => 'Enviar enlace de recuperación',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Demasiados intentos',
            'body' => 'Por favor, intente de nuevo en :seconds segundos.',
        ],

        'sent' => [
            'title' => 'Enlace enviado',
            'body' => 'Si existe una cuenta con ese correo electrónico, le hemos enviado un enlace para restablecer su contraseña.',
        ],

    ],

];
