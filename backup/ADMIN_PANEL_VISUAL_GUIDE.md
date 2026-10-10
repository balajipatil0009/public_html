# 🎨 Admin Panel Visual Guide

## Navigation Structure

```
┌─────────────────────────────────────────────────────────────┐
│  ITINFO ACADEMY - Admin Panel                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📊 Dashboard                                               │
│  🖼️  Hero Slider                                            │
│  💬 Testimonials                                            │
│  📚 Courses                                                 │
│  📷 Gallery                                                 │
│  ℹ️  About Us                                               │
│  ✉️  Inquiries                                              │
│  🎓 Internships                                             │
│  👨‍🎓 Intern Enrollments                                      │
│  📋 Applications          ← NEW!                            │
│  🚀 Migration                                               │
│  ⚙️  Settings                                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Applications Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│  📋 Internship Applications              [Export to Excel] Button   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐          │
│  │   150    │  │    25    │  │    100   │  │    20    │          │
│  │  Total   │  │ Pending  │  │  Active  │  │Completed │          │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘          │
│                                                                     │
│  ┌──────────┐  ┌──────────┐                                       │
│  │     5    │  │₹450,000  │                                       │
│  │Cancelled │  │ Revenue  │                                       │
│  └──────────┘  └──────────┘                                       │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│  Filters:                                                           │
│  [Status ▼] [Payment ▼] [Search...........] [Filter Button]       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ID  │ Name      │ Email        │ Phone      │ Internship │ Date  │
│  ────┼───────────┼──────────────┼────────────┼────────────┼────── │
│  #42 │ John Doe  │ john@ex.com  │ 9876543210 │ Web Dev    │ Jan 15│
│      │ Male      │              │            │ ABC College│       │
│      │ [PENDING] │ [PENDING]    │ ₹3,000     │ [View] [✓] [✗]    │
│  ────┼───────────┼──────────────┼────────────┼────────────┼────── │
│  #41 │ Jane Smith│ jane@ex.com  │ 9876543211 │ Data Sci   │ Jan 14│
│      │ Female    │              │            │ XYZ College│       │
│      │ [ACTIVE]  │ [COMPLETED]  │ ₹5,000     │ [View]            │
│  ────┴───────────┴──────────────┴────────────┴────────────┴────── │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Application Details Page Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│  📋 Application Details [PENDING]          [← Back to List]        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────────────────┐  ┌──────────────────────────┐│
│  │ 👤 Student Information          │  │ ✏️ Update Application     ││
│  │                                 │  │                          ││
│  │ Full Name:    John Doe          │  │ Enrollment Status:       ││
│  │ Email:        john@example.com  │  │ [Pending ▼]              ││
│  │ Phone:        9876543210        │  │                          ││
│  │ Gender:       Male              │  │ Payment Status:          ││
│  │ Country:      INDIA             │  │ [Pending ▼]              ││
│  └─────────────────────────────────┘  │                          ││
│                                        │ Admin Notes:             ││
│  ┌─────────────────────────────────┐  │ [Text area............]  ││
│  │ 🎓 Academic Information         │  │ [...................]  ││
│  │                                 │  │                          ││
│  │ College:      ABC University    │  │ [Update Application]     ││
│  │ Degree:       B.Tech CSE        │  └──────────────────────────┘│
│  │ Current Year: 3                 │                              │
│  │ Source:       Social Media      │  ┌──────────────────────────┐│
│  └─────────────────────────────────┘  │ 📅 Timeline              ││
│                                        │                          ││
│  ┌─────────────────────────────────┐  │ ● Jan 15, 2026 10:30    ││
│  │ 💼 Internship Details           │  │   Application submitted  ││
│  │                                 │  │                          ││
│  │ Program:      Web Development   │  │ ○ Not reviewed yet       ││
│  │ Duration:     30 Days           │  │                          ││
│  │ Fees:         ₹3,000.00         │  └──────────────────────────┘│
│  │ Amount Paid:  ₹3,000.00         │                              │
│  └─────────────────────────────────┘  ┌──────────────────────────┐│
│                                        │ 💳 Payment Information   ││
│  ┌─────────────────────────────────┐  │                          ││
│  │ ❓ Student Queries              │  │ Method:   Online         ││
│  │                                 │  │ Status:   [PENDING]      ││
│  │ "I want to know about the       │  │ Trans ID: RZP_12345      ││
│  │  placement assistance and       │  └──────────────────────────┘│
│  │  certificate validity."         │                              │
│  └─────────────────────────────────┘                              │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Status Badge Colors

```
┌──────────────────────────────────────────────────────────┐
│  Enrollment Status Badges:                               │
│                                                           │
│  [PENDING]    - Yellow background, brown text            │
│  [ACTIVE]     - Green background, dark green text        │
│  [COMPLETED]  - Blue background, dark blue text          │
│  [CANCELLED]  - Red background, dark red text            │
│                                                           │
│  Payment Status Badges:                                  │
│                                                           │
│  [PENDING]    - Yellow background, brown text            │
│  [COMPLETED]  - Green background, dark green text        │
│  [FAILED]     - Red background, dark red text            │
└──────────────────────────────────────────────────────────┘
```

## Action Buttons

```
┌──────────────────────────────────────────────────────────┐
│  Button Types:                                           │
│                                                           │
│  [View]    - Blue button, opens details page             │
│  [✓]       - Green button, quick approve                 │
│  [✗]       - Red button, quick reject                    │
│  [Export]  - Purple button, download CSV                 │
│  [Filter]  - Primary button, apply filters               │
│  [Update]  - Green button, save changes                  │
└──────────────────────────────────────────────────────────┘
```

## Statistics Cards

```
┌─────────────────────────────────────────────────────────────┐
│  Card Layout:                                               │
│                                                             │
│  ┌──────────────┐                                          │
│  │     150      │  ← Large number (stat value)             │
│  │              │                                           │
│  │ TOTAL        │  ← Small label (stat name)               │
│  │ APPLICATIONS │                                           │
│  └──────────────┘                                          │
│   │                                                         │
│   └─ Left border color indicates category                  │
│      • Default (blue-green) - Total                        │
│      • Yellow - Pending                                    │
│      • Green - Active                                      │
│      • Blue - Completed                                    │
│      • Red - Cancelled                                     │
│      • Purple - Revenue                                    │
└─────────────────────────────────────────────────────────────┘
```

## Filter Section

```
┌─────────────────────────────────────────────────────────────┐
│  Filters Layout:                                            │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────────┐  ┌────────┐│
│  │ Status   │  │ Payment  │  │ Search       │  │ Filter ││
│  │ [All ▼]  │  │ [All ▼]  │  │ [Name/Email] │  │ Button ││
│  └──────────┘  └──────────┘  └──────────────┘  └────────┘│
│                                                             │
│  • Dropdowns auto-submit on change                         │
│  • Search requires clicking Filter button                  │
│  • All filters work together (AND logic)                   │
└─────────────────────────────────────────────────────────────┘
```

## Progress Display (Active Students)

```
┌─────────────────────────────────────────────────────────────┐
│  Learning Progress Card:                                    │
│                                                             │
│  ┌────────────────────────────────────────────────────────┐│
│  │  📊 Learning Progress                                  ││
│  │                                                        ││
│  │  ┌──────────────────────────────────────────────────┐ ││
│  │  │  Videos Watched              8/10                 │ ││
│  │  │  Quizzes Passed              6/10                 │ ││
│  │  │  Completion                  80%                  │ ││
│  │  └──────────────────────────────────────────────────┘ ││
│  │  (Purple gradient background with white text)        ││
│  └────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

