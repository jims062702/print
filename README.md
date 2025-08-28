Printing Services System

Overview

This is a two-sided Printing Services System built with HTML, CSS, JavaScript (client) and PHP (server/admin). It supports client uploads with pricing logic, recent uploads via LocalStorage, and admin-side login with notifications and request processing. Email notifications use Gmail SMTP via PHPMailer.

Quick Start

1) Requirements
- PHP 8.0+
- Composer (for PHPMailer)
- Internet access to install dependencies (or manually vendor PHPMailer)

2) Install dependencies
- From the project root:
```bash
composer install || echo "Composer unavailable. You can vendor PHPMailer manually later."
```

3) Configure environment
- Copy `.env.example` to `.env` and fill your SMTP credentials. For Gmail, create an App Password.
```bash
cp .env.example .env
```

4) Run the server
- From the project root, serve the repository root so both client and admin are available:
```bash
php -S localhost:8000 -t .
```

5) Use the app
- Client: Open `http://localhost:8000/public/intro.html` (auto-redirects to landing page).
- Admin: Open `http://localhost:8000/admin/intro.html` (auto-redirects to login page).
- Default admin credentials: username `admin`, password `admin123` (change via `.env`).

Project Structure

public/
- intro.html           Intro animation, redirects to landing
- index.html           Landing page with 3D effect
- printing-list.html   Pricing list
- contact.html         Contact page
- print-now.html       Client upload UI
- assets/css/style.css Shared styles
- assets/js/*.js       Client scripts (intro, pricing, upload, app)

admin/
- intro.html           Admin intro animation, redirects to login
- index.php            Login page
- dashboard.php        Admin dashboard with notifications
- download.php         Secure file download for admins
- logout.php           Ends session
- assets/css/admin.css Admin styles
- assets/js/admin.js   Admin scripts

api/
- upload.php           File upload + order creation + email notify
- notifications.php    Unseen count + mark as seen
- update_order.php     Update order status
- contact.php          Contact form email (optional)

config/
- config.php           Loads environment, paths, admin credentials

storage/
- uploads/             Stored files (created automatically)
- orders.json          Persistent order store (auto-created)

Environment Variables (.env)

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your_gmail@example.com
SMTP_PASS=your_app_password
ADMIN_EMAIL=notify_to@example.com
FROM_EMAIL=no-reply@example.com
FROM_NAME=Printing Services
ADMIN_USER=admin
ADMIN_PASS=admin123

Notes
- For Gmail, enable 2FA and create an App Password.
- If Composer is unavailable, you may vendor PHPMailer in `vendor/` or `lib/phpmailer/` and it will be auto-detected.
- Recent uploads are stored in the browser's LocalStorage under key `ps_recent_uploads`.

