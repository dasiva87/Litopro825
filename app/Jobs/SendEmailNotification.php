<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notification;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notification $notification,
        public array $recipients,
        public ?string $connection = null
    ) {
        $this->onQueue('emails');

        if ($connection) {
            $this->onConnection($connection);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach ($this->recipients as $recipient) {
                if (is_string($recipient)) {
                    // Email address string
                    \Notification::route('mail', $recipient)
                        ->notify($this->notification);
                } else {
                    // Notifiable model
                    $recipient->notify($this->notification);
                }
            }

            Log::info('Email notification sent successfully', [
                'notification' => get_class($this->notification),
                'recipients_count' => count($this->recipients)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'notification' => get_class($this->notification),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email notification job failed permanently', [
            'notification' => get_class($this->notification),
            'recipients_count' => count($this->recipients),
            'error' => $exception->getMessage()
        ]);
    }
}
