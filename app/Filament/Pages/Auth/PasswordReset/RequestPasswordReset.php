<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use App\Notifications\CustomResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/password-reset/request-password-reset.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * Override del método request para enviar email personalizado
     */
    public function request(): void
    {
        $data = $this->form->getState();

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $data,
            function (CanResetPassword $user, string $token): void {
                // Enviar nuestra notificación personalizada
                $user->notify(new CustomResetPassword($token));
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__($status))
            ->success()
            ->send();

        $this->form->fill();
    }
}
