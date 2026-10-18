<?php
/**
 * Gateway Education Services - Email Dispatcher & SMTP Client
 * 
 * Supports:
 * 1. Direct SMTP with TLS/SSL encryption (Gmail, Brevo, SendGrid, custom SMTP).
 * 2. Standard PHP mail() fallback with HTML MIME headers.
 * 3. Local logging to data/sent_emails.json for local testing & delivery audit.
 */

error_reporting(E_ALL);

// Load .env file if available
function load_env_file($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

load_env_file(__DIR__ . '/.env');

/**
 * Returns SMTP and sender configurations from environment or defaults.
 */
function get_mail_config() {
    $get = function($key, $default = '') {
        $val = getenv($key);
        if ($val === false || $val === '') {
            $val = $_ENV[$key] ?? $default;
        }
        return $val;
    };

    return [
        'mail_webhook_url' => $get('MAIL_WEBHOOK_URL', ''),
        'resend_api_key'   => $get('RESEND_API_KEY', ''),
        'brevo_api_key'    => $get('BREVO_API_KEY', ''),
        'smtp_host'        => $get('SMTP_HOST', ''),
        'smtp_port'        => (int)($get('SMTP_PORT', '587')),
        'smtp_secure'      => strtolower($get('SMTP_SECURE', 'tls')),
        'smtp_user'        => $get('SMTP_USER', ''),
        'smtp_pass'        => $get('SMTP_PASS', ''),
        'from_email'       => $get('SMTP_FROM_EMAIL', 'taritech17@gmail.com'),
        'from_name'        => $get('SMTP_FROM_NAME', 'Gateway Education Services'),
        'reply_to'         => $get('SMTP_REPLY_TO', 'taritech17@gmail.com'),
        'site_url'         => $get('SITE_URL', 'http://localhost:8000')
    ];
}

/**
 * Sends an email using HTTP Webhook, Resend, Brevo, SMTP socket, or PHP mail().
 *
 * @param string $to Recipient email
 * @param string $subject Email subject line
 * @param string $htmlContent Full HTML body
 * @param string $plainText Plain text alternative
 * @return array ['success' => bool, 'method' => string, 'error' => string|null]
 */
function send_email($to, $subject, $htmlContent, $plainText = '') {
    $config = get_mail_config();
    $to = trim($to);

    if (empty($plainText)) {
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlContent));
    }

    $result = [
        'success' => false,
        'method'  => 'none',
        'error'   => null
    ];

    // 1. Attempt HTTPS Webhook (e.g. Google Apps Script Web App - bypasses all port blocks on Render)
    if (!empty($config['mail_webhook_url'])) {
        $hookResult = send_via_webhook($to, $subject, $htmlContent, $plainText, $config);
        if ($hookResult['success']) {
            $result = [
                'success' => true,
                'method'  => 'webhook',
                'error'   => null
            ];
        } else {
            error_log("Mail webhook failed: " . $hookResult['error']);
            $result['error'] = 'Webhook Error: ' . $hookResult['error'];
        }
    }

    // 2. Attempt Resend REST API (HTTPS port 443 - generous free tier, never blocked by Render)
    if (!$result['success'] && !empty($config['resend_api_key'])) {
        $resendResult = send_via_resend($to, $subject, $htmlContent, $plainText, $config);
        if ($resendResult['success']) {
            $result = [
                'success' => true,
                'method'  => 'resend',
                'error'   => null
            ];
        } else {
            error_log("Resend API failed: " . $resendResult['error']);
            $result['error'] = 'Resend Error: ' . $resendResult['error'];
        }
    }

    // 3. Attempt Brevo REST API (HTTPS port 443 - 300 free emails/day)
    if (!$result['success'] && !empty($config['brevo_api_key'])) {
        $brevoResult = send_via_brevo($to, $subject, $htmlContent, $plainText, $config);
        if ($brevoResult['success']) {
            $result = [
                'success' => true,
                'method'  => 'brevo',
                'error'   => null
            ];
        } else {
            error_log("Brevo API failed: " . $brevoResult['error']);
            $result['error'] = 'Brevo Error: ' . $brevoResult['error'];
        }
    }

    // 4. Attempt Direct SMTP (works locally, cPanel, VPS, or Render Paid tier)
    if (!$result['success'] && !empty($config['smtp_host']) && !empty($config['smtp_user'])) {
        if (empty($config['smtp_pass'])) {
            $result['error'] = 'SMTP_PASS (Google App Password) is required in environment for ' . $config['smtp_user'];
        } else {
            $smtpResult = send_smtp_socket(
                $to,
                $subject,
                $htmlContent,
                $plainText,
                $config
            );
            if ($smtpResult['success']) {
                $result = [
                    'success' => true,
                    'method'  => 'smtp',
                    'error'   => null
                ];
            } else {
                error_log("SMTP delivery failed: " . $smtpResult['error'] . ". Attempting mail() fallback...");
                $result['error'] = 'SMTP Error: ' . $smtpResult['error'];
            }
        }
    }

    // 5. Fallback to native PHP mail() if SMTP/APIs not configured or failed
    if (!$result['success']) {
        $boundary = "----=_Part_" . md5(uniqid(time(), true));
        $headers  = "From: " . encode_mime_header($config['from_name']) . " <" . $config['from_email'] . ">\r\n";
        $headers .= "Reply-To: <" . $config['reply_to'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $plainText . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlContent . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";

        // Suppress warnings on Windows if no local MTA is running
        $mailSent = @mail($to, $subject, $body, $headers);
        if ($mailSent) {
            $result = [
                'success' => true,
                'method'  => 'mail',
                'error'   => null
            ];
        } else {
            if (!$result['error']) {
                $result['error'] = 'Native PHP mail() failed (requires local MTA or configured SMTP server).';
            }
        }
    }

    // 6. Always log the outgoing email for developer transparency & audit
    log_sent_email([
        'to'        => $to,
        'subject'   => $subject,
        'from'      => $config['from_email'],
        'method'    => $result['method'],
        'success'   => $result['success'],
        'error'     => $result['error'],
        'sent_at'   => date('c'),
        'preview'   => substr(strip_tags($htmlContent), 0, 200) . '...'
    ]);

    return $result;
}

