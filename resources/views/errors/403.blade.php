<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Forbidden - B2B Platform</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-yellow-500 to-red-500 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <!-- 403 Illustration -->
            <div class="mb-8">
                <svg class="w-64 h-64 mx-auto text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <!-- Error Code -->
            <h1 class="text-9xl font-bold text-gray-900 mb-4">403</h1>

            <!-- Error Message -->
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Access Forbidden</h2>
            <p class="text-gray-600 text-lg mb-8">
                You don't have permission to access this resource. Please contact your administrator if you believe this is an error.
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
                    class="px-8 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-semibold transition-colors"
                >
                    Go to Dashboard
                </a>
            </div>

            <!-- Support Information -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-900">
                        If you need access to this resource, please contact your administrator or support team.
                    </p>
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
