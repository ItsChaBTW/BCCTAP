/**
 * Chrome Browser Detection and Recommendation Utility
 * BCCTAP - Bago City College Time Attendance Platform
 */

class ChromeDetector {
    constructor() {
        this.browser = this.detectBrowser();
    }

    /**
     * Detect the current browser
     * @returns {Object} Browser information
     */
    detectBrowser() {
        const userAgent = navigator.userAgent;
        const isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
        const isBrave = navigator.brave && navigator.brave.isBrave && /Chrome/.test(userAgent);
        const isFirefox = /Firefox/.test(userAgent);
        const isSafari = /Safari/.test(userAgent) && !/Chrome/.test(userAgent);
        const isEdge = /Edg/.test(userAgent);
        const isOpera = /Opera|OPR/.test(userAgent);
        
        return {
            isChrome: isChrome && !isBrave, // Chrome but not Brave
            isBrave,
            isFirefox,
            isSafari,
            isEdge,
            isOpera,
            name: isBrave ? 'Brave' :
                  isChrome ? 'Chrome' : 
                  isFirefox ? 'Firefox' : 
                  isSafari ? 'Safari' : 
                  isEdge ? 'Edge' : 
                  isOpera ? 'Opera' : 'Unknown',
            userAgent
        };
    }

    /**
     * Check if the current browser is Chrome (excluding Brave)
     * @returns {boolean}
     */
    isChrome() {
        return this.browser.isChrome;
    }

    /**
     * Check if the current browser is Brave
     * @returns {boolean}
     */
    isBrave() {
        return this.browser.isBrave;
    }

    /**
     * Get the browser name
     * @returns {string}
     */
    getBrowserName() {
        return this.browser.name;
    }

    /**
     * Show Brave-specific recommendation to switch to Chrome
     * @param {Object} options - Configuration options
     */
    showBraveRecommendation(options = {}) {
        if (!this.isBrave()) {
            return Promise.resolve(false);
        }

        const defaultOptions = {
            title: 'Brave Browser Detected',
            message: 'While Brave is a great privacy-focused browser, we recommend switching to Google Chrome for optimal compatibility with our QR scanning system.',
            showDetails: true,
            allowDismiss: true
        };

        const config = { ...defaultOptions, ...options };

        return Swal.fire({
            title: config.title,
            html: `
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V3H13V9H21Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Brave Browser Detected</h3>
                    <p class="text-gray-600 mb-3">You're using <strong>Brave Browser</strong>, which we appreciate for its privacy features!</p>
                    <p class="text-gray-600 mb-4">${config.message}</p>
                    
                    ${config.showDetails ? `
                    <div class="bg-orange-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-orange-700">
                            <strong>Why switch to Chrome for BCCTAP?</strong><br>
                            • Brave's privacy shields may interfere with camera access<br>
                            • QR code scanning works more reliably in Chrome<br>
                            • Better compatibility with our attendance system<br>
                            • Faster processing of QR codes<br>
                            • Reduced chance of scanning errors
                        </p>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-blue-700">
                            <strong>Privacy Note:</strong><br>
                            You can still use Brave for general browsing and only switch to Chrome for BCCTAP attendance. This gives you the best of both worlds!
                        </p>
                    </div>
                    ` : ''}
                    
                    <p class="text-xs text-gray-500">We understand if you prefer to continue with Brave, but Chrome will provide the smoothest experience.</p>
                </div>
            `,
            icon: 'warning',
            iconColor: '#F59E0B',
            showCancelButton: config.allowDismiss,
            confirmButtonText: 'Switch to Chrome',
            cancelButtonText: 'Continue with Brave',
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#F59E0B',
            allowOutsideClick: config.allowDismiss,
            allowEscapeKey: config.allowDismiss,
            customClass: {
                popup: 'brave-recommendation-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Open Chrome download page
                window.open(this.getChromeDownloadUrl(), '_blank');
                
                // Show follow-up instructions
                setTimeout(() => {
                    this.showBraveToChromeSwitchInstructions();
                }, 1000);
            }
            return result;
        });
    }

