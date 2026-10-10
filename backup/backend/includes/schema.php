<?php
/**
 * Structured Data (JSON-LD) Helper for ITINFO ACADEMY
 * Include this file before </body> to add schema markup
 * 
 * Usage:
 * $schema = 'organization'; // or 'course', 'localBusiness', 'faq', 'breadcrumb'
 * $schemaData = [...]; // optional additional data
 * include 'backend/includes/schema.php';
 */

$siteName = 'ITINFO ACADEMY';
$siteUrl = 'https://itinfoacademy.in';
$logo = $siteUrl . '/public/logo.png';

// Default Organization schema - always included
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'EducationalOrganization',
    'name' => $siteName,
    'url' => $siteUrl,
    'logo' => $logo,
    'description' => 'Premier IT training institute offering professional courses with 95% placement rate',
    'address' => [
        '@type' => 'PostalAddress',
        'addressCountry' => 'IN'
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+91-XXXXXXXXXX',
        'contactType' => 'customer service',
        'email' => 'support@itinfoacademy.in'
    ],
    'sameAs' => [
        'https://www.facebook.com/itinfoacademy',
        'https://www.instagram.com/itinfoacademy',
        'https://www.linkedin.com/company/itinfoacademy'
    ]
];

// Determine which schema to output
$schema = $schema ?? 'organization';
$schemas = [];

switch ($schema) {
    case 'organization':
    case 'home':
        $schemas[] = $organizationSchema;
        // Add WebSite schema for homepage
        if ($schema === 'home') {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $siteUrl . '/courses.php?search={search_term_string}',
                    'query-input' => 'required name=search_term_string'
                ]
            ];
        }
        break;

    case 'course':
        if (isset($schemaData['course'])) {
            $course = $schemaData['course'];
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Course',
                'name' => $course['title'] ?? 'IT Course',
                'description' => $course['description'] ?? 'Professional IT training course',
                'provider' => [
                    '@type' => 'EducationalOrganization',
                    'name' => $siteName,
                    'sameAs' => $siteUrl
                ],
                'courseMode' => ['onsite', 'online'],
                'educationalLevel' => 'Beginner to Advanced'
            ];
        }
        break;

    case 'courses':
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'IT Courses at ITINFO ACADEMY',
            'itemListElement' => []
        ];
        if (isset($schemaData['courses']) && is_array($schemaData['courses'])) {
            foreach ($schemaData['courses'] as $index => $course) {
                $schemas[0]['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => [
                        '@type' => 'Course',
                        'name' => $course['title'],
                        'description' => $course['short_description'] ?? '',
                        'url' => $siteUrl . '/course.php?slug=' . ($course['slug'] ?? '')
                    ]
                ];
            }
        }
        break;

    case 'faq':
        if (isset($schemaData['faqs']) && is_array($schemaData['faqs'])) {
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => []
            ];
            foreach ($schemaData['faqs'] as $faq) {
                $faqSchema['mainEntity'][] = [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer']
                    ]
                ];
            }
            $schemas[] = $faqSchema;
        }
        break;

    case 'breadcrumb':
        if (isset($schemaData['breadcrumbs']) && is_array($schemaData['breadcrumbs'])) {
            $breadcrumbSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => []
            ];
            foreach ($schemaData['breadcrumbs'] as $index => $crumb) {
                $breadcrumbSchema['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $siteUrl . $crumb['url']
                ];
            }
            $schemas[] = $breadcrumbSchema;
        }
        break;
}

// Output the schemas
foreach ($schemas as $schemaItem):
    ?>
    <script type="application/ld+json">
    <?php echo json_encode($schemaItem, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
<?php endforeach; ?>