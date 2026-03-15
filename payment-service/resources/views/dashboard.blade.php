@extends('layouts.app')

@section('title', 'Dashboard - Payin')

@section('content')
<div x-data="dashboard()" x-init="init()" x-cloak>
    <!-- Top Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 shadow-lg border-b border-gray-800" style="background:rgba(15,23,42,.95);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden mr-3 text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <a href="https://www.payin.co.tz" class="text-xl font-extrabold text-white tracking-wide" style="letter-spacing:1px;font-family:'Poppins',sans-serif">Pay<span class="text-amber-400">In</span></a>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-400 hidden sm:inline">Welcome, <span class="font-medium text-white" x-text="(user?.firstname || user?.name || 'User')"></span></span>
                    <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded-full capitalize" x-text="user?.role || ''"></span>
                    <button @click="goToTab('account-settings')" :class="activeTab === 'account-settings' ? 'text-white' : 'text-amber-400 hover:text-amber-300'" class="text-sm font-medium inline-flex items-center gap-1 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Settings
                    </button>
                    <button @click="logout()" class="text-xs text-red-400 hover:text-red-300 font-medium transition">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

    <!-- Left Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed top-14 left-0 bottom-0 w-64 bg-white border-r border-gray-200 z-40 lg:z-10 transform transition-transform duration-200">
        <div class="py-5 flex flex-col h-full">

            <!-- Scrollable sidebar content -->
            <div class="flex-1 min-h-0 overflow-y-auto sidebar-scroll">

            <!-- Business Section -->
            <div class="px-4 mb-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Business</h3>
            </div>
            <nav class="flex-1 px-2 space-y-0.5">
                <button @click="goToTab('dashboard')"
                    :class="activeTab === 'dashboard' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'dashboard' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </button>
                <button x-show="hasPerm('view_transactions')" @click="goToTab('transactions')"
                    :class="activeTab === 'transactions' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'transactions' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Transactions
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="goToTab('wallet')"
                    :class="activeTab === 'wallet' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'wallet' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Wallet
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('create_payout') || hasPerm('approve_payout') || hasPerm('view_transactions')" @click="goToTab('send-money')"
                    :class="activeTab === 'send-money' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group relative">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'send-money' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Send Money
                    <span x-show="pendingPayoutsCount > 0" x-cloak class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="pendingPayoutsCount"></span>
                </button>
                <button x-show="hasPerm('view_transactions') || hasPerm('create_payout')" @click="goToTab('invoices')"
                    :class="activeTab === 'invoices' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'invoices' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                    Invoices
                </button>
                <button x-show="hasPerm('view_settlements') || hasPerm('create_settlement')" @click="goToTab('settlements')"
                    :class="activeTab === 'settlements' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'settlements' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Settlements
                </button>
                <button x-show="hasPerm('view_account_info')" @click="goToTab('account')"
                    :class="activeTab === 'account' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'account' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Account Info
                </button>
                <button x-show="hasPerm('view_users') || hasPerm('add_user')" @click="goToTab('users')"
                    :class="activeTab === 'users' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'users' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Users
                </button>
                <button x-show="user?.account?.multi_currency_enabled" @click="goToTab('exchange')"
                    :class="activeTab === 'exchange' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'exchange' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Currency Exchange
                </button>
            </nav>

            <!-- Divider -->
            <div class="px-4 my-3"><div class="border-t border-gray-200"></div></div>

            <!-- Developer Tools Section -->
            <div class="px-4 mb-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Developer Tools</h3>
            </div>
            <nav class="px-2 space-y-0.5">
                <button @click="goToTab('api-docs')"
                    :class="activeTab === 'api-docs' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'api-docs' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    API Documentation
                </button>
                <button x-show="hasPerm('view_settings')" @click="goToTab('settings')"
                    :class="activeTab === 'settings' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'settings' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.11 2.37-2.37.996.608 2.296.07 2.573-1.066z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Webhook & API Keys
                </button>
                <button x-show="hasPerm('view_settings')" @click="goToTab('webhook-logs')"
                    :class="activeTab === 'webhook-logs' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'webhook-logs' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Webhook Logs
                </button>
            </nav>

            </div><!-- /sidebar-scroll -->

            <!-- Bottom account info -->
            <div class="mt-auto px-4 pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-xs font-bold" x-text="(user?.firstname || user?.name || 'U')[0].toUpperCase()"></div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="(user?.firstname && user?.lastname) ? (user.firstname + ' ' + user.lastname) : (user?.name || 'User')"></p>
                        <p class="text-xs text-gray-500 truncate" x-text="user?.email || ''"></p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:ml-64 pt-14 min-h-screen bg-gray-50">

        <!-- Pending KYC Banner -->
        <div x-show="accountPending" x-cloak class="bg-yellow-50 border-b border-yellow-200">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-base font-semibold text-yellow-800">Account Pending KYC Approval</h3>
                        <p class="text-sm text-yellow-700 mt-0.5">Your account is under review. You'll be able to access all features once approved.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 lg:px-8 py-6 pb-12">

        <!-- ==================== DASHBOARD TAB ==================== -->
        <div x-show="activeTab === 'dashboard'">
            <!-- Welcome Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Welcome back, <span x-text="user?.account?.business_name || user?.name || 'User'"></span></h1>
                <p class="text-sm text-gray-500 mt-1">Here's your business overview.</p>
            </div>

            <!-- ===== TOP ROW: Balance Cards (left) + Recent Transactions (right) ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- LEFT COLUMN: Balances -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Collection Balance -->
                    <div class="bg-white rounded-xl shadow-md border p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Collection Balance</p>
                                    <p class="text-xs text-gray-400">Money received (Payin)</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900" x-text="formatAmount(collectionTotal) + ' ' + walletCurrency"></p>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <button @click="goToTab('wallet')" class="inline-flex items-center px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-medium transition">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Top Up
                            </button>
                        </div>
                    </div>

                    <!-- Disbursement Balance -->
                    <div class="bg-white rounded-xl shadow-md border p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disbursement Balance</p>
                                    <p class="text-xs text-gray-400">Available for payouts</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900" x-text="formatAmount(disbursementTotal) + ' ' + walletCurrency"></p>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <button @click="goToTab('send-money')" class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-medium transition">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                Send Money
                            </button>
                        </div>
                    </div>

                    <!-- Transaction Stats Mini -->
                    <div class="bg-white rounded-xl shadow-md border p-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Transaction Summary</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-lg font-bold text-gray-800" x-text="stats.total"></p>
                                <p class="text-[10px] text-gray-500 uppercase">Total</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-lg font-bold text-gray-800" x-text="stats.completed"></p>
                                <p class="text-[10px] text-gray-500 uppercase">Completed</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-lg font-bold text-gray-800" x-text="stats.pending"></p>
                                <p class="text-[10px] text-gray-500 uppercase">Pending</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-lg font-bold text-gray-800" x-text="stats.failed"></p>
                                <p class="text-[10px] text-gray-500 uppercase">Failed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Recent Transactions -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md border overflow-hidden h-full flex flex-col">
                        <div class="px-5 py-4 border-b flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Recent Transactions</h3>
                            <button @click="goToTab('transactions')" class="text-xs text-gray-500 hover:text-gray-700 font-medium">View All &rarr;</button>
                        </div>
                        <div x-show="loadingTxns" class="p-8 text-center text-gray-500 flex-1 flex items-center justify-center">
                            <div>
                                <svg class="animate-spin h-7 w-7 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <p class="mt-2 text-sm">Loading...</p>
                            </div>
                        </div>
                        <div x-show="!loadingTxns && transactions.length === 0" x-cloak class="p-8 text-center text-gray-400 flex-1 flex items-center justify-center">
                            <div>
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <p class="text-sm">No transactions yet</p>
                            </div>
                        </div>
                        <div x-show="!loadingTxns && transactions.length > 0" x-cloak class="flex-1 overflow-y-auto">
                            <div class="divide-y divide-gray-100">
                                <template x-for="txn in transactions.slice(0, 10)" :key="txn.id">
                                    <div class="px-5 py-3 hover:bg-gray-50 transition flex items-center justify-between">
                                        <div class="flex items-center min-w-0">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-gray-100">
                                                <svg x-show="txn.type === 'collection'" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                                <svg x-show="txn.type === 'disbursement'" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                                <svg x-show="txn.type === 'topup'" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                                <svg x-show="txn.type === 'settlement'" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            </div>
                                            <div class="ml-3 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" x-text="txn.transaction_ref"></p>
                                                <p class="text-xs text-gray-400" x-text="formatDate(txn.created_at)"></p>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0 ml-3">
                                            <p class="text-sm font-semibold text-gray-800"
                                                x-text="(txn.type === 'collection' ? '+' : '-') + ' ' + formatAmount(txn.amount) + ' ' + walletCurrency"></p>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium capitalize bg-gray-100 text-gray-600"
                                                x-text="txn.status"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TREASURY DETAIL: Balance per MNO ===== -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden mb-6">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-sm font-semibold text-gray-800">Treasury Detail</h3>
                    <p class="text-xs text-gray-400">Balance breakdown by operator</p>
                </div>
                <div x-show="collectionWallets.length === 0 && disbursementWallets.length === 0" class="p-6 text-center text-gray-400 text-sm">Loading wallet data...</div>
                <div x-show="collectionWallets.length > 0 || disbursementWallets.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Currency</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Collection</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Disbursement</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="op in operators" :key="'treasury_'+op">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center">
                                                <div class="w-2.5 h-2.5 rounded-full mr-2" :class="operatorColor(op)"></div>
                                                <span class="text-sm font-medium text-gray-700" x-text="op"></span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-500" x-text="(collectionWallets.find(w => w.operator === op)?.currency) || (disbursementWallets.find(w => w.operator === op)?.currency) || walletCurrency"></td>
                                        <td class="px-5 py-3 text-right text-sm font-semibold text-gray-700" x-text="formatAmount((collectionWallets.find(w => w.operator === op)?.balance) || 0) + ' ' + ((collectionWallets.find(w => w.operator === op)?.currency) || walletCurrency)"></td>
                                        <td class="px-5 py-3 text-right text-sm font-semibold text-gray-700" x-text="formatAmount((disbursementWallets.find(w => w.operator === op)?.balance) || 0) + ' ' + ((disbursementWallets.find(w => w.operator === op)?.currency) || walletCurrency)"></td>
                                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-800" x-text="formatAmount(((collectionWallets.find(w => w.operator === op)?.balance) || 0) + ((disbursementWallets.find(w => w.operator === op)?.balance) || 0)) + ' ' + ((collectionWallets.find(w => w.operator === op)?.currency) || walletCurrency)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t">
                                <template x-for="curr in (walletByCurrency || [{currency: walletCurrency, collection_total: collectionTotal, disbursement_total: disbursementTotal, overall_balance: overallBalance}])" :key="'total_'+curr.currency">
                                    <tr>
                                        <td class="px-5 py-3 text-sm font-bold text-gray-700" x-text="'Total (' + curr.currency + ')'"></td>
                                        <td class="px-5 py-3 text-sm text-gray-500" x-text="curr.currency"></td>
                                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-800" x-text="formatAmount(curr.collection_total) + ' ' + curr.currency"></td>
                                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-800" x-text="formatAmount(curr.disbursement_total) + ' ' + curr.currency"></td>
                                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900" x-text="formatAmount(curr.overall_balance) + ' ' + curr.currency"></td>
                                    </tr>
                                </template>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===== BOTTOM ROW: Settlement Overview + Charges ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <!-- Settlement Overview -->
                <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Settlement Overview</h3>
                        <button @click="goToTab('settlements')" class="text-xs text-gray-500 hover:text-gray-700 font-medium">View All &rarr;</button>
                    </div>
                    <div x-show="stlLoadingList" class="p-6 text-center text-gray-400">
                        <svg class="animate-spin h-6 w-6 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                    <div x-show="!stlLoadingList && settlements.length === 0" x-cloak class="p-6 text-center text-gray-400 text-sm">No settlements found.</div>
                    <div x-show="!stlLoadingList && settlements.length > 0" x-cloak>
                        <div class="divide-y divide-gray-100">
                            <template x-for="stl in settlements.slice(0, 5)" :key="'dash_stl_'+stl.id">
                                <div class="px-5 py-3 hover:bg-gray-50 transition flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-mono text-gray-700 truncate" x-text="stl.settlement_ref"></p>
                                        <div class="flex items-center space-x-2 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                                :class="operatorBadgeColor(stl.operator)" x-text="stl.operator"></span>
                                            <span class="text-xs text-gray-400" x-text="formatDate(stl.created_at)"></span>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-3">
                                        <p class="text-sm font-semibold text-gray-800" x-text="formatAmount(stl.amount) + ' ' + walletCurrency"></p>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium capitalize bg-gray-100 text-gray-600"
                                            x-text="stl.status"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Charges & Fees Summary -->
                <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="text-sm font-semibold text-gray-800">Service Fees</h3>
                        <p class="text-xs text-gray-400">Platform fees on completed transactions</p>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-500">Total Service Fees</span>
                            <span class="text-xl font-bold text-gray-800" x-text="formatAmount(myCharges.total_charges || 0) + ' ' + walletCurrency"></span>
                        </div>
                        <div x-show="myCharges.by_type && myCharges.by_type.length > 0" class="space-y-2">
                            <template x-for="bt in (myCharges.by_type || [])" :key="'dash_charge_'+bt.type">
                                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 capitalize" x-text="bt.type"></span>
                                        <span class="text-xs text-gray-400 ml-1" x-text="'(' + bt.transaction_count + ' txns)'"></span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800" x-text="formatAmount(Number(bt.platform_charges)) + ' ' + walletCurrency"></span>
                                </div>
                            </template>
                        </div>
                        <div x-show="!myCharges.by_type || myCharges.by_type.length === 0" class="text-center text-gray-400 text-sm py-4">No charges recorded yet.</div>
                    </div>
                </div>
            </div>

            <!-- ===== Quick Actions Row ===== -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button x-show="hasPerm('view_transactions')" @click="goToTab('transactions')" class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition text-center group">
                    <div class="p-2 bg-gray-100 rounded-lg inline-flex group-hover:bg-gray-200 transition mb-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-700">Transactions</p>
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('create_payout') || hasPerm('approve_payout') || hasPerm('view_transactions')" @click="goToTab('send-money')" class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition text-center group relative">
                    <div class="p-2 bg-gray-100 rounded-lg inline-flex group-hover:bg-gray-200 transition mb-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-700">Send Money</p>
                    <span x-show="pendingPayoutsCount > 0" x-cloak class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="pendingPayoutsCount"></span>
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="goToTab('wallet')" class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition text-center group">
                    <div class="p-2 bg-gray-100 rounded-lg inline-flex group-hover:bg-gray-200 transition mb-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-700">Wallets</p>
                </button>
                <button x-show="hasPerm('view_settlements') || hasPerm('create_settlement')" @click="goToTab('settlements')" class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition text-center group">
                    <div class="p-2 bg-gray-100 rounded-lg inline-flex group-hover:bg-gray-200 transition mb-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <p class="text-xs font-semibold text-gray-700">Settlements</p>
                </button>
            </div>
        </div>

        <!-- ==================== TRANSACTIONS TAB ==================== -->
        <div x-show="activeTab === 'transactions'">

            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-md p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" x-model="searchQuery" @input.debounce.400ms="currentPage = 1; fetchTransactions()"
                                placeholder="Search by reference, amount..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 focus:border-gblue-500 outline-none transition">
                            <button x-show="searchQuery" @click="searchQuery = ''; fetchTransactions()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <select x-model="filterStatus" @change="fetchTransactions()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <select x-model="filterType" @change="fetchTransactions()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">All Types</option>
                            <option value="collection">Collection (Payin)</option>
                            <option value="disbursement">Disbursement (Payout)</option>
                            <option value="topup">Topup (Transfer)</option>
                            <option value="settlement">Settlement (Withdrawal)</option>
                        </select>
                    </div>
                    <button @click="searchQuery = ''; filterStatus = ''; filterType = ''; filterOperator = ''; dateFrom = ''; dateTo = ''; currentPage = 1; fetchTransactions()" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">Clear All</button>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-3 w-full">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 font-medium whitespace-nowrap">From:</label>
                        <input type="date" x-model="dateFrom" @change="currentPage=1; fetchTransactions()"
                            :max="dateTo || undefined"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 font-medium whitespace-nowrap">To:</label>
                        <input type="date" x-model="dateTo" @change="currentPage=1; fetchTransactions()"
                            :min="dateFrom || undefined"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <button x-show="dateFrom || dateTo" x-cloak @click="dateFrom=''; dateTo=''; currentPage=1; fetchTransactions()"
                        class="text-xs text-red-600 hover:text-red-800 font-medium underline">Clear Dates</button>
                    <div class="ml-auto flex items-center gap-2">
                        <button @click="downloadTransactions('excel')" :disabled="txnExportLoading"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-300 rounded-lg hover:bg-green-100 disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span x-text="txnExportLoading === 'excel' ? 'Exporting...' : 'Excel'"></span>
                        </button>
                        <button @click="downloadTransactions('pdf')" :disabled="txnExportLoading"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-300 rounded-lg hover:bg-red-100 disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span x-text="txnExportLoading === 'pdf' ? 'Exporting...' : 'PDF'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b"><h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3></div>
                <div x-show="loadingTxns" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="mt-2">Loading transactions...</p>
                </div>
                <div x-show="txnError" x-cloak class="p-6 text-center">
                    <p class="text-red-600" x-text="txnError"></p>
                    <button @click="fetchTransactions()" class="mt-2 text-gblue-500 hover:underline text-sm">Retry</button>
                </div>
                <div x-show="!loadingTxns && !txnError && transactions.length === 0" x-cloak class="p-8 text-center text-gray-500">
                    <p>No transactions found.</p>
                </div>
                <div x-show="!loadingTxns && !txnError && transactions.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charge</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="txn in transactions" :key="txn.id">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(txn.created_at)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600" x-text="txn.operator_receipt || '-'"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-ggreen-50 text-ggreen-700': txn.status==='completed','bg-gyellow-50 text-gyellow-700': txn.status==='pending','bg-gred-50 text-gred-700': txn.status==='failed','bg-gray-100 text-gray-800': txn.status==='cancelled','bg-purple-100 text-purple-800': txn.status==='reversed'}"
                                                x-text="txn.status"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-ggreen-50 text-ggreen-700': txn.type==='collection','bg-gred-50 text-gred-700': txn.type==='disbursement','bg-gblue-50 text-gblue-700': txn.type==='topup','bg-purple-100 text-purple-800': txn.type==='settlement'}"
                                                x-text="txn.type==='collection' ? 'Collection (Payin)' : txn.type==='disbursement' ? 'Disbursement (Payout)' : txn.type==='topup' ? 'Topup (Transfer)' : txn.type==='settlement' ? 'Settlement (Withdrawal)' : txn.type"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600" x-text="Number(txn.platform_charge || 0) > 0 ? formatAmount(Number(txn.platform_charge || 0)) : '-'"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="txn.type==='collection' ? 'text-green-600' : 'text-gray-800'">
                                            <span x-text="(txn.type==='collection' ? '+' : '-') + ' ' + formatAmount(txn.amount) + ' ' + txn.currency"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="pagination.from||0"></span> to <span x-text="pagination.to||0"></span> of <span x-text="pagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="goToPage(pagination.current_page-1)" :disabled="!pagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="goToPage(pagination.current_page+1)" :disabled="!pagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== WALLET TAB ==================== -->
        <div x-show="activeTab === 'wallet'">

            <!-- Overall Balance Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-gblue-500 to-gblue-600 rounded-xl shadow-lg p-5 text-white">
                    <p class="text-xs font-medium opacity-80 uppercase tracking-wide">Overall Balance</p>
                    <p class="text-lg font-semibold mt-1" x-text="formatAmount(overallBalance) + ' ' + walletCurrency"></p>
                </div>
                <div class="bg-gradient-to-br from-ggreen-500 to-ggreen-600 rounded-xl shadow-lg p-5 text-white">
                    <p class="text-xs font-medium opacity-80 uppercase tracking-wide">Collection (Payin)</p>
                    <p class="text-lg font-semibold mt-1" x-text="formatAmount(collectionTotal) + ' ' + walletCurrency"></p>
                </div>
                <div class="bg-gradient-to-br from-gred-400 to-gred-500 rounded-xl shadow-lg p-5 text-white">
                    <p class="text-xs font-medium opacity-80 uppercase tracking-wide">Disbursement (Payout)</p>
                    <p class="text-lg font-semibold mt-1" x-text="formatAmount(disbursementTotal) + ' ' + walletCurrency"></p>
                </div>
            </div>

            <!-- Wallet Sub-tabs: Collection / Disbursement -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-6">
                    <button @click="walletSubTab = 'collection'" :class="walletSubTab === 'collection' ? 'border-ggreen-500 text-ggreen-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Collection (Payin)</button>
                    <button @click="walletSubTab = 'disbursement'" :class="walletSubTab === 'disbursement' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Disbursement (Payout)</button>
                </nav>
            </div>

            <!-- ======= COLLECTION SUB-TAB ======= -->
            <div x-show="walletSubTab === 'collection'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <template x-for="w in collectionWallets" :key="w.id">
                        <div class="bg-white rounded-xl shadow-md border p-5">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full" :class="operatorColor(w.operator)"></div>
                                    <h4 class="text-sm font-semibold text-gray-700" x-text="w.operator"></h4>
                                </div>
                            </div>
                            <p class="text-base font-semibold text-gray-800" x-text="formatAmount(w.balance) + ' ' + walletCurrency"></p>
                        </div>
                    </template>
                </div>

                <!-- Transfer to Disbursement -->
                <div x-show="hasPerm('wallet_transfer')" class="bg-white rounded-xl shadow-md border p-6 mb-6">
                    <h3 class="text-md font-semibold text-gray-700 mb-1">Transfer to Disbursement</h3>
                    <p class="text-xs text-gyellow-600 mb-4">Requires admin approval before funds are moved.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <template x-for="w in collectionWallets" :key="'trf_'+w.id">
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full" :class="operatorColor(w.operator)"></div>
                                    <span class="text-sm font-semibold text-gray-700" x-text="w.operator"></span>
                                </div>
                                <p class="text-xs text-gray-500 mb-2" x-text="'Balance: ' + formatAmount(w.balance) + ' ' + walletCurrency"></p>
                                <div x-show="walletMsg['trf_'+w.operator]" x-cloak class="mb-2 p-2 rounded text-xs"
                                    :class="walletMsgType['trf_'+w.operator] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                                    x-text="walletMsg['trf_'+w.operator]"></div>
                                <input type="text" inputmode="numeric" :value="transferAmountDisplays[w.operator] || ''" @input="formatAmountInput($event, 'transfer', w.operator)" placeholder="Amount"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none mb-2">
                                <button @click="previewTransfer(w.operator, w.balance)" :disabled="walletTransferLoading[w.operator]"
                                    class="w-full py-1.5 bg-gblue-500 text-white rounded-lg text-xs font-medium hover:bg-gblue-600 transition disabled:opacity-50">
                                    <span x-show="!walletTransferLoading[w.operator]">Review & Request →</span>
                                    <span x-show="walletTransferLoading[w.operator]">...</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Transfer Summary/Confirmation Modal -->
                <div x-show="trfSummaryOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="trfSummaryOpen = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" @click.outside="trfSummaryOpen = false">
                        <div class="px-6 py-4 border-b bg-gray-50 rounded-t-2xl">
                            <h3 class="text-lg font-semibold text-gray-800">Transfer Summary</h3>
                            <p class="text-xs text-gray-500 mt-1">Review before submitting. Requires admin approval.</p>
                        </div>
                        <div class="px-6 py-5 space-y-3">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Operator</span><span class="font-medium text-gray-800" x-text="trfSummary.operator"></span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Amount</span><span class="font-bold text-gray-800" x-text="formatAmount(trfSummary.amount) + ' ' + walletCurrency"></span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">From</span><span class="font-medium text-green-600">Collection Wallet</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">To</span><span class="font-medium text-blue-600">Disbursement Wallet</span></div>
                            <div class="border-t pt-3 flex justify-between text-sm"><span class="text-gray-500">Available Balance</span><span class="font-medium" :class="trfSummary.available >= trfSummary.amount ? 'text-green-600' : 'text-red-600'" x-text="formatAmount(trfSummary.available) + ' ' + walletCurrency"></span></div>
                        </div>
                        <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-end space-x-3">
                            <button @click="trfSummaryOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                            <button @click="confirmTransfer()" :disabled="walletTransferLoading[trfSummary.operator]" class="px-5 py-2 text-sm font-medium text-white bg-gblue-600 rounded-lg hover:bg-gblue-700 transition disabled:opacity-50">
                                <span x-show="!walletTransferLoading[trfSummary.operator]">Confirm Request</span>
                                <span x-show="walletTransferLoading[trfSummary.operator]">Submitting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======= DISBURSEMENT SUB-TAB ======= -->
            <div x-show="walletSubTab === 'disbursement'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <template x-for="w in disbursementWallets" :key="w.id">
                        <div class="bg-white rounded-xl shadow-md border p-5">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full" :class="operatorColor(w.operator)"></div>
                                    <h4 class="text-sm font-semibold text-gray-700" x-text="w.operator"></h4>
                                </div>
                            </div>
                            <p class="text-base font-semibold text-gray-800" x-text="formatAmount(w.balance) + ' ' + walletCurrency"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Pending Transfer Requests -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden mt-6 mb-6">
                <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="text-md font-semibold text-gray-700">Transfer Requests (Collection → Disbursement)</h3>
                    <button @click="fetchMyTransfers()" class="text-xs text-gblue-500 hover:text-gblue-700 font-medium">Refresh</button>
                </div>
                <div x-show="myTransfersLoading" class="p-6 text-center text-gray-500">
                    <svg class="animate-spin h-6 w-6 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!myTransfersLoading && myTransfers.length === 0" x-cloak class="p-6 text-center text-gray-500 text-sm">No transfer requests yet.</div>
                <div x-show="!myTransfersLoading && myTransfers.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="t in myTransfers" :key="t.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="t.reference"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="operatorBadgeColor(t.operator)"
                                                x-text="t.operator"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800" x-text="formatAmount(t.amount) + ' ' + walletCurrency"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-gyellow-50 text-gyellow-700': t.status==='pending', 'bg-ggreen-50 text-ggreen-700': t.status==='approved', 'bg-gred-50 text-gred-700': t.status==='rejected'}"
                                                x-text="t.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="formatDate(t.created_at)"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="t.admin_notes || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Wallet Transactions -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">Wallet Transactions</h3>
                    <div class="flex space-x-2">
                        <select x-model="walletTxnOperatorFilter" @change="wtPage = 1; fetchWalletTransactions()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">All Operators</option>
                            <template x-for="op in operators" :key="op">
                                <option :value="op" x-text="op"></option>
                            </template>
                        </select>
                        <select x-model="walletTxnTypeFilter" @change="wtPage = 1; fetchWalletTransactions()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">All Wallets</option>
                            <option value="collection">Collection</option>
                            <option value="disbursement">Disbursement</option>
                        </select>
                    </div>
                </div>
                <div x-show="walletLoading.txns" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!walletLoading.txns && walletTransactions.length === 0" x-cloak class="p-8 text-center text-gray-500">No wallet transactions yet.</div>
                <div x-show="!walletLoading.txns && walletTransactions.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wallet</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance After</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="wt in walletTransactions" :key="wt.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="wt.reference"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="operatorBadgeColor(wt.operator)"
                                                x-text="wt.operator || '-'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="wt.wallet_type==='collection' ? 'bg-ggreen-50 text-ggreen-700' : 'bg-gblue-50 text-gblue-700'"
                                                x-text="wt.wallet_type || '-'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="wt.type==='credit' ? 'bg-ggreen-50 text-ggreen-700' : 'bg-gred-50 text-gred-700'"
                                                x-text="wt.type"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold" :class="wt.type==='credit' ? 'text-green-600' : 'text-red-600'">
                                            <span x-text="(wt.type==='credit' ? '+' : '-') + ' ' + formatAmount(wt.amount) + ' ' + walletCurrency"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="formatAmount(wt.balance_after) + ' ' + walletCurrency"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="wt.description || '-'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(wt.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="wtPagination.total > 0" class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="wtPagination.from||0"></span> to <span x-text="wtPagination.to||0"></span> of <span x-text="wtPagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="goToWtPage(wtPagination.current_page-1)" :disabled="!wtPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="goToWtPage(wtPagination.current_page+1)" :disabled="!wtPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SETTLEMENTS TAB ==================== -->
        <div x-show="activeTab === 'settlements'">
            <!-- New Settlement Form -->
            <div x-show="hasPerm('create_settlement')" class="bg-white rounded-xl shadow-md border p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Settlement</h3>
                <p class="text-sm text-gray-500 mb-4">Settlement debits from the selected operator's <span class="font-medium text-ggreen-600">Collection wallet</span>. Make sure you have enough balance.</p>
                <div x-show="settlementMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="settlementMsgType==='success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="settlementMsg"></div>
                <form @submit.prevent="previewSettlement()" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
                        <select x-model="stlForm.operator" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">Select Operator</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Tigo Pesa">Tigo Pesa</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Halopesa">Halopesa</option>
                        </select>
                        <p x-show="stlForm.operator" x-cloak class="mt-1.5 text-xs font-medium" :class="(collectionWallets.find(w => w.operator === stlForm.operator)?.balance || 0) > 0 ? 'text-green-600' : 'text-red-500'">
                            <span class="text-gray-500">Available:</span>
                            <span x-text="formatAmount(collectionWallets.find(w => w.operator === stlForm.operator)?.balance || 0) + ' ' + walletCurrency"></span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Amount (min 1,000 ' + walletCurrency + ')'"></label>
                        <input type="text" inputmode="numeric" x-model="stlAmountDisplay" @input="formatAmountInput($event, 'settlement')" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account</label>
                        <select x-model="stlForm.bank_account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">Select Bank Account</option>
                            <template x-for="ba in bankAccounts" :key="ba.id">
                                <option :value="ba.id" x-text="ba.bank_name + ' — ' + ba.account_number + ' (' + ba.account_name + ')' + (ba.is_default ? ' ★' : '')"></option>
                            </template>
                        </select>
                        <p x-show="bankAccounts.length === 0 && !bankAccountsLoading" class="text-xs text-red-500 mt-1">No bank accounts saved. <a href="#" @click.prevent="goToTab('account')" class="underline text-gblue-500">Add one in Account tab</a>.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" x-model="stlForm.description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="Settlement description">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="stlPreviewLoading" class="bg-gblue-500 text-white px-6 py-2 rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                            <span x-show="!stlPreviewLoading">Review & Submit</span>
                            <span x-show="stlPreviewLoading">Calculating...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Settlement Summary/Confirmation Modal -->
            <div x-show="stlSummaryOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="stlSummaryOpen = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="stlSummaryOpen = false">
                    <div class="px-6 py-4 border-b bg-gray-50 rounded-t-2xl">
                        <h3 class="text-lg font-semibold text-gray-800">Settlement Summary</h3>
                        <p class="text-xs text-gray-500 mt-1">Please review the details below before confirming.</p>
                    </div>
                    <div class="px-6 py-5 space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Operator</span><span class="font-medium text-gray-800" x-text="stlSummary.operator"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Settlement Amount</span><span class="font-medium text-gray-800" x-text="formatAmount(stlSummary.amount) + ' ' + walletCurrency"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Service Charge</span><span class="font-medium" :class="stlSummary.total_charge > 0 ? 'text-orange-600' : 'text-gray-800'" x-text="formatAmount(stlSummary.total_charge) + ' ' + walletCurrency"></span></div>
                        <div class="flex justify-between text-sm font-bold bg-blue-50 rounded-lg px-3 py-2"><span class="text-gray-700">Total Debit</span><span class="text-blue-700" x-text="formatAmount(stlSummary.total_debit) + ' ' + walletCurrency"></span></div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Bank</span><span class="font-medium text-gray-800" x-text="stlSummary.bank_name"></span></div>
                            <div class="flex justify-between text-sm mt-1"><span class="text-gray-500">Account</span><span class="font-medium text-gray-800" x-text="stlSummary.account_number + ' (' + stlSummary.account_name + ')'"></span></div>
                        </div>
                        <div x-show="stlSummary.description" class="flex justify-between text-sm"><span class="text-gray-500">Description</span><span class="font-medium text-gray-800" x-text="stlSummary.description"></span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Wallet Balance</span><span class="font-medium" :class="stlSummary.available_balance >= stlSummary.total_debit ? 'text-green-600' : 'text-red-600'" x-text="formatAmount(stlSummary.available_balance) + ' ' + walletCurrency"></span></div>
                    </div>
                    <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-end space-x-3">
                        <button @click="stlSummaryOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button @click="confirmSettlement()" :disabled="stlLoading" class="px-5 py-2 text-sm font-medium text-white bg-gblue-600 rounded-lg hover:bg-gblue-700 transition disabled:opacity-50">
                            <span x-show="!stlLoading">Confirm & Submit</span>
                            <span x-show="stlLoading">Submitting...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Settlement Receipt Modal -->
            <div x-show="stlReceiptOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="stlReceiptOpen = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="stlReceiptOpen = false" id="stlReceiptContent">
                    <div class="px-6 py-4 border-b bg-gradient-to-r from-gblue-600 to-gblue-700 rounded-t-2xl text-center">
                        <div class="flex justify-center mb-2">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Settlement Receipt</h3>
                        <p class="text-xs text-blue-100 mt-1">Your settlement request has been submitted</p>
                    </div>
                    <template x-if="stlReceipt">
                        <div>
                            <div class="px-6 py-5 space-y-3">
                                <div class="text-center mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="{'bg-yellow-50 text-yellow-700 border border-yellow-200': stlReceipt.status==='pending','bg-blue-50 text-blue-700 border border-blue-200': stlReceipt.status==='processing','bg-green-50 text-green-700 border border-green-200': stlReceipt.status==='completed','bg-red-50 text-red-700 border border-red-200': stlReceipt.status==='failed'}"
                                        x-text="'Status: ' + stlReceipt.status"></span>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <div class="text-xs text-gray-500 mb-1">Reference</div>
                                    <div class="text-sm font-mono font-bold text-gray-800" x-text="stlReceipt.settlement_ref"></div>
                                </div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Operator</span><span class="font-medium text-gray-800" x-text="stlReceipt.operator"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Amount</span><span class="font-medium text-gray-800" x-text="formatAmount(stlReceipt.amount) + ' ' + stlReceipt.currency"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Service Charge</span><span class="font-medium text-orange-600" x-text="formatAmount(stlReceipt.service_charge) + ' ' + stlReceipt.currency"></span></div>
                                <div class="flex justify-between text-sm font-bold border-t border-b py-2"><span class="text-gray-700">Total Debited</span><span class="text-gblue-700" x-text="formatAmount(stlReceipt.total_debit) + ' ' + stlReceipt.currency"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Bank</span><span class="font-medium text-gray-800" x-text="stlReceipt.bank_name"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Account</span><span class="font-medium text-gray-800" x-text="stlReceipt.account_number + ' (' + stlReceipt.account_name + ')'"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Description</span><span class="font-medium text-gray-800" x-text="stlReceipt.description"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Date</span><span class="font-medium text-gray-800" x-text="formatDate(stlReceipt.created_at)"></span></div>
                            </div>
                            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-between">
                                <button @click="downloadSettlementPdf(stlReceipt)" class="px-4 py-2 text-sm font-medium text-gblue-700 bg-white border border-gblue-300 rounded-lg hover:bg-gblue-50 transition inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Download PDF
                                </button>
                                <button @click="stlReceiptOpen = false" class="px-5 py-2 text-sm font-medium text-white bg-gblue-600 rounded-lg hover:bg-gblue-700 transition">Done</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Settlements Table -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">Settlement History</h3>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="stlSearch" @input.debounce.400ms="stlPage = 1; fetchSettlements()" placeholder="Search ref, bank, amount..." class="w-full sm:w-52 pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <select x-model="stlFilterStatus" @change="stlPage = 1; fetchSettlements()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div x-show="stlLoadingList" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!stlLoadingList && settlements.length === 0" x-cloak class="p-8 text-center text-gray-500">No settlements found.</div>
                <div x-show="!stlLoadingList && settlements.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="stl in settlements" :key="stl.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="stl.settlement_ref"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="operatorBadgeColor(stl.operator)"
                                                x-text="stl.operator || '-'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="formatAmount(stl.amount) + ' ' + stl.currency"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="stl.bank_name"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span x-text="stl.account_name"></span><br>
                                            <span class="text-xs text-gray-400" x-text="stl.account_number"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-gyellow-50 text-gyellow-700': stl.status==='pending','bg-gblue-50 text-gblue-700': stl.status==='processing','bg-ggreen-50 text-ggreen-700': stl.status==='completed','bg-gred-50 text-gred-700': stl.status==='failed','bg-gray-100 text-gray-800': stl.status==='cancelled'}"
                                                x-text="stl.status"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(stl.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <button @click="viewSettlementReceipt(stl)" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg transition"
                                                :class="stl.status === 'completed' ? 'text-green-700 bg-green-50 hover:bg-green-100 border border-green-200' : 'text-gblue-700 bg-gblue-50 hover:bg-gblue-100 border border-gblue-200'">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span x-text="stl.status === 'completed' ? 'Download' : 'View'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="stlPagination.total > 0" class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="stlPagination.from||0"></span> to <span x-text="stlPagination.to||0"></span> of <span x-text="stlPagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="goToStlPage(stlPagination.current_page-1)" :disabled="!stlPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="goToStlPage(stlPagination.current_page+1)" :disabled="!stlPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== INVOICES TAB ==================== -->
        <div x-show="activeTab === 'invoices'" x-cloak>
            <!-- Create Invoice Form -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-8">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-gblue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                    <h3 class="text-lg font-semibold text-gray-800">Create Invoice</h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">Generate an invoice for manual C2B payment. The customer pays using the paybill/till number and reference you provide.</p>
                <div x-show="invoiceMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="invoiceMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="invoiceMsg"></div>
                <form @submit.prevent="createInvoice()" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Amount (' + walletCurrency + ')'"></label>
                        <input type="text" inputmode="numeric" x-model="invoiceAmountDisplay" @input="formatAmountInput($event, 'invoice')" required placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expires In</label>
                        <select x-model="invoiceForm.expires_in" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">7 days (default)</option>
                            <option value="60">1 hour</option>
                            <option value="360">6 hours</option>
                            <option value="1440">24 hours</option>
                            <option value="4320">3 days</option>
                            <option value="10080">7 days</option>
                            <option value="43200">30 days</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" x-model="invoiceForm.description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="e.g. Payment for services rendered">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="invoiceLoading" class="bg-gblue-500 text-white px-6 py-2 rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                            <span x-show="!invoiceLoading">Create Invoice</span>
                            <span x-show="invoiceLoading">Creating...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Invoice Detail Modal -->
            <div x-show="invoiceDetailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="invoiceDetailOpen = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="invoiceDetailOpen = false">
                    <div class="px-6 py-4 border-b bg-gradient-to-r from-gblue-600 to-gblue-700 rounded-t-2xl text-center">
                        <div class="flex justify-center mb-2">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Invoice Details</h3>
                        <p class="text-xs text-blue-100 mt-1">Share these details with your customer</p>
                    </div>
                    <template x-if="invoiceDetail">
                        <div>
                            <div class="px-6 py-5 space-y-3">
                                <div class="text-center mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="{'bg-yellow-50 text-yellow-700 border border-yellow-200': invoiceDetail.status==='waiting','bg-green-50 text-green-700 border border-green-200': invoiceDetail.status==='completed','bg-red-50 text-red-700 border border-red-200': invoiceDetail.status==='failed' || invoiceDetail.status==='cancelled','bg-blue-50 text-blue-700 border border-blue-200': invoiceDetail.status==='processing'}"
                                        x-text="'Status: ' + invoiceDetail.status"></span>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                                    <div class="text-xs font-medium text-amber-600 mb-1">Customer pays with this reference</div>
                                    <div class="text-2xl font-bold text-gray-900 tracking-widest flex items-center justify-center gap-2">
                                        <span x-text="invoiceDetail.external_ref"></span>
                                        <button @click="copyToClipboard(invoiceDetail.external_ref)" class="text-gblue-500 hover:text-gblue-700" title="Copy">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Amount</span><span class="font-medium text-gray-800" x-text="formatAmount(invoiceDetail.amount) + ' ' + (invoiceDetail.currency || walletCurrency)"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Network</span><span class="font-medium text-gray-800" x-text="invoiceDetail.operator_name || 'Pending payment'"></span></div>
                                <div x-show="invoiceDetail.description" class="flex justify-between text-sm"><span class="text-gray-500">Description</span><span class="font-medium text-gray-800" x-text="invoiceDetail.description"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Expires</span><span class="font-medium text-gray-800" x-text="invoiceDetail.error_message ? formatDate(invoiceDetail.error_message) : 'N/A'"></span></div>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Created</span><span class="font-medium text-gray-800" x-text="formatDate(invoiceDetail.created_at)"></span></div>
                                <div class="flex justify-between text-sm text-gray-400"><span>System ID</span><span class="font-mono text-xs" x-text="invoiceDetail.request_ref"></span></div>
                            </div>
                            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex justify-between">
                                <button x-show="invoiceDetail.status === 'waiting'" @click="cancelInvoice(invoiceDetail.request_ref)" class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition">Cancel Invoice</button>
                                <div x-show="invoiceDetail.status !== 'waiting'"></div>
                                <button @click="invoiceDetailOpen = false" class="px-5 py-2 text-sm font-medium text-white bg-gblue-600 rounded-lg hover:bg-gblue-700 transition">Close</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">Invoice History</h3>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="invoiceSearch" @input.debounce.400ms="invoicePage = 1; fetchInvoices()" placeholder="Search ref, amount..." class="w-full sm:w-52 pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <select x-model="invoiceFilterStatus" @change="invoicePage = 1; fetchInvoices()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">All Status</option>
                            <option value="waiting">Waiting</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div x-show="invoicesLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!invoicesLoading && invoices.length === 0" x-cloak class="p-8 text-center text-gray-500">No invoices found.</div>
                <div x-show="!invoicesLoading && invoices.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Network</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="inv in invoices" :key="inv.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-mono font-bold text-gray-900 tracking-wide" x-text="inv.external_ref || inv.request_ref"></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="formatAmount(inv.amount) + ' ' + (inv.currency || walletCurrency)"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-gyellow-50 text-gyellow-700': inv.status==='waiting','bg-ggreen-50 text-ggreen-700': inv.status==='completed','bg-gred-50 text-gred-700': inv.status==='failed' || inv.status==='cancelled','bg-gblue-50 text-gblue-700': inv.status==='processing'}"
                                                x-text="inv.status"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span x-show="inv.operator_name" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="operatorBadgeColor(inv.operator_name)" x-text="inv.operator_name"></span>
                                            <span x-show="!inv.operator_name" class="text-gray-400 text-xs">Pending</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="inv.error_message ? formatDate(inv.error_message) : '-'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(inv.created_at)"></td>
                                        <td class="px-6 py-4 flex items-center gap-2">
                                            <button @click="viewInvoice(inv)" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gblue-700 bg-gblue-50 hover:bg-gblue-100 border border-gblue-200 rounded-lg transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View
                                            </button>
                                            <button x-show="inv.status === 'waiting'" @click="cancelInvoice(inv.request_ref)" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Cancel
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="invoicePagination.total > 0" class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="invoicePagination.from||0"></span> to <span x-text="invoicePagination.to||0"></span> of <span x-text="invoicePagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="invoicePage--; fetchInvoices()" :disabled="!invoicePagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="invoicePage++; fetchInvoices()" :disabled="!invoicePagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== USERS TAB ==================== -->
        <div x-show="activeTab === 'users'" x-cloak>
            <!-- Add User Form (owner/admin only) -->
            <div x-show="hasPerm('add_user')" class="bg-white rounded-xl shadow-md border p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Add User to Account</h3>
                <div x-show="addUserMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="addUserMsgType==='success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="addUserMsg"></div>
                <form @submit.prevent="addUser()">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" x-model="newUserForm.firstname" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" x-model="newUserForm.lastname" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="newUserForm.email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" x-model="newUserForm.password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select x-model="newUserForm.role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                <option value="viewer">Viewer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <!-- Permissions checkboxes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <template x-for="perm in allPermissions" :key="perm">
                                <label class="flex items-center space-x-2 bg-gray-50 border rounded-lg px-3 py-2 cursor-pointer hover:bg-gblue-50 transition text-sm">
                                    <input type="checkbox" :value="perm" x-model="newUserForm.permissions"
                                        class="rounded border-gray-300 text-gblue-500 focus:ring-gblue-500">
                                    <span class="text-gray-700" x-text="permLabel(perm)"></span>
                                </label>
                            </template>
                        </div>
                        <button type="button" @click="newUserForm.permissions = [...allPermissions]" class="text-xs text-gblue-500 hover:text-gblue-700 mt-2 mr-3">Select All</button>
                        <button type="button" @click="newUserForm.permissions = []" class="text-xs text-gray-500 hover:text-gray-700 mt-2">Clear All</button>
                    </div>

                    <div>
                        <button type="submit" :disabled="addUserLoading" class="bg-gblue-500 text-white px-6 py-2 rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                            <span x-show="!addUserLoading">Add User</span>
                            <span x-show="addUserLoading">Adding...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Users Table -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Account Users</h3>
                </div>
                <div x-show="accUsersLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!accUsersLoading && accountUsers.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                    <th x-show="user?.role === 'owner'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="u in accountUsers" :key="u.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="(u.firstname && u.lastname) ? (u.firstname + ' ' + u.lastname) : u.name"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="u.email"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-purple-100 text-purple-800': u.role==='owner','bg-gblue-50 text-gblue-700': u.role==='admin','bg-gray-100 text-gray-800': u.role==='viewer'}"
                                                x-text="u.role"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <template x-if="u.role === 'owner'">
                                                <span class="text-xs text-green-600 font-medium">All permissions</span>
                                            </template>
                                            <template x-if="u.role !== 'owner'">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="p in (u.permissions || [])" :key="p">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gblue-50 text-gblue-700" x-text="permLabel(p)"></span>
                                                    </template>
                                                    <span x-show="!u.permissions || u.permissions.length === 0" class="text-xs text-gray-400">None</span>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(u.created_at)"></td>
                                        <td x-show="user?.role === 'owner'" class="px-6 py-4">
                                            <template x-if="u.role !== 'owner' && u.id !== user?.id">
                                                <div class="flex flex-col space-y-2">
                                                    <div class="flex space-x-2">
                                                        <select @change="changeUserRole(u.id, $event.target.value)" class="text-xs border rounded px-2 py-1">
                                                            <option value="" selected disabled>Change role</option>
                                                            <option value="admin" x-show="u.role !== 'admin'">Admin</option>
                                                            <option value="viewer" x-show="u.role !== 'viewer'">Viewer</option>
                                                        </select>
                                                        <button @click="removeUser(u.id, (u.firstname && u.lastname) ? (u.firstname + ' ' + u.lastname) : u.name)" class="text-xs bg-gred-50 text-gred-700 px-2 py-1 rounded hover:bg-gred-100">Remove</button>
                                                    </div>
                                                    <button @click="editingPermUserId = u.id; editingPerms = [...(u.permissions || [])]"
                                                        class="text-xs bg-gblue-50 text-gblue-700 px-2 py-1 rounded hover:bg-gblue-100 text-left">
                                                        Edit Permissions
                                                    </button>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                    <!-- Inline permissions editor -->
                                    <tr x-show="editingPermUserId === u.id" x-cloak class="bg-gblue-50">
                                        <td colspan="6" class="px-6 py-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-sm font-semibold text-gray-700">Edit Permissions for <span x-text="(u.firstname && u.lastname) ? (u.firstname + ' ' + u.lastname) : u.name"></span></span>
                                                <div class="flex space-x-2">
                                                    <button type="button" @click="editingPerms = [...allPermissions]" class="text-xs text-gblue-500 hover:text-gblue-700">Select All</button>
                                                    <button type="button" @click="editingPerms = []" class="text-xs text-gray-500 hover:text-gray-700">Clear</button>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                                                <template x-for="perm in allPermissions" :key="'edit-'+perm">
                                                    <label class="flex items-center space-x-2 bg-white border rounded-lg px-3 py-2 cursor-pointer hover:bg-gblue-50 transition text-sm">
                                                        <input type="checkbox" :value="perm" x-model="editingPerms"
                                                            class="rounded border-gray-300 text-gblue-500 focus:ring-gblue-500">
                                                        <span class="text-gray-700" x-text="permLabel(perm)"></span>
                                                    </label>
                                                </template>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button @click="saveUserPermissions(u.id)" class="text-xs bg-gblue-500 text-white px-4 py-1.5 rounded-lg hover:bg-gblue-600">Save Permissions</button>
                                                <button @click="editingPermUserId = null" class="text-xs bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-300">Cancel</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ACCOUNT INFO TAB ==================== -->
        <div x-show="activeTab === 'account'" x-cloak class="mt-6">
            <div class="max-w-4xl">
                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                    <div class="bg-gradient-to-r from-gblue-500 to-indigo-600 px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white" x-text="kycData.business_name || 'Account Information'"></h3>
                                <p class="text-sm text-white/70" x-text="kycData.business_type ? (kycData.business_type.replace('_',' ')) : 'Complete your KYC to get started'"></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold capitalize"
                            :class="{'bg-yellow-400/20 text-yellow-100 border border-yellow-400/30': kycData.status==='pending', 'bg-green-400/20 text-green-100 border border-green-400/30': kycData.status==='active', 'bg-red-400/20 text-red-100 border border-red-400/30': kycData.status==='suspended', 'bg-white/20 text-white/80 border border-white/30': !kycData.status}"
                            x-text="kycData.status || 'Not submitted'"></span>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 bg-gray-50">
                        <nav class="flex">
                            <button @click="accountInfoTab = 'business'" :class="accountInfoTab === 'business' ? 'border-gblue-500 text-gblue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 py-3 px-4 border-b-2 font-medium text-sm transition text-center flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                Business
                            </button>
                            <button @click="accountInfoTab = 'identity'" :class="accountInfoTab === 'identity' ? 'border-gblue-500 text-gblue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 py-3 px-4 border-b-2 font-medium text-sm transition text-center flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"></path></svg>
                                Identity
                            </button>
                            <button @click="accountInfoTab = 'bank'" :class="accountInfoTab === 'bank' ? 'border-gblue-500 text-gblue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 py-3 px-4 border-b-2 font-medium text-sm transition text-center flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Bank Accounts
                            </button>
                            <button @click="accountInfoTab = 'documents'" :class="accountInfoTab === 'documents' ? 'border-gblue-500 text-gblue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 py-3 px-4 border-b-2 font-medium text-sm transition text-center flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Documents
                            </button>
                            <button @click="accountInfoTab = 'crypto'" :class="accountInfoTab === 'crypto' ? 'border-gblue-500 text-gblue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 py-3 px-4 border-b-2 font-medium text-sm transition text-center flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Crypto
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">

                    <!-- Stepper Progress (only when not approved or update allowed) -->
                    <div x-show="kycData.status !== 'active' || kycData.kyc_update_allowed" class="flex items-center mb-8">
                        <template x-for="(step, idx) in [{n:1, label:'Business Info'}, {n:2, label:'ID Verification'}, {n:3, label:'Documents'}, {n:4, label:'Crypto Wallet'}]" :key="step.n">
                            <div class="flex items-center" :class="idx < 3 ? 'flex-1' : ''">
                                <button type="button" @click="kycStep = step.n" class="flex items-center space-x-2 group">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition"
                                        :class="kycStep === step.n ? 'bg-gblue-500 text-white' : (kycStep > step.n ? 'bg-ggreen-500 text-white' : 'bg-gray-200 text-gray-500')">
                                        <span x-show="kycStep <= step.n" x-text="step.n"></span>
                                        <svg x-show="kycStep > step.n" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="text-sm font-medium hidden sm:inline" :class="kycStep === step.n ? 'text-gblue-500' : 'text-gray-500'" x-text="step.label"></span>
                                </button>
                                <div x-show="idx < 3" class="flex-1 h-0.5 mx-3" :class="kycStep > step.n ? 'bg-ggreen-400' : 'bg-gray-200'"></div>
                            </div>
                        </template>
                    </div>

                    <!-- ===== APPROVED: Read-only KYC Summary with Tabs ===== -->
                    <div x-show="kycData.status === 'active' && !kycData.kyc_update_allowed && !kycFormLoading" x-cloak>
                        <div class="mb-6 p-4 bg-ggreen-50 border border-ggreen-200 rounded-xl flex items-center">
                            <svg class="w-6 h-6 text-ggreen-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <p class="text-sm font-semibold text-ggreen-800">KYC Verified</p>
                                <p class="text-xs text-ggreen-600">Approved on <span x-text="formatDate(kycData.kyc_approved_at)"></span>. Your account is fully active.</p>
                            </div>
                        </div>

                        <!-- TAB 1: Business Information -->
                        <div x-show="accountInfoTab === 'business'">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                Business Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">Business Name</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycData.business_name || '—'"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">Business Type</p>
                                    <p class="text-sm font-medium text-gray-800 capitalize" x-text="(kycData.business_type || '—').replace('_', ' ')"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">Registration Number</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycData.registration_number || '—'"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">TIN Number</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycData.tin_number || '—'"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg px-4 py-3 md:col-span-2">
                                    <p class="text-xs text-gray-500">Address</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="[kycData.address, kycData.city, kycData.country].filter(Boolean).join(', ') || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Identity Verification -->
                        <div x-show="accountInfoTab === 'identity'" x-cloak>
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"></path></svg>
                                ID Verification
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">ID Type</p>
                                    <p class="text-sm font-medium text-gray-800 capitalize" x-text="(kycData.id_type || '—').replace('_', ' ')"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <p class="text-xs text-gray-500">ID Number</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycData.id_number || '—'"></p>
                                </div>
                            </div>
                            <!-- ID Document preview -->
                            <div x-show="kycData.id_document_url" class="mt-4 bg-gray-50 rounded-lg px-4 py-3 flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                <a :href="'{{ config('services.auth_service.public_url') }}' + kycData.id_document_url" target="_blank" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">View ID Document &rarr;</a>
                            </div>
                        </div>

                        <!-- TAB 3: Bank Accounts -->
                        <div x-show="accountInfoTab === 'bank'" x-cloak>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    Bank Accounts
                                </h4>
                                <span class="text-xs text-gray-400" x-text="bankAccounts.length + ' account(s)'"></span>
                            </div>

                            <!-- Existing bank accounts list -->
                            <div x-show="bankAccounts.length > 0" class="space-y-2 mb-4">
                                <template x-for="ba in bankAccounts" :key="ba.id">
                                    <div class="bg-gray-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-medium text-gray-800" x-text="ba.bank_name"></p>
                                                <span x-show="ba.is_default" class="text-xs bg-gblue-100 text-gblue-700 px-1.5 py-0.5 rounded">Default</span>
                                                <span x-show="ba.label" class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="ba.label"></span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="ba.account_number + ' — ' + ba.account_name"></p>
                                        </div>
                                        <div class="flex items-center gap-1 ml-3">
                                            <button x-show="!ba.is_default" @click="setDefaultBank(ba.id)" class="text-xs text-gblue-500 hover:text-gblue-700 px-2 py-1" title="Set as default">★</button>
                                            <button @click="deleteBankAccount(ba.id)" class="text-xs text-red-500 hover:text-red-700 px-2 py-1" title="Remove">✕</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p x-show="bankAccounts.length === 0 && !bankAccountsLoading" class="text-sm text-gray-400 mb-4">No bank accounts added yet.</p>

                            <!-- Add bank account form -->
                            <button x-show="!showBankForm" @click="showBankForm = true" class="text-sm text-gblue-500 hover:text-gblue-700 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add Bank Account
                            </button>
                            <div x-show="showBankForm" x-cloak class="border border-gray-200 rounded-lg p-4 mt-2">
                                <div x-show="bankMsg" class="mb-3 p-2 rounded text-sm" :class="bankMsgType==='success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" x-text="bankMsg"></div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Bank Name *</label>
                                        <input type="text" x-model="bankForm.bank_name" required placeholder="e.g. CRDB Bank" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Name *</label>
                                        <input type="text" x-model="bankForm.account_name" required placeholder="Account holder name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Number *</label>
                                        <input type="text" x-model="bankForm.account_number" required placeholder="Bank account number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                                        <input type="text" x-model="bankForm.label" placeholder="e.g. Main, Payroll" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">SWIFT Code</label>
                                        <input type="text" x-model="bankForm.swift_code" placeholder="e.g. COLOTZTZ" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                                        <input type="text" x-model="bankForm.branch" placeholder="Branch name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <button @click="addBankAccount()" :disabled="bankFormLoading || !bankForm.bank_name || !bankForm.account_name || !bankForm.account_number" class="bg-gblue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gblue-600 disabled:opacity-50">
                                        <span x-show="!bankFormLoading">Save</span><span x-show="bankFormLoading">Saving...</span>
                                    </button>
                                    <button @click="showBankForm = false; bankMsg = ''" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">Cancel</button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: Documents -->
                        <div x-show="accountInfoTab === 'documents'" x-cloak>
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Uploaded Documents
                            </h4>
                            <div class="space-y-3">
                                <div x-show="kycData.id_document_url" class="bg-gray-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <span class="text-sm text-gray-700">ID Document</span>
                                    </div>
                                    <a :href="'{{ config('services.auth_service.public_url') }}' + kycData.id_document_url" target="_blank" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">View &rarr;</a>
                                </div>
                                <div x-show="kycData.business_license_url" class="bg-gray-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <span class="text-sm text-gray-700">Business License</span>
                                    </div>
                                    <a :href="'{{ config('services.auth_service.public_url') }}' + kycData.business_license_url" target="_blank" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">View &rarr;</a>
                                </div>
                                <div x-show="!kycData.id_document_url && !kycData.business_license_url" class="text-sm text-gray-400">No documents uploaded yet.</div>
                            </div>
                        </div>

                        <!-- TAB 5: Crypto Wallets -->
                        <div x-show="accountInfoTab === 'crypto'" x-cloak>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Crypto Wallets
                                </h4>
                                <span class="text-xs text-gray-400" x-text="cryptoWallets.length + ' wallet(s)'"></span>
                            </div>

                            <!-- Existing crypto wallets list -->
                            <div x-show="cryptoWallets.length > 0" class="space-y-2 mb-4">
                                <template x-for="cw in cryptoWallets" :key="cw.id">
                                    <div class="bg-gray-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-medium text-gray-800" x-text="cw.currency + ' (' + cw.network + ')'"></p>
                                                <span x-show="cw.is_default" class="text-xs bg-gblue-100 text-gblue-700 px-1.5 py-0.5 rounded">Default</span>
                                                <span x-show="cw.label" class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="cw.label"></span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5 font-mono break-all" x-text="cw.wallet_address"></p>
                                        </div>
                                        <div class="flex items-center gap-1 ml-3">
                                            <button x-show="!cw.is_default" @click="setDefaultCrypto(cw.id)" class="text-xs text-gblue-500 hover:text-gblue-700 px-2 py-1" title="Set as default">★</button>
                                            <button @click="deleteCryptoWallet(cw.id)" class="text-xs text-red-500 hover:text-red-700 px-2 py-1" title="Remove">✕</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p x-show="cryptoWallets.length === 0 && !cryptoWalletsLoading" class="text-sm text-gray-400 mb-4">No crypto wallets added yet.</p>

                            <!-- Add crypto wallet form -->
                            <button x-show="!showCryptoForm" @click="showCryptoForm = true" class="text-sm text-gblue-500 hover:text-gblue-700 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add Crypto Wallet
                            </button>
                            <div x-show="showCryptoForm" x-cloak class="border border-gray-200 rounded-lg p-4 mt-2">
                                <div x-show="cryptoMsg" class="mb-3 p-2 rounded text-sm" :class="cryptoMsgType==='success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" x-text="cryptoMsg"></div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Currency *</label>
                                        <select x-model="cryptoForm.currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select currency</option>
                                            <option value="BTC">BTC - Bitcoin</option>
                                            <option value="ETH">ETH - Ethereum</option>
                                            <option value="USDT">USDT - Tether</option>
                                            <option value="USDC">USDC - USD Coin</option>
                                            <option value="BNB">BNB - Binance Coin</option>
                                            <option value="SOL">SOL - Solana</option>
                                            <option value="XRP">XRP - Ripple</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Network *</label>
                                        <select x-model="cryptoForm.network" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select network</option>
                                            <option value="Bitcoin">Bitcoin</option>
                                            <option value="Ethereum">Ethereum (ERC-20)</option>
                                            <option value="BSC">BSC (BEP-20)</option>
                                            <option value="Tron">Tron (TRC-20)</option>
                                            <option value="Solana">Solana</option>
                                            <option value="Polygon">Polygon</option>
                                            <option value="Arbitrum">Arbitrum</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Wallet Address *</label>
                                        <input type="text" x-model="cryptoForm.wallet_address" required placeholder="Enter wallet address" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                                        <input type="text" x-model="cryptoForm.label" placeholder="e.g. Main, Trading" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <button @click="addCryptoWallet()" :disabled="cryptoFormLoading || !cryptoForm.currency || !cryptoForm.network || !cryptoForm.wallet_address" class="bg-gblue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gblue-600 disabled:opacity-50">
                                        <span x-show="!cryptoFormLoading">Save</span><span x-show="cryptoFormLoading">Saving...</span>
                                    </button>
                                    <button @click="showCryptoForm = false; cryptoMsg = ''" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== NOT APPROVED OR UPDATE ALLOWED: Show KYC Form ===== -->
                    <div x-show="kycData.status !== 'active' || kycData.kyc_update_allowed">

                    <!-- Update Permission Banner -->
                    <div x-show="kycData.status === 'active' && kycData.kyc_update_allowed" x-cloak class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">Update Permission Granted</p>
                            <p class="text-xs text-blue-600">Admin has allowed you to update your business details. Make your changes and save. Permission will be revoked after saving.</p>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div x-show="kycData.kyc_notes && kycData.status !== 'active'" x-cloak class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                        <strong>Admin Notes:</strong> <span x-text="kycData.kyc_notes"></span>
                    </div>
                    <div x-show="kycMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                        :class="kycMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                        x-text="kycMsg"></div>

                    <!-- Loading -->
                    <div x-show="kycFormLoading" class="py-8 text-center text-gray-500">
                        <svg class="animate-spin h-6 w-6 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>

                    <form x-show="!kycFormLoading" x-cloak @submit.prevent="saveKyc()" enctype="multipart/form-data">

                        <!-- ===== STEP 1: Business Information ===== -->
                        <div x-show="kycStep === 1">
                            <h4 class="text-md font-semibold text-gray-800 mb-1">Business Information</h4>
                            <p class="text-sm text-gray-500 mb-5">Provide your company or business details.</p>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Business Name <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="kycForm.business_name"
                                            :class="kycErrors.business_name ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        <p x-show="kycErrors.business_name" x-text="kycErrors.business_name" class="text-xs text-red-500 mt-1"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Business Type</label>
                                        <select x-model="kycForm.business_type"
                                            :class="kycErrors.business_type ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select type...</option>
                                            <option value="sole_proprietorship">Sole Proprietorship</option>
                                            <option value="partnership">Partnership</option>
                                            <option value="limited_company">Limited Company</option>
                                            <option value="corporation">Corporation</option>
                                            <option value="ngo">NGO</option>
                                            <option value="government">Government</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Registration Number</label>
                                        <input type="text" x-model="kycForm.registration_number" placeholder="Business registration no."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">TIN Number</label>
                                        <input type="text" x-model="kycForm.tin_number" placeholder="Tax Identification Number"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Street Address</label>
                                    <input type="text" x-model="kycForm.address" placeholder="Street / P.O. Box"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">City</label>
                                        <input type="text" x-model="kycForm.city" placeholder="City"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Country <span class="text-red-500">*</span></label>
                                        <select x-model="kycForm.country"
                                            :class="kycErrors.country ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select country...</option>
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
                                            <option value="Ethiopia">Ethiopia</option>
                                            <option value="Nigeria">Nigeria</option>
                                            <option value="Ghana">Ghana</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Bank Settlement Details -->
                                <div class="border-t border-gray-200 pt-4 mt-4">
                                    <h5 class="text-sm font-semibold text-gray-700 mb-2">Bank Settlement Details</h5>
                                    <p class="text-sm text-gray-500">Bank accounts are managed separately. You can add and manage bank accounts from the Account Info view above.</p>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="button" @click="validateKycStep1() && (kycStep = 2)"
                                    class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                                    Next: ID Verification &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- ===== STEP 2: ID Verification ===== -->
                        <div x-show="kycStep === 2" x-cloak>
                            <h4 class="text-md font-semibold text-gray-800 mb-1">ID Verification</h4>
                            <p class="text-sm text-gray-500 mb-5">Provide identification details for the business owner or authorized representative.</p>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">ID Type <span class="text-red-500">*</span></label>
                                        <select x-model="kycForm.id_type"
                                            :class="kycErrors.id_type ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select ID type...</option>
                                            <option value="national_id">National ID (NIDA)</option>
                                            <option value="passport">Passport</option>
                                            <option value="drivers_license">Driver's License</option>
                                        </select>
                                        <p x-show="kycErrors.id_type" x-text="kycErrors.id_type" class="text-xs text-red-500 mt-1"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">ID Number <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="kycForm.id_number" placeholder="ID document number"
                                            :class="kycErrors.id_number ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        <p x-show="kycErrors.id_number" x-text="kycErrors.id_number" class="text-xs text-red-500 mt-1"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" @click="kycStep = 1"
                                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                                    &larr; Back
                                </button>
                                <button type="button" @click="validateKycStep2() && (kycStep = 3)"
                                    class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                                    Next: Documents &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- ===== STEP 3: Document Upload ===== -->
                        <div x-show="kycStep === 3" x-cloak>
                            <h4 class="text-md font-semibold text-gray-800 mb-1">Document Upload</h4>
                            <p class="text-sm text-gray-500 mb-5">Upload copies of your ID and business license for verification.</p>

                            <div class="space-y-6">
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 hover:border-gblue-400 transition">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Document</label>
                                    <input type="file" @change="kycIdFile = $event.target.files[0]" accept=".jpg,.jpeg,.png,.pdf"
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 cursor-pointer">
                                    <p class="text-xs text-gray-400 mt-2">Accepted: JPG, PNG or PDF &middot; Max 5MB</p>
                                    <div x-show="kycData.id_document_url" x-cloak class="mt-3 flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <a :href="'{{ config('services.auth_service.public_url') }}' + kycData.id_document_url" target="_blank"
                                            class="text-xs text-gblue-500 hover:text-gblue-700 font-medium">View current document &rarr;</a>
                                    </div>
                                </div>

                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 hover:border-gblue-400 transition">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Business License / Registration Certificate</label>
                                    <input type="file" @change="kycLicenseFile = $event.target.files[0]" accept=".jpg,.jpeg,.png,.pdf"
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 cursor-pointer">
                                    <p class="text-xs text-gray-400 mt-2">Accepted: JPG, PNG or PDF &middot; Max 5MB</p>
                                    <div x-show="kycData.business_license_url" x-cloak class="mt-3 flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <a :href="'{{ config('services.auth_service.public_url') }}' + kycData.business_license_url" target="_blank"
                                            class="text-xs text-gblue-500 hover:text-gblue-700 font-medium">View current license &rarr;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" @click="kycStep = 2"
                                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                                    &larr; Back
                                </button>
                                <button type="button" @click="validateKycStep3() && (kycStep = 4)"
                                    class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                                    Next: Crypto Wallet &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- ===== STEP 4: Crypto Wallet Settlement ===== -->
                        <div x-show="kycStep === 4" x-cloak>
                            <h4 class="text-md font-semibold text-gray-800 mb-1">Crypto Wallet Settlement</h4>
                            <p class="text-sm text-gray-500 mb-5">Provide your cryptocurrency wallet details for settlement payouts.</p>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Crypto Currency</label>
                                        <select x-model="kycForm.crypto_currency"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select currency...</option>
                                            <option value="BTC">Bitcoin (BTC)</option>
                                            <option value="ETH">Ethereum (ETH)</option>
                                            <option value="USDT">Tether (USDT)</option>
                                            <option value="USDC">USD Coin (USDC)</option>
                                            <option value="BNB">BNB</option>
                                            <option value="SOL">Solana (SOL)</option>
                                            <option value="XRP">Ripple (XRP)</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">Network</label>
                                        <select x-model="kycForm.crypto_network"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                            <option value="">Select network...</option>
                                            <option value="Bitcoin">Bitcoin</option>
                                            <option value="Ethereum">Ethereum (ERC-20)</option>
                                            <option value="BSC">BNB Smart Chain (BEP-20)</option>
                                            <option value="Tron">Tron (TRC-20)</option>
                                            <option value="Solana">Solana</option>
                                            <option value="Polygon">Polygon</option>
                                            <option value="Arbitrum">Arbitrum</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Wallet Address</label>
                                    <input type="text" x-model="kycForm.crypto_wallet_address" placeholder="Enter your wallet address"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none font-mono">
                                    <p class="text-xs text-gray-400 mt-1">Double-check the address — crypto transactions are irreversible.</p>
                                </div>
                            </div>

                            <div class="flex justify-between mt-6">
                                <button type="button" @click="kycStep = 3"
                                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                                    &larr; Back
                                </button>
                                <button type="submit" :disabled="kycSaving"
                                    class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium disabled:opacity-50 transition">
                                    <span x-show="!kycSaving">Save & Submit KYC</span>
                                    <span x-show="kycSaving">Saving...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    </div><!-- end not-approved wrapper -->
                    </div><!-- end p-6 content wrapper -->
                </div><!-- end bg-white card -->
            </div><!-- end max-w-3xl -->
        </div><!-- end account tab -->

        <!-- ==================== SEND MONEY TAB ==================== -->
        <div x-show="activeTab === 'send-money'" x-cloak>
            <!-- Sub-tabs: Single / Batch / Approvals -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-6">
                    <button x-show="hasPerm('create_payout') || hasPerm('wallet_transfer')" @click="sendMoneySubTab = 'single'" :class="sendMoneySubTab === 'single' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Single Payout</button>
                    <button x-show="hasPerm('create_payout') || hasPerm('wallet_transfer')" @click="sendMoneySubTab = 'batch'" :class="sendMoneySubTab === 'batch' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Batch Payout</button>
                    <button x-show="hasPerm('approve_payout')" @click="sendMoneySubTab = 'approvals'; fetchPendingPayouts()" :class="sendMoneySubTab === 'approvals' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition relative">
                        Pending Approvals
                        <span x-show="pendingPayoutsCount > 0" x-cloak class="ml-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="pendingPayoutsCount"></span>
                    </button>
                </nav>
            </div>

            <!-- ======= SINGLE PAYOUT ======= -->
            <div x-show="sendMoneySubTab === 'single'">
                <div class="bg-white rounded-xl shadow-md border p-6 max-w-xl">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-gblue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Send Money to Phone</h3>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Send a payout (disbursement) directly to a mobile money number. Funds are debited from your disbursement wallet.</p>

                    <div x-show="payoutMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="payoutMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="payoutMsg"></div>

                    <form @submit.prevent="sendSinglePayout()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="text" x-model="payoutForm.phone" @input.debounce.400ms="detectOperator(payoutForm.phone)" @blur="autoFormatPhone('payout')" required :placeholder="'e.g. ' + (currencyPhoneMap[walletCurrency] || {example:'255712345678'}).example" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <!-- Detected Operator Badge -->
                            <div class="mt-2" x-show="detectedOperator.name" x-cloak>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-50 text-green-700 border border-green-200">
                                    <svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="detectedOperator.name"></span>
                                </span>
                            </div>
                            <div class="mt-2" x-show="payoutForm.phone.length >= 10 && !detectedOperator.name && !detectingOp" x-cloak>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-50 text-red-700 border border-red-200">
                                    <svg class="w-4 h-4 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Unknown operator
                                </span>
                            </div>
                            <div class="mt-1" x-show="detectingOp" x-cloak>
                                <span class="text-xs text-gray-400">Detecting operator...</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Amount (' + walletCurrency + ', min 100)'"></label>
                            <input type="text" inputmode="numeric" x-model="payoutAmountDisplay" required
                                placeholder="0"
                                @input="formatAmountInput($event, 'payout')"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>

                        <!-- Charge Summary Preview -->
                        <div x-show="payoutCharges && payoutCharges.platform_charge >= 0 && payoutForm.amount >= 100 && detectedOperator.code" x-cloak
                            class="p-4 rounded-lg border"
                            :class="payoutCharges.platform_charge > 0 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200'">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Charges &amp; Fees Summary
                            </h4>
                            <div x-show="payoutChargesLoading" class="text-xs text-gray-400">Calculating...</div>
                            <div x-show="!payoutChargesLoading" class="space-y-1.5 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Send Amount</span>
                                    <span class="font-medium text-gray-800" x-text="formatAmount(payoutForm.amount) + ' ' + walletCurrency"></span>
                                </div>
                                <div class="flex justify-between" x-show="(payoutCharges.platform_charge || 0) + (payoutCharges.operator_charge || 0) > 0">
                                    <span class="text-gray-600">Service Fee</span>
                                    <span class="font-medium text-amber-700" x-text="formatAmount((payoutCharges.platform_charge || 0) + (payoutCharges.operator_charge || 0)) + ' ' + walletCurrency"></span>
                                </div>
                                <div class="border-t border-gray-300 pt-1.5 flex justify-between font-semibold">
                                    <span class="text-gray-700">Total Debit</span>
                                    <span class="text-gray-900" x-text="formatAmount(Number(payoutForm.amount) + (payoutCharges.platform_charge || 0) + (payoutCharges.operator_charge || 0)) + ' ' + walletCurrency"></span>
                                </div>
                                <div x-show="payoutCharges.platform_charge === 0 && payoutCharges.operator_charge === 0" class="text-xs text-green-600 mt-1">No charges apply to this transaction.</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference (optional)</label>
                            <input type="text" x-model="payoutForm.reference" placeholder="Your internal reference" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <input type="text" x-model="payoutForm.description" placeholder="e.g. Salary payment" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <button type="submit" :disabled="payoutLoading" class="w-full bg-gblue-500 text-white px-6 py-2.5 rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                            <span x-show="!payoutLoading" x-text="(hasPerm('create_payout') && !hasPerm('approve_payout') && user?.role !== 'owner') ? 'Submit for Approval' : 'Send Money'">Send Money</span>
                            <span x-show="payoutLoading">Sending...</span>
                        </button>
                    </form>

                    <!-- Last payout result -->
                    <div x-show="lastPayoutResult" x-cloak class="mt-4 p-4 bg-gray-50 border rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Last Payout Result</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Reference:</span>
                            <span class="font-mono text-gray-800" x-text="lastPayoutResult.request_ref"></span>
                            <span class="text-gray-500">Phone:</span>
                            <span x-text="lastPayoutResult.phone"></span>
                            <span class="text-gray-500">Amount:</span>
                            <span x-text="formatAmount(lastPayoutResult.amount) + ' ' + walletCurrency"></span>
                            <span class="text-gray-500">Status:</span>
                            <span class="capitalize font-medium" :class="lastPayoutResult.status === 'processing' ? 'text-gblue-600' : 'text-gred-600'" x-text="lastPayoutResult.status"></span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button @click="payoutReceiptOpen = true" class="px-3 py-1.5 text-xs font-medium text-gblue-700 bg-gblue-50 border border-gblue-200 rounded-lg hover:bg-gblue-100 transition">View Receipt</button>
                            <button @click="downloadPayoutPdf(payoutReceipt)" class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======= BATCH PAYOUT ======= -->
            <div x-show="sendMoneySubTab === 'batch'">
                <div class="bg-white rounded-xl shadow-md border p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-gblue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Batch Payout (CSV Upload)</h3>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Upload a CSV file or paste data to send money to multiple recipients at once. Maximum 500 recipients per batch.</p>

                    <!-- Batch Name -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Name</label>
                        <input type="text" x-model="batchName" placeholder="e.g. January Salaries, Commission Payout" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1">Give this batch a name to easily identify it later.</p>
                    </div>

                    <div x-show="batchMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="batchMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="batchMsg"></div>

                    <!-- CSV Format Info -->
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="text-sm font-semibold text-blue-800 mb-2">CSV Format</h4>
                        <p class="text-xs text-blue-700 mb-2">Your CSV must have columns: <strong>phone, amount</strong>. Optional: <strong>reference, description</strong>. Operator is auto-detected from the phone number.</p>
                        <div class="bg-white rounded p-2 text-xs font-mono text-gray-700 overflow-x-auto">
                            phone,amount,reference,description<br>
                            0712345678,5000,REF001,Salary Jan<br>
                            0652345678,3000,REF002,Bonus<br>
                            0782345678,10000,REF003,Commission
                        </div>
                        <p class="text-xs text-blue-600 mt-2">Operator is detected automatically from the phone prefix (e.g. 074/075/076 = M-Pesa, 065/067/071 = Tigo Pesa)</p>
                    </div>

                    <!-- Upload CSV File -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload CSV File</label>
                        <input type="file" accept=".csv,.txt" @change="handleBatchFileUpload($event)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <!-- Or Paste CSV -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or Paste CSV Data</label>
                        <textarea x-model="batchCsvText" rows="6" :placeholder="'phone,amount,reference,description\n' + (currencyPhoneMap[walletCurrency] || {example:'255712345678'}).example + ',5000,REF001,Salary'" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-gblue-500 outline-none"></textarea>
                    </div>

                    <div class="flex items-center space-x-3 mb-4">
                        <button @click="parseBatchCsv()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                            Preview &amp; Validate
                        </button>
                        <span x-show="batchItems.length > 0" class="text-sm text-gray-500" x-text="batchItems.length + ' recipient(s) ready'"></span>
                    </div>

                    <!-- Preview Table -->
                    <div x-show="batchItems.length > 0" x-cloak class="mb-4">
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service Fee</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Remove</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="(item, idx) in batchItems" :key="idx">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-4 py-2 text-sm font-mono" x-text="item.phone"></td>
                                            <td class="px-4 py-2 text-sm font-semibold" x-text="formatAmount(item.amount) + ' ' + walletCurrency"></td>
                                            <td class="px-4 py-2 text-sm text-amber-700" x-text="item._charge !== undefined ? formatAmount(item._charge) + ' ' + walletCurrency : '—'"></td>
                                            <td class="px-4 py-2 text-sm text-gray-600" x-text="item.reference || '—'"></td>
                                            <td class="px-4 py-2 text-sm text-gray-600" x-text="item.description || '—'"></td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                    :class="item._status === 'success' ? 'bg-green-50 text-green-700' : item._status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    x-text="item._status || 'ready'"></span>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button @click="batchItems.splice(idx, 1); batchCharges = null;" class="text-red-500 hover:text-red-700 text-sm" title="Remove">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <p class="text-sm text-gray-600">Total: <strong x-text="formatAmount(batchItems.reduce((s, i) => s + Number(i.amount), 0)) + ' ' + walletCurrency"></strong> to <strong x-text="batchItems.length"></strong> recipient(s)</p>
                            <button @click="calculateBatchCharges()" :disabled="batchChargesLoading" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 text-sm font-medium disabled:opacity-50">
                                <span x-show="!batchChargesLoading">Calculate Charges</span>
                                <span x-show="batchChargesLoading">Calculating...</span>
                            </button>
                        </div>

                        <!-- Batch Charge Summary -->
                        <div x-show="batchCharges" x-cloak class="mt-3 p-4 rounded-lg border"
                            :class="batchCharges && batchCharges.totalFees > 0 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200'">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Batch Charges &amp; Fees Summary
                            </h4>
                            <div x-show="batchChargesLoading" class="text-xs text-gray-400">Calculating charges for all recipients...</div>
                            <div x-show="!batchChargesLoading && batchCharges" class="space-y-1.5 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Send Amount</span>
                                    <span class="font-medium text-gray-800" x-text="formatAmount(batchCharges.totalAmount) + ' ' + walletCurrency"></span>
                                </div>
                                <div class="flex justify-between" x-show="batchCharges.totalFees > 0">
                                    <span class="text-gray-600">Total Service Fees (<span x-text="batchCharges.itemCount"></span> items)</span>
                                    <span class="font-medium text-amber-700" x-text="formatAmount(batchCharges.totalFees) + ' ' + walletCurrency"></span>
                                </div>
                                <div class="border-t border-gray-300 pt-1.5 flex justify-between font-semibold">
                                    <span class="text-gray-700">Total Debit from Wallet</span>
                                    <span class="text-gray-900" x-text="formatAmount(batchCharges.totalDebit) + ' ' + walletCurrency"></span>
                                </div>
                                <div x-show="batchCharges.totalFees === 0" class="text-xs text-green-600 mt-1">No charges apply to this batch.</div>
                                <div x-show="batchCharges.errors > 0" class="text-xs text-red-600 mt-1" x-text="batchCharges.errors + ' item(s) could not be calculated (operator not detected)'"></div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-3">
                            <button @click="sendBatchPayout()" :disabled="batchLoading || batchResults.length > 0" class="px-6 py-2.5 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                                <span x-show="!batchLoading" x-text="(hasPerm('create_payout') && !hasPerm('approve_payout') && user?.role !== 'owner') ? 'Submit Batch for Approval' : 'Send Batch'">Send Batch</span>
                                <span x-show="batchLoading">Sending...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Batch Results -->
                    <div x-show="batchResults.length > 0" x-cloak class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-700">Batch Results</h4>
                            <button @click="downloadBatchPdf()" class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download Batch Receipt
                            </button>
                        </div>
                        <div class="p-3 rounded-lg text-sm mb-3" :class="batchResultSummary.failed === 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200'">
                            <span x-text="batchResultSummary.sent + ' sent, ' + batchResultSummary.failed + ' failed out of ' + batchResultSummary.total + ' total'"></span>
                        </div>
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="(r, idx) in batchResults" :key="idx">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-sm text-gray-500" x-text="r.index + 1"></td>
                                            <td class="px-4 py-2 text-sm font-mono" x-text="r.phone"></td>
                                            <td class="px-4 py-2 text-sm" x-text="formatAmount(r.amount) + ' ' + walletCurrency"></td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                    :class="r.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                                                    x-text="r.success ? 'Sent' : 'Failed'"></span>
                                            </td>
                                            <td class="px-4 py-2 text-sm font-mono text-gray-600" x-text="r.request_ref || '—'"></td>
                                            <td class="px-4 py-2 text-sm text-red-600" x-text="r.error || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Add Single Row Manually -->
                    <div class="mt-6 p-4 border border-dashed border-gray-300 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Add Recipient Manually</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <input type="text" x-model="manualRow.phone" :placeholder="'Phone (e.g. ' + (currencyPhoneMap[walletCurrency] || {example:'255712345678'}).example + ')'" @blur="autoFormatPhone('manual')" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" :class="manualRow.phone && !validatePhone(manualRow.phone).valid ? 'border-red-400 bg-red-50' : 'border-gray-300'">
                                <template x-if="manualRow.phone && manualRow.phone.replace(/[\s\-\.+]/g, '').length >= 9">
                                    <p class="text-xs mt-1" :class="validatePhone(manualRow.phone).valid ? 'text-green-600' : 'text-red-500'" x-text="validatePhone(manualRow.phone).valid ? 'Operator: ' + validatePhone(manualRow.phone).operator : validatePhone(manualRow.phone).error"></p>
                                </template>
                            </div>
                            <input type="text" inputmode="numeric" x-model="manualAmountDisplay" @input="formatAmountInput($event, 'manual')" placeholder="Amount" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <input type="text" x-model="manualRow.reference" placeholder="Reference (optional)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <button @click="addManualRow()" class="px-4 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium">+ Add</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======= PENDING APPROVALS ======= -->
            <div x-show="sendMoneySubTab === 'approvals'" x-cloak>
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-md border p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Pending Payouts</p>
                                <p class="text-xl font-bold text-gray-800" x-text="pendingPayoutsCount">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md border p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Total Amount</p>
                                <p class="text-xl font-bold text-gray-800" x-text="formatAmount(pendingPayoutsTotal) + ' ' + (walletCurrency || 'TZS')">0.00 TZS</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md border p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Selected</p>
                                <p class="text-xl font-bold text-gray-800" x-text="selectedPayouts.length">0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Action Bar -->
                <div x-show="selectedPayouts.length > 0" x-cloak class="bg-gblue-50 border border-gblue-200 rounded-xl p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gblue-700"><span x-text="selectedPayouts.length"></span> payout(s) selected</span>
                    <div class="flex gap-2">
                        <button @click="bulkApprovePayouts()" :disabled="bulkApprovalLoading" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 inline-flex items-center">
                            <svg x-show="bulkApprovalLoading" class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Approve All Selected
                        </button>
                        <button @click="bulkRejectPayouts()" :disabled="bulkApprovalLoading" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50 inline-flex items-center">
                            Reject All Selected
                        </button>
                    </div>
                </div>

                <!-- Pending Payouts Table -->
                <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-md font-semibold text-gray-700">Payouts Awaiting Approval</h3>
                        <button @click="fetchPendingPayouts()" class="text-xs text-gblue-500 hover:text-gblue-700 font-medium">Refresh</button>
                    </div>

                    <!-- Loading -->
                    <div x-show="pendingPayoutsLoading" class="p-6 text-center">
                        <svg class="animate-spin h-6 w-6 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>

                    <!-- Empty state -->
                    <div x-show="!pendingPayoutsLoading && pendingPayouts.length === 0" x-cloak class="p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-gray-500 text-sm">No pending payouts to approve.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="!pendingPayoutsLoading && pendingPayouts.length > 0" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500"><input type="checkbox" :checked="selectAllPayouts" @change="toggleSelectAllPayouts()" class="rounded border-gray-300 text-gblue-600 focus:ring-gblue-500"></th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="p in pendingPayouts" :key="p.id">
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3"><input type="checkbox" :value="p.id" x-model="selectedPayouts" class="rounded border-gray-300 text-gblue-600 focus:ring-gblue-500"></td>
                                            <td class="px-4 py-3 text-xs font-mono text-gray-700" x-text="p.request_ref || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="p.phone || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800" x-text="formatAmount(p.amount)"></td>
                                            <td class="px-4 py-3 text-xs text-gray-600 uppercase" x-text="p.operator || '-'"></td>
                                            <td class="px-4 py-3 text-xs text-gray-600 max-w-[150px] truncate" x-text="p.description || p.reference || '-'"></td>
                                            <td class="px-4 py-3 text-xs text-gray-600" x-text="p.created_by_name || p.created_by || '-'"></td>
                                            <td class="px-4 py-3 text-xs text-gray-500" x-text="p.created_at ? new Date(p.created_at).toLocaleString() : '-'"></td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button @click="approveSinglePayout(p.id)" :disabled="approvalLoading[p.id]" class="px-2.5 py-1.5 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700 disabled:opacity-50 inline-flex items-center">
                                                        <svg x-show="approvalLoading[p.id]" class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                        Approve
                                                    </button>
                                                    <button @click="rejectSinglePayout(p.id)" :disabled="approvalLoading[p.id]" class="px-2.5 py-1.5 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700 disabled:opacity-50">Reject</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Disbursement Transactions -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden mt-6">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-md font-semibold text-gray-700">Recent Disbursements</h3>
                    <button @click="fetchRecentDisbursements()" class="text-xs text-gblue-500 hover:text-gblue-700 font-medium">Refresh</button>
                </div>
                <div x-show="recentDisbLoading" class="p-6 text-center text-gray-500">
                    <svg class="animate-spin h-6 w-6 mx-auto text-gblue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!recentDisbLoading && recentDisbursements.length === 0" x-cloak class="p-6 text-center text-gray-500 text-sm">No disbursements yet.</div>
                <div x-show="!recentDisbLoading && recentDisbursements.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="d in recentDisbursements" :key="d.id || d.request_ref">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="d.request_ref"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-600" x-text="d.receipt_number || '—'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="d.batch_name || '—'"></td>
                                        <td class="px-4 py-3 text-sm" x-text="d.phone"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-red-600" x-text="'-' + formatAmount(d.amount) + ' ' + walletCurrency"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="operatorBadgeColor(d.operator_name)"
                                                x-text="d.operator_name"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-gyellow-50 text-gyellow-700': d.status==='pending'||d.status==='processing', 'bg-ggreen-50 text-ggreen-700': d.status==='completed'||d.status==='successful', 'bg-gred-50 text-gred-700': d.status==='failed'}"
                                                x-text="d.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="formatDate(d.created_at)"></td>
                                        <td class="px-4 py-3">
                                            <button @click="viewDisbursementReceipt(d)" class="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                PDF
                                            </button>
                                        </td>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="disbPagination.total > 0" class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="disbPagination.from||0"></span> to <span x-text="disbPagination.to||0"></span> of <span x-text="disbPagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="goToDisbPage(disbPagination.current_page-1)" :disabled="!disbPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="goToDisbPage(disbPagination.current_page+1)" :disabled="!disbPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payout Receipt Modal -->
        <div x-show="payoutReceiptOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="payoutReceiptOpen = false">
            <div class="fixed inset-0 bg-black/50" @click="payoutReceiptOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-5 rounded-t-2xl text-center">
                    <p class="text-2xl font-extrabold tracking-tight">Payin</p>
                    <h3 class="text-lg font-bold mt-1">Payout Receipt</h3>
                    <p class="text-blue-100 text-xs mt-1">Disbursement Transaction</p>
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold"
                        :class="payoutReceipt?.status === 'processing' ? 'bg-blue-200 text-blue-800' : payoutReceipt?.status === 'completed' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'"
                        x-text="(payoutReceipt?.status || '').toUpperCase()"></span>
                </div>
                <div class="p-6" x-show="payoutReceipt">
                    <div class="bg-gray-50 rounded-lg p-3 text-center mb-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Reference Number</p>
                        <p class="text-sm font-mono font-bold text-gray-800 mt-1" x-text="payoutReceipt?.request_ref"></p>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">Recipient Phone</span>
                            <span class="font-semibold text-gray-800" x-text="payoutReceipt?.phone"></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">Operator</span>
                            <span class="font-semibold text-gray-800" x-text="payoutReceipt?.operator"></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">Send Amount</span>
                            <span class="font-semibold text-gray-800" x-text="formatAmount(payoutReceipt?.amount) + ' ' + (payoutReceipt?.currency || 'TZS')"></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100" x-show="(payoutReceipt?.platform_charge || 0) + (payoutReceipt?.operator_charge || 0) > 0">
                            <span class="text-gray-500">Service Fee</span>
                            <span class="font-semibold text-amber-700" x-text="formatAmount((payoutReceipt?.platform_charge || 0) + (payoutReceipt?.operator_charge || 0)) + ' ' + (payoutReceipt?.currency || 'TZS')"></span>
                        </div>
                        <div class="flex justify-between py-2 bg-blue-50 -mx-6 px-6 rounded">
                            <span class="font-bold text-blue-800">Total Debited</span>
                            <span class="font-bold text-blue-800" x-text="formatAmount(payoutReceipt?.total_debit) + ' ' + (payoutReceipt?.currency || 'TZS')"></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100" x-show="payoutReceipt?.reference">
                            <span class="text-gray-500">Reference</span>
                            <span class="font-medium text-gray-700" x-text="payoutReceipt?.reference"></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100" x-show="payoutReceipt?.description">
                            <span class="text-gray-500">Description</span>
                            <span class="font-medium text-gray-700" x-text="payoutReceipt?.description"></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-500">Date</span>
                            <span class="font-medium text-gray-700" x-text="formatDate(payoutReceipt?.date)"></span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex items-center justify-between">
                    <button @click="downloadPayoutPdf(payoutReceipt)" class="px-4 py-2 text-sm font-medium text-blue-700 bg-white border border-blue-300 rounded-lg hover:bg-blue-50 transition inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </button>
                    <button @click="payoutReceiptOpen = false" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">Close</button>
                </div>
            </div>
        </div>

        <!-- ==================== SETTINGS TAB ==================== -->
        <div x-show="activeTab === 'settings'" x-cloak class="mt-6">
            <div class="max-w-2xl">
                <!-- Callback URL Configuration -->
                <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-gblue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Callback URL (Webhook)</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Configure a URL where we will send real-time payment notifications. Your server will receive POST requests with JSON payloads for every payin and payout event.</p>
                    
                    <div x-show="callbackMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                        :class="callbackMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                        x-text="callbackMsg"></div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Callback URL</label>
                        <input type="url" x-model="callbackUrl" placeholder="https://yourserver.com/api/payment/callback"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none font-mono">
                        <p class="text-xs text-gray-500 mt-1">Must be a valid HTTPS URL. Leave empty to disable webhooks.</p>
                    </div>
                    <div class="flex justify-end">
                        <button @click="saveCallback()" :disabled="callbackLoading"
                            class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium disabled:opacity-50 transition">
                            <span x-show="!callbackLoading">Save Callback URL</span>
                            <span x-show="callbackLoading">Saving...</span>
                        </button>
                    </div>
                </div>

                <!-- Webhook Events Reference -->
                <div class="bg-white rounded-xl shadow-md border p-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Webhook Events</h4>
                    <p class="text-sm text-gray-600 mb-4">Your callback URL will receive POST requests with the following event types:</p>
                    <div class="space-y-3">
                        <div class="bg-ggreen-50 border border-ggreen-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-ggreen-200 text-ggreen-800 mr-2">payin.completed</span>
                                <span class="text-sm text-gray-700">When a collection (payin) is successfully received</span>
                            </div>
                        </div>
                        <div class="bg-gblue-50 border border-gblue-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gblue-200 text-gblue-800 mr-2">payout.created</span>
                                <span class="text-sm text-gray-700">When a settlement (payout) request is created</span>
                            </div>
                        </div>
                        <div class="bg-gyellow-50 border border-gyellow-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gyellow-200 text-gyellow-800 mr-2">payout.approved</span>
                                <span class="text-sm text-gray-700">When a settlement is approved by admin</span>
                            </div>
                        </div>
                        <div class="bg-gred-50 border border-gred-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gred-200 text-gred-800 mr-2">payout.rejected</span>
                                <span class="text-sm text-gray-700">When a settlement is rejected (funds refunded)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sample Payload -->
                    <div class="mt-6">
                        <h5 class="text-sm font-semibold text-gray-700 mb-2">Sample Payload (payin.completed)</h5>
                        <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs overflow-x-auto">{
  "event": "payin.completed",
  "request_ref": "PAY-ABCDEF123456",
  "external_ref": "your-order-123",
  "operator_ref": "3000009866588",
  "receipt_number": "QJI23FABC9",
  "type": "collection",
  "phone": "255712345678",
  "operator": "M-Pesa",
  "gross_amount": 10000,
  "net_amount": 9800,
  "platform_charge": 200,
  "operator_charge": 0,
  "currency": "TZS",
  "status": "completed",
  "timestamp": "2026-02-26T12:00:00+03:00"
}</pre>
                    </div>
                </div>

                <!-- IP Whitelist -->
                <div class="bg-white rounded-xl shadow-md border p-6 mt-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-gyellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">API Keys</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Generate API keys to authenticate your server-to-server API requests. Use <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">X-API-Key</code> and <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">X-API-Secret</code> headers.</p>

                    <div x-show="apiKeyMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                        :class="apiKeyMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                        x-text="apiKeyMsg"></div>

                    <!-- New Secret Display (shown once) -->
                    <div x-show="newApiSecret" x-cloak class="mb-4 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                        <p class="text-sm font-semibold text-yellow-800 mb-2">⚠ Copy your API Secret now — it will not be shown again!</p>
                        <div class="space-y-2">
                            <div>
                                <label class="text-xs text-gray-600">API Key</label>
                                <div class="font-mono text-sm bg-white border rounded px-3 py-1.5 select-all" x-text="newApiKey"></div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-600">API Secret</label>
                                <div class="font-mono text-sm bg-white border rounded px-3 py-1.5 select-all text-red-700 font-bold" x-text="newApiSecret"></div>
                            </div>
                        </div>
                        <button @click="newApiSecret = ''; newApiKey = ''" class="mt-3 text-xs text-gray-500 hover:text-gray-700">Dismiss</button>
                    </div>

                    <!-- Generate Key Form -->
                    <form @submit.prevent="generateApiKey()" class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <input type="text" x-model="apiKeyLabel" placeholder="Key label (e.g. Production Server)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" required>
                            </div>
                            <button type="submit" :disabled="apiKeyGenerating"
                                class="px-6 py-2 bg-gyellow-500 text-white rounded-lg hover:bg-gyellow-600 text-sm font-medium disabled:opacity-50 transition whitespace-nowrap">
                                <span x-show="!apiKeyGenerating">+ Generate Key</span>
                                <span x-show="apiKeyGenerating">Generating...</span>
                            </button>
                        </div>
                    </form>

                    <!-- API Keys List -->
                    <div x-show="apiKeysLoading" class="py-4 text-center text-gray-500 text-sm">Loading API keys...</div>
                    <div x-show="!apiKeysLoading && apiKeys.length === 0" x-cloak class="py-4 text-center text-gray-500 text-sm">No API keys generated yet.</div>
                    <div x-show="!apiKeysLoading && apiKeys.length > 0" x-cloak>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">API Key</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Used</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="key in apiKeys" :key="key.id">
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800 font-medium" x-text="key.label"></td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-600" x-text="key.api_key"></td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="key.status === 'active' ? 'bg-ggreen-50 text-ggreen-700' : 'bg-gred-50 text-gred-700'"
                                                x-text="key.status"></span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-500 text-xs" x-text="key.last_used_at ? formatDate(key.last_used_at) : 'Never'"></td>
                                        <td class="px-4 py-2">
                                            <button x-show="key.status === 'active'" @click="revokeApiKey(key.id)" class="text-xs text-red-600 hover:text-red-800 font-medium">Revoke</button>
                                            <span x-show="key.status !== 'active'" class="text-xs text-gray-400">Revoked</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- IP Whitelist -->
                <div class="bg-white rounded-xl shadow-md border p-6 mt-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-gred-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">IP Whitelist</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Add IP addresses that are allowed to access your API. Each IP must be approved by an administrator before it becomes active.</p>

                    <div x-show="ipMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                        :class="ipMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                        x-text="ipMsg"></div>

                    <!-- Add IP Form -->
                    <form @submit.prevent="addIp()" class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <input type="text" x-model="newIpAddress" placeholder="IP Address (e.g. 192.168.1.100)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none font-mono" required>
                            </div>
                            <div class="flex-1">
                                <input type="text" x-model="newIpLabel" placeholder="Label (e.g. Office Server)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            </div>
                            <button type="submit" :disabled="ipAddLoading"
                                class="px-6 py-2 bg-gred-500 text-white rounded-lg hover:bg-gred-600 text-sm font-medium disabled:opacity-50 transition whitespace-nowrap">
                                <span x-show="!ipAddLoading">+ Add IP</span>
                                <span x-show="ipAddLoading">Adding...</span>
                            </button>
                        </div>
                    </form>

                    <!-- IP List Table -->
                    <div x-show="ipLoading" class="py-4 text-center text-gray-500 text-sm">Loading IPs...</div>
                    <div x-show="!ipLoading && ipList.length === 0" x-cloak class="py-4 text-center text-gray-500 text-sm">No IP addresses added yet.</div>
                    <div x-show="!ipLoading && ipList.length > 0" x-cloak>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="ip in ipList" :key="ip.id">
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-800" x-text="ip.ip_address"></td>
                                        <td class="px-4 py-2 text-gray-600" x-text="ip.label || '—'"></td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-gyellow-50 text-gyellow-700': ip.status==='pending', 'bg-ggreen-50 text-ggreen-700': ip.status==='approved', 'bg-gred-50 text-gred-700': ip.status==='rejected'}"
                                                x-text="ip.status"></span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button @click="deleteIp(ip.id)" class="text-xs text-red-600 hover:text-red-800 font-medium">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>

        <!-- ==================== WEBHOOK DELIVERY LOGS TAB ==================== -->
        <div x-show="activeTab === 'webhook-logs'" x-cloak class="mt-6">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800">Webhook Delivery Logs</h2>
                <p class="text-sm text-gray-500 mt-1">Track every webhook notification sent to your callback URL.</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="myWhLogSearch" @input.debounce.400ms="myWhLogPage = 1; fetchMyWebhookLogs()"
                            placeholder="Search by payment ref..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <select x-model="myWhLogFilterStatus" @change="myWhLogPage = 1; fetchMyWebhookLogs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="timeout">Timeout</option>
                        <option value="error">Error</option>
                        <option value="skipped">Skipped (No URL)</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="myWhLogsLoading" class="py-8 text-center text-gray-500 text-sm">Loading webhook logs...</div>
                <div x-show="!myWhLogsLoading && myWhLogs.length === 0" x-cloak class="py-8 text-center text-gray-500 text-sm">No webhook delivery logs found.</div>
                <div x-show="!myWhLogsLoading && myWhLogs.length > 0" x-cloak class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Ref</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">HTTP Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Response Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="log in myWhLogs" :key="log.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-800" x-text="log.payment_request?.request_ref || '—'"></td>
                                    <td class="px-4 py-3 text-xs text-gray-600 capitalize" x-text="log.payment_request?.type || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs font-semibold"
                                            :class="log.http_status >= 200 && log.http_status < 300 ? 'text-green-700' : 'text-red-600'"
                                            x-text="log.http_status || '—'"></span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700" x-text="log.response_time_ms != null ? log.response_time_ms + ' ms' : '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="{
                                                'bg-green-100 text-green-800': log.status === 'success',
                                                'bg-red-100 text-red-800': log.status === 'failed',
                                                'bg-yellow-100 text-yellow-800': log.status === 'timeout',
                                                'bg-orange-100 text-orange-800': log.status === 'error',
                                                'bg-gray-100 text-gray-800': log.status === 'skipped',
                                            }"
                                            x-text="log.status || 'unknown'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-700" x-text="log.attempt_number"></td>
                                    <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(log.created_at)"></td>
                                    <td class="px-4 py-3">
                                        <button @click="viewMyWebhookLog(log)" class="text-xs text-blue-600 hover:text-blue-800 font-medium underline">View</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 text-sm text-gray-600">
                        <span>Showing <span x-text="myWhLogPagination.from || 0"></span>-<span x-text="myWhLogPagination.to || 0"></span> of <span x-text="myWhLogPagination.total || 0"></span></span>
                        <div class="space-x-2">
                            <button @click="myWhLogPage--; fetchMyWebhookLogs()" :disabled="!myWhLogPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="myWhLogPage++; fetchMyWebhookLogs()" :disabled="!myWhLogPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhook Log Detail Modal -->
            <div x-show="myWhLogDetailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="myWhLogDetailOpen = false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
                        <h3 class="text-lg font-semibold text-gray-800">Webhook Delivery Detail</h3>
                        <button @click="myWhLogDetailOpen = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="px-6 py-4 space-y-4" x-show="myWhLogDetail">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-500">Payment Ref:</span> <span class="font-mono font-medium" x-text="myWhLogDetail?.payment_request?.request_ref || '—'"></span></div>
                            <div><span class="text-gray-500">Type:</span> <span class="font-medium capitalize" x-text="myWhLogDetail?.payment_request?.type || '—'"></span></div>
                            <div><span class="text-gray-500">HTTP Status:</span> <span class="font-semibold" :class="myWhLogDetail?.http_status >= 200 && myWhLogDetail?.http_status < 300 ? 'text-green-700' : 'text-red-600'" x-text="myWhLogDetail?.http_status || '—'"></span></div>
                            <div><span class="text-gray-500">Response Time:</span> <span class="font-medium" x-text="myWhLogDetail?.response_time_ms != null ? myWhLogDetail.response_time_ms + ' ms' : '—'"></span></div>
                            <div><span class="text-gray-500">Status:</span> <span class="font-medium capitalize" x-text="myWhLogDetail?.status || '—'"></span></div>
                            <div><span class="text-gray-500">Attempt:</span> <span class="font-medium" x-text="myWhLogDetail?.attempt_number || '—'"></span></div>
                            <div class="col-span-2"><span class="text-gray-500">URL:</span> <span class="font-mono text-xs break-all" x-text="myWhLogDetail?.url || '—'"></span></div>
                            <div><span class="text-gray-500">Sent At:</span> <span class="font-medium" x-text="myWhLogDetail?.created_at ? formatDate(myWhLogDetail.created_at) : '—'"></span></div>
                        </div>

                        <div x-show="myWhLogDetail?.error_message">
                            <h4 class="text-sm font-semibold text-red-700 mb-2">Error</h4>
                            <pre class="bg-red-50 text-red-700 p-4 rounded-lg text-xs overflow-x-auto max-h-40" x-text="myWhLogDetail?.error_message"></pre>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Payload Sent</h4>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto max-h-60" x-text="JSON.stringify(myWhLogDetail?.request_payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== CURRENCY EXCHANGE TAB ==================== -->
        <div x-show="activeTab === 'exchange'" x-cloak>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800">Currency Exchange</h2>
                <p class="text-sm text-gray-500 mt-1">Convert funds between currencies in your wallets.</p>
            </div>

            <!-- Message -->
            <div x-show="fxMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="fxMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="fxMsg"></div>

            <!-- Available Rates Overview -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-semibold text-gray-700">Available Exchange Rates</h3>
                    <button @click="fetchExchangeRates()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Refresh</button>
                </div>
                <div x-show="fxLoading" class="text-center py-4">
                    <svg class="animate-spin h-6 w-6 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!fxLoading && fxRates.length === 0" x-cloak class="text-center py-4 text-sm text-gray-500">No exchange rates available for your account currencies.</div>
                <div x-show="!fxLoading && fxRates.length > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="r in fxRates" :key="r.id">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-gray-800" x-text="r.from_currency + ' → ' + r.to_currency"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full" :class="r.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="r.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div>Buy: <span class="font-medium text-gray-800" x-text="parseFloat(r.buy_rate).toFixed(4)"></span></div>
                                <div>Sell: <span class="font-medium text-gray-800" x-text="parseFloat(r.sell_rate).toFixed(4)"></span></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">Fee: <span class="font-medium" x-text="parseFloat(r.conversion_fee_percent).toFixed(2) + '%'"></span></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Exchange Form -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Left: Form -->
                <div class="bg-white rounded-xl shadow-md border p-6">
                    <h3 class="text-md font-semibold text-gray-700 mb-4">Convert Currency</h3>

                    <!-- Source -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">From Currency</label>
                        <select x-model="fxForm.from_currency" @change="fxPreview = null; fxResult = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select currency</option>
                            <template x-for="c in fxAllowedCurrencies" :key="'from_'+c">
                                <option :value="c" x-text="c + ' – ' + (fxCurrencyLabels[c] || c)"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Source Operator</label>
                            <select x-model="fxForm.from_operator" @change="fxPreview = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select</option>
                                <template x-for="op in operators" :key="'fxfr_'+op">
                                    <option :value="op" x-text="op"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Source Wallet</label>
                            <select x-model="fxForm.from_wallet_type" @change="fxPreview = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="collection">Collection</option>
                                <option value="disbursement">Disbursement</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                        <input type="text" x-model="fxAmountDisplay" @input="formatAmountInput($event, 'fx')" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Enter amount">
                        <p x-show="fxForm.from_currency && fxForm.from_operator" class="text-xs text-gray-500 mt-1">
                            Available: <span class="font-medium" x-text="getSourceWalletBalance()"></span>
                        </p>
                    </div>

                    <!-- Swap Arrow -->
                    <div class="flex justify-center my-3">
                        <button @click="let tmp = fxForm.from_currency; fxForm.from_currency = fxForm.to_currency; fxForm.to_currency = tmp; fxPreview = null; fxResult = null" class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition" title="Swap currencies">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                        </button>
                    </div>

                    <!-- Destination -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">To Currency</label>
                        <select x-model="fxForm.to_currency" @change="fxPreview = null; fxResult = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select currency</option>
                            <template x-for="c in fxAllowedCurrencies.filter(c => c !== fxForm.from_currency)" :key="'to_'+c">
                                <option :value="c" x-text="c + ' – ' + (fxCurrencyLabels[c] || c)"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Destination Operator</label>
                            <select x-model="fxForm.to_operator" @change="fxPreview = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select</option>
                                <template x-for="op in operators" :key="'fxto_'+op">
                                    <option :value="op" x-text="op"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Destination Wallet</label>
                            <select x-model="fxForm.to_wallet_type" @change="fxPreview = null" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="collection">Collection</option>
                                <option value="disbursement">Disbursement</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button @click="previewExchange()" :disabled="fxPreviewLoading || !fxForm.from_currency || !fxForm.to_currency || !fxForm.amount || !fxForm.from_operator || !fxForm.to_operator"
                            class="flex-1 px-4 py-2.5 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center">
                            <svg x-show="fxPreviewLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Preview
                        </button>
                        <button x-show="fxPreview" @click="executeExchange()" :disabled="fxExecuting"
                            class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center">
                            <svg x-show="fxExecuting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Exchange Now
                        </button>
                    </div>
                </div>

                <!-- Right: Preview / Result -->
                <div>
                    <!-- Preview Card -->
                    <div x-show="fxPreview" x-cloak class="bg-white rounded-xl shadow-md border p-6 mb-4">
                        <h3 class="text-md font-semibold text-gray-700 mb-4">Exchange Preview</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">You Send</span>
                                <span class="text-lg font-bold text-gray-800" x-text="formatAmount(fxPreview?.from_amount) + ' ' + fxPreview?.from_currency"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Exchange Rate</span>
                                <span class="text-sm font-medium text-gray-700" x-text="'1 ' + fxPreview?.from_currency + ' = ' + parseFloat(fxPreview?.rate).toFixed(4) + ' ' + fxPreview?.to_currency"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-sm text-gray-500">Conversion Fee (<span x-text="fxPreview?.fee_percent + '%'"></span>)</span>
                                <span class="text-sm font-medium text-red-600" x-text="'-' + formatAmount(fxPreview?.fee_amount) + ' ' + fxPreview?.from_currency"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 bg-blue-50 -mx-6 px-6 rounded-lg">
                                <span class="text-sm font-medium text-blue-700">You Receive</span>
                                <span class="text-xl font-bold text-blue-800" x-text="formatAmount(fxPreview?.to_amount) + ' ' + fxPreview?.to_currency"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Result Card -->
                    <div x-show="fxResult" x-cloak class="bg-green-50 border border-green-200 rounded-xl p-6">
                        <div class="flex items-center mb-3">
                            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h3 class="text-md font-semibold text-green-800">Exchange Completed!</h3>
                        </div>
                        <p class="text-sm text-green-700 mb-3" x-text="fxResult?.message"></p>
                        <div class="grid grid-cols-2 gap-3 text-xs text-green-700">
                            <div>Reference: <span class="font-bold" x-text="fxResult?.exchange?.reference"></span></div>
                            <div>Source Balance: <span class="font-bold" x-text="fxResult?.source_wallet_balance + ' ' + fxResult?.exchange?.from_currency"></span></div>
                            <div>Dest Balance: <span class="font-bold" x-text="fxResult?.destination_wallet_balance + ' ' + fxResult?.exchange?.to_currency"></span></div>
                        </div>
                    </div>

                    <!-- Empty State when no preview -->
                    <div x-show="!fxPreview && !fxResult" x-cloak class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <p class="text-sm text-gray-500">Fill in the form and click <strong>Preview</strong> to see the exchange details before confirming.</p>
                    </div>
                </div>
            </div>

            <!-- Exchange History -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="text-md font-semibold text-gray-700">Exchange History</h3>
                    <button @click="fetchExchangeHistory()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Refresh</button>
                </div>
                <div x-show="fxHistoryLoading" class="p-6 text-center">
                    <svg class="animate-spin h-6 w-6 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!fxHistoryLoading && fxHistory.length === 0" x-cloak class="p-6 text-center text-sm text-gray-500">No exchange transactions yet.</div>
                <div x-show="!fxHistoryLoading && fxHistory.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="ex in fxHistory" :key="ex.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-xs font-mono text-gray-700" x-text="ex.reference"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="font-medium" x-text="formatAmount(ex.from_amount)"></span>
                                            <span class="text-xs text-gray-500 ml-1" x-text="ex.from_currency"></span>
                                            <span class="block text-xs text-gray-400" x-text="ex.from_operator + ' / ' + ex.from_wallet_type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="font-medium text-green-700" x-text="formatAmount(ex.to_amount)"></span>
                                            <span class="text-xs text-gray-500 ml-1" x-text="ex.to_currency"></span>
                                            <span class="block text-xs text-gray-400" x-text="ex.to_operator + ' / ' + ex.to_wallet_type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600" x-text="parseFloat(ex.rate_applied).toFixed(4)"></td>
                                        <td class="px-4 py-3 text-xs text-gray-600" x-text="formatAmount(ex.fee_amount) + ' (' + parseFloat(ex.fee_percent).toFixed(1) + '%)'"></td>
                                        <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full" :class="ex.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" x-text="ex.status"></span></td>
                                        <td class="px-4 py-3 text-xs text-gray-500" x-text="new Date(ex.created_at).toLocaleString()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="fxHistoryPagination.total > 0" class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing <span x-text="fxHistoryPagination.from||0"></span> to <span x-text="fxHistoryPagination.to||0"></span> of <span x-text="fxHistoryPagination.total||0"></span></p>
                        <div class="flex space-x-2">
                            <button @click="fxHistoryPage--; fetchExchangeHistory()" :disabled="!fxHistoryPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                            <button @click="fxHistoryPage++; fetchExchangeHistory()" :disabled="!fxHistoryPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ACCOUNT SETTINGS TAB ==================== -->
        <div x-show="activeTab === 'account-settings'" x-cloak>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800">Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Manage your password and security preferences.</p>
            </div>
            <div class="max-w-3xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Card 1: Change Password -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            <h3 class="text-sm font-bold text-white">Change Password</h3>
                        </div>
                        <p class="text-blue-100 text-xs mt-0.5">Min 8 chars, mixed case, numbers & symbols.</p>
                    </div>
                    <div class="p-4">
                        <div x-show="pwSuccess" x-cloak class="mb-3 p-2 rounded text-xs bg-green-50 text-green-700 border border-green-200" x-text="pwSuccess"></div>
                        <div x-show="pwError" x-cloak class="mb-3 p-2 rounded text-xs bg-red-50 text-red-700 border border-red-200" x-text="pwError"></div>

                        <form @submit.prevent="changePassword()">
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" x-model="currentPassword" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Current password">
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" x-model="newPassword" required minlength="8" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="New password">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" x-model="confirmPassword" required minlength="8" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Confirm password">
                            </div>
                            <button type="submit" :disabled="pwLoading" class="w-full bg-blue-600 text-white py-2 text-sm rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50">
                                <span x-show="!pwLoading">Update Password</span>
                                <span x-show="pwLoading" class="inline-flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Updating...</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card 2: Security Settings -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <h3 class="text-sm font-bold text-white">Security Settings</h3>
                        </div>
                        <p class="text-emerald-100 text-xs mt-0.5">Manage your account security.</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Two-Factor Authentication -->
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-800">Two-Factor Authentication</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Extra layer of security via email code</p>
                            </div>
                            <button @click="tfaShowConfirm = true; tfaPassword = ''; tfaMsg = '';" :disabled="tfaLoading" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out disabled:opacity-50" :class="tfaEnabled ? 'bg-green-500' : 'bg-gray-300'">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="tfaEnabled ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                        </div>
                        <!-- 2FA Confirm Password Inline -->
                        <div x-show="tfaShowConfirm" x-cloak class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-xs font-medium text-gray-700 mb-2" x-text="'Enter password to ' + (tfaEnabled ? 'disable' : 'enable') + ' 2FA'"></p>
                            <div x-show="tfaMsg" x-cloak class="mb-2 p-2 rounded text-xs" :class="tfaMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="tfaMsg"></div>
                            <input type="password" x-model="tfaPassword" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none mb-2" placeholder="Your password" @keydown.enter="toggleTwoFactor()">
                            <div class="flex space-x-2">
                                <button type="button" @click="tfaShowConfirm = false" class="flex-1 px-3 py-1.5 text-xs border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancel</button>
                                <button type="button" @click="toggleTwoFactor()" :disabled="tfaLoading || !tfaPassword" class="flex-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                    <span x-show="!tfaLoading">Confirm</span>
                                    <span x-show="tfaLoading">...</span>
                                </button>
                            </div>
                        </div>
                        <div x-show="!tfaShowConfirm" class="text-center">
                            <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full" :class="tfaEnabled ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" x-text="tfaEnabled ? '2FA Enabled' : '2FA Disabled'"></span>
                        </div>

                        <!-- Account Info -->
                        <div class="border-t pt-3">
                            <h4 class="text-xs font-semibold text-gray-800 mb-2">Account Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Name</span>
                                    <span class="font-medium text-gray-800" x-text="(user?.firstname || '') + ' ' + (user?.lastname || '')"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Email</span>
                                    <span class="font-medium text-gray-800" x-text="user?.email || ''"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Role</span>
                                    <span class="font-medium text-gray-800 capitalize" x-text="user?.role || ''"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Last Login</span>
                                    <span class="font-medium text-gray-800" x-text="user?.last_login_at ? new Date(user.last_login_at).toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : 'N/A'"></span>
                                </div>
                                <div x-show="user?.last_login_ip" class="flex justify-between text-xs">
                                    <span class="text-gray-500">Last Login IP</span>
                                    <span class="font-medium text-gray-800 font-mono text-xs" x-text="user?.last_login_ip || ''"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== API DOCS TAB ==================== -->
        <div x-show="activeTab === 'api-docs'" x-cloak class="mt-6">

            <!-- Download PDF Button -->
            <div class="flex justify-end mb-4">
                <button @click="downloadApiDocsPdf()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download PDF
                </button>
            </div>

            <!-- Overview -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">API Overview</h3>
                <p class="text-gray-600 mb-4">Integrate Payin into your application using our REST API. All requests use JSON over HTTPS.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gblue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gblue-700 mb-1">Base URL</h4>
                        <code class="text-sm text-gblue-600 break-all">https://api.payin.co.tz/api/v1</code>
                    </div>
                    <div class="bg-ggreen-50 rounded-lg p-4">
                        <h4 class="font-semibold text-ggreen-700 mb-1">Format</h4>
                        <code class="text-sm text-ggreen-600">JSON over HTTPS</code>
                    </div>
                    <div class="bg-gyellow-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gyellow-700 mb-1">Auth</h4>
                        <code class="text-sm text-gyellow-600">API Key + Secret</code>
                    </div>
                </div>
            </div>

            <!-- Authentication -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Authentication</h3>
                <p class="text-gray-600 mb-4">All API requests require two headers. Generate your credentials from <strong>Settings → API Keys</strong>.</p>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b border-gray-200"><th class="pb-2 font-semibold text-gray-700">Header</th><th class="pb-2 font-semibold text-gray-700">Description</th></tr></thead>
                        <tbody class="text-gray-600">
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">X-API-Key</code></td><td class="py-2">Your API key (public identifier)</td></tr>
                            <tr><td class="py-2"><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">X-API-Secret</code></td><td class="py-2">Your API secret (keep confidential)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <p class="text-gray-400 text-xs mb-2">Example request headers</p>
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">curl -X POST https://api.payin.co.tz/api/v1/collection \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here" \
  -d '{ ... }'</pre>
                </div>
                <div class="mt-4 bg-gyellow-50 border border-gyellow-200 rounded-lg p-4">
                    <p class="text-sm text-gyellow-800"><strong>IP Whitelisting:</strong> For added security, whitelist your server IPs in <strong>Settings → IP Whitelist</strong>. Requests from non-whitelisted IPs will be rejected once enabled.</p>
                </div>
            </div>

            <!-- Collection -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Collection (Payin)</h3>
                <p class="text-gray-600 mb-2">Initiate a mobile money collection. The customer receives a USSD prompt to confirm.</p>
                <div class="flex items-center space-x-2 mb-4"><span class="bg-ggreen-500 text-white text-xs font-bold px-2 py-1 rounded">POST</span><code class="text-sm text-gray-700">/v1/collection</code></div>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b border-gray-200"><th class="pb-2 font-semibold text-gray-700">Field</th><th class="pb-2 font-semibold text-gray-700">Type</th><th class="pb-2 font-semibold text-gray-700">Required</th><th class="pb-2 font-semibold text-gray-700">Description</th></tr></thead>
                        <tbody class="text-gray-600">
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">phone</code></td><td>string</td><td>Yes</td><td>Customer phone (e.g. <code class="text-xs">255712345678</code>)</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">amount</code></td><td>number</td><td>Yes</td><td>Amount to collect (min: 100)</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">operator</code></td><td>string</td><td>Yes</td><td>Operator code (e.g. <code class="text-xs">mpesa</code>, <code class="text-xs">tigopesa</code>)</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">reference</code></td><td>string</td><td>No</td><td>Your internal reference (max 50 chars)</td></tr>
                            <tr><td class="py-2"><code class="text-xs">callback_url</code></td><td>string</td><td>No</td><td>Override default callback URL</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <p class="text-gray-400 text-xs mb-2">Example request</p>
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "phone": "255712345678",
  "amount": 10000,
  "operator": "mpesa",
  "reference": "ORDER-001"
}</pre>
                </div>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mt-3">
                    <p class="text-gray-400 text-xs mb-2">Success response (201)</p>
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "message": "Collection initiated",
  "request_ref": "PAY-A1B2C3D4E5F6",
  "status": "pending",
  "amount": 10000,
  "charge": 200,
  "operator": "mpesa"
}</pre>
                </div>
            </div>

            <!-- Disbursement -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Disbursement (Payout)</h3>
                <p class="text-gray-600 mb-2">Send money from your wallet to a customer's mobile money account.</p>
                <div class="flex items-center space-x-2 mb-4"><span class="bg-ggreen-500 text-white text-xs font-bold px-2 py-1 rounded">POST</span><code class="text-sm text-gray-700">/v1/disbursement</code></div>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b border-gray-200"><th class="pb-2 font-semibold text-gray-700">Field</th><th class="pb-2 font-semibold text-gray-700">Type</th><th class="pb-2 font-semibold text-gray-700">Required</th><th class="pb-2 font-semibold text-gray-700">Description</th></tr></thead>
                        <tbody class="text-gray-600">
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">phone</code></td><td>string</td><td>Yes</td><td>Recipient phone number</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">amount</code></td><td>number</td><td>Yes</td><td>Amount to send (min: 100)</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">operator</code></td><td>string</td><td>Yes</td><td>Operator code</td></tr>
                            <tr><td class="py-2"><code class="text-xs">reference</code></td><td>string</td><td>No</td><td>Your internal reference (max 50 chars)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <p class="text-gray-400 text-xs mb-2">Example request</p>
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "phone": "255712345678",
  "amount": 5000,
  "operator": "mpesa",
  "reference": "PAYOUT-001"
}</pre>
                </div>
            </div>

            <!-- Transaction Status -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Status</h3>
                <div class="flex items-center space-x-2 mb-4"><span class="bg-gblue-500 text-white text-xs font-bold px-2 py-1 rounded">GET</span><code class="text-sm text-gray-700">/v1/status/{request_ref}</code></div>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b border-gray-200"><th class="pb-2 font-semibold text-gray-700">Status</th><th class="pb-2 font-semibold text-gray-700">Description</th></tr></thead>
                        <tbody class="text-gray-600">
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="bg-gyellow-100 text-gyellow-700 px-1.5 py-0.5 rounded text-xs">pending</code></td><td>Waiting for operator/customer</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="bg-ggreen-100 text-ggreen-700 px-1.5 py-0.5 rounded text-xs">completed</code></td><td>Payment successful</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="bg-gred-100 text-gred-700 px-1.5 py-0.5 rounded text-xs">failed</code></td><td>Payment failed</td></tr>
                            <tr><td class="py-2"><code class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-xs">reversed</code></td><td>Transaction reversed</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Operators -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Active Operators</h3>
                <div class="flex items-center space-x-2 mb-4"><span class="bg-gblue-500 text-white text-xs font-bold px-2 py-1 rounded">GET</span><code class="text-sm text-gray-700">/v1/operators</code></div>
                <p class="text-gray-600 mb-2">Lists available mobile money operators and their supported transaction types.</p>
            </div>

            <!-- Callbacks -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Callbacks (Webhooks)</h3>
                <p class="text-gray-600 mb-4">When a payment completes or fails, Payin sends a POST to your callback URL. Set it in <strong>Account Info → Callback URL</strong>.</p>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <p class="text-gray-400 text-xs mb-2">Callback payload</p>
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "request_ref": "PAY-A1B2C3D4E5F6",
  "type": "collection",
  "status": "completed",
  "amount": 10000,
  "charge": 200,
  "phone": "255712345678",
  "operator": "mpesa",
  "operator_ref": "MPESA123456",
  "reference": "ORDER-001",
  "completed_at": "2026-01-15T10:30:45.000000Z"
}</pre>
                </div>
                <div class="mt-4 bg-gblue-50 border border-gblue-200 rounded-lg p-4">
                    <p class="text-sm text-gblue-800"><strong>Important:</strong> Always verify payment status via <code class="text-xs">/v1/status/{request_ref}</code> before fulfilling orders — never trust callback data alone.</p>
                </div>
            </div>

            <!-- Code Examples -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Code Examples</h3>

                <h4 class="font-semibold text-gray-700 mb-2">PHP</h4>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-4">
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">$ch = curl_init('https://api.payin.co.tz/api/v1/collection');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: YOUR_API_KEY',
        'X-API-Secret: YOUR_API_SECRET',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'phone'     => '255712345678',
        'amount'    => 10000,
        'operator'  => 'mpesa',
        'reference' => 'ORDER-001',
    ]),
]);
$response = curl_exec($ch);
$data = json_decode($response, true);
echo $data['request_ref'];</pre>
                </div>

                <h4 class="font-semibold text-gray-700 mb-2">Python</h4>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-4">
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">import requests

