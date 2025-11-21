<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $stats;
    public $user;

    public function __construct($stats, User $user)
    {
        $this->stats = $stats;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Rapport Hebdomadaire - Archivage Olam Agri')
            ->view('emails.weekly_report')
            ->with([
                'stats' => $this->stats,
                'user' => $this->user,
            ]);
    }
}
