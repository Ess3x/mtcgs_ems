<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $role;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $password, $role)
    {
        $this->user = $user;
        $this->password = $password;
        $this->role = $role;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $to = [];

        if (!empty($this->user?->email)) {
            $to[] = $this->user->email;
        }

        return new Envelope(
            to: $to,
            subject: 'Your MTCGS-EMS Account Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-credentials',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'role' => $this->role,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
