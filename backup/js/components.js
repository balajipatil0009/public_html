function createNavbar() {
    return `
        <header class="navbar-header">
            <nav class="navbar">
                <a href="index.php" class="navbar-logo" style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="public/langImages/Logoreal.jpg" alt="ITINFO Academy Logo" style="height: 45px; width: auto; border-radius: 4px;">
                    <div style="display: flex; flex-direction: column; line-height: 1;">
                        <span style="font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: #002147; letter-spacing: -0.02em;">ITINFO</span>
                        <span style="font-family: 'Poppins', sans-serif; font-size: 0.65rem; font-weight: 700; color: #3db166; letter-spacing: 0.2em; text-transform: uppercase; margin-top: 2px;">Academy</span>
                    </div>
                </a>
                <ul class="navbar-menu" id="navbarMenu">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="about.php" class="nav-link">About</a></li>
                    <li><a href="courses.php" class="nav-link">Courses</a></li>
                    <li><a href="internship.php" class="nav-link">Internship</a></li>
                    <li><a href="gallery.php" class="nav-link">Gallery</a></li>
                    <li><a href="validator.php" class="nav-link">Verify</a></li>
                </ul>
                <div class="navbar-actions">
                    <a href="intern-login.php" class="btn-primary btn-small" style="border-radius: 2px; background: #002147; border: none; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1.25rem; color: white; text-decoration: none;">Member Login</a>
                    <button class="btn-primary btn-small" id="navEnquiryBtn" style="border-radius: 2px; background: #3db166; border: none; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1.25rem;">Enquire Now</button>
                    <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle menu">
                        <span class="hamburger"></span>
                    </button>
                </div>
            </nav>
        </header>
    `;
}

function createFooter() {
    return `
        <footer class="footer-premium">
            <div class="footer-container">
                <div class="footer-top">
                    <div class="footer-info">
                        <a href="index.php" class="footer-logo" style="display: flex; align-items: center; gap: 1rem;">
                            <img src="public/langImages/Logoreal.jpg" alt="ITINFO Academy Logo" style="height: 55px; width: auto; border-radius: 4px;">
                            <div style="display: flex; flex-direction: column; line-height: 1;">
                                <span style="font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">ITINFO</span>
                                <span style="font-family: 'Poppins', sans-serif; font-size: 0.75rem; font-weight: 700; color: #3db166; letter-spacing: 0.2em; text-transform: uppercase;">Academy</span>
                            </div>
                        </a>
                        <p class="footer-description">Leading the way in IT education and professional development. Equipping the next generation of tech leaders with industry-ready skills.</p>
                        <div class="footer-social-links">
                            <a href="https://www.facebook.com/share/ciPrf9ixUD4eeeeT/" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://www.instagram.com/itinfoacademy?igsh=ZHRoYWN2d3o1aWdi" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.linkedin.com/in/itinfo-academy-aa05a02a7?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="footer-links">
                        <h4 style="font-family: 'Playfair Display', serif; color: #ffffff; font-size: 1.25rem; margin-bottom: 2rem;">Quick Links</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 1rem;"><a href="index.php" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Home</a></li>
                            <li style="margin-bottom: 1rem;"><a href="about.php" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">About Us</a></li>
                            <li style="margin-bottom: 1rem;"><a href="courses.php" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Our Courses</a></li>
                            <li style="margin-bottom: 1rem;"><a href="internship.php" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Internship</a></li>
                            <li style="margin-bottom: 1rem;"><a href="validator.php" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Verify Certificate</a></li>
                        </ul>
                    </div>
                    <div class="footer-contact">
                        <h4 style="font-family: 'Playfair Display', serif; color: #ffffff; font-size: 1.25rem; margin-bottom: 2rem;">Contact Info</h4>
                        <ul style="list-style: none; padding: 0; color: rgba(255,255,255,0.7);">
                            <li style="margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 10px;"><i class="fas fa-envelope" style="color: #3db166; margin-top: 5px;"></i> validatoritinfoacademy@gmail.com</li>
                            <li style="margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 10px;"><i class="fas fa-phone" style="color: #3db166; margin-top: 5px;"></i> 8830257210/8007633732 </li>
                            <li style="margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 10px;"><i class="fas fa-map-marker-alt" style="color: #3db166; margin-top: 5px;"></i> 3 rd floor, Swadeshi heights, above IDFC first bank,  beside Vijaynagar bus stop, sangli</li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom" style="border-top: 1px solid rgba(255,255,255,0.1); padding: 2rem 0; margin-top: 4rem; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                    <p>&copy; 2024 ITINFO ACADEMY. All rights reserved.</p>
                </div>
            </div>
        </footer>
    `;
}