response = requests.post(
    'https://api.payin.co.tz/api/v1/collection',
    headers={
        'X-API-Key': 'YOUR_API_KEY',
        'X-API-Secret': 'YOUR_API_SECRET',
    },
    json={
        'phone': '255712345678',
        'amount': 10000,
        'operator': 'mpesa',
        'reference': 'ORDER-001',
    }
)
print(response.json()['request_ref'])</pre>
                </div>

                <h4 class="font-semibold text-gray-700 mb-2">JavaScript (Node.js / Fetch)</h4>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <pre class="text-green-400 text-sm font-mono whitespace-pre">const response = await fetch('https://api.payin.co.tz/api/v1/collection', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'YOUR_API_KEY',
        'X-API-Secret': 'YOUR_API_SECRET',
    },
    body: JSON.stringify({
        phone: '255712345678',
        amount: 10000,
        operator: 'mpesa',
        reference: 'ORDER-001',
    }),
});
const data = await response.json();
console.log(data.request_ref);</pre>
                </div>
            </div>

            <!-- Error Handling -->
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Error Handling</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b border-gray-200"><th class="pb-2 font-semibold text-gray-700">Code</th><th class="pb-2 font-semibold text-gray-700">Meaning</th></tr></thead>
                        <tbody class="text-gray-600">
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">200</code></td><td>Success</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">201</code></td><td>Created (payment initiated)</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">401</code></td><td>Unauthorized — invalid API key/secret</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">403</code></td><td>Forbidden — IP not whitelisted or account inactive</td></tr>
                            <tr class="border-b border-gray-100"><td class="py-2"><code class="text-xs">422</code></td><td>Validation error</td></tr>
                            <tr><td class="py-2"><code class="text-xs">429</code></td><td>Rate limit exceeded</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Support -->
            <div class="bg-gblue-50 border border-gblue-200 rounded-xl p-6 text-center">
                <h3 class="text-lg font-semibold text-gblue-800 mb-2">Need Help?</h3>
                <p class="text-gblue-600 text-sm">Contact us at <a href="mailto:support@payin.co.tz" class="underline font-medium">support@payin.co.tz</a> for integration support.</p>
            </div>
        </div>

        </div><!-- /px-6 py-6 -->
    </div><!-- /lg:ml-64 main content -->

