// This file contains all course data and a template generator
// Run this in browser console on any course page to generate HTML for all courses

const coursesData = {
    // Job Role Courses
    "javaFullStack": {
        title: "Java Full Stack Developer",
        description: "Unlock full-stack proficiency with our Java course - master front-end, back-end, and database for end-to-end web application development.",
        syllabus: [{"title":"Front-End","points":["Introduction","  Different types of Applications","Application Architecture"]},{"title":"HTML","points":["Introduction to HTML","  Basic Structure of HTML"," HTML Editors"," HTML Tags"," Paragraphs, Headings"]},{"title":"CSS","points":["Introduction to CSS","Types of CSS","CSS Properties, Selectors and Values","Applying CSS to HTML","Basic and Advance CSS Properties"]},{"title":"JAVASCRIPT","points":["Introduction to JavaScript","How to Apply JavaScript","Displaying Output in JavaScript","Understanding JavaScript Syntax","Variables & Datatypes","Operators","Math and String Manipulations","Conditional and looping Statements","Functions","Validations","Events"]},{"title":"BOOTSTRAP","points":["Introduction to Bootstrap","Bootstrap Setup","Bootstrap Containers","Bootstrap Grids","Bootstrap Tables","Bootstrap Buttons, Navbars, Alerts","Bootstrap Carousel","Bootstrap Forms"]},{"title":"ANGULARJS","points":["Introduction to Angular","Environment Setup","Installing Angular CLI","Directory Structure of Angular","Angular Fundamentals","Angular Building Blocks","Angular Data Binding","String Interpolation","Directives and Pipes","Forms","Approaches (Driven & Reactive)","Validators","Routing"]},{"title":"Some Advance Topics","points":["  ReactJS","NodeJS"]},{"title":"Backend Part","points":["Introduction","Java LanguageJava Platform","Java Technologies","History of Java"]},{"title":"Basic JAVA","points":["DATA TYPES","METHODS","CONTROL FLOW STATEMENT","OPERATORS","Loops","Object-Oriented Programming(OOPs)","CONSTRUCTOR","ACCESSIBILITY MODE","INTER OBJECT COMMUNICATION","ARRAYS","STATIC MODIFIER","INHERITANCE","POLYMORPHISM","INTERFACE"]},{"title":"Advanced Java","points":["PACKAGES","STRING HANDLING","EXCEPTION HANDLING","IOSTREAMS","MULTITHREADING","INNER CLASSES"]},{"title":"MISCELLANEOUS","points":[" Java.lang.object Class","Garbage Collection","Java.util.properties","Java.util.string Tokenizer","Dealing with date & time","Collection Framework","Annotations","Java JDK 1.8 Features","Stream API","Varargs","Autoboxing and unboxing","Enum types"]},{"title":"JDBC","points":["Introduction To JDBC","JDBC Architecture","Types Of JDBC Drivers"," Creating a Database & Table"," Getting Information from Database","Obtaining Result Set Information","Connecting a Java Program to DB"," Prepared Statement & Statment classes in java","Inserting, Updating & Deleting data"]},{"title":"Java Servlets","points":[" WHAT IS A WEB APPLICATION","    JAVA SERVLETS","SERVLET LIFECYCLE","SERVLET CONTEXT","SESSION MANAGEMENT","BUILDING THE FIRST SERVLET","DEPLOYING THE SERVLET"]},{"title":"SPRING BOOT","points":["Rest Api & Web Service Creation","Integration with JDBC Template","Integration with Hibernate"]},{"title":"JSP","points":["What is a JSP page"," Basic HTML tags","JSP Tag Library","JSP Page Lifecycle","Creating first dynamic web page"]},{"title":"MVC ARCHITECTURE","points":["Introduction to MVC"," MVC Architecture","Advantages of MVC"," Building pages with MVC"," Three-tier architecture"]},{"title":"STRUTS FRAMEWORK    ","points":["Introduction","Actions"," Validation Framework","Interceptors"," Results"," Data Tags"," UI Tags"," Architecture"]},{"title":"HIBERNATE FRAMEWORK","points":["Technologies for Persistency"," Introduction to ORM"," Hibernate Architecture"," Hibernate API"," Configuration Properties"," CRUD OperationIn","heritance in Hibernate"," Component Mapping"," Collection Mapping"," One-to-many, Many-to-many Relations","HQL (Aggregate Function ,clauses ,Criteria)"]},{"title":"SPRING FRAMEWORK","points":[" Introduction to Spring","IOC","DI","AOP","Bean Lifecycle","Types of Injection","Inheritance & Composition","Collections","Bean Auto Wiring","DAO"]},{"title":"XML AND WEB SERVICES","points":[" SAX & DOM","REST API","POSTMAN","SOAP UI","JSON"]},{"title":"Database","points":["DATABASE HANDLING WITH MYSQL","JAVA MYSQL DATABASE ACCESS","CREATE DATABASE CONNECTION","DML AND DDL OPERATIONS WITH DATABASES","PERFORMING TRANSACTIONS","HANDLING DATABASE ERRORS","DISCONNECTING DATABASE","DATABASE HANDLING WITH MONGODB","SQL VS NOSQL","PYMONGO","ESTABLISHING A CONNECTION","ACCESSING DATABASE","DML AND DDL OPERATIONS"]}]
    },
    "MERN-full-stack": {
        title: "MERN Full Stack Web Development",
        description: "Become a versatile full-stack warrior: Conquer front-end, back-end, and database technologies for end-to-end web development mastery.",
        syllabus: [{"title":"Introduction to MERN Stack","points":["What is MERN Stack?","Benefits of MERN Stack","MERN Stack Architecture","Setting up the Development Environment"]},{"title":"MongoDB","points":["Introduction to MongoDB","MongoDB Data Model and BSON","CRUD Operations","Querying and Indexing","Aggregation Framework","MongoDB Atlas and Deployment"]},{"title":"Node.js","points":["Introduction to Node.js","Node.js Architecture and Event Loop","Node.js Core Modules","Node.js Package Manager (NPM)","Asynchronous Programming in Node.js","Node.js Streams","Node.js Cluster and Worker Threads","Node.js Security and Best Practices"]},{"title":"Express.js","points":["Introduction to Express.js","Building APIs with Express.js","Middleware and Routing","Error Handling and Debugging","Template Engines (Pug, EJS)","Express.js Security and Best Practices","Integrating with MongoDB (Mongoose)"]},{"title":"React.js Fundamentals","points":["Introduction to React.js","React Components and JSX","React State and Lifecycle","React Hooks (useState, useEffect, useContext, etc.)","React Router and Navigation","React Forms and User Input"]},{"title":"Advanced React.js","points":["Redux and State Management","React Context API and Context Providers","React Portals and Refs","React Testing and Debugging","React Performance Optimization","React Patterns and Best Practices"]},{"title":"React Ecosystem","points":["React and Webpack","React and Babel","React Developer Tools","Popular React Libraries and Packages","Server-Side Rendering (SSR) with Next.js","React Native for Mobile App Development"]},{"title":"Authentication and Security","points":["User Authentication and Authorization","JSON Web Tokens (JWT)","Bcrypt for Password Hashing","HTTPS and SSL/TLS","Security Best Practices"]},{"title":"MERN Stack Integration","points":["Building a Full-Stack MERN Application","Client-Server Communication (HTTP, AJAX, Fetch)","CORS and Cross-Origin Resource Sharing","Handling Forms and User Input","File Upload and Handling"]},{"title":"Deployment and DevOps","points":["Deploying a MERN Stack Application","Continuous Integration and Continuous Deployment (CI/CD)","Docker and Containerization","Cloud Deployment (AWS, Heroku, etc.)","Monitoring and Logging"]},{"title":"Advanced Topics","points":["GraphQL and Apollo","WebSockets and Real-Time Communication","Progressive Web Apps (PWAs)","Performance Optimization","Serverless Architecture with AWS Lambda and API Gateway"]},{"title":"Projects and Assignments","points":["Hands-on projects and assignments to reinforce concepts","Building a Simple CRUD Application","Building a Real-Time Chat Application","Building an E-Commerce Platform","Building a Social Media Application","Building a Mobile App with React Native"]}]
    }
    // Add more courses here...
};

