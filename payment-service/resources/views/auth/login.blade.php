@extends('layouts.app')

@section('title', 'Login - Payin')

@section('content')
<div class="min-h-screen flex items-center justify-center py-8" x-data="loginForm()" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#7f1d1d 100%)">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-lg">
        <!-- PayIn brand color strip -->
        <div class="flex h-1.5 rounded-t-xl overflow-hidden mb-6 -mx-8 -mt-8">
            <div class="flex-1 bg-gred-500"></div>
            <div class="flex-1 bg-gyellow-500"></div>
            <div class="flex-1 bg-gred-700"></div>
        </div>

        <div class="text-center mb-8" x-show="!showRegister && !showTwoFactor">
            <div class="mb-4">
                <span class="text-3xl font-extrabold text-gray-800 tracking-wide" style="font-family:'Poppins',sans-serif">Pay<span class="text-gyellow-500">In</span></span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-gray-500 mt-1">Sign in to your merchant account</p>
        </div>

        <!-- Register Header -->
        <div x-show="showRegister" x-cloak class="mb-8">
            <div class="mb-4">
                <span class="text-3xl font-extrabold text-gray-800 tracking-wide" style="font-family:'Poppins',sans-serif">Pay<span class="text-gyellow-500">In</span></span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Let's get Started</h2>
            <p class="text-gray-500 text-sm mb-3">To accelerate your application, you will need:</p>
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gred-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">1</span>
                    <span>Your Company information</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gred-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">2</span>
                    <span>Directors' Documents</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gred-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">3</span>
                    <span>Basic Compliance documents</span>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="error"></div>

        <!-- Success Message -->
        <div x-show="success" x-cloak class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm" x-text="success"></div>

        <!-- Two-Factor Verification Form -->
        <form x-show="showTwoFactor && !showRegister" @submit.prevent="verifyTwoFactor" x-cloak>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Two-Factor Verification</h3>
                <p class="text-sm text-gray-500 mt-1">A 6-digit code has been sent to your email.<br>Please enter it below to continue.</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input type="text" x-model="tfaCode" required maxlength="6" pattern="[0-9]{6}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-center text-2xl tracking-[0.5em] font-mono"
                    placeholder="000000" autocomplete="one-time-code">
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Verify & Login</span>
                <span x-show="loading">Verifying...</span>
            </button>
            <div class="flex items-center justify-between mt-4">
                <button type="button" @click="resendTwoFactor()" :disabled="resendLoading || resendCooldown > 0"
                    class="text-sm text-indigo-600 hover:underline disabled:text-gray-400 disabled:no-underline">
                    <span x-show="resendCooldown > 0" x-text="'Resend in ' + resendCooldown + 's'"></span>
                    <span x-show="resendCooldown <= 0 && !resendLoading">Resend Code</span>
                    <span x-show="resendLoading">Sending...</span>
                </button>
                <button type="button" @click="showTwoFactor = false; tfaCode = ''; tfaEmail = ''; error = ''" class="text-sm text-gray-500 hover:underline">Back to Login</button>
            </div>
        </form>

        <!-- Login Form -->
        <form x-show="!showRegister && !showTwoFactor" @submit.prevent="login" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" x-model="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="••••••••">
                <div class="text-right mt-1">
                    <a href="/forgot-password" class="text-sm text-gred-500 hover:underline">Forgot your password?</a>
                </div>
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-gred-500 text-white py-2 px-4 rounded-lg hover:bg-gred-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Login</span>
                <span x-show="loading">Logging in...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Don't have an account?
                <a href="#" @click.prevent="showRegister = true; error = ''" class="text-gred-500 hover:underline">Sign Up</a>
            </p>
        </form>

        <!-- Register Form -->
        <form x-show="showRegister" @submit.prevent="register" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" x-model="firstname" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                        placeholder="John">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" x-model="lastname" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                        placeholder="Doe">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                <input type="text" x-model="business_name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="Your company or business name">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select x-model="country"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition bg-white">
                    <option value="Tanzania">Tanzania</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Rwanda">Rwanda</option>
                    <option value="Burundi">Burundi</option>
                    <option value="DRC">DR Congo</option>
                    <option value="Mozambique">Mozambique</option>
                    <option value="Malawi">Malawi</option>
                    <option value="Zambia">Zambia</option>
                    <option value="South Africa">South Africa</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="Ghana">Ghana</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" x-model="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="password" required minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" x-model="password_confirmation" required minlength="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gred-500 focus:border-gred-500 outline-none transition"
                    placeholder="••••••••">
            </div>

            <!-- Honeypot: hidden from humans, bots will fill this -->
            <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
                <input type="text" x-model="website" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" :disabled="loading"
                class="w-full bg-gred-500 text-white py-2 px-4 rounded-lg hover:bg-gred-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Get Started</span>
                <span x-show="loading">Creating...</span>
            </button>
            <p class="text-center text-xs text-gray-400 mt-4 leading-relaxed">
                By clicking "Get Started" I agree to Payin's
                <a href="#" class="text-gred-500 hover:underline">Terms of Use</a> and
                <a href="#" class="text-gred-500 hover:underline">Privacy Policy</a>
                and to receive electronic communication about your accounts and services.
            </p>
            <p class="text-center text-sm text-gray-500 mt-3">
                Already have an account?
                <a href="#" @click.prevent="showRegister = false; error = ''" class="text-gred-500 hover:underline">Login</a>
            </p>
        </form>

    </div>
