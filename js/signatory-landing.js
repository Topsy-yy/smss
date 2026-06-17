document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Hero Carousel Logic
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.nav-left');
    const nextBtn = document.querySelector('.nav-right');
    let currentSlide = 0;
    let slideInterval;

    function goToSlide(index) {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() { goToSlide(currentSlide + 1); }
    function prevSlide() { goToSlide(currentSlide - 1); }

    // Start Auto-play
    function startSlideShow() { slideInterval = setInterval(nextSlide, 5000); }
    function resetSlideShow() { clearInterval(slideInterval); startSlideShow(); }

    if (slides.length > 0) {
        nextBtn.addEventListener('click', () => { nextSlide(); resetSlideShow(); });
        prevBtn.addEventListener('click', () => { prevSlide(); resetSlideShow(); });
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => { goToSlide(index); resetSlideShow(); });
        });
        startSlideShow();
    }

    // 2. Workspace Tabs Logic
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active from all
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active to clicked
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // 3. SMS Compose Form Mock (Africa's Talking Simulation)
    const smsForm = document.getElementById('smsComposeForm');
    if(smsForm) {
        smsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = smsForm.querySelector('.btn-submit');
            const originalText = btn.innerText;
            
            btn.innerText = "Sending via Africa's Talking...";
            btn.style.opacity = "0.7";
            
            // Simulate API Latency
            setTimeout(() => {
                alert("SMS Dispatched Successfully! (API Simulation)");
                btn.innerText = originalText;
                btn.style.opacity = "1";
                smsForm.reset();
            }, 1200);
        });
    }

});