// Template function
function generateCourseHTML(courseKey, courseData) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${courseData.title} - ITINFO ACADEMY</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body style="background-image: url('../../public/logo.jpg'); background-repeat: no-repeat; background-attachment: fixed; background-position: center;">
    <nav id="navbar"></nav>

    <div class="syllabus-hero">
        <div class="syllabus-hero-content">
            <h1>${courseData.title}</h1>
            <p>${courseData.description}</p>
        </div>
    </div>

    <div class="syllabus-timeline" id="syllabusContainer"></div>

    <div style="margin-top: 1.5rem;">
        <footer id="footer"></footer>
    </div>

    <script src="../js/components.js"></script>
    <script>
        const syllabusData = ${JSON.stringify(courseData.syllabus, null, 8)};

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('syllabusContainer');
            
            container.innerHTML = syllabusData.map((item, index) => {
                const isLeft = index % 2 === 0;
                const cardClass = isLeft ? 'syllabus-card-left' : 'syllabus-card-right';
                
                return \`
                    <div class="syllabus-card-container">
                        <div class="syllabus-card-wrapper">
                            <div class="syllabus-card">
                                <div class="\${cardClass}">
                                    <div class="syllabus-card-content">
                                        <h1 class="syllabus-card-title">\${item.title}</h1>
                                        <div class="syllabus-card-list">
                                            <ul>
                                                \${item.points.map(point => \`<li>\${point}</li>\`).join('')}
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="syllabus-timeline-line"></div>
                                    <div class="syllabus-timeline-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                \`;
            }).join('');
        });
    </script>
</body>
</html>`;
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { coursesData, generateCourseHTML };
}
