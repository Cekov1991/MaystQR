<div class="space-y-4">
    <div class="text-lg font-medium">Monthly Subscription Cost</div>

    <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
            ${{ number_format($totalPrice, 2) }}/month
        </div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Next billing date: {{ auth()->user()->subscription->next_billing_date->format('M d, Y') }}
        </div>
    </div>

    <div class="mt-4">
        <div class="text-sm font-medium mb-2">Current Usage</div>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <div>QR Codes: {{ auth()->user()->qrCodes()->where('type', 'dynamic')->count() }}</div>
            <div>Monthly Scans: {{ auth()->user()->qrCodes()->sum('scan_count') }} / {{ auth()->user()->subscription->monthly_scan_limit }}</div>
        </div>
    </div>

    <div class="text-sm text-gray-500 dark:text-gray-400">
        <div class="font-medium mb-1">Free Tier Includes:</div>
        <ul class="list-disc list-inside space-y-1">
            <li>One dynamic QR code</li>
            <li>1,000 monthly scans</li>
            <li>Basic analytics</li>
            <li>Standard templates</li>
        </ul>
    </div>
</div>
