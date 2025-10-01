<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResetPassword extends BaseResetPassword
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->readOnly();
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.password.label'))
            ->password()
            ->required()
            ->revealable()
            ->rule('confirmed')
            ->minLength(8)
            ->maxLength(255)
            ->autocomplete('new-password');
    }

    protected function getPasswordConfirmationFormComponent(): TextInput
    {
        return TextInput::make('password_confirmation')
            ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.password_confirmation.label'))
            ->password()
            ->required()
            ->revealable()
            ->dehydrated(false)
            ->maxLength(255)
            ->autocomplete('new-password');
    }
}
