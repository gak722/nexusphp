<?php
declare(strict_types=1);

namespace Nexus\Mail;

interface MailTransport
{
    public function send(MailMessage $message): bool;
}
