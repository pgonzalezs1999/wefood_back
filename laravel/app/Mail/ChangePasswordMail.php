<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangePasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public function __construct($url) {
        $this -> url = $url;
    }

    public function envelope() {
        return new Envelope(
            subject: 'Cambio de contraseña en WeFood',
        );
    }

    public function content() {
        return new Content(
            view: 'emails.changePassword',
        );
    }

    public function attachments() {
        return [];
    }
}
