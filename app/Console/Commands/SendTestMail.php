<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Sends a plain test email through the configured mailer, to check the mail
 * server (Resend in production) without waiting for a real notification.
 */
class SendTestMail extends Command
{
    /**
     * @var string
     */
    protected $signature = 'workbench:mail-test
                            {to? : Destinatario (por defecto, el de los avisos de respaldo)}
                            {--mailer= : Mailer a usar (por defecto, MAIL_MAILER)}';

    /**
     * @var string
     */
    protected $description = 'Envía un correo de prueba para comprobar la configuración de correo';

    public function handle(): int
    {
        $recipient = $this->argument('to') ?? config('backup.notifications.mail.to');
        $mailer = $this->option('mailer') ?? config('mail.default');

        if (Validator::make(['to' => $recipient], ['to' => ['required', 'email']])->fails()) {
            $this->components->error('Indica un destinatario válido.');

            return self::FAILURE;
        }

        if (config("mail.mailers.{$mailer}") === null) {
            $this->components->error("El mailer [{$mailer}] no está configurado en config/mail.php.");

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Mailer', $mailer);
        $this->components->twoColumnDetail('Remitente', (string) config('mail.from.address'));
        $this->components->twoColumnDetail('Destinatario', $recipient);

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->components->warn("El mailer [{$mailer}] no envía correos reales.");
        }

        try {
            Mail::mailer($mailer)->raw(
                'Si recibes este correo, el envío desde '.config('app.name').' ('.config('app.url').') funciona.',
                fn (Message $message) => $message->to($recipient)->subject(config('app.name').': correo de prueba'),
            );
        } catch (Throwable $exception) {
            $this->components->error('No se pudo enviar: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Correo de prueba enviado a {$recipient}.");

        return self::SUCCESS;
    }
}
