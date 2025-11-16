/**
 * Utility Functions for B2B Wholesale Platform
 * Helper functions for common operations
 */

/**
 * Format currency amount
 * @param {number} amount - The amount to format
 * @param {string} currency - Currency code (default: MAD)
 * @param {string} locale - Locale for formatting (default: fr-FR)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currency = 'MAD', locale = 'fr-FR') {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
}

/**
 * Format date
 * @param {string|Date} date - The date to format
 * @param {object} options - Intl.DateTimeFormat options
 * @returns {string} Formatted date string
 */
export function formatDate(date, options = {}) {
    if (!date) return '';

    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...options
    };

    return new Date(date).toLocaleDateString('en-US', defaultOptions);
}

/**
 * Format date with time
 * @param {string|Date} date - The date to format
 * @returns {string} Formatted date and time string
 */
export function formatDateTime(date) {
    if (!date) return '';

    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Format relative time (e.g., "2 hours ago")
 * @param {string|Date} date - The date to format
 * @returns {string} Relative time string
 */
export function formatRelativeTime(date) {
    if (!date) return '';

    const now = new Date();
    const past = new Date(date);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 604800)} weeks ago`;

    return formatDate(date);
}

/**
 * Format number with thousands separator
 * @param {number} num - The number to format
 * @returns {string} Formatted number string
 */
export function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(num || 0);
}

/**
 * Format percentage
 * @param {number} value - The value to format as percentage
 * @param {number} decimals - Number of decimal places (default: 1)
 * @returns {string} Formatted percentage string
 */
export function formatPercentage(value, decimals = 1) {
    return (value || 0).toFixed(decimals) + '%';
}

/**
 * Truncate text with ellipsis
 * @param {string} text - The text to truncate
 * @param {number} maxLength - Maximum length before truncation
 * @returns {string} Truncated text
 */
export function truncate(text, maxLength = 50) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

/**
 * Debounce function
 * @param {Function} func - The function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 * @param {Function} func - The function to throttle
 * @param {number} limit - Limit in milliseconds
 * @returns {Function} Throttled function
 */
export function throttle(func, limit = 300) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Get status badge class
 * @param {string} status - The status value
 * @param {string} type - Type of entity (order, product, etc.)
 * @returns {string} CSS classes for badge
 */
export function getStatusBadgeClass(status, type = 'order') {
    const statusClasses = {
        order: {
            pending: 'bg-yellow-100 text-yellow-800',
            confirmed: 'bg-blue-100 text-blue-800',
            processing: 'bg-purple-100 text-purple-800',
            shipped: 'bg-indigo-100 text-indigo-800',
            delivered: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800'
        },
        payment: {
            pending: 'bg-yellow-100 text-yellow-800',
            paid: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
            refunded: 'bg-gray-100 text-gray-800'
        },
        stock: {
            in_stock: 'bg-green-100 text-green-800',
            low_stock: 'bg-orange-100 text-orange-800',
            out_of_stock: 'bg-red-100 text-red-800'
        },
        vendor: {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            suspended: 'bg-red-100 text-red-800'
        }
    };

    return statusClasses[type]?.[status] || 'bg-gray-100 text-gray-800';
}

/**
 * Get stock status from quantity
 * @param {number} stock - Stock quantity
 * @returns {object} Status object with label and class
 */
export function getStockStatus(stock) {
    if (stock === 0) {
        return {
            label: 'Out of Stock',
            class: 'bg-red-100 text-red-800',
            textClass: 'text-red-700',
            bgClass: 'bg-red-50'
        };
    }
    if (stock <= 20) {
        return {
            label: 'Low Stock',
            class: 'bg-orange-100 text-orange-800',
            textClass: 'text-orange-700',
            bgClass: 'bg-orange-50'
        };
    }
    return {
        label: 'In Stock',
        class: 'bg-green-100 text-green-800',
        textClass: 'text-green-700',
        bgClass: 'bg-green-50'
    };
}

/**
 * Calculate trend percentage
 * @param {number} current - Current value
 * @param {number} previous - Previous value
 * @returns {object} Trend object with value, direction, and class
 */
export function calculateTrend(current, previous) {
    if (!previous || previous === 0) {
        return { value: 0, direction: 'neutral', class: 'text-gray-600' };
    }

    const change = ((current - previous) / previous) * 100;

    return {
        value: Math.abs(change).toFixed(1),
        direction: change > 0 ? 'up' : change < 0 ? 'down' : 'neutral',
        class: change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-600',
        icon: change > 0 ? '↑' : change < 0 ? '↓' : '→'
    };
}

/**
 * Validate email address
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid
 */
export function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number (Morocco format)
 * @param {string} phone - Phone number to validate
 * @returns {boolean} True if valid
 */
export function validatePhone(phone) {
    const re = /^(?:(?:\+|00)212|0)[5-7]\d{8}$/;
    return re.test(phone.replace(/\s/g, ''));
}

/**
 * Generate random ID
 * @param {number} length - Length of ID
 * @returns {string} Random ID
 */
export function generateId(length = 8) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Deep clone object
 * @param {object} obj - Object to clone
 * @returns {object} Cloned object
 */
export function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

/**
 * Download data as file
 * @param {string} data - Data to download
 * @param {string} filename - Filename
 * @param {string} type - MIME type
 */
export function downloadFile(data, filename, type = 'text/plain') {
    const blob = new Blob([data], { type });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 * @returns {Promise} Promise that resolves when copied
 */
export function copyToClipboard(text) {
    if (navigator.clipboard) {
        return navigator.clipboard.writeText(text);
    }

    // Fallback for older browsers
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    return Promise.resolve();
}

/**
 * Get query parameter from URL
 * @param {string} param - Parameter name
 * @returns {string|null} Parameter value
 */
export function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Set query parameter in URL
 * @param {string} param - Parameter name
 * @param {string} value - Parameter value
 */
export function setQueryParam(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    window.history.pushState({}, '', url);
}

/**
 * Convert object to query string
 * @param {object} params - Parameters object
 * @returns {string} Query string
 */
export function objectToQueryString(params) {
    return Object.keys(params)
        .filter(key => params[key] !== null && params[key] !== undefined && params[key] !== '')
        .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
        .join('&');
}

/**
 * Local storage helper with JSON support
 */
export const storage = {
    get(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return null;
        }
    },

    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Error writing to localStorage:', error);
        }
    },

    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Error removing from localStorage:', error);
        }
    },

    clear() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('Error clearing localStorage:', error);
        }
    }
};

/**
 * Session storage helper with JSON support
 */
export const sessionStorage = {
    get(key) {
        try {
            const item = window.sessionStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Error reading from sessionStorage:', error);
            return null;
        }
    },

    set(key, value) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Error writing to sessionStorage:', error);
        }
    },

    remove(key) {
        try {
            window.sessionStorage.removeItem(key);
        } catch (error) {
            console.error('Error removing from sessionStorage:', error);
        }
    },

    clear() {
        try {
            window.sessionStorage.clear();
        } catch (error) {
            console.error('Error clearing sessionStorage:', error);
        }
    }
};

/**
 * Check if user is authenticated
 * @returns {boolean} True if authenticated
 */
export function isAuthenticated() {
    return !!storage.get('auth_token');
}

/**
 * Get current user from storage
 * @returns {object|null} User object or null
 */
export function getCurrentUser() {
    return storage.get('user');
}

/**
 * Logout user
 */
export function logout() {
    storage.remove('auth_token');
    storage.remove('user');
    window.location.href = '/login';
}

// Make utilities globally available
if (typeof window !== 'undefined') {
    window.utils = {
        formatCurrency,
        formatDate,
        formatDateTime,
        formatRelativeTime,
        formatNumber,
        formatPercentage,
        truncate,
        debounce,
        throttle,
        getStatusBadgeClass,
        getStockStatus,
        calculateTrend,
        validateEmail,
        validatePhone,
        generateId,
        deepClone,
        downloadFile,
        copyToClipboard,
        getQueryParam,
        setQueryParam,
        objectToQueryString,
        storage,
        sessionStorage,
        isAuthenticated,
        getCurrentUser,
        logout
    };
}
