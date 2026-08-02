<?php

namespace App\Mail;

use App\Models\DemoLead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoLoginNotification extends Mailable
{
    use SerializesModels;

    public function __construct(public DemoLead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New live demo login — '.$this->lead->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-login-notification',
        );
    }
}