## Timeline View

```
┌─────────────────────────────────────────────────────────────┐
│  Timeline Layout:                                           │
│                                                             │
│  │                                                          │
│  ●─ Jan 15, 2026 10:30                                     │
│  │  Application submitted                                  │
│  │                                                          │
│  ●─ Jan 15, 2026 14:20                                     │
│  │  Reviewed by Admin User                                 │
│  │                                                          │
│  ○─ Not yet                                                │
│     Certificate issued                                      │
│                                                             │
│  • Filled circle (●) = Completed event                     │
│  • Empty circle (○) = Future event                         │
│  • Vertical line connects events                           │
└─────────────────────────────────────────────────────────────┘
```

## Mobile View

```
┌──────────────────────────┐
│  📋 Applications         │
│  [☰ Menu]                │
├──────────────────────────┤
│                          │
│  ┌────────────────────┐  │
│  │      150           │  │
│  │  Total Apps        │  │
│  └────────────────────┘  │
│                          │
│  ┌────────────────────┐  │
│  │       25           │  │
│  │  Pending           │  │
│  └────────────────────┘  │
│                          │
│  [Status ▼]              │
│  [Payment ▼]             │
│  [Search..........]      │
│  [Filter]                │
│                          │
│  ┌────────────────────┐  │
│  │ #42 John Doe       │  │
│  │ john@example.com   │  │
│  │ [PENDING]          │  │
│  │ [View] [✓] [✗]     │  │
│  └────────────────────┘  │
│                          │
│  ┌────────────────────┐  │
│  │ #41 Jane Smith     │  │
│  │ jane@example.com   │  │
│  │ [ACTIVE]           │  │
│  │ [View]             │  │
│  └────────────────────┘  │
│                          │
└──────────────────────────┘
```

