<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendVerificationEmail(string $to, string $link): void
    {
        $email = (new Email())
            ->from('no-reply@yourapp.com')   // change to your app’s email
            ->to($to)
            ->subject('Verify your email')
            ->html("
                <h2>Email Verification</h2>
                <p>Please verify your email by clicking 
                <a href='$link'>this link</a>.</p>
            ");

        $this->mailer->send($email);
    }
}