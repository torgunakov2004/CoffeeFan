document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.querySelector('.testimonial-carousel');
    const testimonialItems = carousel.querySelectorAll('.testimonial-item');
    const testimonialCount = carousel.querySelector('.testimonial-count');
    const progressBar = testimonialCount.querySelector('progress');
    let currentIndex = 0;

    function showTestimonial(index) {
        testimonialItems.forEach((item, i) => {
            if (i === index) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        currentIndex = index;
        progressBar.value = (currentIndex + 1) * (100 / testimonialItems.length);
        testimonialCount.querySelector('.testimonial-count__text').textContent = `${currentIndex + 1}/${testimonialItems.length} Особенности`;
    }

    function nextTestimonial() {
        const nextIndex = (currentIndex + 1) % testimonialItems.length;
        showTestimonial(nextIndex);
    }

    function prevTestimonial() {
        const prevIndex = (currentIndex - 1 + testimonialItems.length) % testimonialItems.length;
        showTestimonial(prevIndex);
    }

    const nextBtn = carousel.querySelector('.testimonial-btn_active');
    nextBtn.addEventListener('click', nextTestimonial);

    const prevBtns = Array.from(carousel.querySelectorAll('.testimonial-btn:not(.testimonial-btn_active)'));
    prevBtns.forEach(btn => btn.addEventListener('click', prevTestimonial));

    showTestimonial(currentIndex);
});
function showTestimonial(index) {
    testimonialItems.forEach((item, i) => {
      if (i === index) {
        item.classList.add('active');
      } else {
        item.classList.remove('active');
      }
    });
  
    currentIndex = index;
    progressBar.value = (currentIndex + 1) * (100 / testimonialItems.length);
    testimonialCount.querySelector('.testimonial-count__text').textContent = `${currentIndex + 1}/${testimonialItems.length} Особенности`;
  }