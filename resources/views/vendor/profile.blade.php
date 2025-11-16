@extends('layouts.app')

@section('title', 'Profile & Settings')

@section('sidebar')
    @include('vendor.partials.sidebar')
@endsection

@section('content')
<div x-data="profileData()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Profile & Settings</h1>
        <p class="text-gray-600 mt-1">Manage your account information and preferences</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'profile'"
                :class="activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
            >
                Profile Information
            </button>
            <button
                @click="activeTab = 'company'"
                :class="activeTab === 'company' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
            >
                Company Details
            </button>
            <button
                @click="activeTab = 'security'"
                :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
            >
                Security
            </button>
            <button
                @click="activeTab = 'preferences'"
                :class="activeTab === 'preferences' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
            >
                Preferences
            </button>
        </nav>
    </div>

    <!-- Profile Information Tab -->
    <div x-show="activeTab === 'profile'" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Personal Information</h2>
            <form @submit.prevent="updateProfile()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <input
                            type="text"
                            x-model="profileForm.first_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input
                            type="text"
                            x-model="profileForm.last_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input
                            type="email"
                            x-model="profileForm.email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input
                            type="tel"
                            x-model="profileForm.phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Title</label>
                        <input
                            type="text"
                            x-model="profileForm.job_title"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Company Details Tab -->
    <div x-show="activeTab === 'company'" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Company Information</h2>
            <form @submit.prevent="updateCompany()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                        <input
                            type="text"
                            x-model="companyForm.company_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID</label>
                        <input
                            type="text"
                            x-model="companyForm.tax_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                        <select
                            x-model="companyForm.industry"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Industry</option>
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="manufacturing">Manufacturing</option>
                            <option value="services">Services</option>
                            <option value="technology">Technology</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                        <input
                            type="text"
                            x-model="companyForm.street_address"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <input
                            type="text"
                            x-model="companyForm.city"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                        <input
                            type="text"
                            x-model="companyForm.postal_code"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                        <input
                            type="text"
                            x-model="companyForm.state"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <input
                            type="text"
                            x-model="companyForm.country"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Website</label>
                        <input
                            type="url"
                            x-model="companyForm.website"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="https://example.com"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Tab -->
    <div x-show="activeTab === 'security'" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Change Password</h2>
            <form @submit.prevent="changePassword()">
                <div class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input
                            type="password"
                            x-model="passwordForm.current_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input
                            type="password"
                            x-model="passwordForm.new_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                            minlength="8"
                        >
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input
                            type="password"
                            x-model="passwordForm.confirm_password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>
                </div>

                <div class="mt-6">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span x-show="!saving">Update Password</span>
                        <span x-show="saving">Updating...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Two-Factor Authentication</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">Two-Factor Authentication</p>
                    <p class="text-sm text-gray-600 mt-1">Add an extra layer of security to your account</p>
                </div>
                <button
                    @click="toggle2FA()"
                    :class="twoFactorEnabled ? 'bg-blue-600' : 'bg-gray-300'"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <span
                        :class="twoFactorEnabled ? 'translate-x-5' : 'translate-x-0'"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    ></span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Active Sessions</h2>
            <p class="text-sm text-gray-600 mb-4">Manage devices where you're currently signed in</p>
            <div class="space-y-3">
                <template x-for="session in activeSessions" :key="session.id">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900" x-text="session.device"></p>
                                <p class="text-xs text-gray-500" x-text="session.location + ' • ' + session.last_active"></p>
                            </div>
                        </div>
                        <button
                            x-show="!session.current"
                            @click="revokeSession(session.id)"
                            class="text-sm text-red-600 hover:underline"
                        >
                            Revoke
                        </button>
                        <span x-show="session.current" class="text-xs text-green-600 font-semibold">Current</span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Preferences Tab -->
    <div x-show="activeTab === 'preferences'" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Notification Settings</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Email Notifications</p>
                        <p class="text-sm text-gray-600">Receive email updates about your orders</p>
                    </div>
                    <input type="checkbox" x-model="preferences.email_notifications" class="h-4 w-4 text-blue-600 rounded">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Order Updates</p>
                        <p class="text-sm text-gray-600">Get notified when order status changes</p>
                    </div>
                    <input type="checkbox" x-model="preferences.order_updates" class="h-4 w-4 text-blue-600 rounded">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Product Recommendations</p>
                        <p class="text-sm text-gray-600">Receive AI-powered product suggestions</p>
                    </div>
                    <input type="checkbox" x-model="preferences.recommendations" class="h-4 w-4 text-blue-600 rounded">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Marketing Emails</p>
                        <p class="text-sm text-gray-600">Receive promotional offers and news</p>
                    </div>
                    <input type="checkbox" x-model="preferences.marketing_emails" class="h-4 w-4 text-blue-600 rounded">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    @click="savePreferences()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Save Preferences
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Display Settings</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                    <select
                        x-model="preferences.language"
                        class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="ar">العربية</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                    <select
                        x-model="preferences.timezone"
                        class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="Africa/Casablanca">Africa/Casablanca (GMT+1)</option>
                        <option value="Europe/Paris">Europe/Paris (GMT+1)</option>
                        <option value="Europe/London">Europe/London (GMT)</option>
                        <option value="America/New_York">America/New York (GMT-5)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency Display</label>
                    <select
                        x-model="preferences.currency"
                        class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="MAD">Moroccan Dirham (MAD)</option>
                        <option value="EUR">Euro (€)</option>
                        <option value="USD">US Dollar ($)</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    @click="savePreferences()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Save Preferences
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileData() {
    return {
        activeTab: 'profile',
        saving: false,
        twoFactorEnabled: false,
        profileForm: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            job_title: ''
        },
        companyForm: {
            company_name: '',
            tax_id: '',
            industry: '',
            street_address: '',
            city: '',
            postal_code: '',
            state: '',
            country: '',
            website: ''
        },
        passwordForm: {
            current_password: '',
            new_password: '',
            confirm_password: ''
        },
        preferences: {
            email_notifications: true,
            order_updates: true,
            recommendations: true,
            marketing_emails: false,
            language: 'en',
            timezone: 'Africa/Casablanca',
            currency: 'MAD'
        },
        activeSessions: [
            {
                id: 1,
                device: 'Chrome on Windows',
                location: 'Casablanca, Morocco',
                last_active: 'Active now',
                current: true
            }
        ],

        async init() {
            await this.loadProfile();
        },

        async loadProfile() {
            try {
                const user = JSON.parse(localStorage.getItem('user') || '{}');

                // Load profile data
                this.profileForm = {
                    first_name: user.first_name || '',
                    last_name: user.last_name || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    job_title: user.job_title || ''
                };

                // Load company data from vendor profile
                const vendorData = await api.getVendorProfile();
                if (vendorData) {
                    this.companyForm = {
                        company_name: vendorData.company_name || '',
                        tax_id: vendorData.tax_id || '',
                        industry: vendorData.industry || '',
                        street_address: vendorData.street_address || '',
                        city: vendorData.city || '',
                        postal_code: vendorData.postal_code || '',
                        state: vendorData.state || '',
                        country: vendorData.country || 'Morocco',
                        website: vendorData.website || ''
                    };
                }
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async updateProfile() {
            this.saving = true;
            try {
                await api.client.put('/vendor/profile', this.profileForm);

                // Update localStorage
                const user = JSON.parse(localStorage.getItem('user') || '{}');
                Object.assign(user, this.profileForm);
                localStorage.setItem('user', JSON.stringify(user));

                alert('Profile updated successfully!');
            } catch (error) {
                console.error('Failed to update profile:', error);
                alert('Failed to update profile. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async updateCompany() {
            this.saving = true;
            try {
                await api.client.put('/vendor/company', this.companyForm);
                alert('Company details updated successfully!');
            } catch (error) {
                console.error('Failed to update company:', error);
                alert('Failed to update company details. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async changePassword() {
            if (this.passwordForm.new_password !== this.passwordForm.confirm_password) {
                alert('New passwords do not match!');
                return;
            }

            this.saving = true;
            try {
                await api.client.post('/vendor/change-password', {
                    current_password: this.passwordForm.current_password,
                    new_password: this.passwordForm.new_password
                });

                this.passwordForm = {
                    current_password: '',
                    new_password: '',
                    confirm_password: ''
                };

                alert('Password changed successfully!');
            } catch (error) {
                console.error('Failed to change password:', error);
                alert('Failed to change password. Please check your current password.');
            } finally {
                this.saving = false;
            }
        },

        async toggle2FA() {
            try {
                const action = this.twoFactorEnabled ? 'disable' : 'enable';
                await api.client.post(`/vendor/2fa/${action}`);
                this.twoFactorEnabled = !this.twoFactorEnabled;
                alert(`Two-factor authentication ${this.twoFactorEnabled ? 'enabled' : 'disabled'} successfully!`);
            } catch (error) {
                console.error('Failed to toggle 2FA:', error);
                alert('Failed to update two-factor authentication. Please try again.');
            }
        },

        async revokeSession(sessionId) {
            if (!confirm('Are you sure you want to revoke this session?')) return;

            try {
                await api.client.delete(`/vendor/sessions/${sessionId}`);
                this.activeSessions = this.activeSessions.filter(s => s.id !== sessionId);
                alert('Session revoked successfully!');
            } catch (error) {
                console.error('Failed to revoke session:', error);
                alert('Failed to revoke session. Please try again.');
            }
        },

        async savePreferences() {
            try {
                await api.client.put('/vendor/preferences', this.preferences);
                alert('Preferences saved successfully!');
            } catch (error) {
                console.error('Failed to save preferences:', error);
                alert('Failed to save preferences. Please try again.');
            }
        }
    };
}
</script>
@endpush
