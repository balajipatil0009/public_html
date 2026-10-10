// Main JavaScript for homepage

document.addEventListener('DOMContentLoaded', function () {
    // Hero Slider Functionality
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots = document.querySelectorAll('.slider-dot');
    const heroPrev = document.getElementById('heroPrev');
    const heroNext = document.getElementById('heroNext');
    let currentSlide = 0;
    let slideInterval;

    if (heroSlides.length > 1) {
        function showSlide(index) {
            heroSlides.forEach(slide => slide.classList.remove('active'));
            heroDots.forEach(dot => dot.classList.remove('active'));

            currentSlide = (index + heroSlides.length) % heroSlides.length;

            heroSlides[currentSlide].classList.add('active');
            heroDots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function startSlideShow() {
            stopSlideShow();
            slideInterval = setInterval(nextSlide, 6000);
        }

        function stopSlideShow() {
            if (slideInterval) clearInterval(slideInterval);
        }

        if (heroNext) heroNext.addEventListener('click', () => {
            nextSlide();
            startSlideShow();
        });

        if (heroPrev) heroPrev.addEventListener('click', () => {
            prevSlide();
            startSlideShow();
        });

        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                startSlideShow();
            });
        });

        startSlideShow();
    }

    // Premium Carousel Functionality (Both Courses and Testimonials)
    function setupCarousel(wrapperId, leftBtnId, rightBtnId, scrollAmount = 400) {
        const carousel = document.getElementById(wrapperId);
        const leftBtn = document.getElementById(leftBtnId);
        const rightBtn = document.getElementById(rightBtnId);

        if (!carousel || !leftBtn || !rightBtn) return;

        leftBtn.addEventListener('click', () => {
            carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        rightBtn.addEventListener('click', () => {
            const isAtEnd = carousel.scrollLeft + carousel.offsetWidth >= carousel.scrollWidth - 10;
            if (isAtEnd) {
                carousel.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        });

        // Auto Scroll Logic
        let autoScrollInterval = setInterval(() => {
            const isAtEnd = carousel.scrollLeft + carousel.offsetWidth >= carousel.scrollWidth - 10;
            if (isAtEnd) {
                carousel.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        }, 3000);

        // Pause on Hover
        carousel.addEventListener('mouseenter', () => clearInterval(autoScrollInterval));
        carousel.addEventListener('mouseleave', () => {
            clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                const isAtEnd = carousel.scrollLeft + carousel.offsetWidth >= carousel.scrollWidth - 10;
                if (isAtEnd) {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
            }, 3000);
        });

        // Add keyboard support
        carousel.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            if (e.key === 'ArrowRight') carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
    }

    // Initialize carousels
    setupCarousel('courseCarousel', 'scrollLeft', 'scrollRight');
    setupCarousel('testiCarousel', 'testiLeft', 'testiRight', 380);

    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.glass, .feature-card-premium, .course-card-premium').forEach(el => {
        observer.observe(el);
    });

    // Enquiry Modal Logic
    const enquiryModalOverlay = document.getElementById('enquiryModalOverlay');
    const enquiryForm = document.getElementById('enquiryForm');
    const enquiryResponse = document.getElementById('enquiryResponse');

    if (enquiryForm) {
        enquiryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitEnquiry');
            const originalBtnText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            enquiryResponse.style.display = 'none';

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                course_interest: document.getElementById('course_interest').value,
                message: document.getElementById('message').value
            };

            fetch('backend/api/contact-submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    enquiryResponse.textContent = data.message;
                    enquiryResponse.classList.remove('success', 'error');
                    enquiryResponse.classList.add(data.success ? 'success' : 'error');
                    enquiryResponse.style.display = 'block';

                    if (data.success) {
                        enquiryForm.reset();
                        setTimeout(() => {
                            enquiryModalOverlay.classList.remove('active');
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    enquiryResponse.textContent = 'An error occurred. Please try again later.';
                    enquiryResponse.classList.add('error');
                    enquiryResponse.style.display = 'block';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                });
        });
    }
});

