# Plan: Configure SMTP via Environment Variables in email.php

## Objective
Modify `application/config/email.php` to enable SMTP email delivery using environment variables, allowing flexible configuration across different environments (e.g., local development with Mailpit, production with external SMTP providers).

## Changes to `application/config/email.php`
1. **Protocol**: Change `$config['protocol']` from `'mail'` to `'smtp'`.
2. **Environment Variables**: Use PHP's native `getenv()` function to read SMTP settings, providing fallback defaults where appropriate to maintain compatibility (e.g., local Docker Mailpit setup).
   - `$config['smtp_host'] = getenv('SMTP_HOST') ?: 'localhost';`
   - `$config['smtp_user'] = getenv('SMTP_USER') ?: '';`
   - `$config['smtp_pass'] = getenv('SMTP_PASS') ?: '';`
   - `$config['smtp_port'] = getenv('SMTP_PORT') ?: 1025;` (1025 aligns with the existing Mailpit default in `.env`)
   - `$config['smtp_crypto'] = getenv('SMTP_CRYPTO') ?: 'tls';`
   - `$config['smtp_auth'] = filter_var(getenv('SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN);`
   - `$config['from_name'] = getenv('FROM_NAME') ?: 'Easy!Appointments';`
   - `$config['from_address'] = getenv('FROM_ADDRESS') ?: 'no-reply@example.com';`
   - `$config['reply_to'] = getenv('REPLY_TO') ?: $config['from_address'];`
3. **Preserve Existing Settings**: Keep `$config['useragent']`, `$config['mailtype']`, `$config['crlf']`, and `$config['newline']` as they are, since `\r\n` is required for SMTP compliance.

## Environment Variables to Document (for `.env`)
The user should be advised to add the following to their `.env` file for production use:
```env
SMTP_HOST=smtp.example.com
SMTP_USER=your_smtp_user
SMTP_PASS=your_smtp_password
SMTP_PORT=587
SMTP_CRYPTO=tls
SMTP_AUTH=true
FROM_NAME=Your App Name
FROM_ADDRESS=noreply@yourdomain.com
REPLY_TO=support@yourdomain.com
```

## Validation
- Verify that the PHP syntax in `application/config/email.php` is correct after modification.
- Ensure that local development (using Mailpit on port 1025) continues to work if no production SMTP env vars are provided, due to the fallback defaults.
