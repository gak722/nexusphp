<?php
declare(strict_types=1);

namespace Nexus\Mail;

class ArrayTransport implements MailTransport
{
    protected array $sentMessages = [];

    public function send(MailMessage $message): bool
    {
        $this->sentMessages[] = $message;
        return true;
    }

    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    public function clear(): void
    {
        $this->sentMessages = [];
    }
}
