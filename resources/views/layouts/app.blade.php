<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'B2B Wholesale Platform')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 px-4 bg-gray-800">
                    <h1 class="text-xl font-bold text-white">B2B Platform</h1>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 overflow-y-auto">
                    @yield('sidebar')
                </nav>

                <!-- User Menu -->
                <div class="p-4 bg-gray-800">
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center w-full px-3 py-2 text-sm text-white rounded-lg hover:bg-gray-700"
                        >
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <span id="user-name" class="flex-1 text-left">User</span>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>

                        <div
                            x-show="userMenuOpen"
                            @click.away="userMenuOpen = false"
                            x-transition
                            class="absolute bottom-full left-0 w-full mb-2 bg-white rounded-lg shadow-lg"
                        >
                            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                Profile
                            </a>
                            <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Settings
                            </a>
                            <button
                                @click="logout()"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 rounded-b-lg"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar overlay -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-600 bg-opacity-75 lg:hidden"
        ></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-4 py-3">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-2 text-gray-600 rounded-lg lg:hidden hover:bg-gray-100"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div x-data="{ notificationOpen: false, unreadCount: 0 }" x-init="loadUnreadCount()" class="relative">
                            <button
                                @click="notificationOpen = !notificationOpen"
                                class="relative p-2 text-gray-600 rounded-lg hover:bg-gray-100"
                            >
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                </svg>
                                <span
                                    x-show="unreadCount > 0"
                                    x-text="unreadCount"
                                    class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
                                ></span>
                            </button>

                            <div
                                x-show="notificationOpen"
                                @click.away="notificationOpen = false"
                                x-transition
                                class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg overflow-hidden z-50"
                            >
                                <div class="p-4 border-b">
                                    <h3 class="text-lg font-semibold">Notifications</h3>
                                </div>
                                <div id="notifications-list" class="max-h-96 overflow-y-auto">
                                    <!-- Notifications will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Initialize user data
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (user.name) {
                document.getElementById('user-name').textContent = user.name;
            }
        });

        // Logout function
        async function logout() {
            try {
                await api.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                window.location.href = '/login';
            }
        }

        // Load unread count
        async function loadUnreadCount() {
            try {
                const data = await api.getUnreadCount();
                this.unreadCount = data.unread_count || 0;

                // Load notifications
                const notifications = await api.getNotifications({ limit: 10 });
                const list = document.getElementById('notifications-list');
                if (notifications.data && notifications.data.length > 0) {
                    list.innerHTML = notifications.data.map(n => `
                        <div class="p-4 border-b hover:bg-gray-50 ${n.read_at ? '' : 'bg-blue-50'}">
                            <p class="text-sm font-medium">${n.title}</p>
                            <p class="text-xs text-gray-600 mt-1">${n.message}</p>
                            <p class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                }
            } catch (error) {
                console.error('Failed to load notifications:', error);
                this.unreadCount = 0;
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
