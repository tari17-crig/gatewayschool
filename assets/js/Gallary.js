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

// Gallery Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
  // Filter buttons
  const filterButtons = document.querySelectorAll('.filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item');

  filterButtons.forEach(button => {
      button.addEventListener('click', function() {
          // Remove active class from all buttons
          filterButtons.forEach(btn => btn.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');

          const filterValue = this.getAttribute('data-filter');

          galleryItems.forEach(item => {
              if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                  item.style.display = 'block';
              } else {
                  item.style.display = 'none';
              }
          });
      });
  });

  // Lightbox functionality
  const lightboxModal = document.querySelector('.lightbox-modal');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.querySelector('.lightbox-caption');
  const closeLightbox = document.querySelector('.close-lightbox');
  const prevBtn = document.querySelector('.prev');
  const nextBtn = document.querySelector('.next');

  let currentIndex = 0;
  const galleryImages = Array.from(document.querySelectorAll('.gallery-img'));
  const galleryCaptions = Array.from(document.querySelectorAll('.gallery-overlay'));

  // Open lightbox when gallery image is clicked
  galleryItems.forEach((item, index) => {
      item.addEventListener('click', function() {
          currentIndex = index;
          updateLightbox();
          lightboxModal.style.display = 'block';
          document.body.style.overflow = 'hidden';
      });
  });

  // Close lightbox
  closeLightbox.addEventListener('click', function() {
      lightboxModal.style.display = 'none';
      document.body.style.overflow = 'auto';
  });

  // Close when clicking outside the image
  lightboxModal.addEventListener('click', function(e) {
      if (e.target === lightboxModal) {
          lightboxModal.style.display = 'none';
          document.body.style.overflow = 'auto';
      }
  });

  // Navigation between images
  prevBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
      updateLightbox();
  });

  nextBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      currentIndex = (currentIndex + 1) % galleryImages.length;
      updateLightbox();
  });

  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
      if (lightboxModal.style.display === 'block') {
          if (e.key === 'Escape') {
              lightboxModal.style.display = 'none';
              document.body.style.overflow = 'auto';
          } else if (e.key === 'ArrowLeft') {
              currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
              updateLightbox();
          } else if (e.key === 'ArrowRight') {
              currentIndex = (currentIndex + 1) % galleryImages.length;
              updateLightbox();
          }
      }
  });

  function updateLightbox() {
      const imgSrc = galleryImages[currentIndex].getAttribute('src');
      const caption = galleryCaptions[currentIndex].querySelector('h3').textContent + 
                     ' - ' + 
                     galleryCaptions[currentIndex].querySelector('p').textContent;

      lightboxImg.setAttribute('src', imgSrc);
      lightboxCaption.textContent = caption;
  }
});