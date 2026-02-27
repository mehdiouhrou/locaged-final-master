<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;
    public $locale;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $resetUrl, string $locale = 'fr')
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->locale = $locale;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = ui_t('auth.ui.email_subject', [], $this->locale) . ' - ' . config('app.name', 'Locaged');
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
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

    /**
     * Set the locale for the email content
     */
    public function build()
    {
        return $this->withLocale($this->locale, function () {
            return $this->view('emails.password-reset')
                        ->with([
                            'user' => $this->user,
                            'resetUrl' => $this->resetUrl,
                            'locale' => $this->locale,
                        ]);
        });
    }
}
