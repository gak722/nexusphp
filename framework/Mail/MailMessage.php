<?php
declare(strict_types=1);

namespace Nexus\Mail;

use Nexus\Mail\Exceptions\MailException;

/**
 * Clean encapsulation of an outbound Email Message.
 */
class MailMessage
{
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected ?string $replyTo = null;
    protected ?string $fromAddress = null;
    protected ?string $fromName = null;
    protected string $subject = '';
    protected ?string $textBody = null;
    protected ?string $htmlBody = null;
    protected ?string $viewName = null;
    protected array $viewData = [];
    protected array $attachments = [];
    protected array $headers = [];

    public function to(string|array $address, ?string $name = null): static
    {
        $this->addAddresses($this->to, $address, $name);
        return $this;
    }

    public function cc(string|array $address, ?string $name = null): static
    {
        $this->addAddresses($this->cc, $address, $name);
        return $this;
    }

    public function bcc(string|array $address, ?string $name = null): static
    {
        $this->addAddresses($this->bcc, $address, $name);
        return $this;
    }

    public function replyTo(string $address, ?string $name = null): static
    {
        $this->validateEmail($address);
        $this->replyTo = $name ? "{$name} <{$address}>" : $address;
        return $this;
    }

    public function from(string $address, ?string $name = null): static
    {
        $this->validateEmail($address);
        $this->fromAddress = $address;
        $this->fromName = $name;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function text(string $text): static
    {
        $this->textBody = $text;
        return $this;
    }

    public function html(string $html): static
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function view(string $view, array $data = []): static
    {
        $this->viewName = $view;
        $this->viewData = $data;
        return $this;
    }

    public function attach(string $filePath, ?string $name = null, ?string $mime = null): static
    {
        if (!file_exists($filePath)) {
            throw new MailException("Attachment file not found at [{$filePath}].");
        }

        $this->attachments[] = [
            'path' => $filePath,
            'name' => $name ?? basename($filePath),
            'mime' => $mime ?? mime_content_type($filePath) ?: 'application/octet-stream',
        ];
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    protected function addAddresses(array &$target, string|array $address, ?string $name = null): void
    {
        if (is_array($address)) {
            foreach ($address as $key => $val) {
                if (is_int($key)) {
                    $this->validateEmail($val);
                    $target[] = ['address' => $val, 'name' => null];
                } else {
                    $this->validateEmail($key);
                    $target[] = ['address' => $key, 'name' => $val];
                }
            }
        } else {
            $this->validateEmail($address);
            $target[] = ['address' => $address, 'name' => $name];
        }
    }

    protected function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MailException("Invalid email address [{$email}].");
        }
    }

    // Getters
    public function getTo(): array { return $this->to; }
    public function getCc(): array { return $this->cc; }
    public function getBcc(): array { return $this->bcc; }
    public function getReplyTo(): ?string { return $this->replyTo; }
    public function getFromAddress(): ?string { return $this->fromAddress; }
    public function getFromName(): ?string { return $this->fromName; }
    public function getSubject(): string { return $this->subject; }
    public function getTextBody(): ?string { return $this->textBody; }
    public function getHtmlBody(): ?string { return $this->htmlBody; }
    public function getViewName(): ?string { return $this->viewName; }
    public function getViewData(): array { return $this->viewData; }
    public function getAttachments(): array { return $this->attachments; }
    public function getHeaders(): array { return $this->headers; }
}