## Color Scheme

```
┌─────────────────────────────────────────────────────────────┐
│  Primary Colors:                                            │
│                                                             │
│  • Navy Blue (#002147)    - Headers, titles                │
│  • Green (#3db166)        - Primary buttons, success       │
│  • Yellow (#ffc107)       - Pending status, warnings       │
│  • Red (#f44336)          - Cancelled, failed, reject      │
│  • Blue (#2196f3)         - Info, completed, view          │
│  • Purple (#9c27b0)       - Revenue, export                │
│  • Gray (#6c757d)         - Secondary buttons              │
│                                                             │
│  Background Colors:                                         │
│                                                             │
│  • White (#ffffff)        - Cards, main content            │
│  • Light Gray (#f8f9fa)   - Table headers, hover           │
│  • Off-White (#f5f5f5)    - Page background                │
└─────────────────────────────────────────────────────────────┘
```

## Typography

```
┌─────────────────────────────────────────────────────────────┐
│  Font Hierarchy:                                            │
│                                                             │
│  • Page Title (H1)        - 2rem, bold, navy blue          │
│  • Section Title (H2)     - 1.25rem, bold, navy blue       │
│  • Card Title             - 1rem, semi-bold                │
│  • Body Text              - 0.875rem, regular              │
│  • Small Text             - 0.75rem, regular               │
│  • Labels                 - 0.875rem, semi-bold, uppercase │
│                                                             │
│  Font Family:                                               │
│  • System fonts (-apple-system, BlinkMacSystemFont, etc.)  │
└─────────────────────────────────────────────────────────────┘
```

## Spacing & Layout

```
┌─────────────────────────────────────────────────────────────┐
│  Grid System:                                               │
│                                                             │
│  • Statistics: Auto-fit grid, min 200px columns            │
│  • Details: 2fr (main) + 1fr (sidebar) on desktop          │
│  • Mobile: Single column, stacked layout                   │
│                                                             │
│  Spacing:                                                   │
│                                                             │
│  • Card padding: 2rem (32px)                               │
│  • Section gap: 2rem (32px)                                │
│  • Element gap: 1rem (16px)                                │
│  • Small gap: 0.5rem (8px)                                 │
│                                                             │
│  Border Radius:                                             │
│                                                             │
│  • Cards: 8px                                              │
│  • Buttons: 4px                                            │
│  • Badges: 12px                                            │
└─────────────────────────────────────────────────────────────┘
```

---

**Visual Guide Version:** 1.0.0  
**Last Updated:** January 15, 2026  
**Purpose:** Quick reference for UI layout and design
