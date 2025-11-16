<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - B2B Platform</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <!-- 404 Illustration -->
            <div class="mb-8">
                <svg class="w-64 h-64 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <!-- Error Code -->
            <h1 class="text-9xl font-bold text-gray-900 mb-4">404</h1>

            <!-- Error Message -->
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Page Not Found</h2>
            <p class="text-gray-600 text-lg mb-8">
                Oops! The page you're looking for doesn't exist. It might have been moved or deleted.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="javascript:history.back()"
                    class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors"
                >
                    Go Back
                </a>
                <a
                    href="/vendor/dashboard"
                    class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors"
                >
                    Go to Dashboard
                </a>
            </div>

            <!-- Helpful Links -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-4">You might be looking for:</p>
                <div class="flex flex-wrap justify-center gap-3 text-sm">
                    <a href="/vendor/products" class="text-blue-600 hover:underline">Products</a>
                    <span class="text-gray-400">•</span>
                    <a href="/vendor/orders" class="text-blue-600 hover:underline">Orders</a>
                    <span class="text-gray-400">•</span>
                    <a href="/vendor/analytics" class="text-blue-600 hover:underline">Analytics</a>
                    <span class="text-gray-400">•</span>
                    <a href="/vendor/profile" class="text-blue-600 hover:underline">Profile</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-white text-sm mt-8">
            Need help? <a href="mailto:support@b2bplatform.com" class="underline hover:text-gray-200">Contact Support</a>
        </p>
    </div>
</body>
</html>
