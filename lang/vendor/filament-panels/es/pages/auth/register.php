<?php

return [
    'title' => 'Crear una cuenta',
    'heading' => 'Registrarse',
    'actions' => [
        'login' => [
            'before' => '¿Ya tienes una cuenta?',
            'label' => 'Iniciar sesión',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'Correo electrónico',
        ],
        'name' => [
            'label' => 'Nombre',
        ],
        'password' => [
            'label' => 'Contraseña',
            'validation_attribute' => 'contraseña',
        ],
        'password_confirmation' => [
            'label' => 'Confirmar contraseña',
        ],
        'actions' => [
            'register' => [
                'label' => 'Crear cuenta',
            ],
        ],
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Demasiados intentos de registro',
            'body' => 'Por favor, inténtalo de nuevo en :seconds segundos.',
        ],
    ],
];