    /**
     * Show instructions for switching from Brave to Chrome
     */
    showBraveToChromeSwitchInstructions() {
        return Swal.fire({
            title: 'Switching from Brave to Chrome',
            html: `
                <div class="text-left">
                    <p class="mb-3 text-center">Here's how to get the best experience:</p>
                    <ol class="list-decimal list-inside space-y-2 text-sm">
                        <li><strong>Download Chrome:</strong> Install from the page that just opened</li>
                        <li><strong>Copy this URL:</strong> <code class="bg-gray-100 p-1 rounded text-xs break-all">${window.location.href}</code></li>
                        <li><strong>Open Chrome:</strong> Launch Google Chrome browser</li>
                        <li><strong>Paste URL:</strong> Go to the same page in Chrome</li>
                        <li><strong>Login again:</strong> Enter your credentials in Chrome</li>
                    </ol>
                    
                    <div class="mt-4 p-3 bg-green-50 rounded">
                        <p class="text-sm text-green-700">
                            <strong>Pro Tip:</strong> Bookmark BCCTAP in Chrome for quick access during attendance times!
                        </p>
                    </div>
                    
                    <div class="mt-3 p-3 bg-blue-50 rounded">
                        <p class="text-sm text-blue-700">
                            <strong>Keep Using Brave:</strong> You can continue using Brave for all other browsing. Just use Chrome specifically for BCCTAP.
                        </p>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Copy URL & Continue',
            confirmButtonColor: '#10B981',
            allowOutsideClick: true
        }).then((result) => {
            if (result.isConfirmed) {
                this.copyToClipboard(window.location.href);
            }
            return result;
        });
    }

    /**
     * Show Chrome recommendation modal for QR scanning
     * @param {Object} options - Configuration options
     */
    showQRRecommendation(options = {}) {
        // If using Brave, show specific Brave recommendation
        if (this.isBrave()) {
            return this.showBraveRecommendation({
                title: 'QR Scanning with Brave Browser',
                message: 'For the most reliable QR code scanning experience, we strongly recommend using Google Chrome instead of Brave.',
                ...options
            });
        }

        // If already using Chrome, no need to show recommendation
        if (this.isChrome()) {
            return Promise.resolve(false);
        }

        const defaultOptions = {
            title: 'Browser Recommendation',
            message: 'For the best QR code scanning experience, we recommend using Google Chrome.',
            showDownloadLink: true,
            allowDismiss: true,
            autoShow: true
        };

        const config = { ...defaultOptions, ...options };

        return Swal.fire({
            title: config.title,
            html: `
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V3H13V9H21Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">For the Best Experience</h3>
                    <p class="text-gray-600 mb-3">You're currently using <strong>${this.getBrowserName()}</strong>.</p>
                    <p class="text-gray-600 mb-4">${config.message}</p>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-sm text-blue-700">
                            <strong>Why Chrome?</strong><br>
                            • Better camera access for QR scanning<br>
                            • Faster performance<br>
                            • Enhanced security features<br>
                            • Optimal compatibility with BCCTAP
                        </p>
                    </div>
                </div>
            `,
            icon: 'info',
            showCancelButton: config.allowDismiss,
            confirmButtonText: config.showDownloadLink ? 'Get Chrome' : 'Continue',
            cancelButtonText: `Continue with ${this.getBrowserName()}`,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#10B981',
            allowOutsideClick: config.allowDismiss,
            allowEscapeKey: config.allowDismiss
        });
    }

    /**
     * Show Chrome recommendation with automatic redirect attempt
     * @param {Object} options - Configuration options
     */
    showRedirectRecommendation(options = {}) {
        // If using Brave, show specific Brave recommendation first
        if (this.isBrave()) {
            return this.showBraveRecommendation({
                title: 'Brave Detected - Switching Recommended',
                message: 'Brave\'s privacy features may interfere with QR scanning. For the best experience, please switch to Chrome.',
                ...options
            });
        }

        if (this.isChrome()) {
            return Promise.resolve(false);
        }

        const defaultOptions = {
            title: 'Redirecting to Chrome',
            autoRedirect: true,
            timeout: 8000
        };

        const config = { ...defaultOptions, ...options };

        return Swal.fire({
            title: config.title,
            html: `
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V3H13V9H21Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Attempting Chrome Redirect</h3>
                    <p class="text-gray-600 mb-3">You're currently using <strong>${this.getBrowserName()}</strong>.</p>
                    <p class="text-gray-600 mb-4">For the best QR scanning experience, we're attempting to open this page in Chrome.</p>
                    <div class="bg-green-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-green-700">
                            <strong>What happens next?</strong><br>
                            • If Chrome is installed, it will open automatically<br>
                            • If not, you'll be redirected to download Chrome<br>
                            • You can continue with ${this.getBrowserName()} if needed
                        </p>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded-lg">
                        <p class="text-xs text-yellow-700">
                            <strong>Note:</strong> Some browsers may block automatic redirects. If Chrome doesn't open, you can manually copy this URL to Chrome.
                        </p>
                    </div>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Try Opening in Chrome',
            cancelButtonText: `Continue with ${this.getBrowserName()}`,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#3B82F6',
            allowOutsideClick: false,
            allowEscapeKey: true,
            timer: config.timeout,
            timerProgressBar: true
        }).then((result) => {
            if (result.isConfirmed || (result.isDismissed && result.dismiss === Swal.DismissReason.timer)) {
                this.attemptChromeRedirect();
            }
            return result;
        });
    }

