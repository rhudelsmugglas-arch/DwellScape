# Email Notification Setup

## Installation

### Option 1: Using PHPMailer (Recommended)

For better SMTP support and reliability, install PHPMailer using Composer:

```bash
composer require phpmailer/phpmailer
```

### Option 2: Without PHPMailer

The system will use a socket-based SMTP connection as a fallback if PHPMailer is not installed.

## Configuration

Email settings are configured in `config/email.php`:

- **SMTP_HOST**: smtp.gmail.com
- **SMTP_PORT**: 587
- **SMTP_USER**: budzb58@gmail.com
- **SMTP_PASS**: bjpcniznrcyitwcd
- **SMTP_FROM**: noreply@budzreserve.com

## How It Works

1. When a payment is successfully processed in `payment-success.php`
2. The system automatically sends a booking confirmation email to the user
3. Email includes:
   - Booking ID
   - Room details
   - Check-in/Check-out dates
   - Total amount
   - Professional HTML template

## Testing

To test email functionality:

1. Complete a test booking
2. Check the user's email inbox
3. Verify the email is received with all booking details

## Troubleshooting

If emails are not being sent:

1. Check PHP error logs for SMTP connection errors
2. Verify SMTP credentials are correct
3. Ensure Gmail "Less secure app access" is enabled (if using Gmail)
4. Consider using PHPMailer for better error handling

