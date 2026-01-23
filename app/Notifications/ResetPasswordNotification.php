<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/password/reset/' . $this->token . '?email=' . urlencode($notifiable->email));

        \Illuminate\Support\Facades\Log::info('Preparing password reset email notification', [
            'user_id' => $notifiable->id,
            'user_email' => $notifiable->email,
            'reset_url' => $url,
            'mail_driver' => config('mail.default'),
        ]);

        return (new MailMessage)
            ->subject('Set Your Password - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your account has been created in the ' . config('app.name') . ' system.')
            ->line('To access your account, please set your password using the link below.')
            ->line('**Username:** ' . $notifiable->username)
            ->action('Set Your Password', $url)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not expect this email, please contact your administrator.')
            ->salutation('Regards, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
