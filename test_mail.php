<?php
/**
 * Gateway Education Services - Email & SMTP Diagnostic Tool
 * 
 * Access via browser to verify email delivery configuration on Render or any server.
 * Example: https://gatewayeduserrrvices.onrender.com/test_mail.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/mailer.php';

$config = get_mail_config();
$testRecipient = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : $config['from_email'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 1. Check Port 587 (SMTP) connectivity
$smtpSocketTest = false;
$smtpSocketError = '';
$socketStart = microtime(true);
$testSocket = @stream_socket_client("tcp://{$config['smtp_host']}:{$config['smtp_port']}", $errno, $errstr, 3);
if ($testSocket) {
    $smtpSocketTest = true;
    fclose($testSocket);
} else {
    $smtpSocketError = "{$errstr} ({$errno})";
}
$smtpLatency = round((microtime(true) - $socketStart) * 1000);

// 2. Check Port 443 (HTTPS) connectivity
$httpsSocketTest = false;
$httpsError = '';
$httpsStart = microtime(true);
$httpsSocket = @stream_socket_client("ssl://api.resend.com:443", $errno, $errstr, 3);
if ($httpsSocket) {
    $httpsSocketTest = true;
    fclose($httpsSocket);
} else {
    $httpsError = "{$errstr} ({$errno})";
}
$httpsLatency = round((microtime(true) - $httpsStart) * 1000);

// 3. Test send if requested
$sendResult = null;
if ($action === 'send_test' && !empty($testRecipient)) {
    $subject = 'Test Email Delivery - Gateway Education Services';
    $html = <<<HTML
<div style="font-family:sans-serif; max-width:550px; margin:0 auto; padding:25px; border:1px solid #c8e6c9; border-radius:10px; background:#fbfdfb;">
    <h2 style="color:#1b5e20; margin-top:0;">Email Dispatch Test Successful! &checkmark;</h2>
    <p>This is a diagnostic verification email sent from your website deployment.</p>
    <table style="width:100%; border-collapse:collapse; margin:15px 0;">
        <tr><td style="padding:6px; color:#555;">Server Time:</td><td style="padding:6px; font-weight:bold;">{date('c')}</td></tr>
        <tr><td style="padding:6px; color:#555;">Recipient:</td><td style="padding:6px; font-weight:bold;">{$testRecipient}</td></tr>
        <tr><td style="padding:6px; color:#555;">Sender:</td><td style="padding:6px; font-weight:bold;">{$config['from_email']}</td></tr>
    </table>
    <p style="color:#2e7d32; font-size:13px;">If you are reading this in your inbox, your email delivery pipeline is fully functional!</p>
</div>
HTML;
    $text = "Email Dispatch Test Successful!\nSent to: {$testRecipient}\nTime: " . date('c');
    $sendResult = send_email($testRecipient, $subject, $html, $text);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Diagnostic Tool - Gateway Education Services</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f2b12; color: #f0f4f0; margin: 0; padding: 30px 15px; }
        .container { max-width: 750px; margin: 0 auto; background: #163e1a; border-radius: 12px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid #2d6632; }
        h1 { margin-top: 0; font-size: 24px; color: #a5d6a7; display: flex; align-items: center; gap: 10px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #2e7d32; color: #e8f5e9; }
        .badge-danger { background: #c62828; color: #ffebee; }
        .badge-warning { background: #f57f17; color: #fffde7; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .card { background: #1b4b20; border: 1px solid #2e7034; border-radius: 8px; padding: 15px; }
        .card h3 { margin: 0 0 10px 0; font-size: 15px; color: #c8e6c9; }
        .stat { font-size: 13px; color: #d0ded2; margin: 6px 0; word-break: break-all; }
        .stat strong { color: #ffffff; }
        .notice { padding: 14px; border-radius: 8px; margin: 20px 0; font-size: 14px; line-height: 1.5; }
        .notice-warning { background: rgba(245, 127, 23, 0.2); border: 1px solid #f57f17; color: #fff9c4; }
        .notice-danger { background: rgba(198, 40, 40, 0.25); border: 1px solid #c62828; color: #ffcdd2; }
        .notice-success { background: rgba(46, 125, 50, 0.3); border: 1px solid #43a047; color: #c8e6c9; }
        form { margin-top: 25px; padding-top: 20px; border-top: 1px solid #2d6632; }
        input[type="email"] { width: 65%; padding: 10px 14px; border-radius: 6px; border: 1px solid #43a047; background: #0f2b12; color: #ffffff; font-size: 14px; box-sizing: border-box; }
        button { padding: 10px 20px; border-radius: 6px; background: #43a047; border: none; color: #ffffff; font-weight: bold; cursor: pointer; font-size: 14px; }
        button:hover { background: #2e7d32; }
        code { background: #0b1f0e; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #81c784; }
    </style>
</head>
<body>
<div class="container">
    <h1><span>&#9993;</span> Email System Diagnostic</h1>
    <p style="color:#b2dfdb; font-size:14px; margin-top:-5px;">Gateway Education Services &mdash; Cloud Delivery Verification</p>

    <?php if ($sendResult): ?>
        <?php if ($sendResult['success']): ?>
            <div class="notice notice-success">
                <strong>&checkmark; Test email sent successfully!</strong><br>
                Method used: <code><?= htmlspecialchars($sendResult['method']) ?></code><br>
                Please check the inbox and spam folder for <strong><?= htmlspecialchars($testRecipient) ?></strong>.
            </div>
        <?php else: ?>
            <div class="notice notice-danger">
                <strong>&cross; Test email delivery failed!</strong><br>
                Error: <?= htmlspecialchars($sendResult['error'] ?? 'Unknown error') ?><br>
                Method attempted: <code><?= htmlspecialchars($sendResult['method']) ?></code>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Network & Port Reachability -->
    <div class="grid">
        <div class="card">
            <h3>Outbound Port 587 (Standard SMTP)</h3>
            <?php if ($smtpSocketTest): ?>
                <span class="badge badge-success">&checkmark; Open / Reachable</span>
                <p class="stat">Connected to <?= htmlspecialchars($config['smtp_host']) ?>:587 in <?= $smtpLatency ?>ms</p>
            <?php else: ?>
                <span class="badge badge-danger">&cross; BLOCKED by Host Firewall</span>
                <p class="stat" style="color:#ffcdd2;">Render Free tier blocks port 587: <?= htmlspecialchars($smtpSocketError) ?></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Outbound Port 443 (HTTPS Webhook / API)</h3>
            <?php if ($httpsSocketTest): ?>
                <span class="badge badge-success">&checkmark; Open / Reachable</span>
                <p class="stat">HTTPS connectivity confirmed in <?= $httpsLatency ?>ms</p>
            <?php else: ?>
                <span class="badge badge-danger">&cross; Error</span>
                <p class="stat"><?= htmlspecialchars($httpsError) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Configurations -->
    <div class="card">
        <h3>Configured Email Gateways</h3>
        <p class="stat"><strong>Google Webhook URL:</strong> <?= !empty($config['mail_webhook_url']) ? '<span class="badge badge-success">Configured</span>' : '<span class="badge badge-warning">Not Set</span>' ?></p>
        <p class="stat"><strong>Resend API Key:</strong> <?= !empty($config['resend_api_key']) ? '<span class="badge badge-success">Configured</span>' : '<span class="badge badge-warning">Not Set</span>' ?></p>
        <p class="stat"><strong>Brevo API Key:</strong> <?= !empty($config['brevo_api_key']) ? '<span class="badge badge-success">Configured</span>' : '<span class="badge badge-warning">Not Set</span>' ?></p>
        <p class="stat"><strong>Gmail SMTP User:</strong> <?= htmlspecialchars($config['smtp_user']) ?></p>
        <p class="stat"><strong>Gmail SMTP Pass:</strong> <?= !empty($config['smtp_pass']) ? '<span class="badge badge-success">Set (' . strlen($config['smtp_pass']) . ' chars)</span>' : '<span class="badge badge-danger">Not Set</span>' ?></p>
    </div>

    <?php if (!$smtpSocketTest && empty($config['mail_webhook_url']) && empty($config['resend_api_key']) && empty($config['brevo_api_key'])): ?>
        <div class="notice notice-warning">
            <strong>Why emails are not reaching the inbox on Render:</strong><br>
            Render's Free Web Service blocks outbound connections on SMTP port 587. To send emails on Render Free tier, set <code>MAIL_WEBHOOK_URL</code> (Google Apps Script Webhook), <code>RESEND_API_KEY</code>, or <code>BREVO_API_KEY</code> in Render's <strong>Environment Variables</strong> tab. Because these send over port 443 (HTTPS), Render allows them 100%.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="send_test">
        <label style="display:block; margin-bottom:8px; font-weight:bold; color:#a5d6a7;">Send a Live Test Email:</label>
        <input type="email" name="to" value="<?= htmlspecialchars($testRecipient) ?>" required placeholder="Enter recipient email">
        <button type="submit">Dispatch Test Email</button>
    </form>
</div>
</body>
</html>
