<?php
declare(strict_types=1);

namespace Nexus\Mail;

use Nexus\Mail\Exceptions\MailTransportException;

class SmtpTransport implements MailTransport
{
    public function __construct(
        protected array $config = []
    ) {}

    public function send(MailMessage $message): bool
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = (int) ($this->config['port'] ?? 587);
        $timeout = (int) ($this->config['timeout'] ?? 15);
        $encryption = $this->config['encryption'] ?? 'tls';

        $socketHost = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
        $socket = @fsockopen($socketHost, $port, $errno, $errstr, (float) $timeout);

        if (!$socket) {
            throw new MailTransportException("Failed to connect to SMTP server {$host}:{$port} ({$errno}: {$errstr})");
        }

        try {
            $this->readResponse($socket);

            $this->sendCommand($socket, "EHLO " . gethostname());

            if ($encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new MailTransportException("Failed to establish TLS encryption with SMTP server.");
                }
                $this->sendCommand($socket, "EHLO " . gethostname());
            }

            if (!empty($this->config['username']) && !empty($this->config['password'])) {
                $this->sendCommand($socket, "AUTH LOGIN");
                $this->sendCommand($socket, base64_encode((string) $this->config['username']));
                $this->sendCommand($socket, base64_encode((string) $this->config['password']));
            }

            $from = $message->getFromAddress() ?? ($this->config['from']['address'] ?? 'hello@example.com');
            $this->sendCommand($socket, "MAIL FROM:<{$from}>");

            foreach ($message->getTo() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['address']}>");
            }
            foreach ($message->getCc() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['address']}>");
            }
            foreach ($message->getBcc() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['address']}>");
            }

            $this->sendCommand($socket, "DATA");

            $mimeData = $this->buildMimeMessage($message, $from);
            fwrite($socket, $mimeData . "\r\n.\r\n");
            $this->readResponse($socket);

            $this->sendCommand($socket, "QUIT");
            return true;
        } finally {
            fclose($socket);
        }
    }

    protected function sendCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket);
    }

    protected function readResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new MailTransportException("SMTP Error [{$code}]: {$response}");
        }
        return $response;
    }

    protected function buildMimeMessage(MailMessage $message, string $from): string
    {
        $boundary = '=_Part_' . md5(uniqid((string) time(), true));
        $headers = [];

        $fromName = $message->getFromName() ?? ($this->config['from']['name'] ?? null);
        $headers[] = "From: " . ($fromName ? "{$fromName} <{$from}>" : $from);

        $toStrings = array_map(fn($r) => $r['name'] ? "{$r['name']} <{$r['address']}>" : $r['address'], $message->getTo());
        $headers[] = "To: " . implode(', ', $toStrings);

        if (!empty($message->getCc())) {
            $ccStrings = array_map(fn($r) => $r['name'] ? "{$r['name']} <{$r['address']}>" : $r['address'], $message->getCc());
            $headers[] = "Cc: " . implode(', ', $ccStrings);
        }

        if ($message->getReplyTo()) {
            $headers[] = "Reply-To: " . $message->getReplyTo();
        }

        $headers[] = "Subject: " . $message->getSubject();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

        foreach ($message->getHeaders() as $name => $val) {
            $headers[] = "{$name}: {$val}";
        }

        $bodyLines = [];
        $bodyLines[] = implode("\r\n", $headers);
        $bodyLines[] = "";
        $bodyLines[] = "--{$boundary}";

        if ($message->getHtmlBody()) {
            $bodyLines[] = "Content-Type: text/html; charset=UTF-8";
            $bodyLines[] = "Content-Transfer-Encoding: base64";
            $bodyLines[] = "";
            $bodyLines[] = chunk_split(base64_encode($message->getHtmlBody()));
        } else {
            $bodyLines[] = "Content-Type: text/plain; charset=UTF-8";
            $bodyLines[] = "Content-Transfer-Encoding: base64";
            $bodyLines[] = "";
            $bodyLines[] = chunk_split(base64_encode($message->getTextBody() ?? ''));
        }

        foreach ($message->getAttachments() as $att) {
            $bodyLines[] = "--{$boundary}";
            $bodyLines[] = "Content-Type: {$att['mime']}; name=\"{$att['name']}\"";
            $bodyLines[] = "Content-Disposition: attachment; filename=\"{$att['name']}\"";
            $bodyLines[] = "Content-Transfer-Encoding: base64";
            $bodyLines[] = "";
            $bodyLines[] = chunk_split(base64_encode(file_get_contents($att['path'])));
        }

        $bodyLines[] = "--{$boundary}--";

        return implode("\r\n", $bodyLines);
    }
}
