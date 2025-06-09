/**
 * BCCTAP Device Fingerprinting
 * This script handles device fingerprinting that works across different browsers on the same device
 */

class DeviceFingerprint {
    /**
     * Generate a device fingerprint based on various device characteristics
     * @returns {Promise<string>} A hash representing the device fingerprint
     */
    static async generate() {
        try {
            // Collect hardware-level information that remains consistent across browsers
            const fingerprint = {
                // Screen properties
                screenWidth: window.screen.width,
                screenHeight: window.screen.height,
                screenColorDepth: window.screen.colorDepth,
                screenPixelRatio: window.devicePixelRatio || 1,
                
                // Device properties
                platform: navigator.platform,
                deviceMemory: navigator.deviceMemory || 'unknown',
                hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
                
                // OS information
                os: this.getOS(),
                osVersion: this.getOSVersion(),
                
                // Device type
                deviceType: this.getDeviceType(),
                
                // Canvas fingerprint (hardware-level rendering)
                canvasFingerprint: await this.getCanvasFingerprint(),
                
                // Audio context fingerprint
                audioFingerprint: await this.getAudioFingerprint(),
            };
            
            // Convert the fingerprint to a string and hash it
            const fingerprintStr = JSON.stringify(fingerprint);
            const fingerprintHash = await this.sha256(fingerprintStr);
            
            // Save in multiple storage locations to ensure cross-browser access
            this.storeFingerprint(fingerprintHash);
            
            return fingerprintHash;
        } catch (error) {
            console.error('Error generating fingerprint:', error);
            // Fallback to a simpler fingerprint method
            return this.generateBasicFingerprint();
        }
    }
    
    /**
     * Get a simple OS name
     * @returns {string} OS name
     */
    static getOS() {
        const userAgent = navigator.userAgent;
        
        if (/Windows/.test(userAgent)) return 'Windows';
        if (/Android/.test(userAgent)) return 'Android';
        if (/iPhone|iPad|iPod/.test(userAgent)) return 'iOS';
        if (/Mac/.test(userAgent)) return 'macOS';
        if (/Linux/.test(userAgent)) return 'Linux';
        
        return 'Unknown';
    }
    
    /**
     * Try to determine the OS version
     * @returns {string} OS version or 'unknown'
     */
    static getOSVersion() {
        const userAgent = navigator.userAgent;
        let match;
        
        // Windows
        match = userAgent.match(/Windows NT (\d+\.\d+)/);
        if (match) return match[1];
        
        // Android
        match = userAgent.match(/Android (\d+(?:\.\d+)+)/);
        if (match) return match[1];
        
        // iOS
        match = userAgent.match(/OS (\d+[_]\d+(?:[_]\d+)?)/);
        if (match) return match[1].replace(/_/g, '.');
        
        // macOS
        match = userAgent.match(/Mac OS X (\d+[._]\d+(?:[._]\d+)?)/);
        if (match) return match[1].replace(/_/g, '.');
        
        return 'unknown';
    }
    
