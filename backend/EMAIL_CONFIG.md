# Email Configuration Guide

To enable email notifications for orders, you need to configure your email service in the `.env` file.

## Option 1: Gmail SMTP (Recommended for development)

Add these lines to your `.env` file:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cloudimart.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Important:** For Gmail, you need to:
1. Enable 2-factor authentication on your Gmail account
2. Generate an "App Password" from Google Account settings
3. Use the App Password (not your regular password)

## Option 2: Mailtrap (For testing)

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@cloudimart.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Option 3: SendGrid

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cloudimart.com
MAIL_FROM_NAME="${APP_NAME}"
```

## After Configuration

1. Clear your config cache: `php artisan config:clear`
2. Test by placing a new order
3. Check logs: `tail -f storage/logs/laravel.log`

## SMS Configuration

SMS is already configured with Vonage. The current credentials are:
- API Key: 6deb336f
- API Secret: YlIUXfqvZ92rdRZR

## Testing

You can test SMS notifications by visiting:
`http://localhost:8000/api/send-sms`

Email notifications will be automatically sent when a new order is placed.
