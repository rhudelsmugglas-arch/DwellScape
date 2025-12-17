<?php
/**
 * Email Sender Class
 * Handles sending emails via SMTP
 */

require_once __DIR__ . '/../config/email.php';

// Load PHPMailer if available via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class EmailSender {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_from;
    private $smtp_from_name;
    private $smtp_encryption;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/email.php';
        $this->smtp_host = $config['smtp_host'];
        $this->smtp_port = $config['smtp_port'];
        $this->smtp_user = $config['smtp_user'];
        $this->smtp_pass = $config['smtp_pass'];
        $this->smtp_from = $config['smtp_from'];
        $this->smtp_from_name = $config['smtp_from_name'];
        $this->smtp_encryption = $config['smtp_encryption'];
    }
    
    /**
     * Send email using SMTP with socket connection (works without PHPMailer)
     */
    public function sendEmailSMTP($to, $subject, $message, $is_html = true) {
        // Try to use PHPMailer if available (recommended)
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $message, $is_html);
        } else {
            // Fallback to socket-based SMTP
            return $this->sendWithSocket($to, $subject, $message, $is_html);
        }
    }
    
    /**
     * Send email using PHP mail() function (fallback)
     * Note: This requires proper server SMTP configuration
     * For Gmail SMTP, PHPMailer is strongly recommended
     */
    private function sendWithSocket($to, $subject, $message, $is_html = true) {
        // Configure PHP mail settings
        ini_set("SMTP", $this->smtp_host);
        ini_set("smtp_port", $this->smtp_port);
        ini_set("sendmail_from", $this->smtp_from);
        
        // Build email headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . ($is_html ? "text/html" : "text/plain") . "; charset=UTF-8";
        $headers[] = "From: " . $this->smtp_from_name . " <" . $this->smtp_from . ">";
        $headers[] = "Reply-To: " . $this->smtp_from;
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        $headers_string = implode("\r\n", $headers);
        
        // Send email
        $result = @mail($to, $subject, $message, $headers_string);
        
        if (!$result) {
            error_log("Email sending failed using mail() function. Consider installing PHPMailer for better SMTP support.");
        }
        
        return $result;
    }
    
    /**
     * Send email using PHPMailer library
     */
    private function sendWithPHPMailer($to, $subject, $message, $is_html = true) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = $this->smtp_encryption;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Recipients
            $mail->setFrom($this->smtp_from, $this->smtp_from_name);
            $mail->addAddress($to);
            $mail->addReplyTo($this->smtp_from, $this->smtp_from_name);
            
            // Content
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($user_email, $user_name, $booking_data) {
        $subject = "Booking Confirmation - " . $booking_data['booking_id'];
        
        $message = $this->getBookingEmailTemplate($user_name, $booking_data);
        
        return $this->sendEmailSMTP($user_email, $subject, $message, true);
    }
    
    /**
     * Get booking confirmation email template
     */
    private function getBookingEmailTemplate($user_name, $booking_data) {
        $checkin = date('F d, Y', strtotime($booking_data['checkin_date']));
        $checkout = date('F d, Y', strtotime($booking_data['checkout_date']));
        $total_price = number_format($booking_data['total_price'], 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .detail-row:last-child { border-bottom: none; }
                .detail-label { font-weight: bold; color: #7a6a4f; }
                .detail-value { color: #333; }
                .total { font-size: 18px; font-weight: bold; color: #7a6a4f; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Dwellscape Staycation</h1>
                    <p>Booking Confirmation</p>
                </div>
                <div class='content'>
                    <h2>Hello {$user_name},</h2>
                    <p>Thank you for your booking! Your reservation has been confirmed.</p>
                    
                    <div class='booking-details'>
                        <h3 style='color: #7a6a4f; margin-top: 0;'>Booking Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span>
                            <span class='detail-value'>{$booking_data['booking_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Room:</span>
                            <span class='detail-value'>{$booking_data['room_name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Check-in:</span>
                            <span class='detail-value'>{$checkin}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Check-out:</span>
                            <span class='detail-value'>{$checkout}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Nights:</span>
                            <span class='detail-value'>{$booking_data['nights']} night(s)</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Price per Night:</span>
                            <span class='detail-value'>₱" . number_format($booking_data['price_per_night'], 2) . "</span>
                        </div>
                        <div class='detail-row total'>
                            <span>Total Amount:</span>
                            <span>₱{$total_price}</span>
                        </div>
                    </div>
                    
                    <p>We look forward to hosting you at Dwellscape Staycation!</p>
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                        <p>&copy; " . date('Y') . " Dwellscape Staycation. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}

