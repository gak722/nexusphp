<?php
declare(strict_types=1);

namespace Nexus\Mail;

use Nexus\Mail\Exceptions\MailException;
use Nexus\View\ViewFactory;

class MailManager
{
    protected ?MailTransport $transport = null;
    protected ?ViewFactory $viewFactory = null;
    protected array $config = [];

    public function __construct(array $config = [], ?MailTransport $transport = null, ?ViewFactory $viewFactory = null)
    {
        $this->config = $config;
        $this->transport = $transport;
        $this->viewFactory = $viewFactory;
    }

    public function setTransport(MailTransport $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    public function getTransport(): MailTransport
    {
        if ($this->transport === null) {
            $driver = $this->config['default'] ?? 'smtp';
            $mailerConfig = $this->config['mailers'][$driver] ?? [];
            $mailerConfig['from'] = $this->config['from'] ?? [];

            $this->transport = match ($driver) {
                'array', 'fake' => new ArrayTransport(),
                default => new SmtpTransport($mailerConfig),
            };
        }
        return $this->transport;
    }

    public function getViewFactory(): ViewFactory
    {
        if ($this->viewFactory === null) {
            $this->viewFactory = new ViewFactory();
        }
        return $this->viewFactory;
    }

    public function createMessage(): MailMessage
    {
        $message = new MailMessage();
        if (isset($this->config['from']['address'])) {
            $message->from(
                $this->config['from']['address'],
                $this->config['from']['name'] ?? null
            );
        }
        return $message;
    }

    public function send(MailMessage $message): bool
    {
        if ($message->getViewName()) {
            $view = $this->getViewFactory()->make($message->getViewName(), $message->getViewData());
            $rendered = $view->render();
            if ($message->getHtmlBody() === null) {
                $message->html($rendered);
            }
        }

        if ($message->getHtmlBody() === null && $message->getTextBody() === null) {
            throw new MailException("Email message must contain either text or HTML body.");
        }

        return $this->getTransport()->send($message);
    }
}
