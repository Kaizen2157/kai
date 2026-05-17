// Navigation functions
        function editResume(id) {
            window.location.href = `edit-resume.php?id=${id}`;
        }
        
        function previewResume(id) {
            window.open(`preview-resume.php?id=${id}`, '_blank');
        }
        
        function downloadPDF(id) {
            window.location.href = `export-pdf.php?id=${id}`;
        }

        // Add fade-in animation to cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.action-card, .resume-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(24px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.transitionDelay = `${index * 0.1}s`;
            observer.observe(card);
        });