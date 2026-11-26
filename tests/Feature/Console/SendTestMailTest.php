<?php

use Illuminate\Support\Facades\Mail;

it('sends a test email to the given recipient', function () {
    config(['mail.default' => 'array']);

    $this->artisan('workbench:mail-test', ['to' => 'yo@example.com'])->assertSuccessful();

    $messages = Mail::mailer('array')->getSymfonyTransport()->messages();

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->getEnvelope()->getRecipients()[0]->getAddress())->toBe('yo@example.com');
});

it('defaults to the backup notification recipient', function () {
    config(['mail.default' => 'array', 'backup.notifications.mail.to' => 'avisos@example.com']);

    $this->artisan('workbench:mail-test')->assertSuccessful();

    expect(Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getEnvelope()->getRecipients()[0]->getAddress())
        ->toBe('avisos@example.com');
});

it('fails on an invalid recipient or an unknown mailer', function () {
    $this->artisan('workbench:mail-test', ['to' => 'no-es-correo'])->assertFailed();
    $this->artisan('workbench:mail-test', ['to' => 'yo@example.com', '--mailer' => 'inexistente'])->assertFailed();
});

it('reports a transport error instead of crashing', function () {
    config(['mail.mailers.roto' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]]);

    $this->artisan('workbench:mail-test', ['to' => 'yo@example.com', '--mailer' => 'roto'])
        ->expectsOutputToContain('No se pudo enviar')
        ->assertFailed();
});