    /**
     * Determine if device is mobile, tablet, or desktop
     * @returns {string} Device type
     */
    static getDeviceType() {
        const userAgent = navigator.userAgent;
        
        if (/Mobi|Android/.test(userAgent) && !/Tablet|iPad/.test(userAgent)) {
            return 'mobile';
        } else if (/Tablet|iPad/.test(userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Generate a canvas fingerprint
     * @returns {Promise<string>} Canvas fingerprint hash
     */
    static async getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = 200;
            canvas.height = 50;
            
            // Draw various elements that depend on hardware rendering
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#1a73e8';
            ctx.fillRect(0, 0, 100, 25);
            ctx.fillStyle = '#000000';
            ctx.fillText('BCCTAP Device ID', 2, 15);
            ctx.fillStyle = '#f3af3d';
            ctx.fillRect(100, 0, 100, 25);
            ctx.fillStyle = '#EF6161';
            ctx.arc(170, 10, 15, 0, Math.PI * 2);
            ctx.fill();
            
            // Convert canvas to data URL and hash it
            const dataURL = canvas.toDataURL();
            return this.hashCode(dataURL);
        } catch (e) {
            return 'canvas-not-supported';
        }
    }
    
    /**
     * Generate an audio fingerprint
     * @returns {Promise<string>} Audio fingerprint
     */
    static async getAudioFingerprint() {
        try {
            // Try to create an AudioContext
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return 'audio-not-supported';
            }
            
            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const analyser = context.createAnalyser();
            const gain = context.createGain();
            
            // Configure audio nodes
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(440, context.currentTime);
            gain.gain.setValueAtTime(0, context.currentTime); // Silent
            
            // Connect nodes
            oscillator.connect(analyser);
            analyser.connect(gain);
            gain.connect(context.destination);
            
            // Start oscillator for a short period
            oscillator.start(0);
            oscillator.stop(0.001);
            
            // Get frequency data
            const buffer = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteFrequencyData(buffer);
            
            // Close the audio context when done
            if (context.state !== 'closed') {
                if (context.close) context.close();
            }
            
            // Hash the buffer data
            return this.hashCode(Array.from(buffer).join(','));
        } catch (e) {
            return 'audio-error';
        }
    }
    
    /**
     * Simple hash function for strings
     * @param {string} str String to hash
     * @returns {string} Hashed string
     */
    static hashCode(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return hash.toString(16);
    }
    
    /**
     * SHA-256 hash function
     * @param {string} str String to hash
     * @returns {Promise<string>} SHA-256 hash
     */
    static async sha256(str) {
        try {
            // Use SubtleCrypto API if available (more secure)
            if (window.crypto && window.crypto.subtle) {
                const encoder = new TextEncoder();
                const data = encoder.encode(str);
                const hashBuffer = await window.crypto.subtle.digest('SHA-256', data);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            } else {
                // Fallback to simpler hash function
                return this.hashCode(str);
            }
        } catch (e) {
            // If crypto API fails, use fallback
            return this.hashCode(str);
        }
    }
    
    /**
     * Store the fingerprint in multiple storage locations
     * @param {string} fingerprint Device fingerprint
     */
    static storeFingerprint(fingerprint) {
        const storedValue = {
            fingerprint: fingerprint,
            timestamp: Date.now()
        };
        
        const storeData = JSON.stringify(storedValue);
        
        // Try to store in various places to ensure cross-browser access on the same device
        try {
            // LocalStorage
            localStorage.setItem('bcctap_device_id', storeData);
        } catch (e) {
            console.error('LocalStorage access denied');
        }
        
        try {
            // SessionStorage
            sessionStorage.setItem('bcctap_device_id', storeData);
        } catch (e) {
            console.error('SessionStorage access denied');
        }
        
        try {
            // IndexedDB
            this.storeInIndexedDB('bcctap_device', 'fingerprints', { id: 'current', data: storeData });
        } catch (e) {
            console.error('IndexedDB access denied');
        }
        
        // Set a cookie that's accessible across subdomains
        try {
            const domain = window.location.hostname;
            const rootDomain = domain.split('.').slice(-2).join('.');
            document.cookie = `bcctap_device=${encodeURIComponent(storeData)}; path=/; max-age=31536000; domain=.${rootDomain}`;
        } catch (e) {
            console.error('Cookie access denied');
        }
    }
    
    /**
     * Store data in IndexedDB
     * @param {string} dbName Database name
     * @param {string} storeName Store name
     * @param {object} data Data to store
     * @returns {Promise<void>}
     */
    static storeInIndexedDB(dbName, storeName, data) {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject('IndexedDB not supported');
                return;
            }
            
            const request = indexedDB.open(dbName, 1);
            
