/**
 * Universal Image Fallback Handler
 * Automatically detects corrupted images (< 1000 bytes) and uses fallback images
 */

(function() {
    'use strict';

    // Fallback image URLs for different image types
    const fallbackImages = {
        'dashboard1.png': 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop',
        'dashboard2.png': 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop',
        'dashboard3.png': 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?q=80&w=1920&auto=format&fit=crop',
        'dashboard4.png': 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?q=80&w=1920&auto=format&fit=crop',
        'bookings.png': 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop',
        'virtual.jpg': 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop',
        'boy.png': 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop',
        'woman.png': 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop',
        'dwellscape-logo.png': null // Logo uses SVG fallback
    };

    /**
     * Check if an image file is corrupted (too small)
     */
    function checkImageFile(img) {
        return new Promise((resolve) => {
            const src = img.src || img.getAttribute('src');
            if (!src || src.startsWith('http') || src.startsWith('data:')) {
                resolve({ valid: true, src: src });
                return;
            }

            fetch(src, { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        const contentLength = response.headers.get('content-length');
                        if (contentLength && parseInt(contentLength) < 1000) {
                            // File is too small (corrupted)
                            const filename = src.split('/').pop();
                            const fallback = fallbackImages[filename];
                            resolve({ 
                                valid: false, 
                                src: src, 
                                fallback: fallback,
                                size: parseInt(contentLength)
                            });
                        } else {
                            resolve({ valid: true, src: src });
                        }
                    } else {
                        // File not found
                        const filename = src.split('/').pop();
                        const fallback = fallbackImages[filename];
                        resolve({ 
                            valid: false, 
                            src: src, 
                            fallback: fallback 
                        });
                    }
                })
                .catch(() => {
                    // Can't check, assume valid for now
                    resolve({ valid: true, src: src });
                });
        });
    }

    /**
     * Load image with fallback
     */
    function loadImageWithFallback(img, fallbackUrl) {
        return new Promise((resolve) => {
            const testImg = new Image();
            testImg.onload = () => {
                if (fallbackUrl && img.src !== fallbackUrl) {
                    img.src = fallbackUrl;
                }
                resolve(true);
            };
            testImg.onerror = () => {
                if (fallbackUrl) {
                    img.src = fallbackUrl;
                }
                resolve(false);
            };
            testImg.src = fallbackUrl || img.src;
        });
    }

    /**
     * Process a single image element
     */
    async function processImage(img) {
        // Skip if already processed
        if (img.dataset.processed === 'true') return;
        img.dataset.processed = 'true';

        const result = await checkImageFile(img);
        
        if (!result.valid) {
            // Image is corrupted - using fallback (this is expected behavior)
            // console.warn(`Image appears corrupted or missing: ${result.src} (${result.size || 'N/A'} bytes)`);
            
            if (result.fallback) {
                // Use fallback image
                await loadImageWithFallback(img, result.fallback);
                // Using fallback image (this is expected)
                // console.log(`Using fallback image for: ${result.src}`);
            } else if (img.nextElementSibling && img.nextElementSibling.classList.contains('logo-fallback')) {
                // For logo, show SVG fallback immediately
                img.style.display = 'none';
                img.style.visibility = 'hidden';
                img.style.opacity = '0';
                const fallback = img.nextElementSibling;
                fallback.style.display = 'inline-block';
                fallback.style.visibility = 'visible';
                fallback.style.opacity = '1';
                // Showing SVG logo fallback (this is expected)
                // console.log('Showing SVG logo fallback for:', result.src);
            }
        } else {
            // Verify image actually loads
            const testImg = new Image();
            testImg.onload = () => {
                // Image is valid
            };
            testImg.onerror = () => {
                // Image failed to load, try fallback
                const filename = result.src.split('/').pop();
                const fallback = fallbackImages[filename];
                if (fallback) {
                    img.src = fallback;
                } else if (img.nextElementSibling && img.nextElementSibling.classList.contains('logo-fallback')) {
                    img.style.display = 'none';
                    img.nextElementSibling.style.display = 'inline-block';
                    img.nextElementSibling.style.visibility = 'visible';
                }
            };
            testImg.src = result.src;
        }
    }

    /**
     * Initialize image fallback handler
     */
    function initImageFallback() {
        // Process all images on page load
        const images = document.querySelectorAll('img[src*="pictures/"], img[src*="assets/img/"]');
        images.forEach(img => {
            // Force display initially
            img.style.display = img.style.display || 'block';
            img.style.visibility = img.style.visibility || 'visible';
            img.style.opacity = img.style.opacity || '1';
            
            processImage(img);
        });

        // Watch for dynamically added images
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'IMG' && (node.src.includes('pictures/') || node.src.includes('assets/img/'))) {
                            processImage(node);
                        }
                        // Check for images within added nodes
                        const images = node.querySelectorAll && node.querySelectorAll('img[src*="pictures/"], img[src*="assets/img/"]');
                        if (images) {
                            images.forEach(img => processImage(img));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImageFallback);
    } else {
        initImageFallback();
    }

    // Export for manual use
    window.ImageFallback = {
        processImage: processImage,
        checkImageFile: checkImageFile
    };
})();

