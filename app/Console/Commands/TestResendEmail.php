<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestResendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resend:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de prueba con Resend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Enviando email de prueba a: '.$email);

        try {
            Mail::raw('Este es un email de prueba desde GrafiRed 3.0 usando Resend.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Email de Prueba - GrafiRed 3.0');
            });

            $this->info('✅ Email enviado correctamente!');
            $this->line('');
            $this->line('Revisa tu bandeja de entrada en: '.$email);
            $this->line('Si no lo ves, revisa la carpeta de spam.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar email: '.$e->getMessage());
            $this->line('');
            $this->line('Posibles soluciones:');
            $this->line('1. Verifica que RESEND_API_KEY esté configurada en .env');
            $this->line('2. Verifica que el dominio esté verificado en Resend');
            $this->line('3. Ejecuta: php artisan config:clear');

            return Command::FAILURE;
        }
    }
}