            request.onupgradeneeded = function(event) {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'id' });
                }
            };
            
            request.onsuccess = function(event) {
                const db = event.target.result;
                const transaction = db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                
                store.put(data);
                
                transaction.oncomplete = function() {
                    db.close();
                    resolve();
                };
                
                transaction.onerror = function(e) {
                    reject(e);
                };
            };
            
            request.onerror = function(event) {
                reject('Error opening IndexedDB');
            };
        });
    }
    
    /**
     * Retrieve the stored fingerprint from any available storage
     * @returns {Promise<string|null>} Stored fingerprint or null if not found
     */
    static async getStoredFingerprint() {
        // Try localStorage first
        let storedData = localStorage.getItem('bcctap_device_id');
        if (storedData) {
            try {
                return JSON.parse(storedData).fingerprint;
            } catch (e) {
                // Invalid data
            }
        }
        
        // Try cookies
        const cookieData = this.getCookieValue('bcctap_device');
        if (cookieData) {
            try {
                return JSON.parse(decodeURIComponent(cookieData)).fingerprint;
            } catch (e) {
                // Invalid data
            }
        }
        
        // Try indexedDB
        try {
            const idbData = await this.getFromIndexedDB('bcctap_device', 'fingerprints', 'current');
            if (idbData) {
                return JSON.parse(idbData.data).fingerprint;
            }
        } catch (e) {
            // IndexedDB access issue
        }
        
        // Try session storage as a last resort
        storedData = sessionStorage.getItem('bcctap_device_id');
        if (storedData) {
            try {
                return JSON.parse(storedData).fingerprint;
            } catch (e) {
                // Invalid data
            }
        }
        
        return null;
    }
    
    /**
     * Get data from IndexedDB
     * @param {string} dbName Database name
     * @param {string} storeName Store name
     * @param {string} key Key to retrieve
     * @returns {Promise<any>} Retrieved data or null
     */
    static getFromIndexedDB(dbName, storeName, key) {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject('IndexedDB not supported');
                return;
            }
            
            const request = indexedDB.open(dbName, 1);
            
            request.onupgradeneeded = function(event) {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'id' });
                }
            };
            
            request.onsuccess = function(event) {
                const db = event.target.result;
                try {
                    const transaction = db.transaction([storeName]);
                    const store = transaction.objectStore(storeName);
                    const getRequest = store.get(key);
                    
                    getRequest.onsuccess = function() {
                        resolve(getRequest.result);
                    };
                    
                    getRequest.onerror = function(e) {
                        resolve(null);
                    };
                } catch (e) {
                    resolve(null);
                }
            };
            
            request.onerror = function(event) {
                resolve(null);
            };
        });
    }
    
    /**
     * Get cookie value by name
     * @param {string} name Cookie name
     * @returns {string|null} Cookie value or null
     */
    static getCookieValue(name) {
        const match = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return match ? match.pop() : null;
    }
    
    /**
     * Generate a basic fingerprint as fallback
     * @returns {string} Basic fingerprint
     */
    static generateBasicFingerprint() {
        // Combine the most reliable cross-browser properties
        const basic = {
            screen: window.screen.width + 'x' + window.screen.height,
            colorDepth: window.screen.colorDepth,
            timezone: new Date().getTimezoneOffset(),
            platform: navigator.platform || 'unknown'
        };
        
        return this.hashCode(JSON.stringify(basic));
    }
    
    /**
     * Check if current device matches stored fingerprint
     * @returns {Promise<boolean>} True if device matches
     */
    static async verifyDevice() {
        const storedFingerprint = await this.getStoredFingerprint();
        if (!storedFingerprint) return false;
        
        const currentFingerprint = await this.generate();
        return storedFingerprint === currentFingerprint;
    }
}

// Global function to initialize device fingerprinting
async function initDeviceVerification() {
    try {
        const fingerprint = await DeviceFingerprint.generate();
        
        // Send fingerprint to server for verification
        const formData = new FormData();
        formData.append('action', 'verify_device');
        formData.append('fingerprint', fingerprint);
        
        const response = await fetch('verify_device.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            return result;
        }
    } catch (error) {
        console.error('Device verification failed:', error);
    }
    
    return { status: 'error' };
}

// Auto-initialize on page load if verifyOnLoad is set
if (typeof verifyOnLoad !== 'undefined' && verifyOnLoad) {
    document.addEventListener('DOMContentLoaded', () => {
        initDeviceVerification();
    });
} 