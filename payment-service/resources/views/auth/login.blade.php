@extends('layouts.app')

@section('title', 'Login - Payin')

@section('content')
<div class="min-h-screen flex items-center justify-center" x-data="loginForm()">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
        <!-- Google-style color strip -->
        <div class="flex h-1 rounded-t-xl overflow-hidden mb-6 -mx-8 -mt-8">
            <div class="flex-1 bg-gblue-500"></div>
            <div class="flex-1 bg-gred-500"></div>
            <div class="flex-1 bg-gyellow-500"></div>
            <div class="flex-1 bg-ggreen-500"></div>
        </div>

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gblue-50 rounded-full mb-4">
                <svg class="w-8 h-8 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Payin</h2>
            <p class="text-gray-500 mt-1">Sign in to your account</p>
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
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Sign In</span>
                <span x-show="loading">Signing in...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Don't have an account?
                <a href="#" @click.prevent="showRegister = true; error = ''" class="text-gblue-500 hover:underline">Register</a>
            </p>
        </form>

        <!-- Register Form -->
        <form x-show="showRegister" @submit.prevent="register" x-cloak>
            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Business Details</h3>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                <input type="text" x-model="business_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none text-sm"
                    placeholder="Your company or business name">
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                    <select x-model="business_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        <option value="">Select...</option>
                        <option value="sole_proprietorship">Sole Proprietorship</option>
                        <option value="partnership">Partnership</option>
                        <option value="limited_company">Limited Company</option>
                        <option value="ngo">NGO</option>
                        <option value="government">Government</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" x-model="phone"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="+255...">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Registration No.</label>
                    <input type="text" x-model="registration_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="e.g. BRELA No.">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">TIN Number</label>
                    <input type="text" x-model="tin_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="Tax ID">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" x-model="city"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="e.g. Dar es Salaam">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" x-model="address"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="Street address">
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3 mt-5">Identity Verification</h3>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Type</label>
                    <select x-model="id_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        <option value="">Select...</option>
                        <option value="national_id">National ID (NIDA)</option>
                        <option value="passport">Passport</option>
                        <option value="drivers_license">Driver's License</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                    <input type="text" x-model="id_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none"
                        placeholder="ID number">
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3 mt-5">Account Owner</h3>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                <input type="text" x-model="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none text-sm"
                    placeholder="Your full name">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" x-model="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none text-sm"
                    placeholder="you@example.com">
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" x-model="password" required minlength="8"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none text-sm"
                        placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm *</label>
                    <input type="password" x-model="password_confirmation" required minlength="8"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none text-sm"
                        placeholder="••••••••">
                </div>
            </div>

            <div class="bg-gyellow-50 border border-gyellow-200 rounded-lg p-3 mb-4 text-xs text-gyellow-800">
                <strong>Note:</strong> Your account will be reviewed by our team before activation. Please provide as much KYC information as possible to speed up approval.
            </div>

            <button type="submit" :disabled="loading"
                class="w-full bg-gblue-500 text-white py-2 px-4 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Create Account</span>
                <span x-show="loading">Creating...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Already have an account?
                <a href="#" @click.prevent="showRegister = false; error = ''" class="text-gblue-500 hover:underline">Sign In</a>
            </p>
        </form>
    </div>
</div>

<script>
function loginForm() {
    return {
        email: '',
        password: '',
        name: '',
        business_name: '',
        business_type: '',
        phone: '',
        registration_number: '',
        tin_number: '',
        city: '',
        address: '',
        id_type: '',
        id_number: '',
        password_confirmation: '',
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
                if (data.pending) {
                    localStorage.setItem('account_pending', 'true');
                    window.location.href = '/dashboard';
                } else if (data.user.role === 'super_admin' || data.user.role === 'admin_user') {
                    localStorage.removeItem('account_pending');
                    window.location.href = '/admin';
                } else {
                    localStorage.removeItem('account_pending');
                    window.location.href = '/dashboard';
                }
            } catch (e) {
                this.error = 'Unable to connect to authentication service.';
            } finally {
                this.loading = false;
            }
        },

        async register() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        business_name: this.business_name,
                        business_type: this.business_type || undefined,
                        registration_number: this.registration_number || undefined,
                        tin_number: this.tin_number || undefined,
                        phone: this.phone || undefined,
                        address: this.address || undefined,
                        city: this.city || undefined,
                        id_type: this.id_type || undefined,
                        id_number: this.id_number || undefined,
                        name: this.name,
                        email: this.email,
                        password: this.password,
                        password_confirmation: this.password_confirmation
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
                localStorage.setItem('account_pending', 'true');
                window.location.href = '/dashboard';
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
