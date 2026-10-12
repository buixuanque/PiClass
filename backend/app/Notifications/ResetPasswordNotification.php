<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/').'/reset-password?token='.$this->token
            .'&login_identifier='.$notifiable->getEmailForPasswordReset();

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu PiClass')
            ->line('Bạn (hoặc ai đó) đã yêu cầu đặt lại mật khẩu cho tài khoản này.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Liên kết này sẽ hết hạn sau 60 phút.');
    }
}
