@extends('layouts.app')

@section('title', 'Login - Payment Dashboard')

@section('content')
<div class="min-h-screen flex items-center justify-center" x-data="loginForm()">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Payment Dashboard</h2>
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
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Sign In</span>
                <span x-show="loading">Signing in...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Don't have an account?
                <a href="#" @click.prevent="showRegister = true; error = ''" class="text-indigo-600 hover:underline">Register</a>
            </p>
        </form>

        <!-- Register Form -->
        <form x-show="showRegister" @submit.prevent="register" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" x-model="name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="Your name">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" x-model="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="you@example.com">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="password" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" x-model="password_confirmation" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="••••••••">
            </div>
            <button type="submit" :disabled="loading"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Create Account</span>
                <span x-show="loading">Creating...</span>
            </button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Already have an account?
                <a href="#" @click.prevent="showRegister = false; error = ''" class="text-indigo-600 hover:underline">Sign In</a>
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
                window.location.href = '/dashboard';
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
