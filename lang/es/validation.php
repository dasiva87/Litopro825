<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'unique' => 'El :attribute ya ha sido registrado.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor a :max caracteres.',
    ],
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'accepted' => 'El campo :attribute debe ser aceptado.',
    
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'company_name' => 'nombre de la empresa',
        'company_email' => 'email corporativo',
        'company_phone' => 'teléfono',
        'company_address' => 'dirección',
        'tax_id' => 'NIT',
        'company_type' => 'tipo de empresa',
        'terms' => 'términos y condiciones',
    ],
];
