<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $password Mot de passe en clair
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Bienvenue sur la plateforme d\'archivage Olam Agri')
            ->view('emails.user_created')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
                'loginUrl' => config('app.url') . '/login', // URL de connexion
            ]);
    }
}