/**
 * Sends email via generic HTTPS Webhook (e.g. Google Apps Script Web App).
 */
function send_via_webhook($to, $subject, $htmlContent, $plainText, array $config) {
    $webhookUrl = trim($config['mail_webhook_url']);
    if (empty($webhookUrl)) {
        return ['success' => false, 'error' => 'Webhook URL missing'];
    }

    $payload = [
        'to'         => $to,
        'subject'    => $subject,
        'html'       => $htmlContent,
        'text'       => $plainText,
        'from_name'  => $config['from_name'],
        'from_email' => $config['from_email']
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'Webhook connection error: ' . $curlErr];
    }

    if ($httpCode >= 200 && $httpCode < 400) {
        return ['success' => true, 'error' => null];
    }

    return ['success' => false, 'error' => "Webhook returned HTTP {$httpCode}: {$response}"];
}

/**
 * Sends email via Resend REST API (HTTPS port 443).
 */
function send_via_resend($to, $subject, $htmlContent, $plainText, array $config) {
    $apiKey = trim($config['resend_api_key']);
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Resend API key missing'];
    }

    $url = 'https://api.resend.com/emails';
    $sender = $config['from_email'];
    // Resend test domain fallback if custom domain not verified
    if (strpos($sender, '@gmail.com') !== false) {
        $sender = 'onboarding@resend.dev';
    }

    $payload = [
        'from'     => $config['from_name'] . ' <' . $sender . '>',
        'to'       => [$to],
        'subject'  => $subject,
        'html'     => $htmlContent,
        'text'     => $plainText,
        'reply_to' => $config['reply_to'] ?: $config['from_email']
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'Resend connection error: ' . $curlErr];
    }

    $json = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($json['id'])) {
        return ['success' => true, 'error' => null];
    }

    $msg = $json['message'] ?? ($response ?: "HTTP {$httpCode}");
    return ['success' => false, 'error' => 'Resend error: ' . $msg];
}

/**
 * Sends email via Brevo REST API (HTTPS port 443).
 */
function send_via_brevo($to, $subject, $htmlContent, $plainText, array $config) {
    $apiKey = trim($config['brevo_api_key']);
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Brevo API key missing'];
    }

    $url = 'https://api.brevo.com/v3/smtp/email';
    $payload = [
        'sender'      => ['name' => $config['from_name'], 'email' => $config['from_email']],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $htmlContent,
        'textContent' => $plainText,
        'replyTo'     => ['email' => $config['reply_to']]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'Brevo connection error: ' . $curlErr];
    }

    $json = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'error' => null];
    }

    $msg = $json['message'] ?? ($response ?: "HTTP {$httpCode}");
    return ['success' => false, 'error' => 'Brevo error: ' . $msg];
}

/**
 * Pure PHP RFC-compliant SMTP client with TLS/SSL encryption.
 */
