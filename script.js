// ==================== SCROLL ANIMATIONS ====================
// Parallax scrolling effect
window.addEventListener('scroll', function() {
    const hero = document.querySelector('.hero');
    if (hero) {
        const scrollPosition = window.scrollY;
        hero.style.backgroundPosition = `center ${scrollPosition * 0.5}px`;
    }
});

// Initialize AOS (Animate On Scroll) library
window.addEventListener('load', function() {
    // Add AOS script
    const aosScript = document.createElement('script');
    aosScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js';
    aosScript.onload = function() {
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: false,
            offset: 100,
            disable: 'phone'
        });
    };
    document.head.appendChild(aosScript);
});

// ==================== MODALS ====================
// Open and close functions for Sign In Modal
function openSignIn() {
    document.getElementById('signInModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSignIn() {
    document.getElementById('signInModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Open and close functions for Log In Modal
function openLogIn() {
    document.getElementById('logInModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLogIn() {
    document.getElementById('logInModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Switch between Sign In and Log In
function switchToSignIn() {
    closeLogIn();
    openSignIn();
}

function switchToLogin() {
    closeSignIn();
    openLogIn();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const signInModal = document.getElementById('signInModal');
    const logInModal = document.getElementById('logInModal');
    
    if (event.target == signInModal) {
        closeSignIn();
    }
    if (event.target == logInModal) {
        closeLogIn();
    }
}

// Prevent closing when clicking inside modal-content
document.addEventListener('click', function(event) {
    if (event.target.closest('.modal-content')) {
        event.stopPropagation();
    }
});

// ==================== PRELOADER ====================
// Preloader functionality
let timeLeft = 30;
const timerDisplay = document.getElementById('timer');
const preloader = document.getElementById('preloader');

// Check if the elements exist before starting the timer
if (timerDisplay && preloader) {
    const timerInterval = setInterval(() => {
        timeLeft--;
        timerDisplay.textContent = timeLeft.toString().padStart(2, '5');

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            preloader.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }, 1000);
} else {
    console.error("Required preloader elements (#timer or #preloader) not found in DOM.");
}

// Hide preloader after 30 seconds
setTimeout(() => {
    preloader.classList.add('hidden');
    document.body.style.overflow = 'auto';
}, 30000);

// Form submission
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        fetch('contact-submit.php', {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(form)
        })
            .then(response => response.json())
            .then(result => {
                alert(result.message || 'The message could not be sent.');
                if (result.success) form.reset();
            })
            .catch(() => alert('Could not reach the server. Please try again.'));
    });
}

// Smooth scroll function
function smoothScroll(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth'
        });
    }
}

// Smooth scroll for navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && document.querySelector(href)) {
            e.preventDefault();
            document.querySelector(href).scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Optional: Add scroll animations for elements
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.querySelectorAll('.menu-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(card);
});

// ==================== AUTH FORMS ====================
async function submitAuthForm(form, action, messageId) {
    const message = document.getElementById(messageId);
    const data = new FormData(form);
    data.set('action', action);
    data.set('csrf_token', typeof authCsrfToken === 'undefined' ? '' : authCsrfToken);
    data.set('return_to', typeof authReturnTo === 'undefined' ? 'index.php' : authReturnTo);
    message.textContent = '';
    message.classList.remove('visible', 'success');

    try {
        const response = await fetch('auth.php', { method: 'POST', body: data });
        const result = await response.json();
        message.textContent = result.message || 'The request could not be completed.';
        message.classList.add('visible');

        if (result.success) {
            message.classList.add('success');
            window.location.href = result.redirect || 'index.php';
        }
    } catch (error) {
        message.textContent = 'Could not reach the server. Please try again.';
        message.classList.add('visible');
    }
}

const signInForm = document.getElementById('signInForm');
if (signInForm) {
    signInForm.addEventListener('submit', function(event) {
        event.preventDefault();
        submitAuthForm(this, 'signup', 'signup-message');
    });
}

const logInForm = document.getElementById('logInForm');
if (logInForm) {
    logInForm.addEventListener('submit', function(event) {
        event.preventDefault();
        submitAuthForm(this, 'login', 'login-message');
    });
}

// Load remembered email if exists
window.addEventListener('load', function() {
    const rememberedEmail = localStorage.getItem('hungrymans_email');
    if (rememberedEmail) {
        const loginEmailField = document.getElementById('login-email');
        if (loginEmailField) {
            loginEmailField.value = rememberedEmail;
        }
    }
});
