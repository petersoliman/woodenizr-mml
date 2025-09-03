/**
 * SEO Analysis JavaScript Functionality
 */
$(document).ready(function() {
    // Initialize SEO score circle
    updateSeoScoreCircle();
    
    // Add CSS custom property for score circle
    function updateSeoScoreCircle() {
        const scoreCircle = document.querySelector('.seo-score-circle');
        if (scoreCircle) {
            const score = parseInt(scoreCircle.dataset.score) || 0;
            scoreCircle.style.setProperty('--score', score);
        }
    }
    
    // Refresh SEO Analysis
    window.refreshSeoAnalysis = function() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="icon-spinner2 spinner"></i> Analyzing...';
        
        // Reload the page to get fresh analysis data
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    };
    
    // Handle form submission
    $('#seoForm').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> Saving...');
        
        // Form will submit normally, button state will be reset on page reload
    });
});

