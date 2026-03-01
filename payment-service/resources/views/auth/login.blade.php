@extends('layouts.app')

@section('title', 'Login - Payin')

@section('content')
<div class="min-h-screen flex items-center justify-center py-8" x-data="loginForm()">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg">
        <!-- Google-style color strip -->
        <div class="flex h-1 rounded-t-xl overflow-hidden mb-6 -mx-8 -mt-8">
            <div class="flex-1 bg-gblue-500"></div>
            <div class="flex-1 bg-gred-500"></div>
            <div class="flex-1 bg-gyellow-500"></div>
            <div class="flex-1 bg-ggreen-500"></div>
        </div>

        <div class="text-center mb-8" x-show="!showRegister">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gblue-50 rounded-full mb-4">
                <svg class="w-8 h-8 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">LOGIN</h2>
            <p class="text-gray-500 mt-1">Login to your account</p>
        </div>

        <!-- Register Header -->
        <div x-show="showRegister" x-cloak class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Let's get Started</h2>
            <p class="text-gray-500 text-sm mb-3">To accelerate your application, you will need:</p>
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gblue-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">1</span>
                    <span>Your Company information</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gblue-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">2</span>
                    <span>Directors' Documents</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gblue-500 text-white text-xs font-bold flex-shrink-0 mt-0.5">3</span>
                    <span>Basic Compliance documents</span>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="error"></div>

        <!-- Success Message -->
        <div x-show="success" x-cloak class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm" x-text="success"></div>

        <!-- Login Form -->
        <form x-show="!showRegister" @submit.prevent="login" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" x-model="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="••••••••">
                <div class="text-right mt-1">
                    <a href="/forgot-password" class="text-sm text-gblue-500 hover:underline">Forgot your password?</a>
                </div>
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Login</span>
                <span x-show="loading">Logging in...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Don't have an account?
                <a href="#" @click.prevent="showRegister = true; error = ''" class="text-gblue-500 hover:underline">Sign Up</a>
            </p>
        </form>

        <!-- Register Form -->
        <form x-show="showRegister" @submit.prevent="register" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" x-model="firstname" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                        placeholder="John">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" x-model="lastname" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                        placeholder="Doe">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                <input type="text" x-model="business_name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="Your company or business name">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select x-model="country"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition bg-white">
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
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
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

            <!-- Honeypot: hidden from humans, bots will fill this -->
            <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
                <input type="text" x-model="website" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Get Started</span>
                <span x-show="loading">Creating...</span>
            </button>
            <p class="text-center text-xs text-gray-400 mt-4 leading-relaxed">
                By clicking "Get Started" I agree to Payin's
                <a href="#" class="text-gblue-500 hover:underline">Terms of Use</a> and
                <a href="#" class="text-gblue-500 hover:underline">Privacy Policy</a>
                and to receive electronic communication about your accounts and services.
            </p>
            <p class="text-center text-sm text-gray-500 mt-3">
                Already have an account?
                <a href="#" @click.prevent="showRegister = false; error = ''" class="text-gblue-500 hover:underline">Login</a>
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

        async login() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email, password: this.password })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Login failed. Please check your credentials.';
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
                const res = await fetch('{{ config("services.auth_service.url") }}/api/register', {
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
