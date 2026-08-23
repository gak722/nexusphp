<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Mail\ArrayTransport;
use Nexus\Mail\MailManager;
use Nexus\Mail\MailMessage;

/**
 * Facade and helper entry point for Email delivery.
 */
class Mail
{
    protected static ?ArrayTransport $fakeTransport = null;

    public static function fake(): ArrayTransport
    {
        static::$fakeTransport = new ArrayTransport();
        return static::$fakeTransport;
    }

    public static function restore(): void
    {
        static::$fakeTransport = null;
    }

    public static function to(string|array $address, ?string $name = null): MailPendingSend
    {
        return new MailPendingSend((new MailMessage())->to($address, $name));
    }

    public static function raw(string $text, callable|array $callback): bool
    {
        $message = (new MailMessage())->text($text);
        if (is_callable($callback)) {
            $callback($message);
        }
        return static::send($message);
    }

    public static function send(MailMessage $message): bool
    {
        $manager = static::manager();
        if (static::$fakeTransport !== null) {
            $manager = clone $manager;
            $manager->setTransport(static::$fakeTransport);
        }
        return $manager->send($message);
    }

    public static function assertSent(callable $callback): void
    {
        if (static::$fakeTransport === null) {
            throw new \RuntimeException("Mail::fake() must be called before assertions.");
        }

        $sent = static::$fakeTransport->getSentMessages();
        foreach ($sent as $message) {
            if ($callback($message) === true) {
                return;
            }
        }

        throw new \RuntimeException("The expected mail message was not sent.");
    }

    public static function assertNotSent(callable $callback): void
    {
        if (static::$fakeTransport === null) {
            throw new \RuntimeException("Mail::fake() must be called before assertions.");
        }

        $sent = static::$fakeTransport->getSentMessages();
        foreach ($sent as $message) {
            if ($callback($message) === true) {
                throw new \RuntimeException("Unexpected mail message was sent.");
            }
        }
    }

    protected static function manager(): MailManager
    {
        if (\Nexus\Foundation\Application::getInstance()->has(MailManager::class)) {
            return \Nexus\Foundation\Application::getInstance()->make(MailManager::class);
        }

        $config = config('mail', []);
        return new MailManager(is_array($config) ? $config : []);
    }
}

/**
 * Fluent builder proxy for sending mail.
 */
class MailPendingSend
{
    public function __construct(
        protected MailMessage $message
    ) {}

    public function cc(string|array $address, ?string $name = null): static
    {
        $this->message->cc($address, $name);
        return $this;
    }

    public function bcc(string|array $address, ?string $name = null): static
    {
        $this->message->bcc($address, $name);
        return $this;
    }

    public function replyTo(string $address, ?string $name = null): static
    {
        $this->message->replyTo($address, $name);
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->message->subject($subject);
        return $this;
    }

    public function text(string $text): static
    {
        $this->message->text($text);
        return $this;
    }

    public function html(string $html): static
    {
        $this->message->html($html);
        return $this;
    }

    public function view(string $view, array $data = []): static
    {
        $this->message->view($view, $data);
        return $this;
    }

    public function attach(string $filePath, ?string $name = null, ?string $mime = null): static
    {
        $this->message->attach($filePath, $name, $mime);
        return $this;
    }

    public function send(): bool
    {
        return Mail::send($this->message);
    }
}
