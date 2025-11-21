<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $user;

    public function __construct(Document $document, User $user)
    {
        $this->document = $document;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Accès à un document - ' . $this->document->titre)
            ->view('emails.access_granted')
            ->with([
                'document' => $this->document,
                'user' => $this->user,
            ]);
    }
}
