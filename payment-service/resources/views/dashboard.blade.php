@extends('layouts.app')

@section('title', 'Dashboard - Payin')

@section('content')
<div x-data="dashboard()" x-init="init()" x-cloak>
    <!-- Top Navbar -->
    <nav class="bg-gray-900 shadow-lg border-b border-gray-800 fixed top-0 left-0 right-0 z-30">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden mr-3 text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="ml-2 text-lg font-bold text-white">Payin</span>
                    <span x-show="user?.account" class="ml-3 text-xs bg-blue-500/20 text-blue-300 px-2.5 py-1 rounded-full font-medium hidden sm:inline" x-text="user?.account?.business_name"></span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-400 hidden sm:inline">Welcome, <span class="font-medium text-white" x-text="user?.name || 'User'"></span></span>
                    <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded-full capitalize" x-text="user?.role || ''"></span>
                    <button @click="showPasswordModal = true" class="text-xs text-gray-400 hover:text-white transition" title="Change Password">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
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

            <!-- Business Section -->
            <div class="px-4 mb-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Business</h3>
            </div>
            <nav class="flex-1 px-2 space-y-0.5">
                <button @click="activeTab = 'dashboard'; sidebarOpen = false"
                    :class="activeTab === 'dashboard' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'dashboard' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </button>
                <button x-show="hasPerm('view_transactions')" @click="activeTab = 'transactions'; sidebarOpen = false"
                    :class="activeTab === 'transactions' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'transactions' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Transactions
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="activeTab = 'wallet'; fetchWallet(); sidebarOpen = false"
                    :class="activeTab === 'wallet' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'wallet' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Wallet
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="activeTab = 'send-money'; fetchPayoutOperators(); sidebarOpen = false"
                    :class="activeTab === 'send-money' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'send-money' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Send Money
                </button>
                <button x-show="hasPerm('view_settlements') || hasPerm('create_settlement')" @click="activeTab = 'settlements'; fetchSettlements(); sidebarOpen = false"
                    :class="activeTab === 'settlements' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'settlements' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Settlements
                </button>
                <button x-show="hasPerm('view_account_info')" @click="activeTab = 'account'; fetchKyc(); sidebarOpen = false"
                    :class="activeTab === 'account' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'account' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Account Info
                </button>
                <button x-show="hasPerm('view_users') || hasPerm('add_user')" @click="activeTab = 'users'; fetchAccountUsers(); sidebarOpen = false"
                    :class="activeTab === 'users' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'users' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Users
                </button>
            </nav>

            <!-- Divider -->
            <div class="px-4 my-3"><div class="border-t border-gray-200"></div></div>

            <!-- Developer Tools Section -->
            <div class="px-4 mb-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Developer Tools</h3>
            </div>
            <nav class="px-2 space-y-0.5">
                <button @click="activeTab = 'api-docs'; sidebarOpen = false"
                    :class="activeTab === 'api-docs' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'api-docs' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    API Documentation
                </button>
                <button x-show="hasPerm('view_settings')" @click="activeTab = 'settings'; fetchCallback(); sidebarOpen = false"
                    :class="activeTab === 'settings' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    class="w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-l-lg transition-colors group">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" :class="activeTab === 'settings' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.11 2.37-2.37.996.608 2.296.07 2.573-1.066z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Webhook & API Keys
                </button>
            </nav>

            <!-- Bottom account info -->
            <div class="mt-auto px-4 pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-xs font-bold" x-text="(user?.name || 'U')[0].toUpperCase()"></div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="user?.name || 'User'"></p>
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
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">Welcome back, <span x-text="user?.name || 'User'"></span>. Here's your business overview.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-gblue-50 rounded-lg">
                            <svg class="w-6 h-6 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-ggreen-50 rounded-lg">
                            <svg class="w-6 h-6 text-ggreen-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Completed</p>
                            <p class="text-2xl font-bold text-ggreen-500" x-text="stats.completed"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-gyellow-50 rounded-lg">
                            <svg class="w-6 h-6 text-gyellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Pending</p>
                            <p class="text-2xl font-bold text-gyellow-600" x-text="stats.pending"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-gred-50 rounded-lg">
                            <svg class="w-6 h-6 text-gred-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Failed</p>
                            <p class="text-2xl font-bold text-gred-500" x-text="stats.failed"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charges Summary -->
            <div x-show="myCharges.total_charges > 0" x-cloak class="mb-6 bg-gradient-to-r from-gblue-50 to-ggreen-50 rounded-xl border border-gblue-200 p-5">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-gblue-100 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Total Charges Deducted</p>
                            <p class="text-xs text-gray-500">Across all your completed transactions</p>
                        </div>
                    </div>
                    <div class="flex space-x-6 text-center">
                        <div>
                            <p class="text-xs text-gray-500">Charges</p>
                            <p class="text-lg font-bold text-gblue-500" x-text="formatAmount(myCharges.total_charges || 0) + ' TZS'"></p>
                        </div>
                    </div>
                </div>
                <div x-show="myCharges.by_type && myCharges.by_type.length > 0" class="mt-3 pt-3 border-t border-gblue-200 flex flex-wrap gap-4">
                    <template x-for="bt in (myCharges.by_type || [])" :key="bt.type">
                        <div class="bg-white rounded-lg px-3 py-2 text-xs border">
                            <span class="font-medium text-gray-700 capitalize" x-text="bt.type"></span>:
                            <span class="text-gblue-500 font-semibold" x-text="formatAmount(Number(bt.platform_charges) + Number(bt.operator_charges)) + ' TZS'"></span>
                            <span class="text-gray-400" x-text="'(' + bt.transaction_count + ' txns)'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <button x-show="hasPerm('view_transactions')" @click="activeTab = 'transactions'" class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition text-left group">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-blue-50 rounded-lg group-hover:bg-blue-100 transition">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-800">View Transactions</p>
                            <p class="text-xs text-gray-500">Search & filter history</p>
                        </div>
                    </div>
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="activeTab = 'send-money'; fetchPayoutOperators()" class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition text-left group">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-green-50 rounded-lg group-hover:bg-green-100 transition">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-800">Send Money</p>
                            <p class="text-xs text-gray-500">Disburse to mobile wallets</p>
                        </div>
                    </div>
                </button>
                <button x-show="hasPerm('wallet_transfer') || hasPerm('view_transactions')" @click="activeTab = 'wallet'; fetchWallet()" class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition text-left group">
                    <div class="flex items-center">
                        <div class="p-2.5 bg-purple-50 rounded-lg group-hover:bg-purple-100 transition">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-gray-800">Wallet Balances</p>
                            <p class="text-xs text-gray-500">View & manage wallets</p>
                        </div>
                    </div>
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
                    <button @click="searchQuery = ''; filterStatus = ''; filterType = ''; filterOperator = ''; currentPage = 1; fetchTransactions()" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">Clear All</button>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charge</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="txn in transactions" :key="txn.id">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700" x-text="txn.transaction_ref"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600" x-text="txn.operator_receipt || '-'"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-ggreen-50 text-ggreen-700': txn.type==='collection','bg-gred-50 text-gred-700': txn.type==='disbursement','bg-gblue-50 text-gblue-700': txn.type==='topup','bg-purple-100 text-purple-800': txn.type==='settlement'}"
                                                x-text="txn.type==='collection' ? 'Collection (Payin)' : txn.type==='disbursement' ? 'Disbursement (Payout)' : txn.type==='topup' ? 'Topup (Transfer)' : txn.type==='settlement' ? 'Settlement (Withdrawal)' : txn.type"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="txn.type==='collection' ? 'text-green-600' : 'text-gray-800'">
                                            <span x-text="(txn.type==='collection' ? '+' : '-') + ' ' + formatAmount(txn.amount) + ' ' + txn.currency"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600" x-text="(Number(txn.platform_charge || 0) + Number(txn.operator_charge || 0)) > 0 ? formatAmount(Number(txn.platform_charge || 0) + Number(txn.operator_charge || 0)) : '-'"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize" x-text="(txn.payment_method||'-').replace('_',' ')"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-ggreen-50 text-ggreen-700': txn.status==='completed','bg-gyellow-50 text-gyellow-700': txn.status==='pending','bg-gred-50 text-gred-700': txn.status==='failed','bg-gray-100 text-gray-800': txn.status==='cancelled','bg-purple-100 text-purple-800': txn.status==='reversed'}"
                                                x-text="txn.status"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(txn.created_at)"></td>
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
                    <p class="text-3xl font-bold mt-1" x-text="formatAmount(overallBalance) + ' TZS'"></p>
                    <p class="text-xs mt-1 opacity-60">Collection + Disbursement</p>
                </div>
                <div class="bg-gradient-to-br from-ggreen-500 to-ggreen-600 rounded-xl shadow-lg p-5 text-white">
                    <p class="text-xs font-medium opacity-80 uppercase tracking-wide">Collection (Payin)</p>
                    <p class="text-3xl font-bold mt-1" x-text="formatAmount(collectionTotal) + ' TZS'"></p>
                    <p class="text-xs mt-1 opacity-60">Money received from customers</p>
                </div>
                <div class="bg-gradient-to-br from-gred-400 to-gred-500 rounded-xl shadow-lg p-5 text-white">
                    <p class="text-xs font-medium opacity-80 uppercase tracking-wide">Disbursement (Payout)</p>
                    <p class="text-3xl font-bold mt-1" x-text="formatAmount(disbursementTotal) + ' TZS'"></p>
                    <p class="text-xs mt-1 opacity-60">Available for payouts</p>
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
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Collection</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-800" x-text="formatAmount(w.balance) + ' TZS'"></p>
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
                                <div class="flex items-center space-x-2 mb-2">
                                    <div class="w-2.5 h-2.5 rounded-full" :class="operatorColor(w.operator)"></div>
                                    <span class="text-sm font-semibold text-gray-700" x-text="w.operator"></span>
                                    <span class="text-xs text-gray-400" x-text="'(' + formatAmount(w.balance) + ' TZS)'"></span>
                                </div>
                                <div x-show="walletMsg['trf_'+w.operator]" x-cloak class="mb-2 p-2 rounded text-xs"
                                    :class="walletMsgType['trf_'+w.operator] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                                    x-text="walletMsg['trf_'+w.operator]"></div>
                                <input type="number" x-model="transferAmounts[w.operator]" min="1" placeholder="Amount"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none mb-2">
                                <button @click="transferToDisbursement(w.operator)" :disabled="walletTransferLoading[w.operator]"
                                    class="w-full py-1.5 bg-gblue-500 text-white rounded-lg text-xs font-medium hover:bg-gblue-600 transition disabled:opacity-50">
                                    <span x-show="!walletTransferLoading[w.operator]">Request →</span>
                                    <span x-show="walletTransferLoading[w.operator]">...</span>
                                </button>
                            </div>
                        </template>
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
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gblue-50 text-gblue-700">Disbursement</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-800" x-text="formatAmount(w.balance) + ' TZS'"></p>
                            <p class="text-xs text-gray-500 mt-2">Funds available for payout / settlement</p>
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
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800" x-text="formatAmount(t.amount) + ' TZS'"></td>
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
                        <select x-model="walletTxnOperatorFilter" @change="fetchWalletTransactions()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">All Operators</option>
                            <template x-for="op in operators" :key="op">
                                <option :value="op" x-text="op"></option>
                            </template>
                        </select>
                        <select x-model="walletTxnTypeFilter" @change="fetchWalletTransactions()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
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
                                            <span x-text="(wt.type==='credit' ? '+' : '-') + ' ' + formatAmount(wt.amount) + ' TZS'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700" x-text="formatAmount(wt.balance_after) + ' TZS'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="wt.description || '-'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(wt.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
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
                <form @submit.prevent="createSettlement()" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
                        <select x-model="stlForm.operator" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <option value="">Select Operator</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Tigo Pesa">Tigo Pesa</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Halopesa">Halopesa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (min 1,000 TZS)</label>
                        <input type="number" x-model="stlForm.amount" min="1000" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <input type="text" x-model="stlForm.bank_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="e.g. CRDB, NMB">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                        <input type="text" x-model="stlForm.account_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                        <input type="text" x-model="stlForm.account_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" x-model="stlForm.description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="Settlement description">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="stlLoading" class="bg-gblue-500 text-white px-6 py-2 rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                            <span x-show="!stlLoading">Submit Settlement Request</span>
                            <span x-show="stlLoading">Submitting...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Settlements Table -->
            <div class="bg-white rounded-xl shadow-md border overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Settlement History</h3>
                    <select x-model="stlFilterStatus" @change="fetchSettlements()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
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
                                    </tr>
                                </template>
                            </tbody>
                        </table>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" x-model="newUserForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
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
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="u.name"></td>
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
                                                        <button @click="removeUser(u.id, u.name)" class="text-xs bg-gred-50 text-gred-700 px-2 py-1 rounded hover:bg-gred-100">Remove</button>
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
                                                <span class="text-sm font-semibold text-gray-700">Edit Permissions for <span x-text="u.name"></span></span>
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
            <div class="max-w-2xl">
                <div class="bg-white rounded-xl shadow-md border p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gblue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Account Information</h3>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                            :class="{'bg-gyellow-50 text-gyellow-700': kycData.status==='pending', 'bg-ggreen-50 text-ggreen-700': kycData.status==='active', 'bg-gred-50 text-gred-700': kycData.status==='suspended', 'bg-gray-100 text-gray-800': !kycData.status}"
                            x-text="kycData.status || 'Not submitted'"></span>
                    </div>

                    <!-- Stepper Progress -->
                    <div x-show="kycData.status !== 'active'" class="flex items-center mb-8">
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

                    <!-- ===== APPROVED: Read-only KYC Summary ===== -->
                    <div x-show="kycData.status === 'active' && !kycFormLoading" x-cloak>
                        <div class="mb-6 p-4 bg-ggreen-50 border border-ggreen-200 rounded-xl flex items-center">
                            <svg class="w-6 h-6 text-ggreen-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <p class="text-sm font-semibold text-ggreen-800">KYC Verified</p>
                                <p class="text-xs text-ggreen-600">Approved on <span x-text="formatDate(kycData.kyc_approved_at)"></span>. Your account is fully active.</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Business Information -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
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
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Address</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="[kycData.address, kycData.city, kycData.country].filter(Boolean).join(', ') || '—'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Verification -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
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
                            </div>

                            <!-- Bank Settlement -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    Bank Settlement
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Bank Name</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="kycData.bank_name || '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Account Name</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="kycData.bank_account_name || '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Account Number</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="kycData.bank_account_number || '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">SWIFT / Branch</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="[kycData.bank_swift, kycData.bank_branch].filter(Boolean).join(' / ') || '—'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Crypto Wallet -->
                            <div x-show="kycData.crypto_wallet_address">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Crypto Wallet
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Currency</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="kycData.crypto_currency || '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-gray-500">Network</p>
                                        <p class="text-sm font-medium text-gray-800" x-text="kycData.crypto_network || '—'"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg px-4 py-3 md:col-span-3">
                                        <p class="text-xs text-gray-500">Wallet Address</p>
                                        <p class="text-sm font-medium text-gray-800 font-mono break-all" x-text="kycData.crypto_wallet_address"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 text-gblue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Documents
                                </h4>
                                <div class="flex flex-wrap gap-3">
                                    <div x-show="kycData.id_document_url" class="bg-gray-50 rounded-lg px-4 py-3 flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <a :href="'{{ config('services.auth_service.url') }}' + kycData.id_document_url" target="_blank" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">View ID Document &rarr;</a>
                                    </div>
                                    <div x-show="kycData.business_license_url" class="bg-gray-50 rounded-lg px-4 py-3 flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-ggreen-50 text-ggreen-700 font-medium">Uploaded</span>
                                        <a :href="'{{ config('services.auth_service.url') }}' + kycData.business_license_url" target="_blank" class="text-sm text-gblue-500 hover:text-gblue-700 font-medium">View Business License &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== NOT APPROVED: Show KYC Form ===== -->
                    <div x-show="kycData.status !== 'active'">

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
                                    <h5 class="text-sm font-semibold text-gray-700 mb-3">Bank Settlement Details</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600 mb-1">Bank Name</label>
                                            <input type="text" x-model="kycForm.bank_name" placeholder="e.g. CRDB Bank"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600 mb-1">Account Name</label>
                                            <input type="text" x-model="kycForm.bank_account_name" placeholder="Account holder name"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600 mb-1">Account Number</label>
                                            <input type="text" x-model="kycForm.bank_account_number" placeholder="Bank account number"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600 mb-1">SWIFT Code</label>
                                            <input type="text" x-model="kycForm.bank_swift" placeholder="e.g. COLOTZTZ"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600 mb-1">Bank Branch</label>
                                            <input type="text" x-model="kycForm.bank_branch" placeholder="Branch name"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                        </div>
                                    </div>
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
                                        <a :href="'{{ config('services.auth_service.url') }}' + kycData.id_document_url" target="_blank"
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
                                        <a :href="'{{ config('services.auth_service.url') }}' + kycData.business_license_url" target="_blank"
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
                </div>
            </div>
        </div>

        <!-- ==================== SEND MONEY TAB ==================== -->
        <div x-show="activeTab === 'send-money'" x-cloak>
            <!-- Sub-tabs: Single / Batch -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-6">
                    <button @click="sendMoneySubTab = 'single'" :class="sendMoneySubTab === 'single' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Single Payout</button>
                    <button @click="sendMoneySubTab = 'batch'" :class="sendMoneySubTab === 'batch' ? 'border-gblue-500 text-gblue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition">Batch Payout</button>
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
                            <input type="text" x-model="payoutForm.phone" @input.debounce.400ms="detectOperator(payoutForm.phone)" required placeholder="e.g. 0712345678 or 255712345678" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS, min 100)</label>
                            <input type="number" x-model="payoutForm.amount" min="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
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
                            <span x-show="!payoutLoading">Send Money</span>
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
                            <span x-text="formatAmount(lastPayoutResult.amount) + ' TZS'"></span>
                            <span class="text-gray-500">Status:</span>
                            <span class="capitalize font-medium" :class="lastPayoutResult.status === 'processing' ? 'text-gblue-600' : 'text-gred-600'" x-text="lastPayoutResult.status"></span>
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
                        <textarea x-model="batchCsvText" rows="6" placeholder="phone,amount,reference,description&#10;0712345678,5000,REF001,Salary" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-gblue-500 outline-none"></textarea>
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
                                            <td class="px-4 py-2 text-sm font-semibold" x-text="formatAmount(item.amount) + ' TZS'"></td>
                                            <td class="px-4 py-2 text-sm text-gray-600" x-text="item.reference || '—'"></td>
                                            <td class="px-4 py-2 text-sm text-gray-600" x-text="item.description || '—'"></td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                    :class="item._status === 'success' ? 'bg-green-50 text-green-700' : item._status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    x-text="item._status || 'ready'"></span>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button @click="batchItems.splice(idx, 1)" class="text-red-500 hover:text-red-700 text-sm" title="Remove">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <p class="text-sm text-gray-600">Total: <strong x-text="formatAmount(batchItems.reduce((s, i) => s + Number(i.amount), 0)) + ' TZS'"></strong> to <strong x-text="batchItems.length"></strong> recipient(s)</p>
                            <button @click="sendBatchPayout()" :disabled="batchLoading" class="px-6 py-2.5 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 transition text-sm font-medium disabled:opacity-50">
                                <span x-show="!batchLoading">Send Batch</span>
                                <span x-show="batchLoading">Sending...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Batch Results -->
                    <div x-show="batchResults.length > 0" x-cloak class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Batch Results</h4>
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
                                            <td class="px-4 py-2 text-sm" x-text="formatAmount(r.amount) + ' TZS'"></td>
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
                            <input type="text" x-model="manualRow.phone" placeholder="Phone (e.g. 0712345678)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <input type="number" x-model="manualRow.amount" placeholder="Amount" min="100" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <input type="text" x-model="manualRow.reference" placeholder="Reference (optional)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <button @click="addManualRow()" class="px-4 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium">+ Add</button>
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="d in recentDisbursements" :key="d.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="d.request_ref"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="d.batch_name || '—'"></td>
                                        <td class="px-4 py-3 text-sm" x-text="d.phone"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-red-600" x-text="'-' + formatAmount(d.amount) + ' TZS'"></td>
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
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
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
  "transaction_ref": "TXN-ABCDEF123456",
  "type": "collection",
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

    <!-- Change Password Modal -->
    <div x-show="showPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closePasswordModal()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Change Password</h3>
                <button @click="closePasswordModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div x-show="pwError" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="pwError"></div>
            <div x-show="pwSuccess" x-cloak class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm" x-text="pwSuccess"></div>
            <form @submit.prevent="changePassword()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" x-model="currentPassword" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="Current password">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" x-model="newPassword" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="New password">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" x-model="confirmPassword" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gblue-500 outline-none" placeholder="Confirm password">
                </div>
                <div class="flex space-x-3">
                    <button type="button" @click="closePasswordModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">Cancel</button>
                    <button type="submit" :disabled="pwLoading" class="flex-1 bg-gblue-500 text-white py-2 rounded-lg hover:bg-gblue-600 transition font-medium disabled:opacity-50">
                        <span x-show="!pwLoading">Update Password</span>
                        <span x-show="pwLoading">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
        <!-- ==================== API DOCS TAB ==================== -->
        <div x-show="activeTab === 'api-docs'" x-cloak class="mt-6">

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
        activeTab: 'dashboard',
        sidebarOpen: false,

        // Transactions
        transactions: [], loadingTxns: true, txnError: '',
        searchQuery: '', filterStatus: '', filterType: '', filterOperator: '', currentPage: 1,
        stats: { total: 0, completed: 0, pending: 0, failed: 0 },
        pagination: {},

        // Wallet
        walletSubTab: 'collection',
        collectionWallets: [], disbursementWallets: [], operators: [],
        overallBalance: 0, collectionTotal: 0, disbursementTotal: 0,
        walletTransactions: [], walletTxnOperatorFilter: '', walletTxnTypeFilter: '',
        creditAmounts: {}, creditDescs: {}, transferAmounts: {},
        walletCreditLoading: {}, walletTransferLoading: {},
        walletMsg: {}, walletMsgType: {},
        walletLoading: { txns: false },

        // Settlements
        settlements:[], stlFilterStatus: '', stlLoading: false, stlLoadingList: false,
        stlForm: { operator: '', amount: '', bank_name: '', account_number: '', account_name: '', description: '' },
        settlementMsg: '', settlementMsgType: '',

        // Send Money (Payout)
        sendMoneySubTab: 'single',
        payoutOperators: [],
        detectedOperator: { name: '', code: '' }, detectingOp: false,
        payoutForm: { phone: '', amount: '', reference: '', description: '' },
        payoutLoading: false, payoutMsg: '', payoutMsgType: 'success',
        lastPayoutResult: null,
        // Batch
        batchName: '', batchCsvText: '', batchItems: [], batchLoading: false,
        batchMsg: '', batchMsgType: 'success',
        batchResults: [], batchResultSummary: { sent: 0, failed: 0, total: 0 },
        manualRow: { phone: '', amount: '', reference: '', description: '' },
        recentDisbursements: [], recentDisbLoading: false,

        // Password
        showPasswordModal: false, currentPassword: '', newPassword: '', confirmPassword: '',
        pwError: '', pwSuccess: '', pwLoading: false,

        // Account Users
        accountUsers: [], accUsersLoading: false, addUserLoading: false,
        newUserForm: { name: '', email: '', password: '', role: 'viewer', permissions: [] },
        addUserMsg: '', addUserMsgType: '',
        allPermissions: ['view_transactions', 'create_settlement', 'view_settlements', 'wallet_transfer', 'add_user', 'view_users', 'view_account_info', 'view_settings'],
        editingPermUserId: null, editingPerms: [],

        // Pending KYC
        accountPending: false,

        // KYC form
        kycStep: 1,
        kycData: {}, kycFormLoading: false, kycSaving: false,
        kycMsg: '', kycMsgType: '',
        kycForm: { business_name: '', business_type: '', registration_number: '', tin_number: '', address: '', city: '', country: 'Tanzania', bank_name: '', bank_account_name: '', bank_account_number: '', bank_swift: '', bank_branch: '', id_type: '', id_number: '', crypto_wallet_address: '', crypto_network: '', crypto_currency: '' },
        kycIdFile: null, kycLicenseFile: null,
        kycErrors: {},

        // My Charges
        myCharges: {},

        // Internal Transfers
        myTransfers: [], myTransfersLoading: false,

        // Settings / Callback
        callbackUrl: '', callbackLoading: false,
        callbackMsg: '', callbackMsgType: '',

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
            this.appReady = true;
            this.$nextTick(() => document.dispatchEvent(new Event('alpine:initialized')));
        },

        /**
         * Check if current user has a permission.
         * Owner always has all permissions.
         */
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
                add_user: 'Add User',
                view_users: 'View Users',
                view_account_info: 'View Account Info',
                view_settings: 'View Settings',
            };
            return labels[perm] || perm;
        },

        async fetchStats() {
            try {
                const res = await fetch('{{ config("services.transaction_service.url") }}/api/transactions/stats', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.stats = data;
                }
            } catch (e) { /* silent */ }
        },

        async refreshUser() {
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/user', { headers: this.getHeaders() });
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/callback`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.callbackUrl = data.callback_url || '';
                }
            } catch (e) { console.error(e); }
            this.fetchIps();
        },

        async fetchKyc() {
            this.kycFormLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/kyc`, { headers: this.getHeaders() });
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

                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/kyc`, {
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/callback`, {
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/ips`, { headers: this.getHeaders() });
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/ips`, {
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/ips/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) { this.fetchIps(); }
            } catch (e) { console.error(e); }
        },

        // ---- API Keys ----
        async fetchApiKeys() {
            this.apiKeysLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/api-keys`, { headers: this.getHeaders() });
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/api-keys`, {
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
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/api-keys/${id}`, {
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

        // ---- Transactions ----
        async fetchTransactions() {
            this.loadingTxns = true; this.txnError = '';
            try {
                let url = `{{ config("services.transaction_service.url") }}/api/transactions?page=${this.currentPage}`;
                if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;
                if (this.filterStatus) url += `&status=${this.filterStatus}`;
                if (this.filterType) url += `&type=${this.filterType}`;
                if (this.filterOperator) url += `&operator=${encodeURIComponent(this.filterOperator)}`;
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

        // ---- My Charges ----
        async fetchMyCharges() {
            try {
                const res = await fetch('{{ config("services.transaction_service.url") }}/api/my-charges', { headers: this.getHeaders() });
                if (res.ok) this.myCharges = await res.json();
            } catch (e) { /* silent */ }
        },

        // ---- Wallet ----
        async fetchWallet() {
            try {
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/wallet', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.collectionWallets = data.collection_wallets || [];
                this.disbursementWallets = data.disbursement_wallets || [];
                this.overallBalance = data.overall_balance || 0;
                this.collectionTotal = data.collection_total || 0;
                this.disbursementTotal = data.disbursement_total || 0;
                this.operators = data.operators || [];
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
        },
        async fetchWalletTransactions() {
            this.walletLoading.txns = true;
            try {
                let url = '{{ config("services.wallet_service.url") }}/api/wallet/transactions';
                const params = [];
                if (this.walletTxnOperatorFilter) params.push(`operator=${encodeURIComponent(this.walletTxnOperatorFilter)}`);
                if (this.walletTxnTypeFilter) params.push(`wallet_type=${this.walletTxnTypeFilter}`);
                if (params.length) url += '?' + params.join('&');
                const res = await fetch(url, { headers: this.getHeaders() });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.walletTransactions = data.data || [];
            } catch (e) {}
            finally { this.walletLoading.txns = false; }
        },
        async creditOperator(operator) {
            const key = 'col_' + operator;
            this.walletCreditLoading[operator] = true;
            this.walletMsg[key] = '';
            try {
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/wallet/credit', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ amount: this.creditAmounts[operator], operator: operator, description: this.creditDescs[operator] })
                });
                const data = await res.json();
                if (!res.ok) { this.walletMsg[key] = data.message || 'Credit failed.'; this.walletMsgType[key] = 'error'; return; }
                // Show charge info if present
                let msg = data.message;
                if (data.charges && Number(data.charges.total_charge) > 0) {
                    msg = `Credited ${this.formatAmount(data.charges.net_amount)} TZS (Charges: ${this.formatAmount(data.charges.total_charge)} TZS deducted from ${this.formatAmount(data.charges.gross_amount)} TZS)`;
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
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/wallet/transfer', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ amount: this.transferAmounts[operator], operator: operator })
                });
                const data = await res.json();
                if (!res.ok) { this.walletMsg[key] = data.message || 'Transfer failed.'; this.walletMsgType[key] = 'error'; return; }
                this.walletMsg[key] = data.message; this.walletMsgType[key] = 'success';
                this.transferAmounts[operator] = '';
                this.fetchMyTransfers();
                setTimeout(() => { this.walletMsg[key] = ''; }, 5000);
            } catch (e) { this.walletMsg[key] = 'Service unavailable.'; this.walletMsgType[key] = 'error'; }
            finally { this.walletTransferLoading[operator] = false; }
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
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/wallet/transfers', { headers: this.getHeaders() });
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
                let url = '{{ config("services.settlement_service.url") }}/api/settlements';
                if (this.stlFilterStatus) url += `?status=${this.stlFilterStatus}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.settlements = data.data || [];
            } catch (e) {}
            finally { this.stlLoadingList = false; }
        },
        async createSettlement() {
            this.stlLoading = true; this.settlementMsg = '';
            try {
                const res = await fetch('{{ config("services.settlement_service.url") }}/api/settlements', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(this.stlForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.settlementMsg = errors || 'Failed.'; this.settlementMsgType = 'error'; return;
                }
                this.settlementMsg = data.message; this.settlementMsgType = 'success';
                this.stlForm = { operator: '', amount: '', bank_name: '', account_number: '', account_name: '', description: '' };
                this.fetchSettlements();
                this.fetchMyCharges();
                this.fetchTransactions();
            } catch (e) { this.settlementMsg = 'Service unavailable.'; this.settlementMsgType = 'error'; }
            finally { this.stlLoading = false; }
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
            // Normalize to local 10-digit format (0XXXXXXXXX)
            let cleaned = phone.replace(/[\s\-\.+]/g, '');
            if (cleaned.startsWith('255') && cleaned.length >= 12) {
                cleaned = '0' + cleaned.substring(3);
            } else if (!cleaned.startsWith('0') && cleaned.length === 9) {
                cleaned = '0' + cleaned;
            }
            // Extract 3-digit prefix (e.g., 075)
            if (cleaned.startsWith('0') && cleaned.length >= 10) {
                const prefix = cleaned.substring(0, 3);
                for (const op of this.payoutOperators) {
                    if (op.prefixes && op.prefixes.includes(prefix)) {
                        this.detectedOperator = { name: op.name, code: op.code };
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
                } else {
                    this.detectedOperator = { name: '', code: '' };
                }
            } catch (e) { this.detectedOperator = { name: '', code: '' }; }
            this.detectingOp = false;
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
                const res = await fetch('/api/disbursement', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ ...this.payoutForm, operator: this.detectedOperator.code })
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.payoutMsg = errors || 'Failed to send.';
                    this.payoutMsgType = 'error';
                } else {
                    this.payoutMsg = data.message || 'Payout sent successfully!';
                    this.payoutMsgType = 'success';
                    this.lastPayoutResult = data;
                    this.payoutForm = { phone: '', amount: '', reference: '', description: '' };
                    this.detectedOperator = { name: '', code: '' };
                    this.fetchRecentDisbursements();
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
            for (let i = 1; i < lines.length; i++) {
                const cols = lines[i].split(',').map(c => c.trim());
                const phone = cols[phoneIdx] || '';
                const amount = parseFloat(cols[amountIdx]) || 0;
                if (!phone || amount < 100) {
                    this.batchMsg = `Row ${i + 1}: invalid data (phone and amount >= 100 required).`;
                    this.batchMsgType = 'error';
                    continue;
                }
                items.push({
                    phone,
                    amount,
                    reference: refIdx !== -1 ? (cols[refIdx] || '') : '',
                    description: descIdx !== -1 ? (cols[descIdx] || '') : '',
                    _status: 'ready'
                });
            }
            this.batchItems = items;
            if (items.length > 0 && !this.batchMsg) {
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
            this.batchItems.push({
                ...this.manualRow,
                amount: parseFloat(this.manualRow.amount),
                _status: 'ready'
            });
            this.manualRow = { phone: '', amount: '', reference: '', description: '' };
            this.batchMsg = '';
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
                if (data.failed === 0) { this.batchName = ''; }
                this.fetchRecentDisbursements();
            } catch (e) { this.batchMsg = 'Network error.'; this.batchMsgType = 'error'; }
            this.batchLoading = false;
        },

        async fetchRecentDisbursements() {
            this.recentDisbLoading = true;
            try {
                const res = await fetch('/api/payment-requests?type=disbursement&per_page=20', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.recentDisbursements = data.data || [];
                }
            } catch (e) { console.error(e); }
            this.recentDisbLoading = false;
        },

        // ---- Helpers ----
        formatAmount(a) { return Number(a).toLocaleString('en-US', { minimumFractionDigits: 2 }); },
        formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }); },

        async logout() {
            try { await fetch('{{ config("services.auth_service.url") }}/api/logout', { method: 'POST', headers: this.getHeaders() }); } catch (e) {}
            localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); window.location.href = '/login';
        },

        async changePassword() {
            this.pwError = ''; this.pwSuccess = ''; this.pwLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/change-password', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({ current_password: this.currentPassword, password: this.newPassword, password_confirmation: this.confirmPassword })
                });
                const data = await res.json();
                if (!res.ok) { this.pwError = data.errors ? Object.values(data.errors).flat().join(' ') : data.message; return; }
                this.pwSuccess = data.message; this.currentPassword = ''; this.newPassword = ''; this.confirmPassword = '';
                setTimeout(() => this.closePasswordModal(), 2000);
            } catch (e) { this.pwError = 'Unable to connect to auth service.'; }
            finally { this.pwLoading = false; }
        },
        closePasswordModal() { this.showPasswordModal = false; this.currentPassword = ''; this.newPassword = ''; this.confirmPassword = ''; this.pwError = ''; this.pwSuccess = ''; },

        // ---- Account Users ----
        async fetchAccountUsers() {
            this.accUsersLoading = true;
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/account/users', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) { const data = await res.json(); this.accountUsers = data.users || []; }
            } catch (e) { console.error(e); }
            this.accUsersLoading = false;
        },
        async addUser() {
            this.addUserLoading = true; this.addUserMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/account/users', {
                    method: 'POST', headers: this.getHeaders(), body: JSON.stringify(this.newUserForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.addUserMsg = errors || 'Failed.'; this.addUserMsgType = 'error'; return;
                }
                this.addUserMsg = data.message; this.addUserMsgType = 'success';
                this.newUserForm = { name: '', email: '', password: '', role: 'viewer', permissions: [] };
                this.fetchAccountUsers();
            } catch (e) { this.addUserMsg = 'Service unavailable.'; this.addUserMsgType = 'error'; }
            finally { this.addUserLoading = false; }
        },
        async changeUserRole(id, role) {
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/users/${id}/role`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ role })
                });
                if (res.ok) this.fetchAccountUsers();
                else { const data = await res.json(); alert(data.message || 'Failed to change role.'); }
            } catch (e) { alert('Service unavailable.'); }
        },
        async removeUser(id, name) {
            if (!confirm(`Remove user "${name}" from this account?`)) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/users/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) this.fetchAccountUsers();
                else { const data = await res.json(); alert(data.message || 'Failed to remove user.'); }
            } catch (e) { alert('Service unavailable.'); }
        },
        async saveUserPermissions(userId) {
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/account/users/${userId}/permissions`, {
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
        }
    }
}
</script>
@endsection
