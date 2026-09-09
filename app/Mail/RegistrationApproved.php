<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password = null)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('MTCGS-EMS: Registration Approved')
                    ->view('emails.registration-approved')
                    ->with([
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'password' => $this->password,
                        'loginUrl' => url('/login'),
                    ]);
    }
}
