document.addEventListener('DOMContentLoaded', function() {
    var myCarousel = document.getElementById('testimonialCarousel');
    if (myCarousel) {
        // Reinicia o ciclo automático após interação do usuário
        myCarousel.addEventListener('slid.bs.carousel', function() {
            var carousel = bootstrap.Carousel.getInstance(myCarousel);
            if (carousel) carousel.cycle();
        });
    }
    
    // Funções para animações ao scroll
    function isElementInViewport(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.bottom >= 0
        );
    }
    
    function handleScrollAnimations() {
        const elements = document.querySelectorAll('.service-card, .section-title, .testimonial-author');
        
        elements.forEach(function(element) {
            if (isElementInViewport(element)) {
                element.classList.add('animate-in');
            }
        });
    }
    
    // Executar uma vez quando a página carregar
    handleScrollAnimations();
    
    // Executar durante o scroll
    window.addEventListener('scroll', handleScrollAnimations);
});
