// Mobile Menu Toggle
function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('active');
}

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        const navLinks = document.querySelector('.nav-links');
        navLinks.classList.remove('active');
    });
});

// Hero Slider
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.opacity = "0";
        slides[i].style.zIndex = "0";
    }
    
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    
    slides[slideIndex-1].style.opacity = "1";
    slides[slideIndex-1].style.zIndex = "1";
    dots[slideIndex-1].className += " active";
}

// Auto slide change
let slideInterval = setInterval(() => {
    plusSlides(1);
}, 5000);

// Pause on hover
const slider = document.querySelector('.hero-slider');
slider.addEventListener('mouseenter', () => {
    clearInterval(slideInterval);
});

slider.addEventListener('mouseleave', () => {
    slideInterval = setInterval(() => {
        plusSlides(1);
    }, 5000);
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Sticky header on scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('.navbar');
    header.classList.toggle('sticky', window.scrollY > 0);
});

// Form validation
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const emailInput = this.querySelector('input[type="email"]');
        if (emailInput && !emailInput.value.includes('@')) {
            e.preventDefault();
            emailInput.style.border = '1px solid red';
            emailInput.placeholder = 'Please enter a valid email';
        }
    });
});