    /**
     * Show login-specific Chrome recommendation (every login for non-Chrome users)
     * @param {Object} options - Configuration options
     */
    showLoginRecommendation(options = {}) {
        // If using Brave, show specific Brave recommendation
        if (this.isBrave()) {
            return this.showBraveRecommendation({
                title: 'Brave Browser - Login Recommendation',
                message: 'For the best login and QR scanning experience with BCCTAP, we strongly recommend using Google Chrome instead of Brave.',
                showDetails: true,
                ...options
            });
        }

        // If already using Chrome, no need to show recommendation
        if (this.isChrome()) {
            return Promise.resolve(false);
        }

        const defaultOptions = {
            title: 'Browser Recommendation for BCCTAP',
            message: 'For the best experience with QR code scanning and attendance recording, we recommend using Google Chrome.',
            showDetails: true,
            allowDismiss: true
        };

        const config = { ...defaultOptions, ...options };

        return Swal.fire({
            title: config.title,
            html: `
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V3H13V9H21Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Welcome to BCCTAP!</h3>
                    <p class="text-gray-600 mb-3">You're currently using <strong>${this.getBrowserName()}</strong> to access the attendance system.</p>
                    <p class="text-gray-600 mb-4">${config.message}</p>
                    
                    ${config.showDetails ? `
                    <div class="bg-indigo-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-indigo-700">
                            <strong>Why Chrome works best for BCCTAP?</strong><br>
                            • Superior camera access for QR code scanning<br>
                            • Faster QR code processing and recognition<br>
                            • Better device fingerprinting for security<br>
                            • Optimized performance for attendance recording<br>
                            • Reduced scanning errors and retries
                        </p>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-blue-700">
                            <strong>What this means for you:</strong><br>
                            • More reliable attendance recording<br>
                            • Faster QR code scanning<br>
                            • Better overall user experience<br>
                            • Fewer technical issues
                        </p>
                    </div>
                    ` : ''}
                    
                    <p class="text-xs text-gray-500">You can continue with ${this.getBrowserName()}, but Chrome is recommended for optimal performance.</p>
                </div>
            `,
            icon: 'info',
            iconColor: '#6366F1',
            showCancelButton: config.allowDismiss,
            confirmButtonText: 'Get Chrome for Better Experience',
            cancelButtonText: `Continue with ${this.getBrowserName()}`,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6366F1',
            allowOutsideClick: config.allowDismiss,
            allowEscapeKey: config.allowDismiss,
            customClass: {
                popup: 'login-recommendation-popup',
                title: 'text-lg font-bold',
                content: 'text-sm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Open Chrome download page
                window.open(this.getChromeDownloadUrl(), '_blank');
                
                // Show follow-up instructions specific to login context
                setTimeout(() => {
                    this.showLoginChromeInstructions();
                }, 1000);
            }
            return result;
        });
    }

