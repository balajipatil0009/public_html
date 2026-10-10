// Course data
const jobCourses = [
    { name: "Data Science", path: "courses/dataSci.html" },
    { name: "Full Stack Java", path: "courses/javaFullStack.html" },
    { name: "Full Stack Web Development", path: "courses/MERN-full-stack.html" },
    { name: "Android Development", path: "courses/flutter.html" },
    { name: "Cyber Security", path: "courses/cyber-security.html" },
    { name: "Ethical Hacking", path: "courses/ethical-hacking.html" },
    { name: "Software Testing", path: "courses/software-testing.html" },
    { name: "Hardware and Networking", path: "courses/hardware-networking.html" }
];

const langCourses = [
    { name: "C Programming", path: "courses/c.html" },
    { name: "C++", path: "courses/cpp.html" },
    { name: "Python", path: "courses/python.html" },
    { name: "Core Java", path: "courses/core-java.html" },
    { name: "C#", path: "courses/csharp.html" },
    { name: "HTML", path: "courses/html.html" },
    { name: "CSS", path: "courses/css.html" },
    { name: "JavaScript", path: "courses/javascript.html" },
    { name: "ReactJS", path: "courses/react.html" },
    { name: "NodeJS", path: "courses/nodejs.html" },
    { name: "Angular", path: "courses/angular.html" },
    { name: "PHP", path: "courses/php.html" },
    { name: "MongoDB", path: "courses/mongodb.html" },
    { name: "MySQL", path: "courses/mysql.html" },
    { name: "SQL", path: "courses/sql.html" },
    { name: "ASP.NET", path: "courses/aspnet.html" }
];

const otherCourses = [
    { name: "Adobe Photoshop", path: "courses/photoshop.html" },
    { name: "Adobe Illustrator", path: "courses/illustrator.html" },
    { name: "Adobe InDesign", path: "courses/indesign.html" },
    { name: "CorelDRAW", path: "courses/coreldraw.html" },
    { name: "AutoCAD", path: "courses/autocad.html" },
    { name: "SketchUp", path: "courses/sketchup.html" }
];

// Render courses
function renderCourses(courses, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = courses.map(course => `
        <a href="${course.path}" class="course-option-card">
            <h1>${course.name}</h1>
        </a>
    `).join('');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    renderCourses(jobCourses, 'jobCourses');
    renderCourses(langCourses, 'langCourses');
    renderCourses(otherCourses, 'otherCourses');
});