</div>

<script>
function dashboard() {
    return {
        user: null,
        activeTab: (window.location.hash.replace('#','') || 'dashboard'),
        sidebarOpen: false,

        // Transactions
        transactions: [], loadingTxns: true, txnError: '',
        searchQuery: '', filterStatus: '', filterType: '', filterOperator: '', dateFrom: '', dateTo: '', currentPage: 1, txnExportLoading: false,
        stats: { total: 0, completed: 0, pending: 0, failed: 0 },
        pagination: {},

        // Wallet
        walletSubTab: 'collection',
        walletCurrency: 'TZS',
        currencyPhoneMap: {
            'TZS': { code: '255', digits: 9, example: '255712345678', local: '0712345678' },
            'KES': { code: '254', digits: 9, example: '254712345678', local: '0712345678' },
            'UGX': { code: '256', digits: 9, example: '256712345678', local: '0712345678' },
            'RWF': { code: '250', digits: 9, example: '250712345678', local: '0712345678' },
            'BIF': { code: '257', digits: 8, example: '25712345678', local: '12345678' },
            'CDF': { code: '243', digits: 9, example: '243812345678', local: '0812345678' },
            'MZN': { code: '258', digits: 9, example: '258812345678', local: '0812345678' },
            'MWK': { code: '265', digits: 9, example: '265999123456', local: '0999123456' },
            'ZMW': { code: '260', digits: 9, example: '260971234567', local: '0971234567' },
            'ZAR': { code: '27', digits: 9, example: '27712345678', local: '0712345678' },
            'ETB': { code: '251', digits: 9, example: '251912345678', local: '0912345678' },
            'NGN': { code: '234', digits: 10, example: '2348012345678', local: '08012345678' },
            'GHS': { code: '233', digits: 9, example: '233241234567', local: '0241234567' },
        },
        collectionWallets: [], disbursementWallets: [], operators: [],
        overallBalance: 0, collectionTotal: 0, disbursementTotal: 0,
        walletByCurrency: [],

        walletTransactions: [], walletTxnOperatorFilter: '', walletTxnTypeFilter: '', wtPage: 1, wtPagination: {},
        creditAmounts: {}, creditDescs: {}, transferAmounts: {}, transferAmountDisplays: {},
        walletCreditLoading: {}, walletTransferLoading: {},
        trfSummaryOpen: false, trfSummary: { operator: '', amount: 0, available: 0 },
        walletMsg: {}, walletMsgType: {},
        walletLoading: { txns: false },

        // Settlements
        settlements:[], stlFilterStatus: '', stlSearch: '', stlLoading: false, stlLoadingList: false, stlPage: 1, stlPagination: {},
        stlForm: { operator: '', amount: '', bank_account_id: '', description: '' },
        stlAmountDisplay: '',
        settlementMsg: '', settlementMsgType: '',
        stlPreviewLoading: false, stlSummaryOpen: false,
        stlSummary: { operator: '', amount: 0, platform_charge: 0, operator_charge: 0, total_charge: 0, total_debit: 0, bank_name: '', account_number: '', account_name: '', description: '', available_balance: 0 },
        stlReceiptOpen: false, stlReceipt: null,
        bankAccounts: [], bankAccountsLoading: false,
        bankForm: { bank_name: '', account_name: '', account_number: '', swift_code: '', branch: '', label: '' },
        bankFormLoading: false, bankMsg: '', bankMsgType: '', showBankForm: false,

        // Invoices (Manual C2B)
        invoices: [], invoicesLoading: false, invoicePage: 1, invoicePagination: {},
        invoiceSearch: '', invoiceFilterStatus: '',
        invoiceForm: { amount: '', description: '', expires_in: '' },
        invoiceAmountDisplay: '',
        invoiceLoading: false, invoiceMsg: '', invoiceMsgType: '',
        invoiceDetailOpen: false, invoiceDetail: null,

        // Crypto Wallets
        cryptoWallets: [], cryptoWalletsLoading: false,
        cryptoForm: { currency: '', network: '', wallet_address: '', label: '' },
        cryptoFormLoading: false, cryptoMsg: '', cryptoMsgType: '', showCryptoForm: false,

        // Send Money (Payout)
        sendMoneySubTab: 'single',
        payoutOperators: [],
        detectedOperator: { name: '', code: '' }, detectingOp: false,
        payoutForm: { phone: '', amount: '', reference: '', description: '' },
        payoutAmountDisplay: '',
        payoutLoading: false, payoutMsg: '', payoutMsgType: 'success',
        payoutCharges: null, payoutChargesLoading: false,
        lastPayoutResult: null,
        payoutReceiptOpen: false, payoutReceipt: null,
        // Batch
        batchName: '', batchCsvText: '', batchItems: [], batchLoading: false,
        batchMsg: '', batchMsgType: 'success',
        batchResults: [], batchResultSummary: { sent: 0, failed: 0, total: 0 },
        batchCharges: null, batchChargesLoading: false,
        manualRow: { phone: '', amount: '', reference: '', description: '' },
        manualAmountDisplay: '',
        recentDisbursements: [], recentDisbLoading: false, disbPage: 1, disbPagination: {},

        // Payout Approvals (maker-checker)
        pendingPayouts: [], pendingPayoutsCount: 0, pendingPayoutsTotal: 0,
        pendingPayoutsLoading: false, approvalLoading: {},
        selectedPayouts: [], selectAllPayouts: false,
        bulkApprovalLoading: false,

        // Password
        currentPassword: '', newPassword: '', confirmPassword: '',
        pwError: '', pwSuccess: '', pwLoading: false,

        // Account Users
        accountUsers: [], accUsersLoading: false, addUserLoading: false,
        newUserForm: { firstname: '', lastname: '', email: '', password: '', role: 'viewer', permissions: [] },
        addUserMsg: '', addUserMsgType: '',
        allPermissions: ['view_transactions', 'create_settlement', 'view_settlements', 'wallet_transfer', 'create_payout', 'approve_payout', 'add_user', 'view_users', 'view_account_info', 'view_settings'],
        editingPermUserId: null, editingPerms: [],

        // Webhook Delivery Logs
        myWhLogs: [], myWhLogsLoading: false, myWhLogPage: 1, myWhLogPagination: {},
        myWhLogSearch: '', myWhLogFilterStatus: '',
        myWhLogDetailOpen: false, myWhLogDetail: null,

        // Pending KYC
        accountPending: false,

        // KYC form
        kycStep: 1, accountInfoTab: 'business',
        kycData: {}, kycFormLoading: false, kycSaving: false,
        kycMsg: '', kycMsgType: '',
        kycForm: { business_name: '', business_type: '', registration_number: '', tin_number: '', address: '', city: '', country: 'Tanzania', bank_name: '', bank_account_name: '', bank_account_number: '', bank_swift: '', bank_branch: '', id_type: '', id_number: '', crypto_wallet_address: '', crypto_network: '', crypto_currency: '' },
        kycIdFile: null, kycLicenseFile: null,
        kycErrors: {},

        // My Charges
        myCharges: {},

        // Currency Exchange
        fxRates: [], fxAllowedCurrencies: [], fxBaseCurrency: '', fxCurrencyLabels: {},
        fxLoading: false, fxExecuting: false,
        fxForm: { from_currency: '', to_currency: '', amount: '', from_operator: '', from_wallet_type: 'collection', to_operator: '', to_wallet_type: 'collection' },
        fxAmountDisplay: '',
        fxPreview: null, fxPreviewLoading: false,
        fxMsg: '', fxMsgType: '',
        fxHistory: [], fxHistoryLoading: false, fxHistoryPage: 1, fxHistoryPagination: {},
        fxResult: null,

        // Internal Transfers
        myTransfers: [], myTransfersLoading: false,

        // Settings / Callback
        callbackUrl: '', callbackLoading: false,
        callbackMsg: '', callbackMsgType: '',

        // Two-Factor Authentication
        tfaEnabled: false, tfaLoading: false, tfaShowConfirm: false,
        tfaPassword: '', tfaMsg: '', tfaMsgType: '',

        // IP Whitelist
        ipList: [], ipLoading: false, ipAddLoading: false,
        newIpAddress: '', newIpLabel: '',
        ipMsg: '', ipMsgType: '',

        // API Keys
        apiKeys: [], apiKeysLoading: false, apiKeyGenerating: false,
        apiKeyLabel: '', apiKeyMsg: '', apiKeyMsgType: '',
        newApiKey: '', newApiSecret: '',

        appReady: false,

        init() {
            const token = localStorage.getItem('auth_token');
            if (!token) { window.location.href = '/login'; return; }
            this.user = JSON.parse(localStorage.getItem('auth_user') || 'null');
            if (this.user?.role === 'super_admin') { window.location.href = '/admin'; return; }
            // If KYC not yet submitted, force redirect to KYC page
            const kycRequired = localStorage.getItem('kyc_required');
            if (kycRequired === 'true') { window.location.href = '/kyc'; return; }
            // Check if account is pending
            const pendingFlag = localStorage.getItem('account_pending');
            if (pendingFlag === 'true' || this.user?.account?.status === 'pending') {
                this.accountPending = true;
            }
            // Fetch fresh user data to get permissions
            this.refreshUser();
            this.fetchTransactions();
            this.fetchMyCharges();
            this.fetchStats();
            this.fetchWallet();
            this.fetchSettlements();
            this.fetchBankAccounts();
            this.fetchCryptoWallets();

            // Restore tab from URL hash
            const hash = window.location.hash.replace('#', '');
            const validTabs = ['dashboard','transactions','wallet','send-money','invoices','settlements','account','users','exchange','api-docs','settings','account-settings'];
            if (hash && validTabs.includes(hash)) {
                this.goToTab(hash, true);
            }

            // Sync hash on tab change
            this.$watch('activeTab', (tab) => {
                if (window.location.hash !== '#' + tab) {
                    history.pushState(null, '', '#' + tab);
                }
            });

            // Handle browser back/forward
            window.addEventListener('popstate', () => {
                const h = window.location.hash.replace('#', '');
                if (h && validTabs.includes(h) && this.activeTab !== h) {
                    this.goToTab(h, true);
                }
            });

            // Idle timeout: auto-logout after 15 minutes of inactivity
            this._lastActivity = Date.now();
            const resetActivity = () => { this._lastActivity = Date.now(); };
            ['mousemove','keydown','click','scroll','touchstart'].forEach(e => document.addEventListener(e, resetActivity, { passive: true }));
            this._idleTimer = setInterval(() => {
                if (Date.now() - this._lastActivity > 15 * 60 * 1000) {
                    clearInterval(this._idleTimer);
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('auth_user');
                    window.location.href = '/login?reason=idle';
                }
            }, 60000);

            this.appReady = true;
            this.$nextTick(() => document.dispatchEvent(new Event('alpine:initialized')));
        },

        /**
         * Navigate to a tab and trigger its data fetch.
         */
        goToTab(tab, skipHash) {
            this.activeTab = tab;
            this.sidebarOpen = false;
            if (!skipHash && window.location.hash !== '#' + tab) {
                history.pushState(null, '', '#' + tab);
            }
            // Trigger data fetches for the target tab
            switch(tab) {
                case 'wallet': this.fetchWallet(); break;
                case 'send-money':
                    this.fetchPayoutOperators();
                    if (this.hasPerm('approve_payout')) this.fetchPendingPayouts();
                    // Default to approvals tab if user can only approve
                    if (this.hasPerm('approve_payout') && !this.hasPerm('create_payout') && !this.hasPerm('wallet_transfer')) {
                        this.sendMoneySubTab = 'approvals';
                    }
                    break;
                case 'settlements': this.fetchSettlements(); break;
                case 'invoices': this.fetchInvoices(); break;
                case 'account': this.fetchKyc(); break;
                case 'users': this.fetchAccountUsers(); break;
                case 'exchange': this.fetchExchangeRates(); this.fetchExchangeHistory(); break;
                case 'settings': this.fetchCallback().then(() => this.fetchTwoFactorStatus()).then(() => this.fetchApiKeys()); break;
                case 'webhook-logs': this.fetchMyWebhookLogs(); break;
                case 'account-settings': this.fetchTwoFactorStatus(); break;
            }
        },

        hasPerm(perm) {
            if (!this.user) return false;
            if (this.user.role === 'owner') return true;
            const perms = this.user.effective_permissions || this.user.permissions || [];
            return perms.includes(perm);
        },

        /**
         * Human-readable permission label.
         */
        permLabel(perm) {
            const labels = {
                view_transactions: 'View Transactions',
                create_settlement: 'Create Settlement',
                view_settlements: 'View Settlements',
                wallet_transfer: 'Wallet Transfer',
                create_payout: 'Create Payout',
                approve_payout: 'Approve Payout',
                add_user: 'Add User',
                view_users: 'View Users',
                view_account_info: 'View Account Info',
                view_settings: 'View Settings',
            };
            return labels[perm] || perm;
        },

        async fetchStats() {
            try {
                const res = await fetch('{{ config("services.transaction_service.public_url") }}/api/transactions/stats', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.stats = data;
                }
            } catch (e) { /* silent */ }
        },

        async refreshUser() {
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/user', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.user = data;
                    localStorage.setItem('auth_user', JSON.stringify(data));
                    // Clear pending state if account is now active
                    if (data.account && data.account.status === 'active') {
                        this.accountPending = false;
                        localStorage.removeItem('account_pending');
                    }
                }
            } catch (e) { console.error(e); }
        },

        async fetchCallback() {
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/callback`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.callbackUrl = data.callback_url || '';
                }
            } catch (e) { console.error(e); }
            this.fetchIps();
        },

        async fetchMyWebhookLogs() {
            this.myWhLogsLoading = true;
            try {
                let url = `/api/webhook-logs?page=${this.myWhLogPage}`;
                if (this.myWhLogSearch) url += `&search=${encodeURIComponent(this.myWhLogSearch)}`;
                if (this.myWhLogFilterStatus) url += `&status=${this.myWhLogFilterStatus}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.myWhLogs = data.data || [];
                    this.myWhLogPagination = { current_page: data.current_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
                }
            } catch (e) { console.error('Failed to fetch webhook logs', e); }
            this.myWhLogsLoading = false;
        },

        viewMyWebhookLog(log) {
            this.myWhLogDetail = log;
            this.myWhLogDetailOpen = true;
        },

        async fetchKyc() {
            this.kycFormLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/kyc`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.kycData = data.kyc || {};
                    this.kycForm.business_name = this.kycData.business_name || '';
                    this.kycForm.business_type = this.kycData.business_type || '';
                    this.kycForm.registration_number = this.kycData.registration_number || '';
                    this.kycForm.tin_number = this.kycData.tin_number || '';
                    this.kycForm.address = this.kycData.address || '';
                    this.kycForm.city = this.kycData.city || '';
                    this.kycForm.country = this.kycData.country || 'Tanzania';
                    this.kycForm.bank_name = this.kycData.bank_name || '';
                    this.kycForm.bank_account_name = this.kycData.bank_account_name || '';
                    this.kycForm.bank_account_number = this.kycData.bank_account_number || '';
                    this.kycForm.bank_swift = this.kycData.bank_swift || '';
                    this.kycForm.bank_branch = this.kycData.bank_branch || '';
                    this.kycForm.id_type = this.kycData.id_type || '';
                    this.kycForm.id_number = this.kycData.id_number || '';
                    this.kycForm.crypto_wallet_address = this.kycData.crypto_wallet_address || '';
                    this.kycForm.crypto_network = this.kycData.crypto_network || '';
                    this.kycForm.crypto_currency = this.kycData.crypto_currency || '';
                }
            } catch (e) { console.error(e); }
            this.kycFormLoading = false;
        },

        validateKycStep1() {
            this.kycErrors = {};
            if (!this.kycForm.business_name.trim()) this.kycErrors.business_name = 'Business name is required.';
            if (!this.kycForm.country) this.kycErrors.country = 'Country is required.';
            if (Object.keys(this.kycErrors).length) { this.kycMsg = 'Please fix the errors below before continuing.'; this.kycMsgType = 'error'; return false; }
            this.kycMsg = ''; return true;
        },

        validateKycStep2() {
            this.kycErrors = {};
            if (!this.kycForm.id_type) this.kycErrors.id_type = 'ID type is required.';
            if (!this.kycForm.id_number.trim()) this.kycErrors.id_number = 'ID number is required.';
            if (Object.keys(this.kycErrors).length) { this.kycMsg = 'Please fix the errors below before continuing.'; this.kycMsgType = 'error'; return false; }
            this.kycMsg = ''; return true;
        },

        validateKycStep3() {
            this.kycErrors = {};
            if (this.kycIdFile && this.kycIdFile.size > 5 * 1024 * 1024) this.kycErrors.id_document = 'ID document must be under 5MB.';
            if (this.kycLicenseFile && this.kycLicenseFile.size > 5 * 1024 * 1024) this.kycErrors.business_license = 'Business license must be under 5MB.';
            if (Object.keys(this.kycErrors).length) { this.kycMsg = 'Please fix the errors below before continuing.'; this.kycMsgType = 'error'; return false; }
            this.kycMsg = ''; return true;
        },

        validateKycAll() {
            if (!this.validateKycStep1()) { this.kycStep = 1; return false; }
            if (!this.validateKycStep2()) { this.kycStep = 2; return false; }
            if (!this.validateKycStep3()) { this.kycStep = 3; return false; }
            return true;
        },

        async saveKyc() {
            if (!this.validateKycAll()) return;
            this.kycSaving = true;
            this.kycMsg = '';
            try {
                const formData = new FormData();
                Object.keys(this.kycForm).forEach(k => {
                    if (this.kycForm[k] !== null && this.kycForm[k] !== '') formData.append(k, this.kycForm[k]);
                });
                if (this.kycIdFile) formData.append('id_document', this.kycIdFile);
                if (this.kycLicenseFile) formData.append('business_license', this.kycLicenseFile);

                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/kyc`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.kycMsg = errors || 'Failed to save KYC.'; this.kycMsgType = 'error'; return;
                }
                this.kycMsg = data.message; this.kycMsgType = 'success';
                this.kycData = data.kyc || this.kycData;
                this.kycIdFile = null; this.kycLicenseFile = null;
                setTimeout(() => { this.kycMsg = ''; }, 5000);
            } catch (e) { this.kycMsg = 'Service unavailable.'; this.kycMsgType = 'error'; }
            finally { this.kycSaving = false; }
        },

        async saveCallback() {
            this.callbackLoading = true;
            this.callbackMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/callback`, {
                    method: 'PUT',
                    headers: this.getHeaders(),
                    body: JSON.stringify({ callback_url: this.callbackUrl || null })
                });
                const data = await res.json();
                if (res.ok) {
                    this.callbackMsg = data.message || 'Callback URL saved successfully.';
                    this.callbackMsgType = 'success';
                } else {
                    this.callbackMsg = data.message || 'Failed to save callback URL.';
                    this.callbackMsgType = 'error';
                }
            } catch (e) {
                this.callbackMsg = 'Network error. Please try again.';
                this.callbackMsgType = 'error';
            }
            this.callbackLoading = false;
            setTimeout(() => this.callbackMsg = '', 5000);
        },

        async fetchIps() {
            this.ipLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/ips`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.ipList = data.ips || [];
                }
            } catch (e) { console.error(e); }
            this.ipLoading = false;
        },

        async addIp() {
            if (!this.newIpAddress.trim()) { this.ipMsg = 'Please enter an IP address.'; this.ipMsgType = 'error'; return; }
            this.ipAddLoading = true;
            this.ipMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/ips`, {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ ip_address: this.newIpAddress.trim(), label: this.newIpLabel.trim() || null })
                });
                const data = await res.json();
                if (res.ok) {
                    this.ipMsg = data.message || 'IP added. Pending admin approval.';
                    this.ipMsgType = 'success';
                    this.newIpAddress = ''; this.newIpLabel = '';
                    this.fetchIps();
                } else {
                    this.ipMsg = data.message || 'Failed to add IP.';
                    this.ipMsgType = 'error';
                }
            } catch (e) {
                this.ipMsg = 'Network error.';
                this.ipMsgType = 'error';
            }
            this.ipAddLoading = false;
            setTimeout(() => this.ipMsg = '', 5000);
        },

        async deleteIp(id) {
            if (!confirm('Remove this IP from the whitelist?')) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/ips/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) { this.fetchIps(); }
            } catch (e) { console.error(e); }
        },

        // ---- API Keys ----
        async fetchApiKeys() {
            this.apiKeysLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/api-keys`, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.apiKeys = data.api_keys || [];
                }
            } catch (e) { console.error(e); }
            this.apiKeysLoading = false;
        },

        async generateApiKey() {
            if (!this.apiKeyLabel.trim()) { this.apiKeyMsg = 'Please enter a label.'; this.apiKeyMsgType = 'error'; return; }
            this.apiKeyGenerating = true;
            this.apiKeyMsg = '';
            this.newApiSecret = '';
            this.newApiKey = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/api-keys`, {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ label: this.apiKeyLabel.trim() })
                });
                const data = await res.json();
                if (res.ok) {
                    this.apiKeyMsg = 'API key generated! Copy the secret — it will not be shown again.';
                    this.apiKeyMsgType = 'success';
                    this.newApiKey = data.api_key;
                    this.newApiSecret = data.api_secret;
                    this.apiKeyLabel = '';
                    this.fetchApiKeys();
                } else {
                    this.apiKeyMsg = data.message || 'Failed to generate API key.';
                    this.apiKeyMsgType = 'error';
                }
            } catch (e) {
                this.apiKeyMsg = 'Network error.';
                this.apiKeyMsgType = 'error';
            }
            this.apiKeyGenerating = false;
        },

        async revokeApiKey(id) {
            if (!confirm('Revoke this API key? It will stop working immediately.')) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/api-keys/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) {
                    this.apiKeyMsg = 'API key revoked.';
                    this.apiKeyMsgType = 'success';
                    this.fetchApiKeys();
                    setTimeout(() => this.apiKeyMsg = '', 5000);
                }
            } catch (e) { console.error(e); }
        },

        getHeaders() {
            return { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },

        handleUnauth(res) {
            if (res.status === 401) { localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); window.location.href = '/login'; return true; }
            return false;
        },

        // ---- Transaction Export ----
        buildTxnExportParams() {
            let params = [];
            if (this.searchQuery) params.push(`search=${encodeURIComponent(this.searchQuery)}`);
            if (this.filterStatus) params.push(`status=${this.filterStatus}`);
            if (this.filterType) params.push(`type=${this.filterType}`);
            if (this.filterOperator) params.push(`operator=${encodeURIComponent(this.filterOperator)}`);
            if (this.dateFrom) params.push(`date_from=${this.dateFrom}`);
            if (this.dateTo) params.push(`date_to=${this.dateTo}`);
            return params.length ? '?' + params.join('&') : '';
        },
        async downloadTransactions(format) {
            this.txnExportLoading = format;
            try {
                const endpoint = format === 'excel' ? 'export/excel' : 'export/pdf';
                const url = `{{ config("services.transaction_service.public_url") }}/api/transactions/${endpoint}${this.buildTxnExportParams()}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) { alert('Export failed. Please try again.'); return; }
                const blob = await res.blob();
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                const ext = format === 'excel' ? 'xlsx' : 'pdf';
                link.download = `transactions_${new Date().toISOString().slice(0,10)}.${ext}`;
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (e) { console.error(e); alert('Export failed. Please try again.'); }
            this.txnExportLoading = false;
        },

        // ---- Transactions ----
        async fetchTransactions() {
            this.loadingTxns = true; this.txnError = '';
            try {
                let url = `{{ config("services.transaction_service.public_url") }}/api/transactions?page=${this.currentPage}`;
                if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;
                if (this.filterStatus) url += `&status=${this.filterStatus}`;
                if (this.filterType) url += `&type=${this.filterType}`;
                if (this.filterOperator) url += `&operator=${encodeURIComponent(this.filterOperator)}`;
                if (this.dateFrom) url += `&date_from=${this.dateFrom}`;
                if (this.dateTo) url += `&date_to=${this.dateTo}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.transactions = data.data || [];
                this.pagination = { current_page: data.current_page, last_page: data.last_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) { this.txnError = 'Failed to load transactions.'; }
            finally { this.loadingTxns = false; }
        },
        goToPage(p) { if (p < 1 || p > this.pagination.last_page) return; this.currentPage = p; this.fetchTransactions(); },
        goToDisbPage(p) { if (p < 1 || p > this.disbPagination.last_page) return; this.disbPage = p; this.fetchRecentDisbursements(); },
        goToStlPage(p) { if (p < 1 || p > this.stlPagination.last_page) return; this.stlPage = p; this.fetchSettlements(); },
        goToWtPage(p) { if (p < 1 || p > this.wtPagination.last_page) return; this.wtPage = p; this.fetchWalletTransactions(); },

        // ---- My Charges ----
        async fetchMyCharges() {
            try {
                const res = await fetch('{{ config("services.transaction_service.public_url") }}/api/my-charges', { headers: this.getHeaders() });
                if (res.ok) this.myCharges = await res.json();
            } catch (e) { /* silent */ }
        },

        // ---- Wallet ----
        async fetchWallet() {
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/wallet', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.collectionWallets = data.collection_wallets || [];
                this.disbursementWallets = data.disbursement_wallets || [];
                this.overallBalance = parseFloat(data.overall_balance) || 0;
                this.collectionTotal = parseFloat(data.collection_total) || 0;
                this.disbursementTotal = parseFloat(data.disbursement_total) || 0;
                this.operators = data.operators || [];
                this.walletCurrency = data.currency || 'TZS';
                this.walletByCurrency = data.by_currency || [];

                // Init per-operator reactive state
                this.operators.forEach(op => {
                    if (!this.creditAmounts[op]) this.creditAmounts[op] = '';
                    if (!this.creditDescs[op]) this.creditDescs[op] = '';
                    if (!this.transferAmounts[op]) this.transferAmounts[op] = '';
                    if (!this.walletCreditLoading[op]) this.walletCreditLoading[op] = false;
                    if (!this.walletTransferLoading[op]) this.walletTransferLoading[op] = false;
                });
            } catch (e) {}
            this.fetchWalletTransactions();
            this.fetchMyTransfers();
        },
        updateWalletData(data) {
            if (data.collection_wallets) this.collectionWallets = data.collection_wallets;
            if (data.disbursement_wallets) this.disbursementWallets = data.disbursement_wallets;
            if (data.overall_balance) this.overallBalance = data.overall_balance;
            if (data.collection_total) this.collectionTotal = data.collection_total;
            if (data.disbursement_total) this.disbursementTotal = data.disbursement_total;
            if (data.currency) this.walletCurrency = data.currency;
        },

        async fetchWalletTransactions() {
            this.walletLoading.txns = true;
            try {
                let url = `{{ config("services.wallet_service.public_url") }}/api/wallet/transactions?page=${this.wtPage}`;
                if (this.walletTxnOperatorFilter) url += `&operator=${encodeURIComponent(this.walletTxnOperatorFilter)}`;
                if (this.walletTxnTypeFilter) url += `&wallet_type=${this.walletTxnTypeFilter}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.walletTransactions = data.data || [];
                this.wtPagination = { current_page: data.current_page, last_page: data.last_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) {}
            finally { this.walletLoading.txns = false; }
        },
        async creditOperator(operator) {
            const key = 'col_' + operator;
            this.walletCreditLoading[operator] = true;
            this.walletMsg[key] = '';
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/wallet/credit', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ amount: this.creditAmounts[operator], operator: operator, description: this.creditDescs[operator] })
                });
                const data = await res.json();
                if (!res.ok) { this.walletMsg[key] = data.message || 'Credit failed.'; this.walletMsgType[key] = 'error'; return; }
                // Show charge info if present
                let msg = data.message;
                if (data.charges && Number(data.charges.total_charge) > 0) {
                    msg = `Credited ${this.formatAmount(data.charges.net_amount)} ${this.walletCurrency} (Charges: ${this.formatAmount(data.charges.total_charge)} ${this.walletCurrency} deducted from ${this.formatAmount(data.charges.gross_amount)} ${this.walletCurrency})`;
                }
                this.walletMsg[key] = msg; this.walletMsgType[key] = 'success';
                this.updateWalletData(data);
                this.creditAmounts[operator] = ''; this.creditDescs[operator] = '';
                this.fetchWalletTransactions();
                this.fetchMyCharges();
                setTimeout(() => { this.walletMsg[key] = ''; }, 5000);
            } catch (e) { this.walletMsg[key] = 'Service unavailable.'; this.walletMsgType[key] = 'error'; }
            finally { this.walletCreditLoading[operator] = false; }
        },
        async transferToDisbursement(operator) {
            const key = 'trf_' + operator;
            if (!this.transferAmounts[operator] || this.transferAmounts[operator] < 1) {
                this.walletMsg[key] = 'Enter a valid amount.'; this.walletMsgType[key] = 'error'; return;
            }
            this.walletTransferLoading[operator] = true;
            this.walletMsg[key] = '';
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/wallet/transfer', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ amount: this.transferAmounts[operator], operator: operator })
                });
                const data = await res.json();
                if (!res.ok) { this.walletMsg[key] = data.message || 'Transfer failed.'; this.walletMsgType[key] = 'error'; return; }
                this.walletMsg[key] = data.message; this.walletMsgType[key] = 'success';
                this.transferAmounts[operator] = '';
                this.transferAmountDisplays[operator] = '';
                this.fetchMyTransfers();
                this.fetchWallet();
                setTimeout(() => { this.walletMsg[key] = ''; }, 5000);
            } catch (e) { this.walletMsg[key] = 'Service unavailable.'; this.walletMsgType[key] = 'error'; }
            finally { this.walletTransferLoading[operator] = false; }
        },

        previewTransfer(operator, balance) {
            const key = 'trf_' + operator;
            if (!this.transferAmounts[operator] || this.transferAmounts[operator] < 1) {
                this.walletMsg[key] = 'Enter a valid amount.'; this.walletMsgType[key] = 'error'; return;
            }
            this.trfSummary = {
                operator: operator,
                amount: Number(this.transferAmounts[operator]),
                available: balance || 0,
            };
            this.trfSummaryOpen = true;
        },

        async confirmTransfer() {
            await this.transferToDisbursement(this.trfSummary.operator);
            this.trfSummaryOpen = false;
        },
        operatorColor(op) {
            return { 'bg-green-500': op==='M-Pesa', 'bg-blue-500': op==='Tigo Pesa', 'bg-red-500': op==='Airtel Money', 'bg-orange-500': op==='Halopesa' };
        },
        operatorBtnColor(op) {
            return { 'bg-green-600 hover:bg-green-700': op==='M-Pesa', 'bg-blue-600 hover:bg-blue-700': op==='Tigo Pesa', 'bg-red-600 hover:bg-red-700': op==='Airtel Money', 'bg-orange-600 hover:bg-orange-700': op==='Halopesa' };
        },
        operatorBadgeColor(op) {
            return { 'bg-green-100 text-green-800': op==='M-Pesa', 'bg-blue-100 text-blue-800': op==='Tigo Pesa', 'bg-red-100 text-red-800': op==='Airtel Money', 'bg-orange-100 text-orange-800': op==='Halopesa' };
        },

        // ---- Internal Transfers ----
        async fetchMyTransfers() {
            this.myTransfersLoading = true;
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/wallet/transfers', { headers: this.getHeaders() });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.myTransfers = data.transfers || data.data || [];
            } catch (e) {}
            finally { this.myTransfersLoading = false; }
        },

        // ---- Settlements ----
        async fetchSettlements() {
            this.stlLoadingList = true;
            try {
                let url = `{{ config("services.settlement_service.public_url") }}/api/settlements?page=${this.stlPage}`;
                if (this.stlFilterStatus) url += `&status=${this.stlFilterStatus}`;
                if (this.stlSearch.trim()) url += `&search=${encodeURIComponent(this.stlSearch.trim())}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.settlements = data.data || [];
                this.stlPagination = { current_page: data.current_page, last_page: data.last_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) {}
            finally { this.stlLoadingList = false; }
        },
        async previewSettlement() {
            this.settlementMsg = '';
            if (!this.stlForm.operator) { this.settlementMsg = 'Please select an operator.'; this.settlementMsgType = 'error'; return; }
            if (!this.stlForm.amount || Number(this.stlForm.amount) < 1000) { this.settlementMsg = 'Minimum amount is 1,000 ' + this.walletCurrency + '.'; this.settlementMsgType = 'error'; return; }
            if (!this.stlForm.bank_account_id) { this.settlementMsg = 'Please select a bank account.'; this.settlementMsgType = 'error'; return; }

            this.stlPreviewLoading = true;
            const amount = Number(this.stlForm.amount);
            let platformCharge = 0, operatorCharge = 0, totalCharge = 0;

            try {
                const res = await fetch('{{ config("services.transaction_service.public_url") }}/api/charges/calculate', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ amount: amount, operator: this.stlForm.operator, transaction_type: 'settlement' })
                });
                if (res.ok) {
                    const cd = await res.json();
                    platformCharge = Number(cd.platform_charge || 0);
                    operatorCharge = Number(cd.operator_charge || 0);
                    totalCharge = Number(cd.total_charge || 0);
                }
            } catch (e) { /* proceed with zero charges */ }

            const totalDebit = amount + platformCharge;
            const ba = this.bankAccounts.find(b => b.id == this.stlForm.bank_account_id);
            const wallet = this.collectionWallets.find(w => w.operator === this.stlForm.operator);

            this.stlSummary = {
                operator: this.stlForm.operator,
                amount: amount,
                platform_charge: platformCharge,
                operator_charge: operatorCharge,
                total_charge: totalCharge,
                total_debit: totalDebit,
                bank_name: ba ? ba.bank_name : '',
                account_number: ba ? ba.account_number : '',
                account_name: ba ? ba.account_name : '',
                description: this.stlForm.description || '',
                available_balance: wallet ? wallet.balance : 0,
            };
            this.stlSummaryOpen = true;
            this.stlPreviewLoading = false;
        },

        async confirmSettlement() {
            this.stlLoading = true;
            this.settlementMsg = '';
            try {
                const res = await fetch('{{ config("services.settlement_service.public_url") }}/api/settlements', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(this.stlForm)
                });
                let data;
                const text = await res.text();
                try { data = JSON.parse(text); } catch (pe) { data = { message: text || 'Unexpected response from server.' }; }
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.settlementMsg = errors || 'Failed.'; this.settlementMsgType = 'error';
                } else {
                    // Build receipt
                    const s = data.settlement || {};
                    const c = data.charges || {};
                    this.stlReceipt = {
                        settlement_ref: s.settlement_ref || '',
                        operator: s.operator || this.stlSummary.operator,
                        amount: Number(s.amount || this.stlSummary.amount),
                        service_charge: Number(c.total_charge || this.stlSummary.total_charge),
                        total_debit: Number(c.total_debited || this.stlSummary.total_debit),
                        currency: s.currency || this.walletCurrency,
                        bank_name: s.bank_name || this.stlSummary.bank_name,
                        account_number: s.account_number || this.stlSummary.account_number,
                        account_name: s.account_name || this.stlSummary.account_name,
                        description: s.description || this.stlSummary.description || '-',
                        status: s.status || 'pending',
                        created_at: s.created_at || new Date().toISOString(),
                    };
                    this.stlReceiptOpen = true;
                    this.settlementMsg = data.message; this.settlementMsgType = 'success';
                    this.stlForm = { operator: '', amount: '', bank_account_id: '', description: '' };
                    this.stlAmountDisplay = '';
                    this.fetchSettlements();
                    this.fetchMyCharges();
                    this.fetchTransactions();
                    this.fetchWallet();
                }
            } catch (e) {
                console.error('Settlement submit error:', e);
                this.settlementMsg = e.message || 'Service unavailable. Please try again.';
                this.settlementMsgType = 'error';
            }
            finally { this.stlLoading = false; this.stlSummaryOpen = false; }
        },

        viewSettlementReceipt(stl) {
            const meta = typeof stl.metadata === 'string' ? JSON.parse(stl.metadata || '{}') : (stl.metadata || {});
            this.stlReceipt = {
                settlement_ref: stl.settlement_ref,
                operator: stl.operator,
                amount: Number(stl.amount),
                service_charge: Number(meta.total_charge || 0),
                total_debit: Number(meta.total_debited || stl.amount),
                currency: stl.currency || this.walletCurrency,
                bank_name: stl.bank_name,
                account_number: stl.account_number,
                account_name: stl.account_name,
                description: stl.description || '-',
                status: stl.status,
                created_at: stl.created_at,
            };
            this.stlReceiptOpen = true;
        },

        downloadSettlementPdf(receipt) {
            const r = receipt || this.stlReceipt;
            if (!r) return;
            const statusColor = r.status === 'completed' ? '#059669' : r.status === 'pending' ? '#D97706' : r.status === 'failed' ? '#DC2626' : '#2563EB';
            const statusBg = r.status === 'completed' ? '#ECFDF5' : r.status === 'pending' ? '#FFFBEB' : r.status === 'failed' ? '#FEF2F2' : '#EFF6FF';
            const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Settlement Receipt - ${r.settlement_ref}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f3f4f6;padding:20px}
.receipt{max-width:500px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.header{background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff;padding:28px 24px;text-align:center}
.header h1{font-size:20px;font-weight:700;margin-bottom:4px}.header p{font-size:12px;opacity:.85}
.logo{font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:8px}
.status{display:inline-block;padding:4px 14px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;background:${statusBg};color:${statusColor};margin-top:10px}
.body{padding:24px}.row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.row:last-child{border-bottom:none}.label{color:#6b7280}.value{font-weight:600;color:#1f2937;text-align:right;max-width:60%}
.total-row{background:#eff6ff;margin:12px -24px;padding:12px 24px;border-bottom:none}
.total-row .label{font-weight:700;color:#1e40af;font-size:14px}.total-row .value{color:#1e40af;font-size:14px}
.ref-box{background:#f9fafb;border-radius:8px;padding:12px;text-align:center;margin-bottom:16px}
.ref-box .label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af}.ref-box .value{font-size:14px;font-family:monospace;margin-top:4px}
.footer{padding:16px 24px;border-top:1px solid #e5e7eb;text-align:center;font-size:10px;color:#9ca3af}
@media print{body{padding:0;background:#fff}.receipt{box-shadow:none;border-radius:0}}
</style></head><body>
<div class="receipt">
<div class="header"><div class="logo">Payin</div><h1>Settlement Receipt</h1><p>Payment Settlement Platform</p><div class="status">${r.status}</div></div>
<div class="body">
<div class="ref-box"><div class="label">Reference Number</div><div class="value">${r.settlement_ref}</div></div>
<div class="row"><span class="label">Operator</span><span class="value">${r.operator}</span></div>
<div class="row"><span class="label">Settlement Amount</span><span class="value">${this.formatAmount(r.amount)} ${r.currency}</span></div>
<div class="row"><span class="label">Service Charge</span><span class="value">${this.formatAmount(r.service_charge)} ${r.currency}</span></div>
<div class="row total-row"><span class="label">Total Debited</span><span class="value">${this.formatAmount(r.total_debit)} ${r.currency}</span></div>
<div class="row"><span class="label">Bank</span><span class="value">${r.bank_name}</span></div>
<div class="row"><span class="label">Account Number</span><span class="value">${r.account_number}</span></div>
<div class="row"><span class="label">Account Name</span><span class="value">${r.account_name}</span></div>
<div class="row"><span class="label">Description</span><span class="value">${r.description}</span></div>
<div class="row"><span class="label">Date</span><span class="value">${this.formatDate(r.created_at)}</span></div>
</div>
<div class="footer">This is a system-generated receipt from Payin Settlement Platform.<br>For queries, contact support@payin.co.tz</div>
</div></body></html>`;
            const win = window.open('', '_blank', 'width=600,height=800');
            win.document.write(html);
            win.document.close();
            setTimeout(() => { win.print(); }, 500);
        },

        viewDisbursementReceipt(d) {
            this.payoutReceipt = {
                request_ref: d.request_ref,
                phone: d.phone,
                amount: d.amount,
                operator: d.operator_name,
                status: d.status,
                platform_charge: d.platform_charge || 0,
                total_debit: Number(d.amount) + Number(d.platform_charge || 0),
                currency: d.currency || this.walletCurrency || 'TZS',
                reference: d.external_ref || '',
                description: d.description || '',
                date: d.created_at,
            };
            this.downloadPayoutPdf(this.payoutReceipt);
        },

        downloadPayoutPdf(receipt) {
            const r = receipt || this.payoutReceipt;
            if (!r) return;
            const statusColor = r.status === 'completed' || r.status === 'successful' ? '#059669' : r.status === 'processing' ? '#2563EB' : '#DC2626';
            const statusBg = r.status === 'completed' || r.status === 'successful' ? '#ECFDF5' : r.status === 'processing' ? '#EFF6FF' : '#FEF2F2';
            const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Payout Receipt - ${r.request_ref}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f3f4f6;padding:20px}
.receipt{max-width:500px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.header{background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;padding:28px 24px;text-align:center}
.header h1{font-size:20px;font-weight:700;margin-bottom:4px}.header p{font-size:12px;opacity:.85}
.logo{font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:8px}
.status{display:inline-block;padding:4px 14px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;background:${statusBg};color:${statusColor};margin-top:10px}
.body{padding:24px}.row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.row:last-child{border-bottom:none}.label{color:#6b7280}.value{font-weight:600;color:#1f2937;text-align:right;max-width:60%}
.total-row{background:#eff6ff;margin:12px -24px;padding:12px 24px;border-bottom:none}
.total-row .label{font-weight:700;color:#2563eb;font-size:14px}.total-row .value{color:#2563eb;font-size:14px}
.ref-box{background:#f9fafb;border-radius:8px;padding:12px;text-align:center;margin-bottom:16px}
.ref-box .label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af}.ref-box .value{font-size:14px;font-family:monospace;margin-top:4px}
.footer{padding:16px 24px;border-top:1px solid #e5e7eb;text-align:center;font-size:10px;color:#9ca3af}
@media print{body{padding:0;background:#fff}.receipt{box-shadow:none;border-radius:0}}
</style></head><body>
<div class="receipt">
<div class="header"><div class="logo">Payin</div><h1>Payout Receipt</h1><p>Disbursement Transaction</p><div class="status">${r.status}</div></div>
<div class="body">
<div class="ref-box"><div class="label">Reference Number</div><div class="value">${r.request_ref}</div></div>
<div class="row"><span class="label">Recipient Phone</span><span class="value">${r.phone}</span></div>
<div class="row"><span class="label">Operator</span><span class="value">${r.operator}</span></div>
<div class="row"><span class="label">Send Amount</span><span class="value">${this.formatAmount(r.amount)} ${r.currency || 'TZS'}</span></div>
${r.platform_charge > 0 ? `<div class="row"><span class="label">Service Charge</span><span class="value">${this.formatAmount(r.platform_charge)} ${r.currency || 'TZS'}</span></div>` : ''}
<div class="row total-row"><span class="label">Total Debited</span><span class="value">${this.formatAmount(r.total_debit)} ${r.currency || 'TZS'}</span></div>
${r.reference ? `<div class="row"><span class="label">Reference</span><span class="value">${r.reference}</span></div>` : ''}
${r.description ? `<div class="row"><span class="label">Description</span><span class="value">${r.description}</span></div>` : ''}
<div class="row"><span class="label">Date</span><span class="value">${this.formatDate(r.date)}</span></div>
</div>
<div class="footer">This is a system-generated receipt from Payin Payment Platform.<br>For queries, contact support@payin.co.tz</div>
</div></body></html>`;
            const win = window.open('', '_blank', 'width=600,height=800');
            win.document.write(html);
            win.document.close();
            setTimeout(() => { win.print(); }, 500);
        },

        downloadBatchPdf() {
            if (!this.batchResults || this.batchResults.length === 0) return;
            const s = this.batchResultSummary;
            const bName = this.batchName || 'Unnamed Batch';
            const totalAmount = this.batchResults.reduce((sum, r) => sum + Number(r.amount || 0), 0);
            const successAmount = this.batchResults.filter(r => r.success).reduce((sum, r) => sum + Number(r.amount || 0), 0);
            const rows = this.batchResults.map((r, i) => `<tr>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:12px;color:#6b7280">${i + 1}</td>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:12px;font-family:monospace">${r.phone}</td>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:12px;font-weight:600">${this.formatAmount(r.amount)} TZS</td>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:12px"><span style="padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;background:${r.success ? '#ECFDF5' : '#FEF2F2'};color:${r.success ? '#059669' : '#DC2626'}">${r.success ? 'Sent' : 'Failed'}</span></td>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:11px;font-family:monospace;color:#6b7280">${r.request_ref || '—'}</td>
<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-size:11px;color:#DC2626">${r.error || '—'}</td>
</tr>`).join('');
            const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Batch Payout Receipt - ${bName}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f3f4f6;padding:20px}
.receipt{max-width:800px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.header{background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;padding:28px 24px;text-align:center}
.header h1{font-size:20px;font-weight:700;margin-bottom:4px}.header p{font-size:12px;opacity:.85}
.logo{font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:8px}
.summary{display:flex;gap:16px;padding:16px 24px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.summary-card{flex:1;text-align:center;padding:12px;border-radius:8px}
.summary-card .num{font-size:20px;font-weight:700;margin-top:4px}
.summary-card .lbl{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#6b7280}
.body{padding:24px}
table{width:100%;border-collapse:collapse}
th{padding:8px 12px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;background:#f9fafb;border-bottom:2px solid #e5e7eb}
.footer{padding:16px 24px;border-top:1px solid #e5e7eb;text-align:center;font-size:10px;color:#9ca3af}
@media print{body{padding:0;background:#fff}.receipt{box-shadow:none;border-radius:0}}
</style></head><body>
<div class="receipt">
<div class="header"><div class="logo">Payin</div><h1>Batch Payout Receipt</h1><p>${bName}</p></div>
<div class="summary">
<div class="summary-card" style="background:#ECFDF5"><div class="lbl">Sent</div><div class="num" style="color:#059669">${s.sent}</div></div>
<div class="summary-card" style="background:#FEF2F2"><div class="lbl">Failed</div><div class="num" style="color:#DC2626">${s.failed}</div></div>
<div class="summary-card" style="background:#EFF6FF"><div class="lbl">Total</div><div class="num" style="color:#2563EB">${s.total}</div></div>
<div class="summary-card" style="background:#F5F3FF"><div class="lbl">Total Amount</div><div class="num" style="color:#7C3AED">${this.formatAmount(successAmount)} TZS</div></div>
</div>
<div class="body">
<table><thead><tr><th>#</th><th>Phone</th><th>Amount</th><th>Status</th><th>Reference</th><th>Error</th></tr></thead>
<tbody>${rows}</tbody>
</table>
</div>
<div class="footer">Batch generated on ${this.formatDate(new Date().toISOString())}<br>This is a system-generated receipt from Payin Payment Platform. Contact support@payin.co.tz</div>
</div></body></html>`;
            const win = window.open('', '_blank', 'width=900,height=800');
            win.document.write(html);
            win.document.close();
            setTimeout(() => { win.print(); }, 500);
        },

        // ---- Bank Accounts ----
        async fetchBankAccounts() {
            this.bankAccountsLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/bank-accounts', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.bankAccounts = data.bank_accounts || [];
                    // Auto-select default bank account for settlement form
                    if (this.bankAccounts.length && !this.stlForm.bank_account_id) {
                        const def = this.bankAccounts.find(b => b.is_default);
                        if (def) this.stlForm.bank_account_id = def.id;
                    }
                }
            } catch (e) { console.error('Failed to fetch bank accounts', e); }
            finally { this.bankAccountsLoading = false; }
        },
        async addBankAccount() {
            this.bankFormLoading = true; this.bankMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/bank-accounts', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(this.bankForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.bankMsg = errors || 'Failed.'; this.bankMsgType = 'error'; return;
                }
                this.bankMsg = data.message; this.bankMsgType = 'success';
                this.bankForm = { bank_name: '', account_name: '', account_number: '', swift_code: '', branch: '', label: '' };
                this.showBankForm = false;
                this.fetchBankAccounts();
            } catch (e) { this.bankMsg = 'Service unavailable.'; this.bankMsgType = 'error'; }
            finally { this.bankFormLoading = false; }
        },
        async deleteBankAccount(id) {
            if (!confirm('Remove this bank account?')) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/bank-accounts/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) this.fetchBankAccounts();
            } catch (e) { console.error(e); }
        },
        async setDefaultBank(id) {
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/bank-accounts/${id}/default`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                if (res.ok) this.fetchBankAccounts();
            } catch (e) { console.error(e); }
        },

        // ---- Crypto Wallets ----
        async fetchCryptoWallets() {
            this.cryptoWalletsLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/crypto-wallets', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.cryptoWallets = data.crypto_wallets || [];
                }
            } catch (e) { console.error('Failed to fetch crypto wallets', e); }
            finally { this.cryptoWalletsLoading = false; }
        },
        async addCryptoWallet() {
            this.cryptoFormLoading = true; this.cryptoMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/crypto-wallets', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(this.cryptoForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.cryptoMsg = errors || 'Failed.'; this.cryptoMsgType = 'error'; return;
                }
                this.cryptoMsg = data.message; this.cryptoMsgType = 'success';
                this.cryptoForm = { currency: '', network: '', wallet_address: '', label: '' };
                this.showCryptoForm = false;
                this.fetchCryptoWallets();
            } catch (e) { this.cryptoMsg = 'Service unavailable.'; this.cryptoMsgType = 'error'; }
            finally { this.cryptoFormLoading = false; }
        },
        async deleteCryptoWallet(id) {
            if (!confirm('Remove this crypto wallet?')) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/crypto-wallets/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) this.fetchCryptoWallets();
            } catch (e) { console.error(e); }
        },
        async setDefaultCrypto(id) {
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/crypto-wallets/${id}/default`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                if (res.ok) this.fetchCryptoWallets();
            } catch (e) { console.error(e); }
        },

        // ---- Send Money (Payout) ----
        async fetchPayoutOperators() {
            try {
                const res = await fetch('/api/operators', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.payoutOperators = data.operators || [];
                }
            } catch (e) { console.error('Failed to fetch operators', e); }
            this.fetchRecentDisbursements();
        },

        async detectOperator(phone) {
            if (!phone || phone.replace(/[\s\-\.]/g, '').length < 9) {
                this.detectedOperator = { name: '', code: '' };
                return;
            }
            // Normalize to local format using currency-aware function
            let cleaned = this.normalizePhone(phone);
            // Extract 3-digit prefix (e.g., 075)
            if (cleaned.startsWith('0') && cleaned.length >= 10) {
                const prefix = cleaned.substring(0, 3);
                for (const op of this.payoutOperators) {
                    if (op.prefixes && op.prefixes.includes(prefix)) {
                        this.detectedOperator = { name: op.name, code: op.code };
                        this.calculatePayoutCharges();
                        return;
                    }
                }
            }
            // Fallback to API detection
            this.detectingOp = true;
            try {
                const res = await fetch('/api/detect-operator', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ phone })
                });
                const data = await res.json();
                if (data.detected) {
                    this.detectedOperator = { name: data.operator.name, code: data.operator.code };
                    this.calculatePayoutCharges();
                } else {
                    this.detectedOperator = { name: '', code: '' };
                    this.payoutCharges = null;
                }
            } catch (e) { this.detectedOperator = { name: '', code: '' }; this.payoutCharges = null; }
            this.detectingOp = false;
        },

        async calculatePayoutCharges() {
            if (!this.detectedOperator.code || !this.payoutForm.amount || this.payoutForm.amount < 100) {
                this.payoutCharges = null;
                return;
            }
            this.payoutChargesLoading = true;
            try {
                const res = await fetch('{{ config("services.transaction_service.public_url") }}/api/charges/calculate', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({
                        amount: Number(this.payoutForm.amount),
                        operator: this.detectedOperator.code,
                        transaction_type: 'disbursement'
                    })
                });
                if (res.ok) {
                    this.payoutCharges = await res.json();
                } else {
                    this.payoutCharges = { platform_charge: 0, total_charge: 0 };
                }
            } catch (e) {
                this.payoutCharges = { platform_charge: 0, total_charge: 0 };
            }
            this.payoutChargesLoading = false;
        },

        async sendSinglePayout() {
            this.payoutLoading = true;
            this.payoutMsg = '';
            this.lastPayoutResult = null;
            if (!this.detectedOperator.code) {
                this.payoutMsg = 'Could not detect operator. Please check the phone number.';
                this.payoutMsgType = 'error';
                this.payoutLoading = false;
                return;
            }
            try {
                const normalizedPhone = this.normalizePhoneToCountry(this.payoutForm.phone);
                const res = await fetch('/api/disbursement', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ ...this.payoutForm, phone: normalizedPhone, operator: this.detectedOperator.code })
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.payoutMsg = errors || 'Failed to send.';
                    this.payoutMsgType = 'error';
                } else {
                    if (data.requires_approval) {
                        this.payoutMsg = 'Payout submitted for approval. An approver will review it before funds are sent.';
                        this.payoutMsgType = 'success';
                        this.payoutForm = { phone: '', amount: '', reference: '', description: '' };
                        this.payoutAmountDisplay = '';
                        this.detectedOperator = { name: '', code: '' };
                        this.payoutCharges = null;
                        this.fetchPendingPayouts();
                    } else {
                        this.payoutMsg = data.message || 'Payout sent successfully!';
                        this.payoutMsgType = 'success';
                        this.lastPayoutResult = data;
                        // Show receipt modal
                        this.payoutReceipt = {
                            request_ref: data.request_ref,
                            phone: data.phone || this.payoutForm.phone,
                            amount: data.amount || this.payoutForm.amount,
                            operator: data.operator || this.detectedOperator.name,
                            status: data.status || 'processing',
                            platform_charge: this.payoutCharges?.platform_charge || 0,
                            operator_charge: this.payoutCharges?.operator_charge || 0,
                            total_debit: Number(data.amount || this.payoutForm.amount) + Number(this.payoutCharges?.platform_charge || 0) + Number(this.payoutCharges?.operator_charge || 0),
                            currency: this.walletCurrency || 'TZS',
                            reference: this.payoutForm.reference || '',
                            description: this.payoutForm.description || '',
                            date: new Date().toISOString(),
                        };
                        this.payoutReceiptOpen = true;
                        this.payoutForm = { phone: '', amount: '', reference: '', description: '' };
                        this.payoutAmountDisplay = '';
                        this.detectedOperator = { name: '', code: '' };
                        this.payoutCharges = null;
                        this.disbPage = 1;
                        this.fetchRecentDisbursements();
                    }
                }
            } catch (e) { this.payoutMsg = 'Network error.'; this.payoutMsgType = 'error'; }
            this.payoutLoading = false;
        },

        handleBatchFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.batchCsvText = e.target.result;
                this.parseBatchCsv();
            };
            reader.readAsText(file);
        },

        parseBatchCsv() {
            this.batchMsg = '';
            this.batchItems = [];
            this.batchCharges = null;
            const lines = this.batchCsvText.trim().split('\n').map(l => l.trim()).filter(l => l);
            if (lines.length < 2) {
                this.batchMsg = 'CSV must have a header row and at least one data row.';
                this.batchMsgType = 'error';
                return;
            }
            const header = lines[0].toLowerCase().split(',').map(h => h.trim());
            const phoneIdx = header.indexOf('phone');
            const amountIdx = header.indexOf('amount');
            const refIdx = header.indexOf('reference');
            const descIdx = header.indexOf('description');

            if (phoneIdx === -1 || amountIdx === -1) {
                this.batchMsg = 'CSV header must contain: phone, amount';
                this.batchMsgType = 'error';
                return;
            }

            const items = [];
            const warnings = [];
            for (let i = 1; i < lines.length; i++) {
                const cols = lines[i].split(',').map(c => c.trim());
                const phone = cols[phoneIdx] || '';
                const amount = parseFloat(cols[amountIdx]) || 0;
                if (!phone || amount < 100) {
                    warnings.push(`Row ${i + 1}: invalid data (phone and amount >= 100 required).`);
                    continue;
                }
                const phoneCheck = this.validatePhone(phone);
                if (!phoneCheck.valid) {
                    warnings.push(`Row ${i + 1}: ${phoneCheck.error} (${phone})`);
                    continue;
                }
                items.push({
                    phone: phoneCheck.normalized,
                    amount,
                    reference: refIdx !== -1 ? (cols[refIdx] || '') : '',
                    description: descIdx !== -1 ? (cols[descIdx] || '') : '',
                    _status: 'ready'
                });
            }
            this.batchItems = items;
            if (warnings.length > 0) {
                this.batchMsg = warnings.join(' | ');
                this.batchMsgType = 'error';
            } else if (items.length > 0) {
                this.batchMsg = items.length + ' recipient(s) parsed successfully.';
                this.batchMsgType = 'success';
            }
        },

        addManualRow() {
            if (!this.manualRow.phone || !this.manualRow.amount) {
                this.batchMsg = 'Phone and amount are required.';
                this.batchMsgType = 'error';
                return;
            }
            const phoneCheck = this.validatePhone(this.manualRow.phone);
            if (!phoneCheck.valid) {
                this.batchMsg = phoneCheck.error;
                this.batchMsgType = 'error';
                return;
            }
            const amount = parseFloat(this.manualRow.amount);
            if (!amount || amount < 100) {
                this.batchMsg = 'Amount must be at least 100.';
                this.batchMsgType = 'error';
                return;
            }
            this.batchItems.push({
                ...this.manualRow,
                phone: phoneCheck.normalized,
                amount: amount,
                _status: 'ready'
            });
            this.manualRow = { phone: '', amount: '', reference: '', description: '' };
            this.manualAmountDisplay = '';
            this.batchMsg = '';
            this.batchCharges = null;
        },

        getPhoneConfig() {
            return this.currencyPhoneMap[this.walletCurrency] || { code: '255', digits: 9, example: '255712345678', local: '0712345678' };
        },

        normalizePhone(phone) {
            if (!phone) return '';
            const cfg = this.getPhoneConfig();
            let cleaned = phone.replace(/[\s\-\.+]/g, '');
            if (cleaned.startsWith(cfg.code) && cleaned.length >= (cfg.code.length + cfg.digits)) {
                cleaned = '0' + cleaned.substring(cfg.code.length);
            } else if (!cleaned.startsWith('0') && cleaned.length === cfg.digits) {
                cleaned = '0' + cleaned;
            }
            return cleaned;
        },

        normalizePhoneToCountry(phone) {
            if (!phone) return '';
            const cfg = this.getPhoneConfig();
            let cleaned = phone.replace(/[\s\-\.+]/g, '');
            if (cleaned.startsWith('0') && cleaned.length === (cfg.digits + 1)) {
                cleaned = cfg.code + cleaned.substring(1);
            } else if (cleaned.length === cfg.digits && !cleaned.startsWith('0') && !cleaned.startsWith(cfg.code)) {
                cleaned = cfg.code + cleaned;
            }
            return cleaned;
        },

        autoFormatPhone(field) {
            const cfg = this.getPhoneConfig();
            const re = new RegExp('^' + cfg.code + '\\d{' + cfg.digits + '}$');
            if (field === 'payout') {
                const formatted = this.normalizePhoneToCountry(this.payoutForm.phone);
                if (formatted && re.test(formatted)) {
                    this.payoutForm.phone = formatted;
                }
            } else if (field === 'manual') {
                const formatted = this.normalizePhoneToCountry(this.manualRow.phone);
                if (formatted && re.test(formatted)) {
                    this.manualRow.phone = formatted;
                }
            }
        },

        validatePhone(phone) {
            const cfg = this.getPhoneConfig();
            const cleaned = this.normalizePhone(phone);
            const localRe = new RegExp('^0\\d{' + cfg.digits + '}$');
            if (!localRe.test(cleaned)) {
                return { valid: false, error: 'Phone must start with ' + cfg.code + ' (e.g. ' + cfg.example + ')' };
            }
            const operator = this.detectOperatorFromPhone(phone);
            if (!operator) {
                return { valid: false, error: 'Unrecognized operator. Supported prefixes: 074/075/076 (M-Pesa), 065/067/071 (Tigo Pesa), 078 (Airtel), 068/069 (Halotel)' };
            }
            return { valid: true, operator: operator, normalized: this.normalizePhoneToCountry(phone) };
        },

        detectOperatorFromPhone(phone) {
            if (!phone) return null;
            const cleaned = this.normalizePhone(phone);
            if (cleaned.startsWith('0') && cleaned.length >= 10) {
                const prefix = cleaned.substring(0, 3);
                for (const op of this.payoutOperators) {
                    if (op.prefixes && op.prefixes.includes(prefix)) {
                        return op.code;
                    }
                }
            }
            return null;
        },

        async calculateBatchCharges() {
            if (this.batchItems.length === 0) return;
            this.batchChargesLoading = true;
            this.batchCharges = null;

            let totalAmount = 0;
            let totalFees = 0;
            let errors = 0;

            // Group items by operator+amount to minimize API calls
            const chargeCache = {};

            for (let i = 0; i < this.batchItems.length; i++) {
                const item = this.batchItems[i];
                const operatorCode = this.detectOperatorFromPhone(item.phone);
                const amount = Number(item.amount);
                totalAmount += amount;

                if (!operatorCode || amount < 100) {
                    item._charge = 0;
                    errors++;
                    continue;
                }

                const cacheKey = operatorCode + '_' + amount;
                if (chargeCache[cacheKey] !== undefined) {
                    item._charge = chargeCache[cacheKey];
                    totalFees += chargeCache[cacheKey];
                    continue;
                }

                try {
                    const res = await fetch('{{ config("services.transaction_service.public_url") }}/api/charges/calculate', {
                        method: 'POST', headers: this.getHeaders(),
                        body: JSON.stringify({
                            amount: amount,
                            operator: operatorCode,
                            transaction_type: 'disbursement'
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        const fee = data.platform_charge || 0;
                        chargeCache[cacheKey] = fee;
                        item._charge = fee;
                        totalFees += fee;
                    } else {
                        chargeCache[cacheKey] = 0;
                        item._charge = 0;
                    }
                } catch (e) {
                    chargeCache[cacheKey] = 0;
                    item._charge = 0;
                }
            }

            this.batchCharges = {
                totalAmount: totalAmount,
                totalFees: totalFees,
                totalDebit: totalAmount + totalFees,
                itemCount: this.batchItems.length,
                errors: errors
            };
            this.batchChargesLoading = false;
        },

        async sendBatchPayout() {
            if (this.batchItems.length === 0) return;
            this.batchLoading = true;
            this.batchMsg = '';
            this.batchResults = [];
            try {
                const payload = {
                    batch_name: this.batchName || null,
                    items: this.batchItems.map(i => ({
                        phone: i.phone,
                        amount: i.amount,
                        reference: i.reference || null,
                        description: i.description || null,
                    }))
                };
                const res = await fetch('/api/disbursement/batch', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.requires_approval) {
                    this.batchMsg = 'Batch submitted for approval. An approver will review before funds are sent.';
                    this.batchMsgType = 'success';
                    this.batchItems = [];
                    this.batchName = '';
                    this.batchCsvText = '';
                    this.batchCharges = null;
                    this.fetchPendingPayouts();
                } else {
                    if (data.results) {
                        this.batchResults = data.results;
                        this.batchResultSummary = { sent: data.sent || 0, failed: data.failed || 0, total: data.total || 0 };
                        data.results.forEach(r => {
                            if (this.batchItems[r.index]) {
                                this.batchItems[r.index]._status = r.success ? 'success' : 'failed';
                            }
                        });
                    }
                    this.batchMsg = data.message || (res.ok ? 'Batch sent.' : 'Batch failed.');
                    this.batchMsgType = data.failed === 0 ? 'success' : 'error';
                    // Clear form to prevent duplicate sends
                    this.batchItems = [];
                    this.batchName = '';
                    this.batchCsvText = '';
                    this.batchCharges = null;
                    this.disbPage = 1;
                    this.fetchRecentDisbursements();
                }
            } catch (e) { this.batchMsg = 'Network error.'; this.batchMsgType = 'error'; }
            this.batchLoading = false;
        },

        async fetchRecentDisbursements() {
            this.recentDisbLoading = true;
            try {
                const res = await fetch(`/api/payment-requests?type=disbursement&page=${this.disbPage}`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.recentDisbursements = data.data || [];
                    this.disbPagination = { current_page: data.current_page, last_page: data.last_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
                }
            } catch (e) { console.error(e); }
            this.recentDisbLoading = false;
        },

        // ---- Payout Approval (Maker-Checker) ----
        async fetchPendingPayouts() {
            this.pendingPayoutsLoading = true;
            try {
                const res = await fetch('/api/payouts/pending', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.pendingPayouts = data.data || [];
                    this.pendingPayoutsCount = data.count ?? this.pendingPayouts.length;
                    this.pendingPayoutsTotal = data.total_amount ?? this.pendingPayouts.reduce((s, p) => s + Number(p.amount || 0), 0);
                }
            } catch (e) { console.error('fetchPendingPayouts', e); }
            this.pendingPayoutsLoading = false;
        },

        async approveSinglePayout(id) {
            if (!confirm('Approve this payout? Funds will be sent to the recipient.')) return;
            this.approvalLoading = { ...this.approvalLoading, [id]: true };
            try {
                const res = await fetch(`/api/payouts/${id}/approve`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                const data = await res.json();
                if (res.ok) {
                    this.fetchPendingPayouts();
                    this.fetchRecentDisbursements();
                    this.fetchWallet();
                } else {
                    alert(data.message || 'Failed to approve payout.');
                }
            } catch (e) { alert('Network error approving payout.'); }
            this.approvalLoading = { ...this.approvalLoading, [id]: false };
        },

        async rejectSinglePayout(id) {
            const notes = prompt('Rejection reason (optional):');
            if (notes === null) return; // cancelled
            this.approvalLoading = { ...this.approvalLoading, [id]: true };
            try {
                const res = await fetch(`/api/payouts/${id}/reject`, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ notes: notes || '' })
                });
                const data = await res.json();
                if (res.ok) {
                    this.fetchPendingPayouts();
                } else {
                    alert(data.message || 'Failed to reject payout.');
                }
            } catch (e) { alert('Network error rejecting payout.'); }
            this.approvalLoading = { ...this.approvalLoading, [id]: false };
        },

        toggleSelectAllPayouts() {
            if (this.selectAllPayouts) {
                this.selectedPayouts = [];
                this.selectAllPayouts = false;
            } else {
                this.selectedPayouts = this.pendingPayouts.map(p => p.id);
                this.selectAllPayouts = true;
            }
        },

        async bulkApprovePayouts() {
            if (!this.selectedPayouts.length) return;
            if (!confirm(`Approve ${this.selectedPayouts.length} payout(s)? Funds will be sent.`)) return;
            this.bulkApprovalLoading = true;
            try {
                const res = await fetch('/api/payouts/bulk-approve', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ ids: this.selectedPayouts })
                });
                const data = await res.json();
                if (res.ok) {
                    const msg = `Approved: ${data.approved || 0}, Failed: ${data.failed || 0}`;
                    alert(msg);
                    this.selectedPayouts = [];
                    this.selectAllPayouts = false;
                    this.fetchPendingPayouts();
                    this.fetchRecentDisbursements();
                    this.fetchWallet();
                } else {
                    alert(data.message || 'Bulk approve failed.');
                }
            } catch (e) { alert('Network error during bulk approve.'); }
            this.bulkApprovalLoading = false;
        },

        async bulkRejectPayouts() {
            if (!this.selectedPayouts.length) return;
            const notes = prompt('Rejection reason for all selected (optional):');
            if (notes === null) return;
            this.bulkApprovalLoading = true;
            try {
                const res = await fetch('/api/payouts/bulk-reject', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ ids: this.selectedPayouts, notes: notes || '' })
                });
                const data = await res.json();
                if (res.ok) {
                    alert(`Rejected: ${data.rejected || 0} payout(s).`);
                    this.selectedPayouts = [];
                    this.selectAllPayouts = false;
                    this.fetchPendingPayouts();
                } else {
                    alert(data.message || 'Bulk reject failed.');
                }
            } catch (e) { alert('Network error during bulk reject.'); }
            this.bulkApprovalLoading = false;
        },

        // ---- Helpers ----
        formatAmount(a) { const n = Number(a); return isNaN(n) ? '0.00' : n.toLocaleString('en-US', { minimumFractionDigits: 2 }); },
        formatAmountInput(event, target, key) {
            let raw = event.target.value.replace(/[^0-9]/g, '');
            let num = parseInt(raw, 10) || 0;
            let formatted = num > 0 ? num.toLocaleString('en-US') : '';
            if (target === 'payout') {
                this.payoutForm.amount = num > 0 ? num : '';
                this.payoutAmountDisplay = formatted;
                this.calculatePayoutCharges();
            } else if (target === 'transfer') {
                this.transferAmounts[key] = num > 0 ? num : '';
                this.transferAmountDisplays[key] = formatted;
            } else if (target === 'settlement') {
                this.stlForm.amount = num > 0 ? num : '';
                this.stlAmountDisplay = formatted;
            } else if (target === 'manual') {
                this.manualRow.amount = num > 0 ? num : '';
                this.manualAmountDisplay = formatted;
            } else if (target === 'fx') {
                this.fxForm.amount = num > 0 ? num : '';
                this.fxAmountDisplay = formatted;
                this.fxPreview = null;
                this.fxResult = null;
            } else if (target === 'invoice') {
                this.invoiceForm.amount = num > 0 ? num : '';
                this.invoiceAmountDisplay = formatted;
            }
            event.target.value = formatted;
        },
        formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }); },

        async logout() {
            try { await fetch('{{ config("services.auth_service.public_url") }}/api/logout', { method: 'POST', headers: this.getHeaders() }); } catch (e) {}
            localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); window.location.href = '/login';
        },

        async changePassword() {
            this.pwError = ''; this.pwSuccess = ''; this.pwLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/change-password', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ current_password: this.currentPassword, password: this.newPassword, password_confirmation: this.confirmPassword })
                });
                const data = await res.json();
                if (!res.ok) { this.pwError = data.errors ? Object.values(data.errors).flat().join(' ') : data.message; return; }
                this.pwSuccess = data.message || 'Password updated successfully.';
                this.currentPassword = ''; this.newPassword = ''; this.confirmPassword = '';
                if (data.token) { localStorage.setItem('auth_token', data.token); }
                setTimeout(() => { this.pwSuccess = ''; }, 5000);
            } catch (e) { this.pwError = 'Unable to connect to auth service.'; }
            finally { this.pwLoading = false; }
        },

        // ---- Download API Docs as PDF ----
        downloadApiDocsPdf() {
            const el = document.querySelector('[x-show="activeTab === \'api-docs\'"]');
            if (!el) return;
            const win = window.open('', '_blank');
            win.document.write('<html><head><title>PayIn API Documentation</title>');
            win.document.write('<style>body{font-family:Arial,sans-serif;margin:40px;color:#333}h3{margin-top:24px}table{width:100%;border-collapse:collapse;margin:12px 0}th,td{text-align:left;padding:6px 10px;border-bottom:1px solid #ddd}th{font-weight:600}code{background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:13px}pre{background:#1e293b;color:#4ade80;padding:16px;border-radius:8px;overflow-x:auto;font-size:13px;white-space:pre-wrap}.bg-gblue-50,.bg-ggreen-50,.bg-gyellow-50,.bg-gblue-500,.bg-ggreen-500{padding:8px 12px;border-radius:6px;display:inline-block;margin:4px}@media print{body{margin:20px}pre{break-inside:avoid}}</style>');
            win.document.write('</head><body>');
            win.document.write('<h1 style="text-align:center;color:#1e40af">PayIn API Documentation</h1>');
            win.document.write('<hr style="margin-bottom:24px">');
            const clone = el.cloneNode(true);
            clone.querySelectorAll('button').forEach(b => b.remove());
            win.document.write(clone.innerHTML);
            win.document.write('</body></html>');
            win.document.close();
            setTimeout(() => { win.print(); }, 500);
        },

        // ---- Two-Factor Authentication ----
        async fetchTwoFactorStatus() {
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/two-factor/status', { headers: this.getHeaders() });
                if (res.ok) { const data = await res.json(); this.tfaEnabled = data.two_factor_enabled; }
            } catch (e) { console.error(e); }
        },
        async toggleTwoFactor() {
            this.tfaMsg = ''; this.tfaLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/two-factor/toggle', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ enabled: !this.tfaEnabled, password: this.tfaPassword })
                });
                const data = await res.json();
                if (!res.ok) { this.tfaMsg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message; this.tfaMsgType = 'error'; return; }
                this.tfaEnabled = data.two_factor_enabled;
                this.tfaMsg = data.message; this.tfaMsgType = 'success';
                this.tfaShowConfirm = false; this.tfaPassword = '';
            } catch (e) { this.tfaMsg = 'Unable to connect to auth service.'; this.tfaMsgType = 'error'; }
            finally { this.tfaLoading = false; }
        },

        // ---- Account Users ----
        async fetchAccountUsers() {
            this.accUsersLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/users', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) { const data = await res.json(); this.accountUsers = data.users || []; }
            } catch (e) { console.error(e); }
            this.accUsersLoading = false;
        },
        async addUser() {
            this.addUserLoading = true; this.addUserMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/users', {
                    method: 'POST', headers: this.getHeaders(), body: JSON.stringify(this.newUserForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.addUserMsg = errors || 'Failed.'; this.addUserMsgType = 'error'; return;
                }
                this.addUserMsg = data.message; this.addUserMsgType = 'success';
                this.newUserForm = { firstname: '', lastname: '', email: '', password: '', role: 'viewer', permissions: [] };
                this.fetchAccountUsers();
            } catch (e) { this.addUserMsg = 'Service unavailable.'; this.addUserMsgType = 'error'; }
            finally { this.addUserLoading = false; }
        },
        async changeUserRole(id, role) {
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/users/${id}/role`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ role })
                });
                if (res.ok) this.fetchAccountUsers();
                else { const data = await res.json(); alert(data.message || 'Failed to change role.'); }
            } catch (e) { alert('Service unavailable.'); }
        },
        async removeUser(id, name) {
            if (!confirm(`Remove user "${name}" from this account?`)) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/users/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) this.fetchAccountUsers();
                else { const data = await res.json(); alert(data.message || 'Failed to remove user.'); }
            } catch (e) { alert('Service unavailable.'); }
        },
        async saveUserPermissions(userId) {
            try {
                const res = await fetch(`{{ config("services.auth_service.public_url") }}/api/account/users/${userId}/permissions`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ permissions: this.editingPerms })
                });
                if (res.ok) {
                    this.editingPermUserId = null;
                    this.fetchAccountUsers();
                } else {
                    const data = await res.json();
                    alert(data.message || 'Failed to update permissions.');
                }
            } catch (e) { alert('Service unavailable.'); }
        },

        // ---- Currency Exchange ----
        getSourceWalletBalance() {
            if (!this.fxForm.from_operator || !this.fxForm.from_currency) return '—';
            const wallets = this.fxForm.from_wallet_type === 'collection' ? this.collectionWallets : this.disbursementWallets;
            const w = wallets.find(w => w.operator === this.fxForm.from_operator);
            return w ? this.formatAmount(w.balance) + ' ' + this.walletCurrency : '0.00 ' + this.walletCurrency;
        },

        async fetchExchangeRates() {
            this.fxLoading = true;
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/exchange/rates', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.fxRates = data.rates || [];
                    this.fxAllowedCurrencies = data.allowed_currencies || [];
                    this.fxBaseCurrency = data.base_currency || this.walletCurrency;
                    this.fxCurrencyLabels = data.currencies || {};
                    // Pre-select base currency as from
                    if (!this.fxForm.from_currency && this.fxBaseCurrency) {
                        this.fxForm.from_currency = this.fxBaseCurrency;
                    }
                }
            } catch (e) { console.error('Failed to fetch exchange rates', e); }
            this.fxLoading = false;
        },

        async previewExchange() {
            this.fxPreviewLoading = true;
            this.fxMsg = '';
            this.fxPreview = null;
            this.fxResult = null;
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/exchange/preview', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({
                        from_currency: this.fxForm.from_currency,
                        to_currency: this.fxForm.to_currency,
                        amount: this.fxForm.amount,
                        from_operator: this.fxForm.from_operator,
                        from_wallet_type: this.fxForm.from_wallet_type
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    this.fxPreview = data;
                } else {
                    this.fxMsg = data.message || 'Preview failed.';
                    this.fxMsgType = 'error';
                }
            } catch (e) {
                this.fxMsg = 'Service unavailable.';
                this.fxMsgType = 'error';
            }
            this.fxPreviewLoading = false;
        },

        async executeExchange() {
            if (!this.fxPreview) return;
            if (!confirm(`Exchange ${this.formatAmount(this.fxForm.amount)} ${this.fxForm.from_currency} → ${this.fxForm.to_currency}?\n\nYou will receive approximately ${this.formatAmount(this.fxPreview.to_amount)} ${this.fxForm.to_currency} after ${this.fxPreview.fee_percent}% fee.`)) return;

            this.fxExecuting = true;
            this.fxMsg = '';
            this.fxResult = null;
            try {
                const res = await fetch('{{ config("services.wallet_service.public_url") }}/api/exchange/execute', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({
                        from_currency: this.fxForm.from_currency,
                        to_currency: this.fxForm.to_currency,
                        amount: this.fxForm.amount,
                        from_operator: this.fxForm.from_operator,
                        from_wallet_type: this.fxForm.from_wallet_type,
                        to_operator: this.fxForm.to_operator,
                        to_wallet_type: this.fxForm.to_wallet_type
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    this.fxResult = data;
                    this.fxPreview = null;
                    this.fxMsg = data.message || 'Exchange completed successfully!';
                    this.fxMsgType = 'success';
                    // Reset form
                    this.fxForm.amount = '';
                    this.fxAmountDisplay = '';
                    // Refresh wallet balances and history
                    this.fetchWallet();
                    this.fetchExchangeHistory();
                } else {
                    this.fxMsg = data.message || 'Exchange failed.';
                    this.fxMsgType = 'error';
                }
            } catch (e) {
                this.fxMsg = 'Service unavailable.';
                this.fxMsgType = 'error';
            }
            this.fxExecuting = false;
        },

        // ---- Invoices (Manual C2B) ----
        async fetchInvoices() {
            this.invoicesLoading = true;
            try {
                let url = `/api/payment-requests?type=manual_c2b&page=${this.invoicePage}`;
                if (this.invoiceSearch) url += `&search=${encodeURIComponent(this.invoiceSearch)}`;
                if (this.invoiceFilterStatus) url += `&status=${this.invoiceFilterStatus}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.invoices = data.data || [];
                    this.invoicePagination = { current_page: data.current_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
                }
            } catch (e) { console.error('Failed to fetch invoices', e); }
            this.invoicesLoading = false;
        },

        async createInvoice() {
            this.invoiceLoading = true;
            this.invoiceMsg = '';
            try {
                const ref = String(Date.now()).slice(-4) + String(Math.floor(1000 + Math.random() * 9000));
                const body = {
                    amount: this.invoiceForm.amount,
                    currency: this.walletCurrency,
                    reference: ref,
                };
                if (this.invoiceForm.description) body.description = this.invoiceForm.description;
                if (this.invoiceForm.expires_in) body.expires_in = parseInt(this.invoiceForm.expires_in);

                const res = await fetch('/api/invoice', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (!res.ok) {
                    this.invoiceMsg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Failed to create invoice.');
                    this.invoiceMsgType = 'error';
                    return;
                }
                this.invoiceMsg = `Invoice created! Customer reference: ${data.external_ref}`;
                this.invoiceMsgType = 'success';
                this.invoiceForm = { amount: '', description: '', expires_in: '' };
                this.invoiceAmountDisplay = '';
                this.fetchInvoices();
            } catch (e) {
                this.invoiceMsg = 'Service unavailable.';
                this.invoiceMsgType = 'error';
            }
            this.invoiceLoading = false;
        },

        viewInvoice(inv) {
            this.invoiceDetail = inv;
            this.invoiceDetailOpen = true;
        },

        async cancelInvoice(requestRef) {
            if (!confirm('Cancel this invoice?')) return;
            try {
                const res = await fetch(`/api/invoice/${requestRef}/cancel`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                const data = await res.json();
                if (!res.ok) {
                    alert(data.message || 'Failed to cancel invoice.');
                    return;
                }
                this.invoiceDetailOpen = false;
                this.fetchInvoices();
            } catch (e) { alert('Service unavailable.'); }
        },

        async fetchExchangeHistory() {
            this.fxHistoryLoading = true;
            try {
                const res = await fetch(`{{ config("services.wallet_service.public_url") }}/api/exchange/history?page=${this.fxHistoryPage}`, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.fxHistory = data.data || [];
                    this.fxHistoryPagination = { current_page: data.current_page, from: data.from, to: data.to, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
                }
            } catch (e) { console.error('Failed to fetch exchange history', e); }
            this.fxHistoryLoading = false;
        }
    }
}
</script>
@endsection
