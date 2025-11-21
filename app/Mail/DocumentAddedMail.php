<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentAddedMail extends Mailable
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
        return $this->subject('Nouveau document ajouté - ' . $this->document->titre)
            ->view('emails.document_added')
            ->with([
                'document' => $this->document,
                'user' => $this->user,
            ]);
    }
}
