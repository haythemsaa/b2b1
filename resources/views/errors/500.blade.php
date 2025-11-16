<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error - B2B Platform</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-br from-red-500 to-orange-600 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <!-- 500 Illustration -->
            <div class="mb-8">
                <svg class="w-64 h-64 mx-auto text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <!-- Error Code -->
            <h1 class="text-9xl font-bold text-gray-900 mb-4">500</h1>

            <!-- Error Message -->
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Internal Server Error</h2>
            <p class="text-gray-600 text-lg mb-8">
                We're sorry! Something went wrong on our end. Our team has been notified and we're working to fix it.
            </p>

            <!-- Error Details (if available in debug mode) -->
            @if(config('app.debug') && isset($exception))
                <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg text-left">
                    <p class="text-sm font-semibold text-red-900 mb-2">Error Details (Debug Mode):</p>
                    <p class="text-xs text-red-700 font-mono break-all">{{ $exception->getMessage() }}</p>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    onclick="window.location.reload()"
                    class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors"
                >
                    Try Again
                </button>
                <a
                    href="/vendor/dashboard"
                    class="px-8 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors"
                >
                    Go to Dashboard
                </a>
            </div>

            <!-- Support Information -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-blue-900">Need immediate assistance?</p>
                            <p class="text-xs text-blue-700 mt-1">
                                If the problem persists, please contact our support team with the error code and timestamp.
                            </p>
                            <div class="mt-3 text-xs text-blue-800">
                                <p><strong>Error ID:</strong> {{ uniqid('ERR-') }}</p>
                                <p><strong>Timestamp:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-white text-sm mt-8">
            Contact us: <a href="mailto:support@b2bplatform.com" class="underline hover:text-gray-200">support@b2bplatform.com</a>
        </p>
    </div>
</body>
</html>
