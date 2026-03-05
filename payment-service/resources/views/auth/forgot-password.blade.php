@extends('layouts.app')

@section('title', 'Reset Password - Payin')

@section('content')
<div class="min-h-screen flex items-center justify-center py-8" x-data="resetForm()">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg">
        <!-- Google-style color strip -->
        <div class="flex h-1 rounded-t-xl overflow-hidden mb-6 -mx-8 -mt-8">
            <div class="flex-1 bg-gblue-500"></div>
            <div class="flex-1 bg-gred-500"></div>
            <div class="flex-1 bg-gyellow-500"></div>
            <div class="flex-1 bg-ggreen-500"></div>
        </div>

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gyellow-50 rounded-full mb-4">
                <svg class="w-8 h-8 text-gyellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
            <p class="text-gray-500 mt-1" x-text="stepDescription"></p>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="error"></div>

        <!-- Success Message -->
        <div x-show="success" x-cloak class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm" x-text="success"></div>

        <!-- Step 1: Enter Email -->
        <form x-show="step === 1" @submit.prevent="sendCode" x-cloak>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" x-model="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Send Reset Code</span>
                <span x-show="loading">Sending...</span>
            </button>
        </form>

        <!-- Step 2: Enter Code -->
        <form x-show="step === 2" @submit.prevent="verifyCode" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" :value="email" readonly
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">6-Digit Code</label>
                <input type="text" x-model="code" required maxlength="6" pattern="[0-9]{6}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition text-center text-2xl tracking-widest font-mono"
                    placeholder="000000">
                <p class="text-xs text-gray-400 mt-1">Check your email for the 6-digit verification code.</p>
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Verify Code</span>
                <span x-show="loading">Verifying...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-3">
                <a href="#" @click.prevent="step = 1; error = ''; success = ''" class="text-gblue-500 hover:underline">Resend code</a>
            </p>
        </form>

        <!-- Step 3: New Password -->
        <form x-show="step === 3" @submit.prevent="resetPassword" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" x-model="password" required minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" x-model="password_confirmation" required minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-ggreen-500 text-white py-2 px-4 rounded-lg hover:bg-ggreen-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Reset Password</span>
                <span x-show="loading">Resetting...</span>
            </button>
        </form>

        <!-- Step 4: Done -->
        <div x-show="step === 4" x-cloak class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-ggreen-50 rounded-full mb-4">
                <svg class="w-8 h-8 text-ggreen-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <p class="text-gray-700 font-medium mb-4">Your password has been reset successfully!</p>
            <a href="/login"
                class="inline-block w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium text-center">
                Back to Sign In
            </a>
        </div>

        <!-- Back to login link -->
        <p x-show="step < 4" class="text-center text-sm text-gray-500 mt-4">
            <a href="/login" class="text-gblue-500 hover:underline">← Back to Sign In</a>
        </p>
    </div>
</div>

<script>
function resetForm() {
    return {
        step: 1,
        email: '',
        code: '',
        password: '',
        password_confirmation: '',
        error: '',
        success: '',
        loading: false,

        get stepDescription() {
            const descriptions = {
                1: 'Enter your email to receive a reset code',
                2: 'Enter the 6-digit code sent to your email',
                3: 'Choose a new password',
                4: 'All done!'
            };
            return descriptions[this.step] || '';
        },

        async sendCode() {
            this.loading = true;
            this.error = '';
            this.success = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Failed to send reset code.';
                    return;
                }
                this.success = 'A verification code has been sent to your email.';
                this.step = 2;
            } catch (e) {
                this.error = 'Unable to connect to authentication service.';
            } finally {
                this.loading = false;
            }
        },

        async verifyCode() {
            this.loading = true;
            this.error = '';
            this.success = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/verify-reset-code', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email, code: this.code })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Invalid code.';
                    return;
                }
                this.success = 'Code verified! Set your new password.';
                this.step = 3;
            } catch (e) {
                this.error = 'Unable to connect to authentication service.';
            } finally {
                this.loading = false;
            }
        },

        async resetPassword() {
            this.loading = true;
            this.error = '';
            this.success = '';
            if (this.password !== this.password_confirmation) {
                this.error = 'Passwords do not match.';
                this.loading = false;
                return;
            }
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email: this.email,
                        code: this.code,
                        password: this.password,
                        password_confirmation: this.password_confirmation
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Failed to reset password.';
                    return;
                }
                this.step = 4;
            } catch (e) {
                this.error = 'Unable to connect to authentication service.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
