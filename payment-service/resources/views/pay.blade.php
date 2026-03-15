<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice — PayIn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: { fontFamily: { sans: ['Poppins', 'system-ui', 'sans-serif'] } } }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen font-sans flex items-center justify-center p-4">

<div x-data="paymentPage()" x-init="init()" class="w-full max-w-md">

    <!-- Brand -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-extrabold text-gray-800 tracking-wide">Pay<span class="text-amber-500">In</span></h1>
        <p class="text-xs text-gray-500 mt-1">Secure Mobile Payment</p>
    </div>

    <!-- Invoice Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5 text-center">
            <svg class="w-10 h-10 text-white mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            <h2 class="text-lg font-bold text-white">Payment Invoice</h2>
            <p class="text-blue-100 text-xs mt-1" x-text="invoice.business_name || ''"></p>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="p-10 text-center">
            <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <p class="text-sm text-gray-500 mt-3">Loading invoice...</p>
        </div>

        <!-- Error -->
        <div x-show="error && !loading" x-cloak class="p-8 text-center">
            <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <p class="text-sm font-medium text-red-600" x-text="error"></p>
        </div>

        <!-- Invoice Details -->
        <div x-show="!loading && !error && step === 'details'" x-cloak>
            <div class="px-6 py-5 space-y-3">
                <!-- Amount -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                    <div class="text-xs font-medium text-blue-500 mb-1">Amount to Pay</div>
                    <div class="text-3xl font-bold text-gray-900" x-text="formatAmount(invoice.amount) + ' ' + invoice.currency"></div>
                </div>

                <div class="flex justify-between text-sm"><span class="text-gray-500">Reference</span><span class="font-semibold text-gray-800" x-text="invoice.reference"></span></div>
                <div x-show="invoice.description" class="flex justify-between text-sm"><span class="text-gray-500">Description</span><span class="font-medium text-gray-700" x-text="invoice.description"></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Expires</span><span class="font-medium text-gray-700" x-text="invoice.expires_at ? formatDate(invoice.expires_at) : 'N/A'"></span></div>
            </div>

            <!-- Phone Input -->
            <div class="px-6 pb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Phone Number</label>
                <div class="flex gap-2">
                    <input type="tel" x-model="phone" placeholder="e.g. 0712345678" maxlength="15"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        @keydown.enter="submitPayment()">
                    <button @click="submitPayment()" :disabled="submitting || !phone || phone.length < 10"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 whitespace-nowrap">
                        <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Pay
                    </button>
                </div>
                <p x-show="submitError" x-cloak class="mt-2 text-xs text-red-600" x-text="submitError"></p>
                <p class="mt-2 text-xs text-gray-400">You will receive a USSD prompt on your phone. Confirm to complete payment.</p>
            </div>
        </div>

        <!-- Processing / Waiting for confirmation -->
        <div x-show="!loading && step === 'processing'" x-cloak class="px-6 py-8 text-center">
            <div class="mb-4">
                <svg class="animate-spin h-12 w-12 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Check Your Phone</h3>
            <p class="text-sm text-gray-500 mb-4">A USSD prompt has been sent to <strong x-text="phone"></strong>. Please confirm the payment on your device.</p>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-700">
                <strong>Amount:</strong> <span x-text="formatAmount(invoice.amount) + ' ' + invoice.currency"></span>
            </div>
            <p class="text-xs text-gray-400 mt-4">Waiting for confirmation... This page will update automatically.</p>
        </div>

        <!-- Completed -->
        <div x-show="!loading && step === 'completed'" x-cloak class="px-6 py-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-bold text-green-700 mb-1">Payment Successful!</h3>
            <p class="text-sm text-gray-500 mb-4">Your payment has been confirmed.</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="font-semibold text-gray-800" x-text="formatAmount(invoice.amount) + ' ' + invoice.currency"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Reference</span><span class="font-semibold text-gray-800" x-text="invoice.reference"></span></div>
                <div x-show="paymentResult.receipt_number" class="flex justify-between"><span class="text-gray-500">Receipt</span><span class="font-semibold text-gray-800" x-text="paymentResult.receipt_number"></span></div>
            </div>
        </div>

        <!-- Failed -->
        <div x-show="!loading && step === 'failed'" x-cloak class="px-6 py-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="text-lg font-bold text-red-700 mb-1">Payment Failed</h3>
            <p class="text-sm text-gray-500 mb-4" x-text="paymentResult.error || 'The payment was not completed. Please try again.'"></p>
            <button @click="step = 'details'; submitError = ''" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">Try Again</button>
        </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-gray-400 mt-6">Powered by <strong>PayIn</strong> &middot; Secure Payment Gateway</p>
</div>

<script>
function paymentPage() {
    return {
        token: '{{ $token }}',
        loading: true,
        error: null,
        invoice: {},
        phone: '',
        submitting: false,
        submitError: '',
        step: 'details', // details → processing → completed / failed
        paymentResult: {},
        pollTimer: null,
        pollCount: 0,

        async init() {
            try {
                const res = await fetch(`/api/pay/${this.token}`);
                if (!res.ok) {
                    const data = await res.json();
                    this.error = data.message || 'Invoice not found or expired.';
                    return;
                }
                this.invoice = await res.json();
            } catch (e) {
                this.error = 'Failed to load invoice. Please try again.';
            }
            this.loading = false;
        },

        async submitPayment() {
            if (!this.phone || this.phone.length < 10) return;
            this.submitting = true;
            this.submitError = '';
            try {
                const res = await fetch(`/api/pay/${this.token}/initiate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ phone: this.phone })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.submitError = data.message || 'Failed to initiate payment.';
                    this.submitting = false;
                    return;
                }
                this.step = 'processing';
                this.startPolling();
            } catch (e) {
                this.submitError = 'Service unavailable. Please try again.';
            }
            this.submitting = false;
        },

        startPolling() {
            this.pollCount = 0;
            this.pollTimer = setInterval(async () => {
                this.pollCount++;
                try {
                    const res = await fetch(`/api/pay/${this.token}/status`);
                    if (res.ok) {
                        const data = await res.json();
                        if (data.status === 'completed') {
                            clearInterval(this.pollTimer);
                            this.paymentResult = data;
                            this.step = 'completed';
                        } else if (data.status === 'failed' || data.status === 'cancelled' || data.status === 'timeout') {
                            clearInterval(this.pollTimer);
                            this.paymentResult = data;
                            this.step = 'failed';
                        }
                    }
                } catch (e) { /* continue polling */ }

                // Stop after 2 minutes (24 polls x 5s)
                if (this.pollCount >= 24) {
                    clearInterval(this.pollTimer);
                    this.paymentResult = { error: 'Payment confirmation timed out. If you completed the payment, it will be reflected shortly.' };
                    this.step = 'failed';
                }
            }, 5000);
        },

        formatAmount(n) {
            if (!n) return '0';
            return Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>
</body>
</html>
