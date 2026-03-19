document.addEventListener("DOMContentLoaded", function() {
  const container = document.getElementById("board-accordion-list");
  if (!container) return;
  
  // 1. Accordion Logic (One at a time)
  const allDetails = container.querySelectorAll("details");
  allDetails.forEach((targetDetail) => {
    const summary = targetDetail.querySelector("summary");
    
    summary.addEventListener("click", (e) => {
      // Important: Don't toggle if clicking the copy button
      if (e.target.closest('.copy-link-btn')) {
        e.preventDefault();
        return;
      }
      
      allDetails.forEach((detail) => {
        if (detail !== targetDetail && detail.hasAttribute('open')) {
          detail.removeAttribute('open');
        }
      });
    });
  });
  
  // 2. Copy Link Logic
  const copyButtons = container.querySelectorAll('.copy-link-btn');
  copyButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      
      const id = btn.getAttribute('data-id');
      const url = window.location.href.split('#')[0] + '#' + id;
      
      navigator.clipboard.writeText(url).then(() => {
        // Show Tooltip
        const tooltip = btn.querySelector('.copy-tooltip');
        tooltip.classList.add('visible');
        
        setTimeout(() => {
          tooltip.classList.remove('visible');
        }, 2000);
      });
    });
  });
  
  // 3. Deep Linking (Hash handling)
  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const targetEl = document.getElementById(hash);
    
    if (targetEl && targetEl.tagName === 'DETAILS') {
      targetEl.setAttribute("open", "");
      setTimeout(() => {
        targetEl.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }, 100);
    }
  }
});