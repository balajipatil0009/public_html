# API Endpoints

## Contact Form Submission

**Endpoint:** `POST /backend/api/contact-submit.php`

**Request Body (JSON):**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+91 1234567890",
  "course_interest": "Java Full Stack",
  "message": "I am interested in learning more about your courses."
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Thank you for contacting us! We will get back to you soon."
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Name is required, Email is required"
}
```

## Usage Example (JavaScript)

```javascript
async function submitContactForm(formData) {
  try {
    const response = await fetch('/backend/api/contact-submit.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  }
}

// Example usage
submitContactForm({
  name: 'John Doe',
  email: 'john@example.com',
  phone: '+91 1234567890',
  course_interest: 'Java Full Stack',
  message: 'I am interested in your courses.'
});
```


---

## All Available API Endpoints

### 1. Contact Form Submission
**POST** `/backend/api/contact-submit.php`
- Submit contact form inquiries
- Required: name, email, message
- Optional: phone, course_interest

### 2. Site Settings
**GET** `/backend/api/settings.php`
- Get all site settings: `/backend/api/settings.php`
- Get specific setting: `/backend/api/settings.php?key=site_name`

### 3. Testimonials
**GET** `/backend/api/testimonials.php`
- Get all testimonials: `/backend/api/testimonials.php`
- Get featured only: `/backend/api/testimonials.php?featured=true`
- Limit results: `/backend/api/testimonials.php?limit=5`

### 4. Courses
**GET** `/backend/api/courses.php`
- Get all courses: `/backend/api/courses.php`
- Get by category: `/backend/api/courses.php?category=programming`
- Get featured: `/backend/api/courses.php?featured=true`
- Get by slug: `/backend/api/courses.php?slug=java-full-stack`
- Get by ID: `/backend/api/courses.php?id=1`
- Limit results: `/backend/api/courses.php?limit=10`

### 5. Gallery
**GET** `/backend/api/gallery.php`
- Get all images: `/backend/api/gallery.php`
- Get by category: `/backend/api/gallery.php?category=events`
- Limit results: `/backend/api/gallery.php?limit=12`

### 6. About Sections
**GET** `/backend/api/about-sections.php`
- Get all sections: `/backend/api/about-sections.php`
- Get specific section: `/backend/api/about-sections.php?key=company_overview`

---

## Complete Frontend Integration Example

```javascript
// Fetch site settings
async function loadSiteSettings() {
  const response = await fetch('/backend/api/settings.php');
  const result = await response.json();
  if (result.success) {
    const settings = result.data;
    document.title = settings.site_name;
    // Use settings throughout your site
  }
}

// Fetch featured courses
async function loadFeaturedCourses() {
  const response = await fetch('/backend/api/courses.php?featured=true&limit=6');
  const result = await response.json();
  if (result.success) {
    const courses = result.data;
    // Render courses on homepage
  }
}

// Fetch testimonials
async function loadTestimonials() {
  const response = await fetch('/backend/api/testimonials.php?featured=true');
  const result = await response.json();
  if (result.success) {
    const testimonials = result.data;
    // Display testimonials
  }
}

// Fetch about sections
async function loadAboutSections() {
  const response = await fetch('/backend/api/about-sections.php');
  const result = await response.json();
  if (result.success) {
    const sections = result.data;
    // Render about page sections
  }
}

// Submit contact form
async function submitContact(formData) {
  const response = await fetch('/backend/api/contact-submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
  });
  const result = await response.json();
  return result;
}
```
