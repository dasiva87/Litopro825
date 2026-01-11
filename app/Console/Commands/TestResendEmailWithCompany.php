<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestResendEmailWithCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resend:test-company {email} {--company=GrafiRed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de prueba con nombre de empresa en el asunto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $companyName = $this->option('company');

        $this->info('Enviando email de prueba a: '.$email);
        $this->info('Nombre de empresa: '.$companyName);

        try {
            Mail::raw('Este es un email de prueba desde GrafiRed 3.0 usando Resend con nombre de empresa en el asunto.', function ($message) use ($email, $companyName) {
                $message->to($email)
                    ->subject("{$companyName} - Email de Prueba - Cotización #12345");
            });

            $this->info('✅ Email enviado correctamente!');
            $this->line('');
            $this->line('Revisa tu bandeja de entrada en: '.$email);
            $this->line('El asunto debe ser: '.$companyName.' - Email de Prueba - Cotización #12345');

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
