# ITINFO Academy - Copilot Instructions

## Project Overview
A hybrid PHP/HTML/CSS/JS educational platform for IT training. Dual architecture:
- **Frontend**: Public pages (index.php, courses.php, etc.) serving templates from `backend/api/`
- **Backend**: Admin panel (`backend/admin/`) + REST APIs (`backend/api/`) + SQLite/MySQL database
- **Internship Module**: Separate student portal (intern-login.php, intern-dashboard.php)

## Architecture Patterns

### Frontend (PHP-based MVC-lite)
- Root `.php` files (index.php, courses.php, about.php) fetch data from database via `Database` class
- Use `require_once 'backend/config/config.php'` and `require_once 'backend/config/database.php'`
- Fallback data arrays in PHP files for when database is unavailable
- JavaScript components in `js/components.js` provide navbar, footer, modals (no DOM dependencies)

### Backend Architecture
```
backend/
├── config/database.php     # PDO connection class (MySQLi)
├── config/config.php       # Global constants (SITE_NAME, UPLOAD_DIR, SESSION_TIMEOUT)
├── includes/auth.php       # Session-based auth class with role checking
├── includes/functions.php  # Utilities: sanitize(), uploadImage(), generateSlug(), etc.
├── admin/                  # Admin dashboard (protected by Auth::requireLogin())
├── api/                    # JSON endpoints (GET only, CORS enabled)
└── database/schema.sql     # Table definitions
```

### Database Pattern
- All PHP files instantiate: `$db = (new Database())->connect()` returning PDO instance
- Prepared statements everywhere: `$db->prepare()` then `bindParam()` 
- Error handling: wrap DB calls in try-catch; log errors with `error_log()`
- Key tables: `admin_users`, `testimonials`, `courses`, `gallery`, `about_sections`, `contact_inquiries`, `site_settings`

## Critical Conventions

### Session Management
- Admin sessions: `$_SESSION['admin_id']`, `$_SESSION['admin_role']` set in `Auth::login()`
- Intern sessions: `$_SESSION['intern_id']`, `$_SESSION['intern_name']` set in intern-login.php
- Session timeout: 3600 seconds (1 hour), checked in `Auth::isLoggedIn()`
- Always call `Auth::requireLogin()` at top of protected admin pages

### Security
- Password hashing: `password_hash()` with `PASSWORD_BCRYPT` (configured in config.php)
- Input sanitization: use `sanitize()` function from functions.php for user input
- CSRF tokens: generated via `generateCSRFToken()`, verified with `verifyCSRFToken()`
- File uploads: validate MIME types against `ALLOWED_IMAGE_TYPES`, max 5MB (`MAX_FILE_SIZE`)

### API Response Format
All endpoints return JSON:
```php
json_encode(['success' => true, 'data' => $result])
json_encode(['success' => false, 'message' => 'error text'])
```
Headers: `Content-Type: application/json`, `Access-Control-Allow-Origin: *`

### Slug Generation
- Use `generateSlug()` function for course/content slugs
- Slugs are URL-friendly lowercase with hyphens (e.g., "java-full-stack")

## Frontend Integration

### Component Loading (js/components.js)
- `createNavbar()`, `createFooter()`, `createEnquiryModal()` insert into DOM on DOMContentLoaded
- Navbar links set active class based on current page pathname
- Mobile menu toggle: `#navbarToggle` toggles `#navbarMenu` visibility

### Course Rendering (js/courses.js)
- Fetch course data from `backend/api/courses.php`
- Handle categories: `job_role`, `programming`, `web_dev`, `database`, `design`
- Fallback to hardcoded course arrays if API fails

## Key Workflows

### Adding a New Admin Page
1. Create `backend/admin/feature-form.php` (or similar)
2. Start with: `require_once '../includes/auth.php'; $auth = new Auth(); $auth->requireLogin();`
3. Include header: `include 'includes/header.php'`
4. Use `setSuccessMessage()` / `setErrorMessage()` for feedback
5. Include footer: `include 'includes/footer.php'`

### Adding API Endpoint
1. Create `backend/api/endpoint-name.php`
2. Add CORS headers: `header('Access-Control-Allow-Origin: *')`
3. Check method: `if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); ... }`
4. Use PDO prepared statements for all queries
5. Return JSON: `json_encode(['success' => true, 'data' => ...])`

### Database Changes
- Update `backend/database/schema.sql` for schema changes
- Add migration helper if data transform needed (see `backend/migrate-existing-content.php` pattern)
- Test with: `mysql -u root -p itinfo_academy < backend/database/schema.sql`

## Common Gotchas

1. **Database Connection Failures**: Always wrap in try-catch; check PDO error logs with `error_log()`
2. **Path Issues**: Relative paths from root PHP files differ from backend/; use absolute patterns: `__DIR__ . '/../path'`
3. **CORS for API**: Endpoints accessed from JS must have `Access-Control-Allow-Origin: *`
4. **File Uploads**: Ensure `backend/uploads/` directory is writable (chmod 755); paths configured in config.php
5. **Session Scope**: Intern and admin sessions use different keys; verify correct session namespace

## File References for Patterns
- Database pattern: [backend/config/database.php](backend/config/database.php)
- Auth pattern: [backend/includes/auth.php](backend/includes/auth.php)
- API pattern: [backend/api/courses.php](backend/api/courses.php)
- Admin CRUD pattern: [backend/admin/courses.php](backend/admin/courses.php)
- Frontend integration: [index.php](index.php), [js/components.js](js/components.js)
