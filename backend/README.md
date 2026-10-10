# ITINFO ACADEMY - Backend Admin Panel

## Setup Instructions

### 1. Database Setup
```bash
# Import the database schema
mysql -u root -p < database/schema.sql
```

Or manually:
- Open phpMyAdmin
- Create database `itinfo_academy`
- Import `database/schema.sql`

### 2. Configuration
Edit `config/database.php` with your MySQL credentials:
```php
private $host = 'localhost';
private $db_name = 'itinfo_academy';
private $username = 'root';
private $password = '';
```

### 3. File Permissions
Create uploads directory:
```bash
mkdir -p uploads/testimonials uploads/courses uploads/gallery
chmod 755 uploads -R
```

### 4. Access Admin Panel
```
URL: http://localhost/backend/admin/login.php
Username: admin
Password: admin123
```

## Features

### ✅ Implemented
- **Authentication System** - Secure login with session management
- **Dashboard** - Statistics and recent inquiries overview
- **Testimonials Management** - Full CRUD operations
  - Add/Edit/Delete testimonials
  - Toggle active status
  - Set featured testimonials
  - Rating system (1-5 stars)
  - Display order management

### 🚧 To Be Implemented
- Courses Management
- Gallery Management
- About Us Sections Editor
- Contact Inquiries Management
- Site Settings
- File Upload for Images
- Rich Text Editor for Content

## Security Features
- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (input sanitization)
- CSRF token support (ready to implement)
- Session timeout (1 hour)
- Role-based access control

## Database Tables
- `admin_users` - Admin authentication
- `testimonials` - Student testimonials
- `courses` - Course information
- `gallery` - Image gallery
- `about_sections` - About page content
- `contact_inquiries` - Contact form submissions
- `site_settings` - Site-wide settings

## File Structure
```
backend/
├── admin/
│   ├── assets/
│   │   ├── css/admin.css
│   │   └── js/admin.js
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   ├── index.php (Dashboard)
│   ├── login.php
│   ├── logout.php
│   ├── testimonials.php
│   └── testimonial-form.php
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── auth.php
│   └── functions.php
├── database/
│   └── schema.sql
└── uploads/ (create this)
```

## Next Steps
1. Test the admin panel
2. Add remaining CRUD pages (courses, gallery, etc.)
3. Implement file upload functionality
4. Add rich text editor for content
5. Create API endpoints for frontend
6. Integrate with existing frontend
