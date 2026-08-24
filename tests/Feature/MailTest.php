<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Mail\ArrayTransport;
use Nexus\Mail\MailManager;
use Nexus\Mail\MailMessage;
use Nexus\Support\Mail;

use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    public function testSendMailMessage(): void
    {
        $transport = new ArrayTransport();
        $manager = new MailManager(['from' => ['address' => 'system@example.com', 'name' => 'System']], $transport);

        $message = $manager->createMessage()
            ->to('user@example.com', 'User Name')
            ->subject('Welcome!')
            ->text('Welcome to our application.');

        $result = $manager->send($message);

        if (!$result) {
            throw new \RuntimeException("Mail send returned false.");
        }

        $sent = $transport->getSentMessages();
        if (count($sent) !== 1) {
            throw new \RuntimeException("Expected 1 sent message.");
        }
        $msg = $sent[0];
        if ($msg->getTo()[0]['address'] !== 'user@example.com') {
            throw new \RuntimeException("Recipient mismatch.");
        }
        if ($msg->getSubject() !== 'Welcome!') {
            throw new \RuntimeException("Subject mismatch.");
        }
        if ($msg->getFromAddress() !== 'system@example.com') {
            throw new \RuntimeException("From address mismatch.");
        }
    }

    public function testMailFacadeFakeAndAssertion(): void
    {
        Mail::fake();

        Mail::to('john@example.com')
            ->subject('Order Confirmation')
            ->html('<h1>Thank you for your order</h1>')
            ->send();

        Mail::assertSent(function (MailMessage $msg) {
            return $msg->getTo()[0]['address'] === 'john@example.com'
                && $msg->getSubject() === 'Order Confirmation';
        });

        Mail::restore();
    }

    public function testMailWithViewRendering(): void
    {
        $transport = new ArrayTransport();
        $manager = new MailManager([], $transport);

        // Create a temporary view in storage or test directory
        $viewPath = sys_get_temp_dir() . '/nexus_mail_test_view.php';
        file_put_contents($viewPath, 'Hello <?= $name ?>!');

        $viewFactory = new class($viewPath) extends \Nexus\View\ViewFactory {
            public function __construct(protected string $file) {}
            public function make(string $name, array $data = []): \Nexus\View\View {
                return new \Nexus\View\View(new \Nexus\View\Engine(), $this->file, $data);
            }
        };

        $manager = new MailManager([], $transport, $viewFactory);

        $message = (new MailMessage())
            ->to('test@example.com')
            ->subject('View Test')
            ->view('test.view', ['name' => 'Alice']);

        $manager->send($message);

        $sent = $transport->getSentMessages()[0];
        if ($sent->getHtmlBody() !== 'Hello Alice!') {
            throw new \RuntimeException("View rendering failed in mail. Body: " . $sent->getHtmlBody());
        }

        @unlink($viewPath);
    }
}