function createEnquiryModal() {
    return `
        <div class="enquiry-modal-overlay" id="enquiryModalOverlay">
            <div class="enquiry-modal">
                <button class="enquiry-modal-close" id="closeEnquiryModal">&times;</button>
                <div class="enquiry-modal-header">
                    <h2 class="enquiry-modal-title">Get in Touch</h2>
                    <p class="enquiry-modal-subtitle">Have questions? Our experts are here to help you choose the right path.</p>
                </div>
                <form id="enquiryForm" class="enquiry-form">
                    <div class="enquiry-form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="enquiry-input" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="enquiry-form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="enquiry-input" placeholder="e.g. john@example.com" required>
                    </div>
                    <div class="enquiry-form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="enquiry-input" placeholder="e.g. +91 9876543210">
                    </div>
                    <div class="enquiry-form-group">
                        <label for="course_interest">Interested Course</label>
                        <select id="course_interest" name="course_interest" class="enquiry-input">
                            <option value="">Select a course</option>
                            <option value="full_stack">Full Stack Development (Java/MERN)</option>
                            <option value="data_science">Data Science & AI</option>
                            <option value="cyber_security">Cyber Security & Ethical Hacking</option>
                            <option value="software_testing">Software Testing</option>
                            <option value="programming">Programming Languages (C/C++/Python/Java)</option>
                            <option value="web_dev">Web Development (React/Angular/Node)</option>
                            <option value="design">Graphic & UI/UX Design</option>
                            <option value="other">Other Inquiry</option>
                        </select>
                    </div>
                    <div class="enquiry-form-group">
                        <label for="message">How can we help?</label>
                        <textarea id="message" name="message" class="enquiry-input enquiry-textarea" placeholder="Tell us about your career goals..." required></textarea>
                    </div>
                    <button type="submit" class="enquiry-submit-btn" id="submitEnquiry">Send Enquiry</button>
                    <div id="enquiryResponse" class="enquiry-response"></div>
                </form>
            </div>
        </div>
    `;
}

function createChatWidget() {
    return `
        <div class="ai-chat-widget">
            <div class="ai-chat-bubble" id="aiChatBubble">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8V4H8"></path>
                    <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                    <path d="M2 14h2"></path>
                    <path d="M20 14h2"></path>
                    <path d="M15 13v2"></path>
                    <path d="M9 13v2"></path>
                </svg>
            </div>
            <div class="ai-chat-window" id="aiChatWindow">
                <div class="ai-chat-header">
                    <div class="ai-chat-bot-info">
                        <div class="ai-chat-avatar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"></path><rect width="16" height="12" x="4" y="8" rx="2"></rect></svg>
                        </div>
                        <div class="ai-chat-name">
                            <h4>Aria</h4>
                            <span class="ai-chat-status">Academy Counselor</span>
                        </div>
                    </div>
                    <button class="ai-chat-close" id="aiChatClose">&times;</button>
                </div>
                <div class="ai-chat-messages" id="aiChatMessages"></div>
                <form class="ai-chat-input-area" id="aiChatForm">
                    <input type="text" class="ai-chat-input" id="aiChatInput" placeholder="Ask about courses, fees..." autocomplete="off">
                    <button type="submit" class="ai-chat-send" id="aiChatSend">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>
        </div>
    `;
}

function setActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const navbarContainer = document.getElementById('navbar');
    if (navbarContainer) {
        navbarContainer.innerHTML = createNavbar();

        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');

        if (navbarToggle && navbarMenu) {
            navbarToggle.addEventListener('click', function () {
                navbarMenu.classList.toggle('active');
                navbarToggle.classList.toggle('open');
            });

            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    navbarMenu.classList.remove('active');
                    navbarToggle.classList.remove('open');
                });
            });
        }

        window.addEventListener('scroll', () => {
            const header = document.querySelector('.navbar-header');
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 20) {
                header.classList.add('scrolled-header');
                nav.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled-header');
                nav.classList.remove('scrolled');
            }
        });

        setActiveNavLink();
    }

    const footerContainer = document.getElementById('footer');
    if (footerContainer) {
        footerContainer.innerHTML = createFooter();
    }

    // Insert Enquiry Modal
    if (!document.getElementById('enquiryModalOverlay')) {
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = createEnquiryModal();
        document.body.appendChild(modalContainer.firstElementChild);

        // Enquiry Modal Logic
        const enquiryModal = document.getElementById('enquiryModalOverlay');
        const openEnquiryBtn = document.getElementById('navEnquiryBtn');
        const closeEnquiryBtn = document.getElementById('closeEnquiryModal');

        const openModal = () => {
            enquiryModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scroll
        };

        const closeModal = () => {
            enquiryModal.classList.remove('active');
            document.body.style.overflow = '';
        };

        if (openEnquiryBtn) {
            openEnquiryBtn.addEventListener('click', openModal);
        }

        if (closeEnquiryBtn) {
            closeEnquiryBtn.addEventListener('click', closeModal);
        }

        // Close on outside click
        enquiryModal.addEventListener('click', (e) => {
            if (e.target === enquiryModal) closeModal();
        });

        // Auto-popup after 5 seconds (only once per session)
        if (!sessionStorage.getItem('enquiryPopUpShown')) {
            setTimeout(() => {
                if (!enquiryModal.classList.contains('active')) {
                    openModal();
                    sessionStorage.setItem('enquiryPopUpShown', 'true');
                }
            }, 5000);
        }
    }

    // Insert AI Chatbot
    if (!document.getElementById('aiChatBubble')) {
        // Enforce CSS
        if (!document.querySelector('link[href="css/chat.css"]')) {
            const chatCss = document.createElement('link');
            chatCss.rel = 'stylesheet';
            chatCss.href = 'css/chat.css';
            document.head.appendChild(chatCss);
        }

        const chatContainer = document.createElement('div');
        chatContainer.innerHTML = createChatWidget();
        document.body.appendChild(chatContainer.firstElementChild);

        // Load chat logic
        const chatScript = document.createElement('script');
        chatScript.src = 'js/chat.js';
        document.body.appendChild(chatScript);
    }
});