function send_smtp_socket($to, $subject, $htmlContent, $plainText, array $config) {
    $host = $config['smtp_host'];
    $port = $config['smtp_port'];
    $secure = $config['smtp_secure'];
    $timeout = 4;

    $remoteHost = ($secure === 'ssl' || $port === 465) ? "ssl://{$host}" : "tcp://{$host}";
    $socket = @stream_socket_client("{$remoteHost}:{$port}", $errno, $errstr, $timeout);

    if (!$socket) {
        $hint = ($errno === 110 || strpos(strtolower($errstr), 'timed out') !== false || strpos(strtolower($errstr), 'refused') !== false)
            ? " (Note: Render Free Tier blocks outbound ports 25, 465, and 587. Configure MAIL_WEBHOOK_URL, RESEND_API_KEY, or BREVO_API_KEY for HTTPS delivery)"
            : "";
        return ['success' => false, 'error' => "Could not connect to SMTP server {$host}:{$port} - {$errstr} ({$errno}){$hint}"];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function($cmd, $expectedCode) use ($socket, $readResponse) {
        fwrite($socket, $cmd . "\r\n");
        $res = $readResponse();
        $code = (int)substr($res, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("SMTP command [{$cmd}] expected {$expectedCode}, received: {$res}");
        }
        return $res;
    };

    try {
        $greeting = $readResponse();
        if ((int)substr($greeting, 0, 3) !== 220) {
            throw new Exception("Invalid SMTP greeting: " . $greeting);
        }

        $sendCommand("EHLO localhost", 250);

        // Upgrade connection to TLS if required
        if ($secure === 'tls' || $port === 587) {
            $sendCommand("STARTTLS", 220);
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                throw new Exception("TLS negotiation failed with {$host}");
            }
            $sendCommand("EHLO localhost", 250);
        }

        // Authenticate if credentials provided
        if (!empty($config['smtp_user']) && !empty($config['smtp_pass'])) {
            $smtpPass = str_replace(' ', '', $config['smtp_pass']);
            $sendCommand("AUTH LOGIN", 334);
            $sendCommand(base64_encode(trim($config['smtp_user'])), 334);
            $sendCommand(base64_encode($smtpPass), 235);
        }

        $sendCommand("MAIL FROM: <" . $config['from_email'] . ">", 250);
        $sendCommand("RCPT TO: <" . $to . ">", 250);
        $sendCommand("DATA", 354);

        $boundary = "----=_Part_" . md5(uniqid(time(), true));
        $msgHeaders  = "To: <{$to}>\r\n";
        $msgHeaders .= "From: " . encode_mime_header($config['from_name']) . " <{$config['from_email']}>\r\n";
        $msgHeaders .= "Reply-To: <{$config['reply_to']}>\r\n";
        $msgHeaders .= "Subject: " . encode_mime_header($subject) . "\r\n";
        $msgHeaders .= "Date: " . date('r') . "\r\n";
        $msgHeaders .= "MIME-Version: 1.0\r\n";
        $msgHeaders .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";

        $msgBody  = "--{$boundary}\r\n";
        $msgBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msgBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msgBody .= $plainText . "\r\n\r\n";
        $msgBody .= "--{$boundary}\r\n";
        $msgBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msgBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msgBody .= $htmlContent . "\r\n\r\n";
        $msgBody .= "--{$boundary}--\r\n";

        // Single dot on a line terminates the DATA section
        $fullPayload = $msgHeaders . $msgBody . "\r\n.";
        $sendCommand($fullPayload, 250);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        if (is_resource($socket)) {
            @fclose($socket);
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Helper to encode MIME headers cleanly.
 */
function encode_mime_header($string) {
    return '=?UTF-8?B?' . base64_encode($string) . '?=';
}

/**
 * Logs sent emails to data/sent_emails.json.
 */
function log_sent_email(array $record) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/sent_emails.json';
    $list = [];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if (!empty($content)) {
            $list = json_decode($content, true) ?: [];
        }
    }
    $list[] = $record;
    @file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Generates a stunning, responsive HTML confirmation email template.
 */
function get_newsletter_confirmation_html($recipientEmail) {
    $year = date('Y');
    $config = get_mail_config();
    $siteUrl = rtrim($config['site_url'], '/');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed - Gateway Education Services</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7f4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e1e8e2;
        }
        .email-header {
            background: linear-gradient(135deg, #0f3d13 0%, #1b5e20 50%, #2e7d32 100%);
            color: #ffffff;
            padding: 40px 30px 30px 30px;
            text-align: center;
        }
        .school-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            color: #c8e6c9;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .email-header h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .email-header p {
            margin: 0;
            font-size: 15px;
            color: #e8f5e9;
            opacity: 0.95;
        }
        .email-body {
            padding: 35px 32px;
        }
        .welcome-title {
            font-size: 20px;
            font-weight: 700;
            color: #1b5e20;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .email-body p {
            font-size: 15px;
            color: #425466;
            margin-bottom: 18px;
        }
        .highlight-card {
            background-color: #f1f8f3;
            border-left: 4px solid #43a047;
            padding: 18px 20px;
            border-radius: 6px;
            margin: 24px 0;
        }
        .highlight-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #1b5e20;
        }
        .highlight-card ul {
            margin: 0;
            padding-left: 20px;
            color: #38503b;
            font-size: 14px;
        }
        .highlight-card li {
            margin-bottom: 6px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0 20px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 13px 28px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
        }
        .contact-box {
            background-color: #fafbfb;
            border: 1px solid #eef2ef;
            border-radius: 8px;
            padding: 18px 20px;
            margin-top: 25px;
            font-size: 13px;
            color: #5a6e60;
        }
        .contact-box strong {
            color: #1b5e20;
        }
        .email-footer {
            background-color: #0f2b12;
            color: #a5d6a7;
            padding: 25px 30px;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }
        .email-footer a {
            color: #c8e6c9;
            text-decoration: underline;
        }
        .footer-note {
            color: #81c784;
            margin-top: 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <span class="school-badge">Official Confirmation</span>
            <h1>Gateway Education Services</h1>
            <p>Nurturing Creative, Critical Thinkers in Uganda</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="welcome-title">Welcome to Our Newsletter Community! 🎉</h2>
            <p>Hello,</p>
            <p>Thank you for subscribing to the <strong>Gateway Education Services</strong> newsletter with your email: <strong>{$recipientEmail}</strong>.</p>
            <p>Your subscription is now confirmed! You will be among the first to receive updates about our academic programs, admissions deadlines, campus events, and student achievements.</p>

            <div class="highlight-card">
                <h3>What You Can Look Forward To:</h3>
                <ul>
                    <li><strong>Admissions & Term Announcements</strong> — Key dates and application forms.</li>
                    <li><strong>Academic & Co-Curricular Highlights</strong> — STEM, arts, sports, and holistic learning.</li>
                    <li><strong>Campus Life & Community Events</strong> — School open days, parent-teacher forums, and celebrations.</li>
                </ul>
            </div>

            <div class="cta-container">
                <a href="{$siteUrl}" class="cta-button">Visit Our Website</a>
            </div>

            <div class="contact-box">
                <p style="margin: 0 0 6px 0;"><strong>Need immediate assistance?</strong></p>
                <p style="margin: 0 0 4px 0;">📍 <strong>Campus:</strong> Kawuku-Bwerenga, Entebbe, Uganda (Plot 198, P.O.BOX 700285 Entebbe)</p>
                <p style="margin: 0 0 4px 0;">📞 <strong>Phone:</strong> +256 7098 01 722 &nbsp;|&nbsp; +256 760 8679 60</p>
                <p style="margin: 0;">✉️ <strong>Email:</strong> info@gatewayeducationservices.sc.ug</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0 0 6px 0;">&copy; {$year} Gateway Education Services. All rights reserved.</p>
            <p style="margin: 0 0 6px 0;">Entebbe Katabi, Plot 198, P.O.BOX 700285 Entebbe, Uganda</p>
            <p class="footer-note">You received this email because this email address was subscribed to updates at <a href="{$siteUrl}">Gateway Education Services</a>. If you did not make this request, you can simply ignore this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * High-level function to send the newsletter confirmation email.
 */
function send_newsletter_confirmation($recipientEmail) {
    $subject = 'Subscription Confirmed - Gateway Education Services Newsletter';
    $htmlContent = get_newsletter_confirmation_html($recipientEmail);
    $plainText = "Welcome to Gateway Education Services Newsletter!\n\n"
        . "Thank you for subscribing with {$recipientEmail}.\n\n"
        . "Your subscription is now confirmed. You will receive the latest updates, admissions news, and announcements.\n\n"
        . "Gateway Education Services\n"
        . "Kawuku-Bwerenga, Entebbe, Uganda\n"
        . "Phone: +256 7098 01 722 / +256 760 8679 60\n"
        . "Website: " . (getenv('SITE_URL') ?: 'http://localhost:8000') . "\n";

    return send_email($recipientEmail, $subject, $htmlContent, $plainText);
}
