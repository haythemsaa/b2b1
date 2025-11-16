import axios from 'axios';

class ApiClient {
    constructor() {
        this.client = axios.create({
            baseURL: '/api',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        });

        // Add token to requests
        this.client.interceptors.request.use(config => {
            const token = localStorage.getItem('auth_token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        });

        // Handle 401 responses
        this.client.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    window.location.href = '/login';
                }
                return Promise.reject(error);
            }
        );
    }

    // Auth
    async login(email, password) {
        const response = await this.client.post('/auth/login', { email, password });
        if (response.data.token) {
            localStorage.setItem('auth_token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user));
        }
        return response.data;
    }

    async logout() {
        try {
            await this.client.post('/auth/logout');
        } finally {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
        }
    }

    async me() {
        const response = await this.client.get('/auth/me');
        return response.data;
    }

    // Products
    async getProducts(params = {}) {
        const response = await this.client.get('/vendor/products', { params });
        return response.data;
    }

    async getProduct(id) {
        const response = await this.client.get(`/vendor/products/${id}`);
        return response.data;
    }

    async searchProducts(query, params = {}) {
        const response = await this.client.get('/vendor/products/search', {
            params: { query, ...params }
        });
        return response.data;
    }

    // Orders
    async getOrders(params = {}) {
        const response = await this.client.get('/vendor/orders', { params });
        return response.data;
    }

    async getOrder(id) {
        const response = await this.client.get(`/vendor/orders/${id}`);
        return response.data;
    }

    async createOrder(orderData) {
        const response = await this.client.post('/vendor/orders', orderData);
        return response.data;
    }

    async getOrderStats() {
        const response = await this.client.get('/vendor/orders/stats');
        return response.data;
    }

    async reorder(orderId) {
        const response = await this.client.post(`/vendor/orders/${orderId}/reorder`);
        return response.data;
    }

    // RFQs
    async getRfqs(params = {}) {
        const response = await this.client.get('/vendor/rfqs', { params });
        return response.data;
    }

    async getRfq(id) {
        const response = await this.client.get(`/vendor/rfqs/${id}`);
        return response.data;
    }

    async createRfq(rfqData) {
        const response = await this.client.post('/vendor/rfqs', rfqData);
        return response.data;
    }

    async acceptQuote(rfqId, quoteId) {
        const response = await this.client.post(`/vendor/rfqs/${rfqId}/quotes/${quoteId}/accept`);
        return response.data;
    }

    // Analytics
    async getDashboard(period = 'monthly') {
        const response = await this.client.get('/vendor/analytics/dashboard', {
            params: { period }
        });
        return response.data;
    }

    async getTopProducts(params = {}) {
        const response = await this.client.get('/vendor/analytics/top-products', { params });
        return response.data;
    }

    // Recommendations
    async getPersonalizedRecommendations() {
        const response = await this.client.get('/vendor/recommendations/personalized');
        return response.data;
    }

    async getTrendingProducts() {
        const response = await this.client.get('/vendor/recommendations/trending');
        return response.data;
    }

    async getProductRecommendations(productId) {
        const response = await this.client.get(`/vendor/recommendations/product/${productId}`);
        return response.data;
    }

    // Predictions
    async getOrderPredictions() {
        const response = await this.client.get('/vendor/predictions');
        return response.data;
    }

    async generatePredictions() {
        const response = await this.client.post('/vendor/predictions/generate');
        return response.data;
    }

    // Notifications
    async getNotifications(params = {}) {
        const response = await this.client.get('/vendor/notifications', { params });
        return response.data;
    }

    async getUnreadCount() {
        const response = await this.client.get('/vendor/notifications/unread-count');
        return response.data;
    }

    async markAsRead(notificationId) {
        const response = await this.client.post(`/vendor/notifications/${notificationId}/mark-read`);
        return response.data;
    }

    async markAllAsRead() {
        const response = await this.client.post('/vendor/notifications/mark-all-read');
        return response.data;
    }

    // Account Management
    async getAccountUsers() {
        const response = await this.client.get('/vendor/account');
        return response.data;
    }

    async createAccountUser(userData) {
        const response = await this.client.post('/vendor/account', userData);
        return response.data;
    }

    // Approvals
    async getApprovalWorkflows() {
        const response = await this.client.get('/vendor/approvals/workflows');
        return response.data;
    }

    async getPendingApprovals() {
        const response = await this.client.get('/vendor/approvals/pending');
        return response.data;
    }

    async approveRequest(requestId, comments = null) {
        const response = await this.client.post(`/vendor/approvals/requests/${requestId}/approve`, {
            comments
        });
        return response.data;
    }

    async rejectRequest(requestId, reason) {
        const response = await this.client.post(`/vendor/approvals/requests/${requestId}/reject`, {
            reason
        });
        return response.data;
    }

    // Documents
    async getDocuments(params = {}) {
        const response = await this.client.get('/vendor/documents', { params });
        return response.data;
    }

    async uploadDocument(formData) {
        const response = await this.client.post('/vendor/documents', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        return response.data;
    }

    async downloadDocument(documentId) {
        const response = await this.client.get(`/vendor/documents/${documentId}/download`, {
            responseType: 'blob'
        });
        return response.data;
    }

    // Admin - Vendors
    async adminGetVendors(params = {}) {
        const response = await this.client.get('/admin/vendors', { params });
        return response.data;
    }

    async adminGetVendor(id) {
        const response = await this.client.get(`/admin/vendors/${id}`);
        return response.data;
    }

    async adminCreateVendor(vendorData) {
        const response = await this.client.post('/admin/vendors', vendorData);
        return response.data;
    }

    async adminUpdateVendor(id, vendorData) {
        const response = await this.client.put(`/admin/vendors/${id}`, vendorData);
        return response.data;
    }

    // Admin - Products
    async adminGetProducts(params = {}) {
        const response = await this.client.get('/admin/products', { params });
        return response.data;
    }

    async adminCreateProduct(productData) {
        const response = await this.client.post('/admin/products', productData);
        return response.data;
    }

    async adminUpdateProduct(id, productData) {
        const response = await this.client.put(`/admin/products/${id}`, productData);
        return response.data;
    }

    async adminGetInventoryStats() {
        const response = await this.client.get('/admin/products/inventory-stats');
        return response.data;
    }

    // Admin - Orders
    async adminGetOrders(params = {}) {
        const response = await this.client.get('/admin/orders', { params });
        return response.data;
    }

    async adminGetOrder(id) {
        const response = await this.client.get(`/admin/orders/${id}`);
        return response.data;
    }

    async adminConfirmOrder(id) {
        const response = await this.client.post(`/admin/orders/${id}/confirm`);
        return response.data;
    }

    async adminShipOrder(id, trackingData) {
        const response = await this.client.post(`/admin/orders/${id}/ship`, trackingData);
        return response.data;
    }

    async adminGetOrderStats() {
        const response = await this.client.get('/admin/orders/stats');
        return response.data;
    }
}

// Export singleton instance
const api = new ApiClient();
export default api;

// Also make it available globally
window.api = api;
