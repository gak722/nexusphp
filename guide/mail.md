# Mail

NexusPHP provides a robust, zero-dependency mail subsystem for sending emails. Instead of relying on heavy third-party packages like Symfony Mailer or PHPMailer, NexusPHP natively implements a highly efficient SMTP transport using raw PHP sockets, alongside an Array transport for seamless testing.

The system natively integrates with the framework's view engine, allowing you to quickly render complex HTML templates into email bodies.

---

## Configuration

Mail configuration is passed as an array to the `Nexus\Mail\MailManager`. While you can instantiate this manager manually, it is typically resolved from the application container using your environment variables and configuration files.

The mail configuration array structure expects a `default` driver, a `from` address, and specific `mailers` options.

### Example Configuration Structure

```php
return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'NexusPHP Application'),
    ],

    'mailers' => [
        'smtp' => [
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'), // 'tls' or 'ssl'
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 15,
        ],
        
        'array' => [
            // No configuration needed
        ],
    ],
];
```

---

## Sending Emails

To send an email, you first ask the `MailManager` to create a `MailMessage`. You then build your message using a fluent interface before passing it back to the manager's `send()` method.

```php
use Nexus\Mail\MailManager;

// Assume $mailManager is injected or resolved via the app() helper
$message = $mailManager->createMessage()
    ->to('user@example.com', 'John Doe')
    ->subject('Welcome to NexusPHP!')
    ->text('Hello, John! Welcome to our platform.');

$mailManager->send($message);
```

### Specifying Recipients

You can define recipients, CCs, and BCCs using single strings or arrays.

```php
$message->to('user@example.com')
        ->cc(['team@example.com', 'manager@example.com' => 'The Manager'])
        ->bcc('hidden@example.com');
```

### Overriding the Sender

By default, the `MailManager` injects the `from` address defined in your configuration into the `MailMessage`. You can override this, or add a Reply-To header manually:

```php
$message->from('noreply@example.com', 'System Daemon')
        ->replyTo('support@example.com', 'Support Team');
```

### Rendering Views

Instead of passing raw text or HTML, you can instruct the message to compile a view template from your `views/` directory.

```php
$message->view('emails/welcome', ['name' => 'John Doe']);
```

When you call `send()`, the `MailManager` will automatically resolve the `Nexus\View\ViewFactory`, render the view with the provided data, and inject it as the HTML body of the email.

> [!NOTE]
> Every email must contain either a text body, an HTML body, or a compiled view. Attempting to send an empty message will throw a `MailException`.

---

## Attachments

You can attach files to your email using the `attach()` method. You must provide the absolute file path. You may optionally provide a display name and a MIME type; if omitted, NexusPHP will attempt to auto-detect them.

```php
$message->attach('/path/to/invoice.pdf', 'Invoice_001.pdf', 'application/pdf');
```

> [!TODO]
> Verify raw data attachments. The current `MailMessage` implementation strictly expects a physical file path via `attach()`. Explicit methods for attaching raw in-memory data (e.g., `attachData()`) were not found in the initial codebase scan.

---

## Custom Headers

If you need to pass specific SMTP headers (like Message-IDs or custom tracking headers), use the `header()` method:

```php
$message->header('X-Mailgun-Variables', '{"user_id": 123}');
```

---

## Available Drivers

NexusPHP currently bundles two native transport drivers:

### 1. SMTP Driver (`smtp`)
Powered by `Nexus\Mail\SmtpTransport`. This is the production workhorse. It uses raw PHP sockets (`fsockopen`) to connect directly to SMTP servers. It supports `tls` and `ssl` encryption protocols and handles MIME multi-part chunking for attachments entirely natively.

### 2. Array Driver (`array` or `fake`)
Powered by `Nexus\Mail\ArrayTransport`. Instead of sending real emails, this driver pushes outbound `MailMessage` instances into an internal array. This is extremely useful for local development or during automated testing to assert that an email *would* have been sent.

```php
// In a test environment
$transport = $mailManager->getTransport();

if ($transport instanceof \Nexus\Mail\ArrayTransport) {
    $sentMessages = $transport->getSentMessages();
    $this->assertCount(1, $sentMessages);
    $this->assertEquals('Welcome!', $sentMessages[0]->getSubject());
}
```

> [!TODO]
> Check for native queued jobs support. While the framework likely supports pushing closures or commands to a queue, dedicated configuration to automatically queue mail sending asynchronously (e.g., a `queue()` method instead of `send()`) was not verified natively within the `MailManager` or `MailMessage`.

---

## Best Practices

1. **Keep Secrets Secret**: Never hardcode SMTP credentials in your configuration file. Always pull them from the `.env` file.
2. **Use the Array Driver in Development**: Set `MAIL_MAILER=array` locally to prevent accidentally sending test emails to real users.
3. **Handle Exceptions**: Wrap `send()` calls in `try/catch` blocks. Network timeouts or invalid recipient formats will throw `MailException` or `MailTransportException`.

---

## Next Steps

Now that you can send emails, explore other framework utilities:

- [Views](views.md): Learn how to build HTML templates for your emails.
- [Validation](validation.md): Validate user input before sending an email.
- [Events](events.md): Fire an event to trigger an email notification.