</div>

<script>
function loginForm() {
    return {
        email: '',
        password: '',
        firstname: '',
        lastname: '',
        business_name: '',
        country: 'Tanzania',
        password_confirmation: '',
        website: '',
        _form_loaded_at: Date.now(),
        error: '',
        success: '',
        loading: false,
        showRegister: false,
        // Two-Factor
        showTwoFactor: false,
        tfaEmail: '',
        tfaCode: '',
        resendLoading: false,
        resendCooldown: 0,

        async login() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email, password: this.password })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Login failed. Please check your credentials.';
                    return;
                }
                // Handle 2FA requirement
                if (data.two_factor_required) {
                    this.tfaEmail = data.email;
                    this.showTwoFactor = true;
                    this.success = data.message;
                    this.error = '';
                    this.startResendCooldown();
                    return;
                }
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('auth_user', JSON.stringify(data.user));
                if (data.kyc_required) {
                    localStorage.setItem('kyc_required', 'true');
                    localStorage.removeItem('account_pending');
                    window.location.href = '/kyc';
                } else if (data.pending) {
                    localStorage.setItem('account_pending', 'true');
                    localStorage.removeItem('kyc_required');
                    window.location.href = '/dashboard';
                } else if (data.user.role === 'super_admin' || data.user.role === 'admin_user') {
                    localStorage.removeItem('account_pending');
                    localStorage.removeItem('kyc_required');
                    window.location.href = '/admin';
                } else {
                    localStorage.removeItem('account_pending');
                    localStorage.removeItem('kyc_required');
                    window.location.href = '/dashboard';
                }
            } catch (e) {
                console.error('Login error:', e);
                this.error = e.message && e.message !== 'Failed to fetch'
                    ? e.message
                    : 'Unable to connect to authentication service. Please check your internet connection and try again.';
            } finally {
                this.loading = false;
            }
        },

        async verifyTwoFactor() {
            this.loading = true;
            this.error = '';
            this.success = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/verify-two-factor', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.tfaEmail, code: this.tfaCode })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Invalid verification code.';
                    return;
                }
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('auth_user', JSON.stringify(data.user));
                if (data.kyc_required) {
                    localStorage.setItem('kyc_required', 'true');
                    window.location.href = '/kyc';
                } else if (data.pending) {
                    localStorage.setItem('account_pending', 'true');
                    window.location.href = '/dashboard';
                } else if (data.user.role === 'super_admin' || data.user.role === 'admin_user') {
                    window.location.href = '/admin';
                } else {
                    window.location.href = '/dashboard';
                }
            } catch (e) {
                this.error = 'Unable to connect to authentication service.';
            } finally {
                this.loading = false;
            }
        },

        async resendTwoFactor() {
            this.resendLoading = true;
            this.error = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/resend-two-factor', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.tfaEmail })
                });
                const data = await res.json();
                this.success = data.message || 'A new code has been sent.';
                this.startResendCooldown();
            } catch (e) {
                this.error = 'Unable to resend code.';
            } finally {
                this.resendLoading = false;
            }
        },

        startResendCooldown() {
            this.resendCooldown = 60;
            const interval = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) clearInterval(interval);
            }, 1000);
        },

        async register() {
            this.loading = true;
            this.error = '';

            // Bot check: honeypot must be empty
            if (this.website) {
                this.error = 'Registration failed. Please try again.';
                this.loading = false;
                return;
            }

            // Bot check: form must be open for at least 3 seconds
            const elapsed = (Date.now() - this._form_loaded_at) / 1000;
            if (elapsed < 3) {
                this.error = 'Please take your time filling out the form.';
                this.loading = false;
                return;
            }

            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        firstname: this.firstname,
                        lastname: this.lastname,
                        business_name: this.business_name,
                        country: this.country,
                        email: this.email,
                        password: this.password,
                        password_confirmation: this.password_confirmation,
                        website: this.website,
                        _form_loaded_at: this._form_loaded_at
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.error = errors || 'Registration failed.';
                    return;
                }
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('auth_user', JSON.stringify(data.user));
                localStorage.setItem('kyc_required', 'true');
                window.location.href = '/kyc';
            } catch (e) {
                console.error('Registration error:', e);
                this.error = e.message && e.message !== 'Failed to fetch'
                    ? e.message
                    : 'Unable to connect to authentication service. Please check your internet connection and try again.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