    /**
     * Show instructions for using Chrome with BCCTAP after login
     */
    showLoginChromeInstructions() {
        return Swal.fire({
            title: 'Getting Started with Chrome',
            html: `
                <div class="text-left">
                    <p class="mb-3 text-center">Here's how to set up Chrome for the best BCCTAP experience:</p>
                    <ol class="list-decimal list-inside space-y-2 text-sm">
                        <li><strong>Install Chrome:</strong> Download from the page that just opened</li>
                        <li><strong>Bookmark BCCTAP:</strong> Save this link in Chrome: <code class="bg-gray-100 p-1 rounded text-xs break-all">${window.location.origin}${window.location.pathname.replace(/\/[^\/]*$/, '')}</code></li>
                        <li><strong>Login in Chrome:</strong> Use your same credentials</li>
                        <li><strong>Test QR Scanning:</strong> Try scanning a QR code to verify camera access</li>
                        <li><strong>Enable Notifications:</strong> Allow notifications for attendance reminders</li>
                    </ol>
                    
                    <div class="mt-4 p-3 bg-green-50 rounded">
                        <p class="text-sm text-green-700">
                            <strong>Pro Tips:</strong><br>
                            • Use Chrome only for BCCTAP - keep your current browser for everything else<br>
                            • Pin the BCCTAP tab in Chrome for quick access<br>
                            • Enable camera permissions when prompted
                        </p>
                    </div>
                    
                    <div class="mt-3 p-3 bg-blue-50 rounded">
                        <p class="text-sm text-blue-700">
                            <strong>Need Help?</strong> Contact your instructor or IT support if you experience any issues with Chrome setup.
                        </p>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Got it! Continue with Setup',
            confirmButtonColor: '#10B981',
            allowOutsideClick: true,
            customClass: {
                popup: 'chrome-setup-instructions'
            }
        });
    }

    /**
     * Show a subtle dashboard recommendation (once per day)
     * @param {Object} options - Configuration options
     */
    showDashboardRecommendation(options = {}) {
        // Special handling for Brave users - show more frequently since it's important
        if (this.isBrave()) {
            const lastShownBrave = localStorage.getItem('brave_chrome_recommendation_shown');
            const today = new Date().toDateString();
            
            if (lastShownBrave === today) {
                return Promise.resolve(false);
            }
            
            return new Promise((resolve) => {
                setTimeout(() => {
                    this.showBraveRecommendation({
                        title: 'Brave Browser - Consider Chrome for QR Scanning',
                        message: 'While we appreciate your privacy-focused choice, Chrome provides better QR scanning reliability for BCCTAP.',
                        showDetails: true
                    }).then((result) => {
                        localStorage.setItem('brave_chrome_recommendation_shown', today);
                        resolve(result);
                    });
                }, 2000);
            });
        }

        if (this.isChrome()) {
            return Promise.resolve(false);
        }

        // Check if user has already seen this recommendation today
        const lastShown = localStorage.getItem('chrome_recommendation_shown');
        const today = new Date().toDateString();
        
        if (lastShown === today) {
            return Promise.resolve(false);
        }

        const defaultOptions = {
            delay: 3000,
            title: 'Optimize Your Experience'
        };

        const config = { ...defaultOptions, ...options };

        return new Promise((resolve) => {
            setTimeout(() => {
                Swal.fire({
                    title: config.title,
                    html: `
                        <div class="text-center">
                            <div class="mb-4">
                                <svg class="mx-auto h-12 w-12 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">For Better QR Scanning</h3>
                            <p class="text-gray-600 mb-3">You're using <strong>${this.getBrowserName()}</strong>. Consider switching to <strong>Chrome</strong> for the best QR code scanning experience.</p>
                            <div class="bg-blue-50 p-3 rounded-lg mb-4">
                                <p class="text-sm text-blue-700">
                                    <strong>Chrome benefits:</strong><br>
                                    • Faster camera access<br>
                                    • Better QR code detection<br>
                                    • Improved reliability<br>
                                    • Enhanced security
                                </p>
                            </div>
                            <p class="text-xs text-gray-500">You can dismiss this and it won't show again today.</p>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Get Chrome',
                    cancelButtonText: 'Maybe Later',
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#6B7280',
                    allowOutsideClick: true,
                    allowEscapeKey: true
                }).then((result) => {
                    // Save that user has seen this today
                    localStorage.setItem('chrome_recommendation_shown', today);
                    
                    if (result.isConfirmed) {
                        window.open('https://www.google.com/chrome/', '_blank');
                    }
                    
                    resolve(result);
                });
            }, config.delay);
        });
    }

    /**
     * Attempt to redirect the current page to Chrome browser
     */
    attemptChromeRedirect() {
        const currentUrl = window.location.href;
        
        // Try different methods to open in Chrome
        const chromeUrls = [
            `googlechrome://${currentUrl}`,
            `chrome://${currentUrl}`
        ];
        
        let success = false;
        
        // Method 1: Try Chrome protocol handlers
        chromeUrls.forEach((url, index) => {
            setTimeout(() => {
                try {
                    window.location.href = url;
                    success = true;
                } catch (e) {
                    console.log(`Chrome redirect method ${index + 1} failed:`, e);
                }
            }, index * 1000);
        });
        
        // Method 2: Fallback - show instructions if protocol handlers fail
        setTimeout(() => {
            if (!success) {
                this.showManualInstructions(currentUrl);
            }
        }, 3000);
    }

    /**
     * Show manual instructions for opening in Chrome
     * @param {string} url - Current URL to copy
     */
    showManualInstructions(url) {
        return Swal.fire({
            title: 'Manual Chrome Instructions',
            html: `
                <div class="text-left">
                    <p class="mb-3">Automatic redirect didn't work. Here's how to open in Chrome:</p>
                    <ol class="list-decimal list-inside space-y-2 text-sm">
                        <li>Copy this URL: <code class="bg-gray-100 p-1 rounded text-xs break-all">${url}</code></li>
                        <li>Open Google Chrome browser</li>
                        <li>Paste the URL in Chrome's address bar</li>
                        <li>Press Enter</li>
                    </ol>
                    <div class="mt-4 p-3 bg-blue-50 rounded">
                        <p class="text-sm text-blue-700">
                            <strong>Don't have Chrome?</strong><br>
                            <a href="https://www.google.com/chrome/" target="_blank" class="underline">Download Chrome here</a>
                        </p>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Copy URL & Open Chrome',
            showCancelButton: true,
            cancelButtonText: 'Continue Here',
            confirmButtonColor: '#10B981'
        }).then((result) => {
            if (result.isConfirmed) {
                // Copy URL to clipboard and open Chrome download
                this.copyToClipboard(url).then(() => {
                    window.open('https://www.google.com/chrome/', '_blank');
                }).catch(() => {
                    // Fallback if clipboard doesn't work
                    window.open('https://www.google.com/chrome/', '_blank');
                });
            }
            return result;
        });
    }

    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     * @returns {Promise}
     */
    copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            return new Promise((resolve, reject) => {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    textArea.remove();
                    resolve();
                } catch (err) {
                    textArea.remove();
                    reject(err);
                }
            });
        }
    }

    /**
     * Get Chrome download URL
     * @returns {string}
     */
    getChromeDownloadUrl() {
        return 'https://www.google.com/chrome/';
    }

    /**
     * Check if Chrome is likely installed (basic check)
     * @returns {boolean}
     */
    isChromeInstalled() {
        // This is a basic check - we can't reliably detect if Chrome is installed
        // from a web page due to security restrictions
        return this.isChrome() || /Chrome/.test(navigator.userAgent);
    }
}

// Create global instance
window.ChromeDetector = new ChromeDetector();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChromeDetector;
} 