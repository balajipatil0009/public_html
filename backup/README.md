# ITINFO ACADEMY - Professional IT Training Platform

A complete PHP-based educational platform for IT courses and internship programs with admin panel, student dashboard, and secure payment integration.

## 🚀 Features

- **Course Management**: Comprehensive course catalog with detailed syllabi
- **Internship Programs**: Video-based learning with progress tracking
- **Student Dashboard**: Interactive portal for enrolled students
- **Admin Panel**: Full-featured administration interface
- **Payment Integration**: Razorpay integration for online payments
- **Certificate Generation**: Automated certificate creation for completions
- **Email Notifications**: Gmail SMTP integration for automated emails
- **AI Chatbot**: Groq API-powered assistance
- **Gallery Management**: Photo gallery with admin controls
- **Inquiry System**: Contact form with admin notifications

## 📋 Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer (optional, for future dependencies)
- Web server (Apache/Nginx) or PHP built-in server

## 🔧 Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd html-version
```

### 2. Configure Environment

Copy the environment template:

```bash
copy .env.example .env
```

Edit `.env` with your actual credentials:

```env
# Database
DB_HOST=localhost
DB_NAME=itinfo_academy
DB_USER=root
DB_PASS=your_secure_password

# Email (Gmail SMTP)
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_TO_ADMIN=admin@example.com

# API Keys
GROQ_API_KEY=your-groq-api-key
```

### 3. Database Setup

Import the database schema:

```bash
mysql -u root -p itinfo_academy < database/schema.sql
```

### 4. File Permissions

Ensure upload directories are writable:

```bash
chmod 755 uploads/
chmod 755 backend/uploads/
```

### 5. Start Development Server

```bash
php -S localhost:8080
```

Visit: `http://localhost:8080`

## 🔐 Security

This project implements several security measures:

- **Environment Variables**: All sensitive credentials stored in `.env` (not committed to git)
- **XSS Protection**: Helper functions for safe HTML output
- **SQL Injection Prevention**: Prepared statements for all database queries
- **CSRF Protection**: Token-based form protection
- **Secure Sessions**: HTTP-only, secure cookies
- **Security Headers**: X-Frame-Options, X-Content-Type-Options, etc.

**Important**: Never commit `.env` file to version control!

For detailed security guidelines, see [SECURITY_BEST_PRACTICES.md](brain/SECURITY_BEST_PRACTICES.md)

## 📁 Project Structure

```
html-version/
├── backend/
│   ├── admin/          # Admin panel pages
│   ├── api/            # API endpoints
│   ├── config/         # Configuration files
│   │   ├── config.php      # Main configuration
│   │   ├── database.php    # Database connection
│   │   └── EnvLoader.php   # Environment loader
│   ├── includes/       # Utility classes
│   │   ├── Mailer.php      # Email functionality
│   │   └── security.php    # Security helpers
│   └── uploads/        # Admin uploads
├── css/                # Stylesheets
├── js/                 # JavaScript files
├── uploads/            # User uploads
├── .env                # Environment config (DO NOT COMMIT)
├── .env.example        # Environment template
├── .gitignore          # Git ignore rules
└── index.php           # Homepage
```

## 🎨 Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Frontend**: Vanilla HTML, CSS, JavaScript
- **Styling**: Custom CSS with modern design
- **Email**: Native PHP SMTP (Gmail)
- **Payments**: Razorpay API
- **AI**: Groq API

## 👨‍💼 Admin Panel

Access the admin panel at: `http://localhost:8080/backend/admin`

**Default Credentials**: Configure in the database after setup

### Admin Features:

- Dashboard with statistics
- Course management
- Internship video management
- Student enrollment tracking
- Inquiry management
- Gallery management
- Settings configuration

## 🔒 Production Deployment

Before deploying to production:

1. **Set Environment to Production**:
   ```env
   APP_ENV=production
   DEBUG_MODE=false
   ```

2. **Secure Database**:
   - Use strong password
   - Create dedicated database user
   - Restrict remote access

3. **Enable HTTPS**:
   - Install SSL certificate
   - Force HTTPS redirects

4. **File Permissions**:
   ```bash
   chmod 600 .env
   chmod 755 uploads/
   ```

5. **Error Logging**:
   - Disable `display_errors`
   - Enable error logging to file

## 🛠️ Development

### Adding Security to New Pages

```php
<?php
require_once 'backend/config/config.php';
require_once 'backend/includes/security.php';

// Escape user output
echo e($user_input);

// Add CSRF protection to forms
echo csrf_field();
?>
```

### Database Queries

Always use prepared statements:

```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
```

## 📧 Email Configuration

The system uses Gmail SMTP. You need to:

1. Enable 2-factor authentication on your Google account
2. Generate an App Password
3. Add credentials to `.env` file

## 🤝 Contributing

1. Create feature branch
2. Make changes
3. Test thoroughly
4. Submit pull request

## 📄 License

Proprietary - All rights reserved

## 📞 Support

For support, email: admin@itinfoacademy.com

---

**Last Updated**: 2026-01-23  
**Version**: 2.0 (Security Hardened)