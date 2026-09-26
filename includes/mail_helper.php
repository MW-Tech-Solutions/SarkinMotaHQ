<?php
/**
 * Global Mail Dispatcher & Dynamic Branding Communication Engine
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/settings_helper.php';
require_once __DIR__ . '/../config/app.php';

/**
 * Format corporate HTML email template styled dynamically with system theme colors
 */
function format_corporate_email_html($subject, $body_content, $recipient_name = '') {
    // Dynamic System Theme & Branding Tokens
    $primary_color = setting('primary_color', '#f59e0b');
    $primary_hover = setting('primary_hover_color', '#d97706');
    $company_name = setting('company_name', 'Sarkin Mota HQ');
    $company_short = setting('company_short_name', 'Sarkin Mota HQ');
    $company_tagline = setting('company_tagline', 'Enterprise Real Estate & Strategic Consulting');
    $company_logo = setting('company_logo', 'assets/images/logo.png');
    $company_email = setting('company_email', 'contact@sarkinmotahq.com');
    $company_phone = setting('company_phone', '+234 800 536 3284');
    $company_address = setting('company_address', 'Abuja & Yola Offices, Nigeria');
    $website_url = rtrim(setting('website_url', 'http://localhost:8080/SarkinMota'), '/');

    // Resolve full absolute logo URL
    $logo_url = (strpos($company_logo, 'http') === 0) 
        ? $company_logo 
        : $website_url . '/' . ltrim($company_logo, '/');

    $greeting = !empty($recipient_name) ? "Dear " . htmlspecialchars($recipient_name) . "," : "Greetings,";

    // Format Paragraphs
    $formatted_body = nl2br(trim($body_content));

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>" . htmlspecialchars($subject) . "</title>
        <style>
            body { font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0b0f19; color: #334155; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
            .email-wrapper { background-color: #0b0f19; padding: 40px 15px; }
            .email-container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 24px; overflow: hidden; border: 1px solid #1e293b; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.5); }
            
            /* Dynamic Theme Header Bar */
            .header-bar { background-color: #020617; padding: 36px 40px; text-align: center; border-bottom: 4px solid {$primary_color}; position: relative; }
            .header-logo { max-height: 48px; width: auto; margin-bottom: 12px; }
            .header-title { color: #ffffff; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin: 6px 0 0 0; font-family: 'Montserrat', sans-serif; }
            .header-subtitle { color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; margin-top: 4px; }
            .badge-pill { display: inline-block; background-color: rgba(245, 158, 11, 0.12); color: {$primary_color}; font-size: 10px; font-weight: 800; padding: 5px 14px; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; border: 1px solid rgba(245, 158, 11, 0.25); }
            
            /* Body Styling */
            .content-body { padding: 44px 40px; font-size: 14px; line-height: 1.8; color: #334155; }
            .greeting-text { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 24px; font-family: 'Montserrat', sans-serif; }
            
            .message-card { background-color: #f8fafc; border-left: 4px solid {$primary_color}; padding: 22px 24px; border-radius: 12px; margin: 24px 0; font-size: 14px; color: #1e293b; border-top: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
            
            .cta-button { display: inline-block; background-color: {$primary_color}; color: #090d16; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 14px 30px; border-radius: 12px; text-decoration: none; margin-top: 24px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); }
            
            /* Dynamic Theme Footer */
            .footer-bar { background-color: #0f172a; padding: 32px 40px; text-align: center; color: #94a3b8; font-size: 11px; line-height: 1.6; border-top: 1px solid #1e293b; }
            .footer-bar a { color: {$primary_color}; text-decoration: none; font-weight: 700; }
            .footer-brand { color: #ffffff; font-weight: 800; font-size: 13px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
            .divider { height: 1px; background-color: #1e293b; margin: 18px 0; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='email-container'>
                <!-- Header -->
                <div class='header-bar'>
                    <span class='badge-pill'>Official Communication</span>
                    <div class='header-title'>" . htmlspecialchars($company_short) . "</div>
                    <div class='header-subtitle'>" . htmlspecialchars($company_tagline) . "</div>
                </div>
                
                <!-- Main Body -->
                <div class='content-body'>
                    <div class='greeting-text'>" . $greeting . "</div>
                    
                    <div class='message-card'>
                        " . $formatted_body . "
                    </div>

                    <div style='text-align: center;'>
                        <a href='" . htmlspecialchars($website_url) . "' class='cta-button'>Access Sarkin Mota HQ Portal</a>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class='footer-bar'>
                    <div class='footer-brand'>" . htmlspecialchars($company_name) . "</div>
                    <p style='margin: 0 0 6px 0;'>" . htmlspecialchars($company_address) . "</p>
                    <p style='margin: 0 0 10px 0;'>Phone: " . htmlspecialchars($company_phone) . " &bull; Email: <a href='mailto:" . htmlspecialchars($company_email) . "'>" . htmlspecialchars($company_email) . "</a></p>
                    <div class='divider'></div>
                    <p style='margin: 0;'>Visit our portal: <a href='" . htmlspecialchars($website_url) . "'>" . htmlspecialchars($website_url) . "</a></p>
                    <p style='margin: 12px 0 0 0; font-size: 10px; color: #64748b;'>Confidential Notice: This electronic mail transmission contains privileged information intended solely for the recipient named above.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Socket SMTP Transport helper
 */
function send_smtp_socket_mail($to, $subject, $html_body, $from_email, $from_name) {
    $smtp_host = env('MAIL_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
    $smtp_port = (int)env('MAIL_PORT', env('SMTP_PORT', 587));
    $smtp_user = env('MAIL_USER', env('MAIL_USERNAME', env('SMTP_USERNAME', '')));
    $smtp_pass = env('MAIL_PASS', env('MAIL_PASSWORD', env('SMTP_PASSWORD', '')));
    $smtp_enc = strtolower(env('MAIL_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls')));

    if (empty($smtp_host) || empty($smtp_user) || empty($smtp_pass)) {
        return false;
    }

    $socket_prefix = ($smtp_enc === 'ssl' || $smtp_port === 465) ? 'ssl://' : '';
    $timeout = 15;

    $socket = @fsockopen($socket_prefix . $smtp_host, $smtp_port, $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("SMTP Socket Connection Failed: $errstr ($errno)");
        return false;
    }

    $read = function($sock) {
        $reply = '';
        while ($line = fgets($sock, 512)) {
            $reply .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $reply;
    };

    $send = function($sock, $cmd) use ($read) {
        fputs($sock, $cmd . "\r\n");
        return $read($sock);
    };

    $greeting = $read($socket);
    if (empty($greeting) || substr($greeting, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }

    // EHLO initial
    $send($socket, "EHLO " . gethostname());

    // STARTTLS if TLS on port 587 or encryption configured as tls
    if ($smtp_enc === 'tls' || $smtp_port === 587) {
        $reply = $send($socket, "STARTTLS");
        if (strpos($reply, '220') !== 0) {
            fclose($socket);
            return false;
        }
        
        // Disable SSL peer verification for XAMPP local environment compatibility
        stream_context_set_option($socket, 'ssl', 'verify_peer', false);
        stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
        stream_context_set_option($socket, 'ssl', 'allow_self_signed', true);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            error_log("SMTP TLS Crypto Negotiation Failed.");
            fclose($socket);
            return false;
        }
        $send($socket, "EHLO " . gethostname());
    }

    // AUTH LOGIN
    $reply = $send($socket, "AUTH LOGIN");
    if (strpos($reply, '334') === 0) {
        $send($socket, base64_encode($smtp_user));
        $reply = $send($socket, base64_encode($smtp_pass));
        if (strpos($reply, '235') !== 0) {
            error_log("SMTP Authentication Failed: " . trim($reply));
            fclose($socket);
            return false;
        }
    } else if (strpos($reply, '235') !== 0) {
        error_log("SMTP AUTH LOGIN Challenge Rejected: " . trim($reply));
        fclose($socket);
        return false;
    }

    // MAIL FROM / RCPT TO / DATA
    $reply_from = $send($socket, "MAIL FROM: <$from_email>");
    if (strpos($reply_from, '250') !== 0) {
        error_log("SMTP MAIL FROM Rejected: " . trim($reply_from));
        fclose($socket);
        return false;
    }

    $reply_rcpt = $send($socket, "RCPT TO: <$to>");
    if (strpos($reply_rcpt, '250') !== 0 && strpos($reply_rcpt, '251') !== 0) {
        error_log("SMTP RCPT TO Rejected: " . trim($reply_rcpt));
        fclose($socket);
        return false;
    }

    $reply_data = $send($socket, "DATA");
    if (strpos($reply_data, '354') !== 0) {
        error_log("SMTP DATA Command Rejected: " . trim($reply_data));
        fclose($socket);
        return false;
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    $data_payload = $headers . "\r\n" . $html_body . "\r\n.";
    $reply_send = $send($socket, $data_payload);
    if (strpos($reply_send, '250') !== 0) {
        error_log("SMTP Payload Submission Failed: " . trim($reply_send));
        fclose($socket);
        return false;
    }

    $send($socket, "QUIT");
    fclose($socket);

    return true;
}

/**
 * Core Mail Dispatch & Logging Function
 */
function send_system_email($recipient_email, $subject, $body_content, $mail_type = 'custom', $recipient_name = '', $sender_user_id = null, $vacancy_id = null) {
    global $pdo;

    $recipient_email = filter_var(trim($recipient_email), FILTER_VALIDATE_EMAIL);
    if (!$recipient_email) {
        return false;
    }

    $subject = trim($subject);
    if (empty($subject)) {
        $subject = "Notice from " . setting('company_short_name', 'Sarkin Mota HQ');
    }

    // Generate Dynamic Styled Corporate HTML Email
    $html_message = format_corporate_email_html($subject, $body_content, $recipient_name);

    $company_name = setting('company_name', 'Sarkin Mota HQ');
    $company_email = setting('company_email', 'contact@sarkinmotahq.com');

    // Environment Overrides for From Address & Name
    $env_from_email = env('MAIL_FROM_EMAIL', env('MAIL_USER', $company_email));
    $env_from_name = env('MAIL_FROM_NAME', $company_name);

    if (empty($env_from_name) || strpos($env_from_name, 'AFRO VILLAGE') !== false) {
        $env_from_name = $company_name;
    }

    $mail_mailer = strtolower(env('MAIL_MAILER', 'smtp'));
    $mail_status = 'failed';
    $sent_via_smtp = false;

    // 1. Attempt Socket SMTP if configured in .env
    if ($mail_mailer === 'smtp' || !empty(env('MAIL_HOST'))) {
        try {
            $sent_via_smtp = send_smtp_socket_mail($recipient_email, $subject, $html_message, $env_from_email, $env_from_name);
            if ($sent_via_smtp) {
                $mail_status = 'sent';
            }
        } catch (Exception $e) {
            error_log("SMTP Dispatch Exception: " . $e->getMessage());
        }
    }

    // 2. Fallback to native PHP mail() if SMTP socket not active or fails
    if (!$sent_via_smtp) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $env_from_name . ' <' . $env_from_email . '>',
            'Reply-To: ' . $env_from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        try {
            if (@mail($recipient_email, $subject, $html_message, implode("\r\n", $headers))) {
                $mail_status = 'sent';
            }
        } catch (Exception $e) {
            error_log("Native Mail Dispatch Warning: " . $e->getMessage());
        }
    }

    // 3. Always log dispatch in database mail_logs table
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mail_logs (sender_user_id, recipient_email, recipient_name, subject, body_content, mail_type, vacancy_id, status, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $sender_user_id,
            $recipient_email,
            $recipient_name,
            $subject,
            $body_content,
            $mail_type,
            $vacancy_id,
            $mail_status
        ]);
    } catch (Exception $e) {
        error_log("Mail Logging Error: " . $e->getMessage());
    }

    return ($mail_status === 'sent');
}

/**
 * Get notification recipient email for job applications
 */
function get_vacancy_notification_email($pdo, $vacancy_id = null) {
    if (!empty($vacancy_id)) {
        try {
            $stmt = $pdo->prepare("SELECT notification_email FROM careers WHERE id = ?");
            $stmt->execute([$vacancy_id]);
            $custom_email = $stmt->fetchColumn();
            if (!empty($custom_email) && filter_var($custom_email, FILTER_VALIDATE_EMAIL)) {
                return $custom_email;
            }
        } catch (Exception $e) {
            error_log("Fetch Vacancy Notification Email Error: " . $e->getMessage());
        }
    }
    
    // Fallback to system recruitment / corporate email
    return setting('recruitment_notification_email', setting('company_email', 'careers@sarkinmotahq.com'));
}

/**
 * Process new job application notifications (To Recipient Email & Confirmation to Candidate)
 */
function notify_job_application($pdo, $candidate_email, $candidate_name, $vacancy_title, $vacancy_id = null, $application_id = null) {
    // 1. Send application alert to designated recruitment notification email (role-specific or general)
    $recruitment_email = get_vacancy_notification_email($pdo, $vacancy_id);
    $alert_subject = "New Candidate Application: " . $vacancy_title . " - " . $candidate_name;
    $alert_body = "A new candidate has submitted an application for the position of '$vacancy_title'.\n\n" .
                 "Candidate Name: $candidate_name\n" .
                 "Candidate Email: $candidate_email\n" .
                 "Position: $vacancy_title\n" .
                 "Submission Date: " . date('Y-m-d H:i:s') . "\n\n" .
                 "Please log in to the HR Portal to review the candidate's resume and application profile.";
    
    send_system_email($recruitment_email, $alert_subject, $alert_body, 'recruitment_notification', 'Recruitment Team', null, $vacancy_id);

    // 2. Send acknowledgment confirmation email to candidate
    $ack_subject = "Application Received: " . $vacancy_title . " — " . setting('company_short_name', 'Sarkin Mota HQ');
    $ack_body = "Thank you for applying for the position of '$vacancy_title' at " . setting('company_name', 'Sarkin Mota HQ') . ".\n\n" .
               "We have successfully received your application credentials. Our HR & Recruitment team is currently reviewing candidate profiles for this role.\n\n" .
               "If your qualifications match our vacancy requirements, a representative from our Talent Acquisition team will reach out to schedule an initial interview.\n\n" .
               "We appreciate your interest in building your career with " . setting('company_name', 'Sarkin Mota HQ') . ".";
    
    send_system_email($candidate_email, $ack_subject, $ack_body, 'application_acknowledgment', $candidate_name, null, $vacancy_id);
}
