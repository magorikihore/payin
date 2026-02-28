@extends('layouts.app')

@section('title', 'Admin Panel - Payin')

@section('content')
<div x-data="adminPanel()" x-init="init()" x-cloak>
    <!-- Navigation -->
    <nav class="bg-gray-900 shadow-sm border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-white">Payin Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-300">Super Admin: <span class="font-medium text-white" x-text="user?.firstname || user?.name || 'Admin'"></span></span>
                    <button @click="logout()" class="text-sm text-red-400 hover:text-red-300 font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Tab Navigation -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="bg-blue-600 rounded-t-lg">
            <nav class="flex space-x-8 flex-wrap items-center px-4">
                <button x-show="hasPerm('admin_overview')" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">Overview</button>
                <button x-show="hasPerm('admin_accounts')" @click="activeTab = 'accounts'; fetchAccounts()" :class="activeTab === 'accounts' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">
                    Accounts
                    <span x-show="stats.pending_accounts > 0" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full" x-text="stats.pending_accounts"></span>
                </button>
                <button x-show="hasPerm('admin_transactions')" @click="activeTab = 'transactions'; fetchAdminTransactions()" :class="activeTab === 'transactions' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">Transactions</button>
                <button x-show="hasPerm('admin_wallets')" @click="activeTab = 'wallets'; fetchAdminWallets()" :class="activeTab === 'wallets' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">Wallets</button>
                <button x-show="hasPerm('admin_settlements')" @click="activeTab = 'settlements'; fetchAdminSettlements()" :class="activeTab === 'settlements' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">
                    Settlements
                    <span x-show="pendingSettlementsCount > 0" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-yellow-500 rounded-full" x-text="pendingSettlementsCount"></span>
                </button>
                <button x-show="hasPerm('admin_charges')" @click="activeTab = 'charges'; fetchCharges()" :class="activeTab === 'charges' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">Charges</button>
                <button x-show="hasPerm('admin_ip_whitelist')" @click="activeTab = 'ipwhitelist'; fetchAdminIps()" :class="activeTab === 'ipwhitelist' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">
                    IP Whitelist
                    <span x-show="pendingIpCount > 0" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full" x-text="pendingIpCount"></span>
                </button>
                <button x-show="hasPerm('admin_transfers')" @click="activeTab = 'transfers'; fetchAdminInternalTransfers()" :class="activeTab === 'transfers' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">
                    Transfers
                    <span x-show="pendingTransferCount > 0" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-purple-500 rounded-full" x-text="pendingTransferCount"></span>
                </button>
                <button x-show="hasPerm('admin_reversals')" @click="activeTab = 'reversals'; fetchAdminReversals()" :class="activeTab === 'reversals' ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap">
                    Reversals
                    <span x-show="pendingReversalCount > 0" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-purple-500 rounded-full" x-text="pendingReversalCount"></span>
                </button>
                <!-- More dropdown for last 4 items -->
                <div class="relative" x-data="{ moreOpen: false }" @click.away="moreOpen = false"
                     x-show="hasPerm('admin_users') || hasPerm('admin_operators') || hasPerm('admin_payments') || user?.role === 'super_admin'"
                     >
                    <button @click="moreOpen = !moreOpen"
                        :class="['users','operators','payments','admin_users','logs','mail_config'].includes(activeTab) ? 'border-white text-white' : 'border-transparent text-white/70 hover:text-white hover:border-white/50'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition whitespace-nowrap inline-flex items-center">
                        <span x-text="activeTab === 'users' ? 'Users' : activeTab === 'operators' ? 'Operators' : activeTab === 'payments' ? 'Payment Requests' : activeTab === 'admin_users' ? 'Admin Users' : activeTab === 'logs' ? 'Error Logs' : activeTab === 'mail_config' ? 'Mail Config' : 'More'"></span>
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="moreOpen" x-transition class="absolute left-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg border z-50">
                        <button x-show="hasPerm('admin_users')" @click="activeTab = 'users'; fetchUsers(); moreOpen = false"
                            :class="activeTab === 'users' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Users</button>
                        <button x-show="hasPerm('admin_operators')" @click="activeTab = 'operators'; fetchOperators(); moreOpen = false"
                            :class="activeTab === 'operators' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Operators</button>
                        <button x-show="hasPerm('admin_payments')" @click="activeTab = 'payments'; fetchPaymentRequests(); moreOpen = false"
                            :class="activeTab === 'payments' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Payment Requests</button>
                        <button x-show="user?.role === 'super_admin'" @click="activeTab = 'admin_users'; fetchAdminUsers(); moreOpen = false"
                            :class="activeTab === 'admin_users' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Admin Users</button>
                        <button x-show="user?.role === 'super_admin'" @click="activeTab = 'logs'; fetchLogs(); moreOpen = false"
                            :class="activeTab === 'logs' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Error Logs</button>
                        <button x-show="user?.role === 'super_admin'" @click="activeTab = 'mail_config'; fetchMailConfig(); moreOpen = false"
                            :class="activeTab === 'mail_config' ? 'bg-red-50 text-red-600' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm">Mail Config</button>
                    </div>
                </div>
            </nav>
        </div>

        <!-- ==================== OVERVIEW TAB ==================== -->
        <div x-show="activeTab === 'overview'" class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Accounts</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="stats.total_accounts"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Active</p>
                            <p class="text-2xl font-bold text-green-600" x-text="stats.active_accounts"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border cursor-pointer hover:ring-2 hover:ring-orange-300" @click="activeTab='accounts'; accStatusFilter='pending'; fetchAccounts()">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Pending KYC</p>
                            <p class="text-2xl font-bold text-orange-600" x-text="stats.pending_accounts || 0"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Suspended</p>
                            <p class="text-2xl font-bold text-red-600" x-text="stats.suspended_accounts"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Users</p>
                            <p class="text-2xl font-bold text-indigo-600" x-text="stats.total_users"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border cursor-pointer hover:ring-2 hover:ring-purple-300" @click="activeTab='transfers'; fetchAdminInternalTransfers()">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Pending Transfers</p>
                            <p class="text-2xl font-bold text-purple-600" x-text="pendingTransferCount"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charge Revenue Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm">Total Platform Charges</p>
                            <p class="text-2xl font-bold mt-1" x-text="formatAmount(chargeRevenue.total_platform_charges || 0) + ' TZS'"></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Operator Charges</p>
                            <p class="text-2xl font-bold mt-1" x-text="formatAmount(chargeRevenue.total_operator_charges || 0) + ' TZS'"></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm">Today's Platform Revenue</p>
                            <p class="text-2xl font-bold mt-1" x-text="formatAmount(chargeRevenue.today_platform_charges || 0) + ' TZS'"></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Total Charges Collected</p>
                            <p class="text-2xl font-bold mt-1" x-text="formatAmount(chargeRevenue.total_charges || 0) + ' TZS'"></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charge Revenue Breakdown -->
            <div x-show="chargeRevenue.by_operator && chargeRevenue.by_operator.length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- By Operator -->
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Charges by Operator</h3>
                    <div class="space-y-3">
                        <template x-for="op in (chargeRevenue.by_operator || [])" :key="op.operator">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="font-medium text-gray-700" x-text="op.operator"></span>
                                    <span class="text-xs text-gray-400 ml-2" x-text="op.transaction_count + ' txns'"></span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-emerald-600" x-text="formatAmount(Number(op.platform_charges) + Number(op.operator_charges)) + ' TZS'"></p>
                                    <p class="text-xs text-gray-400" x-text="'Platform: ' + formatAmount(op.platform_charges) + ' | Operator: ' + formatAmount(op.operator_charges)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <!-- By Type -->
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Charges by Transaction Type</h3>
                    <div class="space-y-3">
                        <template x-for="tp in (chargeRevenue.by_type || [])" :key="tp.type">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="font-medium text-gray-700 capitalize" x-text="tp.type"></span>
                                    <span class="text-xs text-gray-400 ml-2" x-text="tp.transaction_count + ' txns'"></span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-emerald-600" x-text="formatAmount(Number(tp.platform_charges) + Number(tp.operator_charges)) + ' TZS'"></p>
                                    <p class="text-xs text-gray-400" x-text="'Platform: ' + formatAmount(tp.platform_charges) + ' | Operator: ' + formatAmount(tp.operator_charges)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Charges Per Account -->
            <div x-show="chargeRevenue.by_account && chargeRevenue.by_account.length > 0" class="mt-6">
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b"><h3 class="text-lg font-semibold text-gray-800">Charges Collected Per Account</h3></div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transactions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform Charges</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator Charges</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Charges</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="ac in (chargeRevenue.by_account || [])" :key="ac.account_id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-700" x-text="accountName(ac.account_id)"></td>
                                        <td class="px-6 py-3 text-sm text-gray-600" x-text="ac.transaction_count"></td>
                                        <td class="px-6 py-3 text-sm text-gray-600" x-text="formatAmount(ac.total_volume) + ' TZS'"></td>
                                        <td class="px-6 py-3 text-sm text-emerald-600 font-medium" x-text="formatAmount(ac.platform_charges) + ' TZS'"></td>
                                        <td class="px-6 py-3 text-sm text-blue-600 font-medium" x-text="formatAmount(ac.operator_charges) + ' TZS'"></td>
                                        <td class="px-6 py-3 text-sm text-purple-600 font-bold" x-text="formatAmount(Number(ac.platform_charges) + Number(ac.operator_charges)) + ' TZS'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ACCOUNTS TAB ==================== -->
        <div x-show="activeTab === 'accounts'" class="mt-6">
            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="accSearch" @input.debounce.400ms="accPage=1; fetchAccounts()"
                            placeholder="Search by name, paybill, email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <select x-model="accStatusFilter" @change="accPage=1; fetchAccounts()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending KYC</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="closed">Closed</option>
                    </select>
                    <button @click="showAddBusinessModal = true; resetAddBusinessForm()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Business
                    </button>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="accLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!accLoading && accounts.length === 0" x-cloak class="p-8 text-center text-gray-500">No accounts found.</div>
                <div x-show="!accLoading && accounts.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paybill</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Collection Balance</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Disbursement Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="acc in accounts" :key="acc.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="acc.account_ref"></td>
                                        <td class="px-6 py-4 text-sm font-mono font-semibold text-indigo-700" x-text="acc.paybill || '—'"></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="acc.business_name"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="acc.email"></td>
                                        <td class="px-6 py-4 text-sm text-right font-semibold text-green-700" x-text="formatAmount(acc.collection_balance || 0) + ' ' + (acc.currency || 'TZS')"></td>
                                        <td class="px-6 py-4 text-sm text-right font-semibold text-blue-700" x-text="formatAmount(acc.disbursement_balance || 0) + ' ' + (acc.currency || 'TZS')"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="acc.users_count"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-green-100 text-green-800': acc.status==='active','bg-red-100 text-red-800': acc.status==='suspended','bg-gray-100 text-gray-800': acc.status==='closed','bg-orange-100 text-orange-800': acc.status==='pending'}"
                                                x-text="acc.status === 'pending' ? 'Pending KYC' : acc.status"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(acc.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button @click="viewKycDetails(acc.id)"
                                                    class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 font-medium">View KYC</button>
                                                <button x-show="acc.status === 'pending'" @click="viewKycDetails(acc.id, true)"
                                                    class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 font-medium">Approve KYC</button>
                                                <button x-show="acc.status !== 'active' && acc.status !== 'pending'" @click="updateAccountStatus(acc.id, 'active')"
                                                    class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">Activate</button>
                                                <button x-show="acc.status === 'active'" @click="updateAccountStatus(acc.id, 'suspended')"
                                                    class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200">Suspend</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Page <span x-text="accPagination.current_page"></span> of <span x-text="accPagination.last_page"></span> (<span x-text="accPagination.total"></span> total)</p>
                        <div class="flex space-x-2">
                            <button @click="accPage--; fetchAccounts()" :disabled="!accPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="accPage++; fetchAccounts()" :disabled="!accPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== USERS TAB ==================== -->
        <div x-show="activeTab === 'users'" class="mt-6">
            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="usrSearch" @input.debounce.400ms="usrPage=1; fetchUsers()"
                            placeholder="Search by name, email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <select x-model="usrRoleFilter" @change="usrPage=1; fetchUsers()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Roles</option>
                        <option value="owner">Owner</option>
                        <option value="admin">Admin</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="usrLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!usrLoading && adminUsers.length === 0" x-cloak class="p-8 text-center text-gray-500">No users found.</div>
                <div x-show="!usrLoading && adminUsers.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="usr in adminUsers" :key="usr.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="(usr.firstname && usr.lastname) ? (usr.firstname + ' ' + usr.lastname) : usr.name"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="usr.email"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span x-text="usr.account?.business_name || '-'"></span>
                                            <br><span class="text-xs text-gray-400" x-text="usr.account?.account_ref || ''"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-purple-100 text-purple-800': usr.role==='owner','bg-blue-100 text-blue-800': usr.role==='admin','bg-gray-100 text-gray-800': usr.role==='viewer'}"
                                                x-text="usr.role"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(usr.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <button @click="adminResetPassword(usr.id, (usr.firstname && usr.lastname) ? (usr.firstname + ' ' + usr.lastname) : usr.name)" class="text-xs bg-yellow-500 text-white px-3 py-1.5 rounded hover:bg-yellow-600 font-medium">Reset Password</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Page <span x-text="usrPagination.current_page"></span> of <span x-text="usrPagination.last_page"></span> (<span x-text="usrPagination.total"></span> total)</p>
                        <div class="flex space-x-2">
                            <button @click="usrPage--; fetchUsers()" :disabled="!usrPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="usrPage++; fetchUsers()" :disabled="!usrPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TRANSACTIONS TAB ==================== -->
        <div x-show="activeTab === 'transactions'" class="mt-6">
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="txnSearch" @input.debounce.400ms="txnPage=1; fetchAdminTransactions()"
                            placeholder="Search by reference, phone, amount..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <select x-model="txnStatusFilter" @change="txnPage=1; fetchAdminTransactions()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select x-model="txnTypeFilter" @change="txnPage=1; fetchAdminTransactions()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="collection">Collection</option>
                        <option value="disbursement">Disbursement</option>
                        <option value="topup">Topup</option>
                        <option value="settlement">Settlement</option>
                    </select>
                    <select x-model="txnOperatorFilter" @change="txnPage=1; fetchAdminTransactions()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Operators</option>
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Tigo Pesa">Tigo Pesa</option>
                        <option value="Airtel Money">Airtel Money</option>
                        <option value="Halopesa">Halopesa</option>
                    </select>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="txnLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!txnLoading && adminTransactions.length === 0" x-cloak class="p-8 text-center text-gray-500">No transactions found.</div>
                <div x-show="!txnLoading && adminTransactions.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charges</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="txn in adminTransactions" :key="txn.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-xs font-mono text-gray-700" x-text="txn.transaction_ref"></td>
                                        <td class="px-4 py-3 text-xs text-gray-600" x-text="accountName(txn.account_id)"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-green-100 text-green-800': txn.type==='collection','bg-blue-100 text-blue-800': txn.type==='disbursement','bg-purple-100 text-purple-800': txn.type==='topup','bg-yellow-100 text-yellow-800': txn.type==='settlement'}"
                                                x-text="txn.type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="txn.operator"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800" x-text="formatAmount(txn.amount) + ' ' + (txn.currency || 'TZS')"></td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            <span x-show="txn.platform_charge > 0" x-text="'P:' + formatAmount(txn.platform_charge)"></span>
                                            <span x-show="txn.operator_charge > 0" x-text="' O:' + formatAmount(txn.operator_charge)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="txn.phone_number || '-'"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-green-100 text-green-800': txn.status==='completed','bg-yellow-100 text-yellow-800': txn.status==='pending','bg-red-100 text-red-800': txn.status==='failed','bg-gray-100 text-gray-800': txn.status==='cancelled','bg-purple-100 text-purple-800': txn.status==='reversed'}"
                                                x-text="txn.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(txn.created_at)"></td>
                                        <td class="px-4 py-3">
                                            <button x-show="txn.status === 'completed' && (txn.type === 'collection' || txn.type === 'disbursement')" x-cloak
                                                @click="openDirectReversal(txn)" :disabled="directRevLoading"
                                                class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded hover:bg-purple-700 font-medium disabled:opacity-50">Reverse</button>
                                            <span x-show="txn.status === 'reversed'" x-cloak class="text-xs text-purple-600 font-medium">Reversed</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Page <span x-text="txnPagination.current_page"></span> of <span x-text="txnPagination.last_page"></span> (<span x-text="txnPagination.total"></span> total)</p>
                        <div class="flex space-x-2">
                            <button @click="txnPage--; fetchAdminTransactions()" :disabled="!txnPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="txnPage++; fetchAdminTransactions()" :disabled="!txnPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== WALLETS TAB ==================== -->
        <div x-show="activeTab === 'wallets'" class="mt-6">
            <div x-show="wltLoading" class="p-8 text-center text-gray-500">
                <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <div x-show="!wltLoading" x-cloak>
                <!-- Platform Totals -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 border">
                        <p class="text-sm text-gray-500">Platform Collection Total</p>
                        <p class="text-2xl font-bold text-green-600" x-text="formatAmount(walletData.platform_collection_total || 0) + ' TZS'"></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border">
                        <p class="text-sm text-gray-500">Platform Disbursement Total</p>
                        <p class="text-2xl font-bold text-blue-600" x-text="formatAmount(walletData.platform_disbursement_total || 0) + ' TZS'"></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border">
                        <p class="text-sm text-gray-500">Platform Overall Total</p>
                        <p class="text-2xl font-bold text-gray-800" x-text="formatAmount(walletData.platform_overall_total || 0) + ' TZS'"></p>
                    </div>
                </div>

                <!-- Per-account wallets -->
                <template x-for="acctWallet in (walletData.accounts || [])" :key="acctWallet.account_id">
                    <div class="bg-white rounded-xl shadow-sm border mb-4">
                        <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-gray-800" x-text="accountName(acctWallet.account_id)"></span>
                                <span class="ml-4 text-sm text-gray-500">Collection: <span class="font-medium text-green-600" x-text="formatAmount(acctWallet.collection_total) + ' ' + (acctWallet.wallets?.[0]?.currency || 'TZS')"></span></span>
                                <span class="ml-3 text-sm text-gray-500">Disbursement: <span class="font-medium text-blue-600" x-text="formatAmount(acctWallet.disbursement_total) + ' ' + (acctWallet.wallets?.[0]?.currency || 'TZS')"></span></span>
                                <span class="ml-3 text-sm text-gray-500">Total: <span class="font-bold text-gray-800" x-text="formatAmount(acctWallet.overall_balance) + ' ' + (acctWallet.wallets?.[0]?.currency || 'TZS')"></span></span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="w in acctWallet.wallets" :key="w.operator + w.wallet_type">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-sm" x-text="w.operator"></td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                    :class="w.wallet_type === 'collection' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"
                                                    x-text="w.wallet_type"></span>
                                            </td>
                                            <td class="px-4 py-2 text-sm font-semibold" x-text="formatAmount(w.balance) + ' ' + (w.currency || 'TZS')"></td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                    :class="w.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                    x-text="w.status"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <div x-show="!walletData.accounts || walletData.accounts.length === 0" class="bg-white rounded-xl shadow-sm border p-8 text-center text-gray-500">No wallet data found.</div>
            </div>
        </div>

        <!-- ==================== SETTLEMENTS TAB ==================== -->
        <div x-show="activeTab === 'settlements'" class="mt-6">
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="stlSearch" @input.debounce.400ms="stlPage=1; fetchAdminSettlements()"
                            placeholder="Search by reference, bank, account name..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <select x-model="stlStatusFilter" @change="stlPage=1; fetchAdminSettlements()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>

            <!-- Settlement action message -->
            <div x-show="stlMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                :class="stlMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                x-text="stlMsg"></div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="stlLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!stlLoading && adminSettlements.length === 0" x-cloak class="p-8 text-center text-gray-500">No settlements found.</div>
                <div x-show="!stlLoading && adminSettlements.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acc Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acc Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="stl in adminSettlements" :key="stl.id">
                                    <tr class="hover:bg-gray-50" :class="stl.status === 'pending' ? 'bg-yellow-50' : ''">
                                        <td class="px-4 py-3 text-xs font-mono text-gray-700" x-text="stl.settlement_ref"></td>
                                        <td class="px-4 py-3 text-xs text-gray-600" x-text="accountName(stl.account_id)"></td>
                                        <td class="px-4 py-3 text-sm" x-text="stl.operator"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800" x-text="formatAmount(stl.amount) + ' ' + (stl.currency || 'TZS')"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="stl.bank_name || '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="stl.account_name || '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="stl.account_number || '-'"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-yellow-100 text-yellow-800': stl.status==='pending','bg-green-100 text-green-800': stl.status==='approved' || stl.status==='completed','bg-red-100 text-red-800': stl.status==='rejected' || stl.status==='failed','bg-blue-100 text-blue-800': stl.status==='processing','bg-gray-100 text-gray-800': stl.status==='cancelled'}"
                                                x-text="stl.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(stl.created_at)"></td>
                                        <td class="px-4 py-3">
                                            <div x-show="stl.status === 'pending'" class="flex space-x-2">
                                                <button @click="approveSettlement(stl.id)" :disabled="stlActionLoading"
                                                    class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 font-medium disabled:opacity-50">Approve</button>
                                                <button @click="rejectSettlement(stl.id)" :disabled="stlActionLoading"
                                                    class="text-xs bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 font-medium disabled:opacity-50">Reject</button>
                                            </div>
                                            <span x-show="stl.status !== 'pending'" class="text-xs text-gray-400">—</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Page <span x-text="stlPagination.current_page"></span> of <span x-text="stlPagination.last_page"></span> (<span x-text="stlPagination.total"></span> total)</p>
                        <div class="flex space-x-2">
                            <button @click="stlPage--; fetchAdminSettlements()" :disabled="!stlPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="stlPage++; fetchAdminSettlements()" :disabled="!stlPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== CHARGES TAB ==================== -->
        <div x-show="activeTab === 'charges'" class="mt-6">
            <!-- Add Charge Form -->
            <div class="bg-white rounded-xl shadow-sm p-6 border mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Charge Configuration</h3>
                <div x-show="chargeMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                    :class="chargeMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                    x-text="chargeMsg"></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" x-model="chargeForm.name" placeholder="e.g. M-Pesa Collection Fee"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business</label>
                        <select x-model="chargeForm.account_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All Businesses (Global)</option>
                            <template x-for="[accId, accName] in Object.entries(accountMap)" :key="accId">
                                <option :value="accId" x-text="accName"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator</label>
                        <select x-model="chargeForm.operator" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="all">All Operators</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Tigo Pesa">Tigo Pesa</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Halopesa">Halopesa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <select x-model="chargeForm.transaction_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="all">All Types</option>
                            <option value="collection">Collection (Payin)</option>
                            <option value="disbursement">Disbursement (Payout)</option>
                            <option value="topup">Topup (Transfer)</option>
                            <option value="settlement">Settlement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Type</label>
                        <select x-model="chargeForm.charge_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="fixed">Fixed Amount (TZS)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="chargeForm.charge_type === 'percentage' ? 'Percentage (%)' : 'Amount (TZS)'"></label>
                        <input type="number" step="0.01" x-model="chargeForm.charge_value" placeholder="0.00"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Applies To</label>
                        <select x-model="chargeForm.applies_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="platform">Platform Fee</option>
                            <option value="operator">Operator Fee</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Amount (TZS)</label>
                        <input type="number" step="0.01" x-model="chargeForm.min_amount" placeholder="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Amount (TZS, 0 = no limit)</label>
                        <input type="number" step="0.01" x-model="chargeForm.max_amount" placeholder="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="addCharge()" :disabled="chargeLoading"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium disabled:opacity-50">
                        <span x-show="!chargeLoading">Add Charge</span>
                        <span x-show="chargeLoading">Adding...</span>
                    </button>
                </div>
            </div>

            <!-- Charges Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Charge Configurations</h3>
                    <div class="flex gap-2">
                        <select x-model="chargeAccountFilter" @change="fetchCharges()" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                            <option value="">All Businesses</option>
                            <option value="global">Global Only</option>
                            <template x-for="[accId, accName] in Object.entries(accountMap)" :key="accId">
                                <option :value="accId" x-text="accName"></option>
                            </template>
                        </select>
                        <select x-model="chargeOperatorFilter" @change="fetchCharges()" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                            <option value="">All Operators</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Tigo Pesa">Tigo Pesa</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Halopesa">Halopesa</option>
                            <option value="all">Global (all)</option>
                        </select>
                        <select x-model="chargeStatusFilter" @change="fetchCharges()" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div x-show="chargesLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!chargesLoading && charges.length === 0" x-cloak class="p-8 text-center text-gray-500">No charge configurations found.</div>
                <div x-show="!chargesLoading && charges.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Txn Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charge</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount Range</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="ch in charges" :key="ch.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800" x-text="ch.name"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span x-show="!ch.account_id" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">All Businesses</span>
                                            <span x-show="ch.account_id" class="text-sm text-gray-700" x-text="accountName(ch.account_id)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="{'bg-green-100 text-green-800': ch.operator==='M-Pesa','bg-blue-100 text-blue-800': ch.operator==='Tigo Pesa','bg-red-100 text-red-800': ch.operator==='Airtel Money','bg-orange-100 text-orange-800': ch.operator==='Halopesa','bg-gray-100 text-gray-800': ch.operator==='all'}"
                                                x-text="ch.operator === 'all' ? 'All Operators' : ch.operator"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm capitalize" x-text="ch.transaction_type === 'all' ? 'All Types' : ch.transaction_type"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                            <span x-text="ch.charge_type === 'fixed' ? formatAmount(ch.charge_value) + ' TZS' : ch.charge_value + '%'"></span>
                                            <span class="text-xs text-gray-400 ml-1" x-text="'(' + ch.charge_type + ')'"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="ch.applies_to === 'platform' ? 'bg-indigo-100 text-indigo-800' : 'bg-yellow-100 text-yellow-800'"
                                                x-text="ch.applies_to"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <span x-text="formatAmount(ch.min_amount)"></span> - <span x-text="ch.max_amount == 0 ? '∞' : formatAmount(ch.max_amount)"></span> TZS
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="ch.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                x-text="ch.status"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex space-x-2">
                                                <button @click="toggleChargeStatus(ch)"
                                                    class="text-xs px-2 py-1 rounded"
                                                    :class="ch.status === 'active' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                                    x-text="ch.status === 'active' ? 'Disable' : 'Enable'"></button>
                                                <button @click="deleteCharge(ch.id)"
                                                    class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200">Delete</button>
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

        <!-- ==================== IP WHITELIST TAB ==================== -->
        <div x-show="activeTab === 'ipwhitelist'" class="mt-6">
            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="ipSearch" @input.debounce.400ms="fetchAdminIps()"
                            placeholder="Search by IP, label, business..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none">
                    </div>
                    <select x-model="ipStatusFilter" @change="fetchAdminIps()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- IP Whitelist Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="ipListLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!ipListLoading && adminIpList.length === 0" x-cloak class="p-8 text-center text-gray-500">No IP whitelist entries found.</div>
                <div x-show="!ipListLoading && adminIpList.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paybill</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="ip in adminIpList" :key="ip.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="ip.account?.business_name || '—'"></td>
                                        <td class="px-6 py-4 text-sm font-mono text-indigo-700" x-text="ip.account?.paybill || '—'"></td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="ip.ip_address"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="ip.label || '—'"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-orange-100 text-orange-800': ip.status==='pending', 'bg-green-100 text-green-800': ip.status==='approved', 'bg-red-100 text-red-800': ip.status==='rejected'}"
                                                x-text="ip.status"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(ip.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button x-show="ip.status === 'pending'" @click="approveIp(ip.id)"
                                                    class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 font-medium">Approve</button>
                                                <button x-show="ip.status === 'pending'" @click="rejectIp(ip.id)"
                                                    class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 font-medium">Reject</button>
                                                <span x-show="ip.status === 'approved'" class="text-xs text-green-600 font-medium">Active</span>
                                                <span x-show="ip.status === 'rejected'" class="text-xs text-red-600 font-medium">Denied</span>
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

        <!-- ==================== TRANSFERS TAB ==================== -->
        <div x-show="activeTab === 'transfers'" class="mt-6">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <select x-model="trfStatusFilter" @change="fetchAdminInternalTransfers()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <input type="text" x-model="trfAccountFilter" @input.debounce.400ms="fetchAdminInternalTransfers()"
                        placeholder="Account ID..."
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none w-40">
                    <span class="text-sm text-gray-500">
                        Total: <span class="font-semibold" x-text="adminInternalTransfers.length"></span>
                        | Pending: <span class="font-semibold text-orange-600" x-text="pendingTransferCount"></span>
                    </span>
                </div>
            </div>
            <!-- Transfer Action Messages -->
            <div x-show="trfMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                :class="trfMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                x-text="trfMsg"></div>

            <!-- Transfers Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="trfLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!trfLoading && adminInternalTransfers.length === 0" x-cloak class="p-8 text-center text-gray-500">No transfer requests found.</div>
                <div x-show="!trfLoading && adminInternalTransfers.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved At</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="t in adminInternalTransfers" :key="t.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-4 text-sm font-mono text-gray-700" x-text="t.reference"></td>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-800" x-text="accountMap[t.account_id] || ('Account #' + t.account_id)"></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{'bg-green-100 text-green-800': t.operator==='M-Pesa', 'bg-blue-100 text-blue-800': t.operator==='Tigo Pesa', 'bg-red-100 text-red-800': t.operator==='Airtel Money', 'bg-orange-100 text-orange-800': t.operator==='Halopesa'}"
                                                x-text="t.operator"></span>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-800" x-text="formatAmount(t.amount) + ' ' + (t.currency || 'TZS')"></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-orange-100 text-orange-800': t.status==='pending', 'bg-green-100 text-green-800': t.status==='approved', 'bg-red-100 text-red-800': t.status==='rejected'}"
                                                x-text="t.status"></span>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-500" x-text="formatDate(t.created_at)"></td>
                                        <td class="px-5 py-4 text-sm text-gray-500" x-text="t.approved_at ? formatDate(t.approved_at) : '—'"></td>
                                        <td class="px-5 py-4">
                                            <div class="flex space-x-2" x-show="t.status === 'pending'">
                                                <button @click="approveInternalTransfer(t.id)" :disabled="trfActionLoading"
                                                    class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 font-medium disabled:opacity-50">Approve</button>
                                                <button @click="rejectInternalTransfer(t.id)" :disabled="trfActionLoading"
                                                    class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200 font-medium disabled:opacity-50">Reject</button>
                                            </div>
                                            <div x-show="t.status === 'approved'" class="text-xs text-green-600 font-medium">Completed</div>
                                            <div x-show="t.status === 'rejected'">
                                                <span class="text-xs text-red-600 font-medium">Denied</span>
                                                <p x-show="t.admin_notes" class="text-xs text-gray-400 mt-0.5" x-text="t.admin_notes"></p>
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

    <!-- ==================== KYC DETAIL MODAL ==================== -->
    <div x-show="showKycModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showKycModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showKycModal = false"></div>
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all w-full max-w-3xl">
                <!-- Modal Header -->
                <div class="bg-gray-900 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white">KYC Details</h3>
                        <p class="text-sm text-gray-400" x-text="kycAccount?.account_ref"></p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                            :class="{'bg-orange-100 text-orange-800': kycAccount?.status==='pending','bg-green-100 text-green-800': kycAccount?.status==='active','bg-red-100 text-red-800': kycAccount?.status==='suspended','bg-gray-100 text-gray-800': kycAccount?.status==='closed'}"
                            x-text="kycAccount?.status === 'pending' ? 'Pending KYC' : kycAccount?.status"></span>
                        <button @click="showKycModal = false" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <div x-show="kycLoading" class="p-12 text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>

                <div x-show="!kycLoading && kycAccount" x-cloak>
                    <!-- KYC Completeness Bar + Edit Toggle -->
                    <div class="px-6 pt-5 pb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">KYC Completeness</span>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold" :class="(kycAccount?.kyc_completeness || 0) >= 80 ? 'text-green-600' : (kycAccount?.kyc_completeness || 0) >= 50 ? 'text-yellow-600' : 'text-red-600'" x-text="(kycAccount?.kyc_completeness || 0) + '%'"></span>
                                <button @click="toggleKycEdit()" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition"
                                    :class="kycEditing ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700'">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    <span x-text="kycEditing ? 'Cancel Edit' : 'Edit KYC'"></span>
                                </button>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all"
                                :class="(kycAccount?.kyc_completeness || 0) >= 80 ? 'bg-green-500' : (kycAccount?.kyc_completeness || 0) >= 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                :style="`width: ${kycAccount?.kyc_completeness || 0}%`"></div>
                        </div>
                        <!-- Edit save message -->
                        <div x-show="kycEditMsg" x-cloak class="mt-3 p-2 rounded-lg text-sm" :class="kycEditMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="kycEditMsg"></div>
                    </div>

                    <!-- ===== VIEW MODE ===== -->
                    <div x-show="!kycEditing">
                        <!-- Business Information -->
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Business Information</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Business Name</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.business_name || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Business Type</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.business_type || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Registration Number</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.registration_number || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">TIN Number</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.tin_number || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Paybill Number</label>
                                    <p class="text-sm font-medium text-indigo-700 font-mono" x-text="kycAccount?.paybill || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Email</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.email || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Phone</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.phone || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="px-6 py-4 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Location</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Address</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.address || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">City</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.city || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Country</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.country || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Identity Verification -->
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Identity Verification</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">ID Type</label>
                                    <p class="text-sm font-medium text-gray-800 capitalize" x-text="(kycAccount?.id_type || '—').replace('_', ' ')"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">ID Number</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.id_number || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="px-6 py-4 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Documents</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.id_document_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.id_document_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View ID Document &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.id_document_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">ID Document — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.certificate_of_incorporation_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.certificate_of_incorporation_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View Certificate of Incorporation &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.certificate_of_incorporation_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">Certificate of Incorporation — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.business_license_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.business_license_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View Business License &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.business_license_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">Business License — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.tax_clearance_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.tax_clearance_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View Tax Clearance &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.tax_clearance_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">Tax Clearance — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.tin_certificate_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.tin_certificate_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View TIN Certificate &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.tin_certificate_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">TIN Certificate — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.company_memorandum_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.company_memorandum_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View Company Memorandum &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.company_memorandum_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">Company Memorandum — Not uploaded</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <template x-if="kycAccount?.company_resolution_url">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 font-medium">Uploaded</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.company_resolution_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View Company Resolution &rarr;</a>
                                        </div>
                                    </template>
                                    <template x-if="!kycAccount?.company_resolution_url">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-600 font-medium">Company Resolution — Not uploaded</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Details -->
                        <div class="px-6 py-4" x-show="kycAccount?.bank_name || kycAccount?.bank_account_number">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Bank Settlement</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Bank</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.bank_name || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Account Name</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.bank_account_name || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Account Number</label>
                                    <p class="text-sm font-medium text-gray-800 font-mono" x-text="kycAccount?.bank_account_number || '—'"></p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">SWIFT / Branch</label>
                                    <p class="text-sm font-medium text-gray-800" x-text="[kycAccount?.bank_swift, kycAccount?.bank_branch].filter(Boolean).join(' / ') || '—'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== EDIT MODE ===== -->
                    <div x-show="kycEditing" x-cloak>
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-blue-700 uppercase tracking-wide mb-3 border-b border-blue-200 pb-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit Business Information
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Business Name</label>
                                    <input type="text" x-model="kycEditForm.business_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Business Type</label>
                                    <select x-model="kycEditForm.business_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
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
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Registration Number</label>
                                    <input type="text" x-model="kycEditForm.registration_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">TIN Number</label>
                                    <input type="text" x-model="kycEditForm.tin_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Email</label>
                                    <input type="email" x-model="kycEditForm.email" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Phone</label>
                                    <input type="text" x-model="kycEditForm.phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Location Edit -->
                        <div class="px-6 py-4 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Location</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Address</label>
                                    <input type="text" x-model="kycEditForm.address" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">City</label>
                                    <input type="text" x-model="kycEditForm.city" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Country</label>
                                    <select x-model="kycEditForm.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                        <option value="">Select...</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="DRC">DR Congo</option>
                                        <option value="Mozambique">Mozambique</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Identity Edit -->
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Identity Verification</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">ID Type</label>
                                    <select x-model="kycEditForm.id_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                        <option value="">Select...</option>
                                        <option value="national_id">National ID</option>
                                        <option value="passport">Passport</option>
                                        <option value="drivers_license">Driver's License</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">ID Number</label>
                                    <input type="text" x-model="kycEditForm.id_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Bank Edit -->
                        <div class="px-6 py-4 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Bank Settlement</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Bank Name</label>
                                    <input type="text" x-model="kycEditForm.bank_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Account Name</label>
                                    <input type="text" x-model="kycEditForm.bank_account_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Account Number</label>
                                    <input type="text" x-model="kycEditForm.bank_account_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">SWIFT Code</label>
                                    <input type="text" x-model="kycEditForm.bank_swift" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase mb-1 block">Branch</label>
                                    <input type="text" x-model="kycEditForm.bank_branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Document Upload -->
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Upload Documents</h4>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">ID Document (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycIdDocFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.id_document_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.id_document_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">Certificate of Incorporation (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycIncorpFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.certificate_of_incorporation_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.certificate_of_incorporation_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">Business License (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycBizLicFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.business_license_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.business_license_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">Tax Clearance (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycTaxFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.tax_clearance_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.tax_clearance_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">TIN Certificate (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycTinCertFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.tin_certificate_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.tin_certificate_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">Company Memorandum (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycMemoFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.company_memorandum_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.company_memorandum_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 uppercase mb-2">Company Resolution (JPG, PNG, PDF — max 5MB)</label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="kycResolutionFile = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <template x-if="kycAccount?.company_resolution_url">
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="text-xs text-green-600">Current:</span>
                                            <a :href="'{{ config('services.auth_service.url') }}' + kycAccount.company_resolution_url" target="_blank" class="text-xs text-blue-600 hover:underline">View existing &rarr;</a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="px-6 py-4 bg-blue-50 border-t border-blue-100 flex items-center justify-between">
                            <p class="text-xs text-blue-600">Changes will update the business KYC record directly.</p>
                            <button @click="saveKycEdit()" :disabled="kycEditSaving"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                                <svg x-show="kycEditSaving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="kycEditSaving ? 'Saving...' : 'Save KYC Changes'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Account Owner (always visible) -->
                    <div class="px-6 py-4 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Account Owner</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Owner Name</label>
                                <p class="text-sm font-medium text-gray-800" x-text="(kycAccount?.owner?.firstname && kycAccount?.owner?.lastname) ? (kycAccount.owner.firstname + ' ' + kycAccount.owner.lastname) : (kycAccount?.owner?.name || '—')"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Owner Email</label>
                                <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.owner?.email || '—'"></p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="text-xs text-gray-500 uppercase">Registered</label>
                            <p class="text-sm font-medium text-gray-800" x-text="formatDate(kycAccount?.created_at)"></p>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="px-6 py-4">
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Timeline</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase">KYC Submitted</label>
                                <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.kyc_submitted_at ? formatDate(kycAccount.kyc_submitted_at) : '—'"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">KYC Approved</label>
                                <p class="text-sm font-medium" :class="kycAccount?.kyc_approved_at ? 'text-green-600' : 'text-gray-400'" x-text="kycAccount?.kyc_approved_at ? formatDate(kycAccount.kyc_approved_at) : 'Not yet'"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase">Total Users</label>
                                <p class="text-sm font-medium text-gray-800" x-text="kycAccount?.users?.length || 0"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Paybill Assignment -->
                    <div class="px-6 py-4">
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Paybill Assignment</h4>
                        <div class="flex items-center gap-3">
                            <input type="text" x-model="kycPaybill" placeholder="Enter paybill number..."
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none font-mono">
                            <button @click="savePaybill()" :disabled="kycPaybillSaving"
                                class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                                <span x-show="!kycPaybillSaving">Save Paybill</span>
                                <span x-show="kycPaybillSaving">Saving...</span>
                            </button>
                            <span x-show="kycPaybillMsg" x-cloak class="text-sm text-green-600" x-text="kycPaybillMsg"></span>
                        </div>
                    </div>

                    <!-- API Rate Limit -->
                    <div class="px-6 py-4 bg-blue-50">
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">API Rate Limit</h4>
                        <p class="text-xs text-gray-500 mb-3">Control how many API requests per minute this account can make. Default: 60/min.</p>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <button @click="kycRateLimit = Math.max(1, kycRateLimit - 10)" class="w-8 h-8 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300 text-gray-700 font-bold">−</button>
                                <input type="number" x-model.number="kycRateLimit" min="1" max="10000"
                                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-blue-500 outline-none font-mono">
                                <button @click="kycRateLimit = Math.min(10000, kycRateLimit + 10)" class="w-8 h-8 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300 text-gray-700 font-bold">+</button>
                                <span class="text-sm text-gray-500">req/min</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="kycRateLimit = 10" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">10</button>
                                <button @click="kycRateLimit = 30" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200">30</button>
                                <button @click="kycRateLimit = 60" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200">60</button>
                                <button @click="kycRateLimit = 120" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">120</button>
                                <button @click="kycRateLimit = 300" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200">300</button>
                            </div>
                            <button @click="saveRateLimit()" :disabled="kycRateLimitSaving"
                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium">
                                <span x-show="!kycRateLimitSaving">Save</span>
                                <span x-show="kycRateLimitSaving">Saving...</span>
                            </button>
                        </div>
                        <div x-show="kycRateLimitMsg" x-cloak class="mt-2 text-sm" :class="kycRateLimitMsgType === 'success' ? 'text-green-600' : 'text-red-600'" x-text="kycRateLimitMsg"></div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="px-6 py-4 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Admin Notes</h4>
                        <textarea x-model="kycNotesText" rows="3" placeholder="Add notes about this KYC review..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 outline-none"></textarea>
                        <div class="mt-2 flex items-center justify-between">
                            <button @click="saveKycNotes()" :disabled="kycNoteSaving"
                                class="text-sm bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-300 disabled:opacity-50">
                                <span x-show="!kycNoteSaving">Save Notes</span>
                                <span x-show="kycNoteSaving">Saving...</span>
                            </button>
                            <span x-show="kycNoteMsg" x-cloak class="text-sm text-green-600" x-text="kycNoteMsg"></span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div x-show="!kycLoading && kycAccount" x-cloak class="px-6 py-4 bg-white border-t flex items-center justify-between">
                    <button @click="showKycModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Close</button>
                    <div class="flex space-x-3">
                        <button x-show="kycAccount?.status === 'pending'" @click="kycApprove()" :disabled="kycActionLoading"
                            class="px-5 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
                            <span x-show="!kycActionLoading">Approve KYC</span>
                            <span x-show="kycActionLoading">Processing...</span>
                        </button>
                        <button x-show="kycAccount?.status === 'pending'" @click="kycReject()" :disabled="kycActionLoading"
                            class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">Reject</button>
                        <button x-show="kycAccount?.status === 'active'" @click="updateAccountStatus(kycAccount.id, 'suspended'); showKycModal = false"
                            class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Suspend</button>
                        <button x-show="kycAccount?.status === 'suspended'" @click="updateAccountStatus(kycAccount.id, 'active'); showKycModal = false"
                            class="px-5 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Reactivate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- ==================== REVERSALS TAB ==================== -->
        <div x-show="activeTab === 'reversals'" class="mt-6">
            <!-- Filter -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <select x-model="revStatusFilter" @change="fetchAdminReversals()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="revLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!revLoading && adminReversals.length === 0" x-cloak class="p-8 text-center text-gray-500">No reversal requests found.</div>
                <div x-show="!revLoading && adminReversals.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reversal Ref</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Original Ref</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="rev in adminReversals" :key="rev.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-xs font-mono text-gray-700" x-text="rev.reversal_ref"></td>
                                        <td class="px-4 py-3 text-xs font-mono text-gray-600" x-text="rev.original_ref"></td>
                                        <td class="px-4 py-3 text-xs text-gray-600" x-text="accountName(rev.account_id)"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-green-100 text-green-800': rev.type==='collection','bg-blue-100 text-blue-800': rev.type==='disbursement'}"
                                                x-text="rev.type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="rev.operator"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800" x-text="formatAmount(rev.amount) + ' ' + (rev.currency || 'TZS')"></td>
                                        <td class="px-4 py-3 text-xs text-gray-600 max-w-[200px] truncate" x-text="rev.reason" :title="rev.reason"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-yellow-100 text-yellow-800': rev.status==='pending','bg-green-100 text-green-800': rev.status==='approved','bg-red-100 text-red-800': rev.status==='rejected'}"
                                                x-text="rev.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(rev.created_at)"></td>
                                        <td class="px-4 py-3">
                                            <div x-show="rev.status === 'pending'" class="flex space-x-2">
                                                <button @click="approveReversal(rev)" :disabled="revActionLoading"
                                                    class="text-xs bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700 font-medium disabled:opacity-50">Approve</button>
                                                <button @click="rejectReversal(rev.id)" :disabled="revActionLoading"
                                                    class="text-xs bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 font-medium disabled:opacity-50">Reject</button>
                                            </div>
                                            <span x-show="rev.status !== 'pending'" class="text-xs text-gray-400" x-text="rev.admin_notes || '—'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t flex items-center justify-between">
                        <p class="text-sm text-gray-500">Page <span x-text="revPagination.current_page || 1"></span> of <span x-text="revPagination.last_page || 1"></span></p>
                        <div class="flex space-x-2">
                            <button @click="revPage--; fetchAdminReversals()" :disabled="!revPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="revPage++; fetchAdminReversals()" :disabled="!revPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== OPERATORS TAB ==================== -->
        <div x-show="activeTab === 'operators'" class="mt-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Operator Connections</h3>
                <button x-show="user?.role === 'super_admin'" @click="openOperatorModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">+ Add Operator</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="opLoading" class="py-8 text-center text-gray-500 text-sm">Loading operators...</div>
                <div x-show="!opLoading && operatorsList.length === 0" x-cloak class="py-8 text-center text-gray-500 text-sm">No operators configured yet.</div>
                <div x-show="!opLoading && operatorsList.length > 0" x-cloak>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">API URL</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SP ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Merchant Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Callback URL</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="op in operatorsList" :key="op.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800" x-text="op.name"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800" x-text="op.code"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 font-mono text-xs max-w-[200px] truncate" x-text="op.api_url"></td>
                                    <td class="px-4 py-3 text-gray-600 text-xs" x-text="op.sp_id || '—'"></td>
                                    <td class="px-4 py-3 text-gray-600 text-xs" x-text="op.merchant_code || '—'"></td>
                                    <td class="px-4 py-3 text-gray-600 font-mono text-xs max-w-[200px] truncate" x-text="op.callback_url || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="op.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            x-text="op.status"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button x-show="user?.role === 'super_admin'" @click="openOperatorModal(op)" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                                            <button x-show="user?.role === 'super_admin'" @click="deleteOperator(op)" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== PAYMENT REQUESTS TAB ==================== -->
        <div x-show="activeTab === 'payments'" class="mt-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Payment Requests</h3>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-4">
                <input type="text" x-model="paySearch" @input.debounce.400ms="payPage=1; fetchPaymentRequests()" placeholder="Search ref, phone..."
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-200 outline-none w-64">
                <select x-model="payStatusFilter" @change="payPage=1; fetchPaymentRequests()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="timeout">Timeout</option>
                </select>
                <select x-model="payTypeFilter" @change="payPage=1; fetchPaymentRequests()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Types</option>
                    <option value="collection">Collection</option>
                    <option value="disbursement">Disbursement</option>
                </select>
                <select x-model="payOperatorFilter" @change="payPage=1; fetchPaymentRequests()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Operators</option>
                    <template x-for="op in operatorsList" :key="op.code">
                        <option :value="op.code" x-text="op.name"></option>
                    </template>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="payLoading" class="py-8 text-center text-gray-500 text-sm">Loading payment requests...</div>
                <div x-show="!payLoading && paymentRequests.length === 0" x-cloak class="py-8 text-center text-gray-500 text-sm">No payment requests found.</div>
                <div x-show="!payLoading && paymentRequests.length > 0" x-cloak class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Callback</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="pr in paymentRequests" :key="pr.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-mono text-xs text-gray-800" x-text="pr.request_ref"></div>
                                        <div x-show="pr.external_ref" class="text-[10px] text-gray-400" x-text="'Ext: ' + pr.external_ref"></div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-xs" x-text="accountName(pr.account_id)"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="pr.type === 'collection' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"
                                            x-text="pr.type"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs" x-text="pr.phone"></td>
                                    <td class="px-4 py-3 font-semibold text-gray-800" x-text="Number(pr.amount).toLocaleString() + ' ' + (pr.currency || 'TZS')"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="pr.operator_name"></td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500" x-text="pr.gateway_id || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': pr.status === 'pending',
                                                'bg-blue-100 text-blue-800': pr.status === 'processing',
                                                'bg-green-100 text-green-800': pr.status === 'completed',
                                                'bg-red-100 text-red-800': pr.status === 'failed',
                                                'bg-gray-100 text-gray-800': pr.status === 'timeout' || pr.status === 'cancelled',
                                            }"
                                            x-text="pr.status"></span>
                                        <div x-show="pr.error_message" class="text-[10px] text-red-500 mt-0.5 max-w-[150px] truncate" x-text="pr.error_message"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': pr.callback_status === 'pending',
                                                'bg-green-100 text-green-800': pr.callback_status === 'sent',
                                                'bg-red-100 text-red-800': pr.callback_status === 'failed',
                                            }"
                                            x-text="pr.callback_status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(pr.created_at)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 text-sm text-gray-600">
                        <span>Showing <span x-text="payPagination.from || 0"></span>-<span x-text="payPagination.to || 0"></span> of <span x-text="payPagination.total || 0"></span></span>
                        <div class="space-x-2">
                            <button @click="payPage--; fetchPaymentRequests()" :disabled="!payPagination.prev_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button @click="payPage++; fetchPaymentRequests()" :disabled="!payPagination.next_page_url" class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ADMIN USERS TAB (super_admin only) ==================== -->
        <div x-show="activeTab === 'admin_users' && user?.role === 'super_admin'" class="mt-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Admin Users</h3>
                <button @click="openAdminUserModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">+ Create Admin User</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div x-show="adminUsersLoading" class="p-8 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 mx-auto text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div x-show="!adminUsersLoading && adminUsersList.length === 0" x-cloak class="p-8 text-center text-gray-500">No admin users found.</div>
                <div x-show="!adminUsersLoading && adminUsersList.length > 0" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="au in adminUsersList" :key="au.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="(au.firstname && au.lastname) ? (au.firstname + ' ' + au.lastname) : au.name"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="au.email"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                                :class="{'bg-red-100 text-red-800': au.role==='super_admin', 'bg-blue-100 text-blue-800': au.role==='admin_user'}"
                                                x-text="au.role === 'super_admin' ? 'Super Admin' : 'Admin User'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <template x-if="au.role === 'super_admin'">
                                                <span class="text-xs text-green-600 font-medium">All Permissions</span>
                                            </template>
                                            <template x-if="au.role === 'admin_user'">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="perm in (au.permissions || [])" :key="perm">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-700"
                                                            x-text="adminPermLabels[perm] || perm"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(au.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <template x-if="au.role === 'admin_user'">
                                                <div class="flex space-x-2">
                                                    <button @click="openAdminUserModal(au)" class="text-xs bg-blue-500 text-white px-3 py-1.5 rounded hover:bg-blue-600 font-medium">Edit</button>
                                                    <button @click="deleteAdminUser(au)" class="text-xs bg-red-500 text-white px-3 py-1.5 rounded hover:bg-red-600 font-medium">Delete</button>
                                                </div>
                                            </template>
                                            <template x-if="au.role === 'super_admin'">
                                                <span class="text-xs text-gray-400">—</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ADMIN USER MODAL ==================== -->
        <div x-show="showAdminUserModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showAdminUserModal = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showAdminUserModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="editingAdminUser ? 'Edit Admin User' : 'Create Admin User'"></h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input type="text" x-model="adminUserForm.firstname" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="First name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                <input type="text" x-model="adminUserForm.lastname" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Last name">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" x-model="adminUserForm.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="admin@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" x-text="editingAdminUser ? 'New Password (leave blank to keep)' : 'Password *'"></label>
                            <input type="password" x-model="adminUserForm.password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Min 8 characters">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="[key, label] in Object.entries(adminPermLabels)" :key="key">
                                    <label class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer hover:bg-gray-50"
                                        :class="adminUserForm.permissions.includes(key) ? 'border-red-300 bg-red-50' : 'border-gray-200'">
                                        <input type="checkbox" :value="key" @change="toggleAdminPerm(key)"
                                            :checked="adminUserForm.permissions.includes(key)"
                                            class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                        <span class="text-sm text-gray-700" x-text="label"></span>
                                    </label>
                                </template>
                            </div>
                            <button @click="adminUserForm.permissions = Object.keys(adminPermLabels)" class="mt-2 text-xs text-red-600 hover:underline">Select All</button>
                            <button @click="adminUserForm.permissions = []" class="mt-2 ml-3 text-xs text-gray-500 hover:underline">Clear All</button>
                        </div>
                    </div>

                    <div x-show="adminUserError" class="mt-3 text-sm text-red-600 bg-red-50 rounded-lg p-2" x-text="adminUserError"></div>
                    <div x-show="adminUserSuccess" class="mt-3 text-sm text-green-600 bg-green-50 rounded-lg p-2" x-text="adminUserSuccess"></div>

                    <div class="flex justify-end space-x-3 mt-5">
                        <button @click="showAdminUserModal = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button @click="saveAdminUser()" :disabled="adminUserSaving"
                            class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 font-medium">
                            <span x-show="!adminUserSaving" x-text="editingAdminUser ? 'Update' : 'Create'"></span>
                            <span x-show="adminUserSaving">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Direct Reversal Modal -->
    <div x-show="showDirectRevModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showDirectRevModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDirectRevModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Reverse Transaction</h3>
                <div class="mb-3 text-sm text-gray-600">
                    <p><strong>Reference:</strong> <span x-text="directRevTxn?.transaction_ref"></span></p>
                    <p><strong>Type:</strong> <span class="capitalize" x-text="directRevTxn?.type"></span></p>
                    <p><strong>Amount:</strong> <span x-text="formatAmount(directRevTxn?.amount || 0) + ' ' + (directRevTxn?.currency || 'TZS')"></span></p>
                    <p><strong>Operator:</strong> <span x-text="directRevTxn?.operator"></span></p>
                    <p class="mt-2 text-xs text-purple-700 bg-purple-50 rounded-lg p-2">
                        <span x-show="directRevTxn?.type === 'collection'">This will <strong>debit</strong> the collection wallet by the net amount.</span>
                        <span x-show="directRevTxn?.type === 'disbursement'">This will <strong>credit</strong> the disbursement wallet by the net amount.</span>
                    </p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for reversal *</label>
                    <textarea x-model="directRevReason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" rows="3" placeholder="Enter reason for reversal..."></textarea>
                </div>
                <div x-show="directRevError" class="mb-3 text-sm text-red-600 bg-red-50 rounded-lg p-2" x-text="directRevError"></div>
                <div x-show="directRevSuccess" class="mb-3 text-sm text-green-600 bg-green-50 rounded-lg p-2" x-text="directRevSuccess"></div>
                <div class="flex justify-end space-x-3">
                    <button @click="showDirectRevModal = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button @click="submitDirectReversal()" :disabled="directRevLoading || !directRevReason.trim()"
                        class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium">
                        <span x-show="!directRevLoading">Confirm Reversal</span>
                        <span x-show="directRevLoading">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Operator Modal -->
    <div x-show="showOperatorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showOperatorModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showOperatorModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="editingOperator ? 'Edit Operator' : 'Add Operator'"></h3>

                <div x-show="opError" class="mb-3 text-sm text-red-600 bg-red-50 rounded-lg p-2" x-text="opError"></div>
                <div x-show="opSuccess" class="mb-3 text-sm text-green-600 bg-green-50 rounded-lg p-2" x-text="opSuccess"></div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" x-model="opForm.name" placeholder="M-Pesa" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code *</label>
                            <input type="text" x-model="opForm.code" placeholder="mpesa" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">API Base URL *</label>
                        <input type="url" x-model="opForm.api_url" placeholder="https://operator.example.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">SP ID</label>
                            <input type="text" x-model="opForm.sp_id" placeholder="100100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Merchant Code</label>
                            <input type="text" x-model="opForm.merchant_code" placeholder="1001001" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">SP Password</label>
                        <input type="password" x-model="opForm.sp_password" :placeholder="editingOperator ? '(leave blank to keep current)' : 'Enter SP password'" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Collection Path</label>
                            <input type="text" x-model="opForm.collection_path" placeholder="/api/v1/ussd-push" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Disbursement Path</label>
                            <input type="text" x-model="opForm.disbursement_path" placeholder="/api/v1/b2c" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Our Callback URL (shared with operator)</label>
                        <input type="url" x-model="opForm.callback_url" placeholder="https://api.payin.com/api/callback/mpesa" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        <p class="text-[10px] text-gray-400 mt-1">The URL you register with operator. Format: https://yourhost/api/callback/{operator_code}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">API Version</label>
                            <input type="text" x-model="opForm.api_version" placeholder="5.0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                            <select x-model="opForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="showOperatorModal = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button @click="saveOperator()" :disabled="opSaving"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 font-medium">
                        <span x-show="!opSaving" x-text="editingOperator ? 'Update' : 'Create'"></span>
                        <span x-show="opSaving">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

        <!-- ==================== ERROR LOGS TAB (super_admin only) ==================== -->
        <div x-show="activeTab === 'logs'" x-cloak class="mt-6">
            <!-- Controls -->
            <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <select x-model="logService" @change="fetchLogs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium">
                        <option value="auth">Auth Service</option>
                        <option value="payment">Payment Service</option>
                        <option value="transaction">Transaction Service</option>
                        <option value="wallet">Wallet Service</option>
                        <option value="settlement">Settlement Service</option>
                    </select>
                    <select x-model="logLevel" @change="fetchLogs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Levels</option>
                        <option value="emergency">Emergency</option>
                        <option value="alert">Alert</option>
                        <option value="critical">Critical</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="notice">Notice</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" x-model="logSearch" @input.debounce.500ms="fetchLogs()"
                            placeholder="Search logs..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <select x-model="logLines" @change="fetchLogs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="50">Last 50</option>
                        <option value="100">Last 100</option>
                        <option value="200" selected>Last 200</option>
                        <option value="500">Last 500</option>
                    </select>
                    <button @click="fetchLogs()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                    <button @click="clearLogs()" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Clear Logs</button>
                </div>
                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                    <span x-show="logFileSize">File size: <strong x-text="logFileSize"></strong></span>
                    <span x-show="logTotalEntries">Showing: <strong x-text="logTotalEntries"></strong> entries</span>
                    <span x-show="logAutoRefresh" class="text-green-600 font-medium">Auto-refresh: ON (30s)</span>
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" x-model="logAutoRefresh" @change="toggleLogAutoRefresh()" class="rounded text-blue-600">
                        Auto-refresh
                    </label>
                </div>
            </div>

            <!-- Log Entries -->
            <div class="bg-gray-900 rounded-xl shadow-sm border border-gray-700 overflow-hidden">
                <div x-show="logLoading" class="p-8 text-center text-gray-400">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="mt-2">Loading logs...</p>
                </div>
                <div x-show="!logLoading && logEntries.length === 0" x-cloak class="p-8 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    No log entries found.
                </div>
                <div x-show="logError" x-cloak class="p-4 text-red-400 text-sm" x-text="logError"></div>
                <div x-show="!logLoading && logEntries.length > 0" x-cloak class="max-h-[70vh] overflow-y-auto font-mono text-xs">
                    <template x-for="(entry, idx) in logEntries" :key="idx">
                        <div class="border-b border-gray-800 hover:bg-gray-800/50 cursor-pointer" @click="entry._open = !entry._open">
                            <div class="px-4 py-2 flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :class="{
                                        'bg-red-900 text-red-300': ['emergency','alert','critical','error'].includes(entry.level),
                                        'bg-yellow-900 text-yellow-300': entry.level === 'warning',
                                        'bg-blue-900 text-blue-300': entry.level === 'info' || entry.level === 'notice',
                                        'bg-gray-700 text-gray-300': entry.level === 'debug'
                                    }" x-text="entry.level"></span>
                                <span class="shrink-0 text-gray-500" x-text="entry.timestamp"></span>
                                <span class="text-gray-200 break-all" x-text="entry.message.substring(0, 200) + (entry.message.length > 200 ? '...' : '')"></span>
                            </div>
                            <div x-show="entry._open" x-cloak class="px-4 pb-3">
                                <div x-show="entry.message.length > 200" class="text-gray-300 mb-2 whitespace-pre-wrap break-all" x-text="entry.message"></div>
                                <div x-show="entry.context" class="bg-gray-950 rounded p-3 text-gray-400 whitespace-pre-wrap break-all max-h-60 overflow-y-auto" x-text="entry.context"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ==================== MAIL CONFIG TAB ==================== -->
        <div x-show="activeTab === 'mail_config'" x-cloak class="mt-6">
            <div class="bg-white rounded-xl shadow-md border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Mail Configuration</h3>
                <p class="text-sm text-gray-500 mb-6">Configure SMTP settings for sending emails (signup welcome, password reset, KYC notifications).</p>

                <!-- Status Messages -->
                <div x-show="mailMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="mailMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="mailMsg"></div>

                <div x-show="mailLoading" class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div></div>

                <form x-show="!mailLoading" @submit.prevent="saveMailConfig()" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Mailer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mail Driver</label>
                            <select x-model="mailForm.MAIL_MAILER" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="smtp">SMTP</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="log">Log (debug only)</option>
                            </select>
                        </div>
                        <!-- Host -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                            <input type="text" x-model="mailForm.MAIL_HOST" placeholder="smtp.mailgun.org" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- Port -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                            <input type="number" x-model="mailForm.MAIL_PORT" placeholder="587" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- Encryption -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                            <select x-model="mailForm.MAIL_ENCRYPTION" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="null">None</option>
                            </select>
                        </div>
                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Username</label>
                            <input type="text" x-model="mailForm.MAIL_USERNAME" placeholder="postmaster@mg.example.com" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                            <input type="password" x-model="mailForm.MAIL_PASSWORD" placeholder="••••••••" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- From Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Address</label>
                            <input type="email" x-model="mailForm.MAIL_FROM_ADDRESS" placeholder="noreply@payin.co.tz" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- From Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                            <input type="text" x-model="mailForm.MAIL_FROM_NAME" placeholder="Payin" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 pt-2">
                        <button type="submit" :disabled="mailSaving" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50">
                            <span x-show="!mailSaving">Save Configuration</span>
                            <span x-show="mailSaving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Test Email -->
            <div class="bg-white rounded-xl shadow-md border p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Send Test Email</h3>
                <p class="text-sm text-gray-500 mb-4">Verify your mail configuration by sending a test email.</p>

                <div x-show="testMailMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="testMailMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="testMailMsg"></div>

                <form @submit.prevent="sendTestEmail()" class="flex items-end space-x-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                        <input type="email" x-model="testMailAddress" placeholder="you@example.com" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" :disabled="testMailSending" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50 whitespace-nowrap">
                        <span x-show="!testMailSending">Send Test</span>
                        <span x-show="testMailSending">Sending...</span>
                    </button>
                </form>
            </div>

            <!-- Email Templates -->
            <div class="bg-white rounded-xl shadow-md border p-6 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Email Templates</h3>
                        <p class="text-sm text-gray-500">Customize the content of each notification email. Use <code class="bg-gray-100 px-1 rounded text-xs">@{{name}}</code> for placeholders.</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button @click="showNewTplForm = !showNewTplForm" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">+ New Template</button>
                        <button @click="fetchEmailTemplates()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Refresh</button>
                    </div>
                </div>

                <!-- Create New Template Form -->
                <div x-show="showNewTplForm" x-cloak class="mb-6 border border-blue-200 rounded-lg p-4 bg-blue-50">
                    <h4 class="font-medium text-gray-800 mb-3">Create New Template</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Template Key <span class="text-gray-400">(lowercase, underscores only)</span></label>
                            <input type="text" x-model="newTplForm.key" placeholder="e.g. operator_downtime" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Display Name</label>
                            <input type="text" x-model="newTplForm.name" placeholder="e.g. Operator Downtime Notice" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                            <input type="text" x-model="newTplForm.subject" placeholder="e.g. Payin — Service Notice" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Greeting</label>
                            <input type="text" x-model="newTplForm.greeting" placeholder="e.g. Hello @{{name}}," class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Body</label>
                        <textarea x-model="newTplForm.body" rows="4" placeholder="Enter the email body content..." class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Button Text <span class="text-gray-400">(optional)</span></label>
                            <input type="text" x-model="newTplForm.action_text" placeholder="e.g. View Status" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Button URL <span class="text-gray-400">(optional)</span></label>
                            <input type="text" x-model="newTplForm.action_url" placeholder="https://login.payin.co.tz/dashboard" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Footer</label>
                            <input type="text" x-model="newTplForm.footer" placeholder="— Payin Team" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button @click="createEmailTemplate()" :disabled="tplSaving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50">
                            <span x-show="!tplSaving">Create Template</span>
                            <span x-show="tplSaving">Creating...</span>
                        </button>
                        <button @click="showNewTplForm = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">Cancel</button>
                    </div>
                </div>

                <div x-show="tplMsg" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="tplMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="tplMsg"></div>

                <div x-show="tplLoading" class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div></div>

                <!-- Template cards -->
                <div x-show="!tplLoading" class="space-y-4">
                    <template x-for="tpl in emailTemplates" :key="tpl.id">
                        <div class="border rounded-lg overflow-hidden">
                            <!-- Template header -->
                            <button @click="tpl._open = !tpl._open" class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <span class="w-2 h-2 rounded-full" :class="tpl.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                                    <span class="font-medium text-gray-800 text-sm" x-text="tpl.name"></span>
                                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded" x-text="tpl.key"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500" x-text="tpl.is_active ? 'Active' : 'Disabled'"></span>
                                    <svg :class="tpl._open ? 'rotate-180' : ''" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>

                            <!-- Template editor (collapsible) -->
                            <div x-show="tpl._open" x-cloak class="p-4 border-t space-y-4">
                                <!-- Placeholders info -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                                    <strong>Available placeholders:</strong>
                                    <span x-show="tpl.key === 'welcome'"><code>@{{name}}</code></span>
                                    <span x-show="tpl.key === 'password_reset'"><code>@{{name}}</code>, <code>@{{code}}</code></span>
                                    <span x-show="tpl.key === 'kyc_approved'"><code>@{{name}}</code></span>
                                    <span x-show="tpl.key === 'kyc_rejected'"><code>@{{name}}</code>, <code>@{{reason}}</code></span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                                        <input type="text" x-model="tpl.subject" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Greeting</label>
                                        <input type="text" x-model="tpl.greeting" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Body <span class="text-gray-400">(use blank lines for paragraphs, **bold** for emphasis)</span></label>
                                    <textarea x-model="tpl.body" rows="6" class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Button Text <span class="text-gray-400">(optional)</span></label>
                                        <input type="text" x-model="tpl.action_text" placeholder="e.g. Go to Dashboard" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Button URL <span class="text-gray-400">(optional)</span></label>
                                        <input type="text" x-model="tpl.action_url" placeholder="https://login.payin.co.tz/dashboard" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Footer / Salutation</label>
                                    <input type="text" x-model="tpl.footer" placeholder="— Payin Team" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center space-x-2 text-sm">
                                        <input type="checkbox" x-model="tpl.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>Active</span>
                                    </label>
                                </div>

                                <!-- Preview -->
                                <div class="border rounded-lg overflow-hidden">
                                    <div class="bg-gray-100 px-4 py-2 text-xs font-medium text-gray-600 border-b">Preview</div>
                                    <div class="p-4 bg-white text-sm space-y-2">
                                        <div class="font-semibold text-gray-800" x-text="tpl.greeting.replace(/\{\{name\}\}/g, 'John Doe').replace(/\{\{code\}\}/g, '123456').replace(/\{\{reason\}\}/g, 'Document unclear')"></div>
                                        <template x-for="line in tpl.body.replace(/\{\{name\}\}/g, 'John Doe').replace(/\{\{code\}\}/g, '123456').replace(/\{\{reason\}\}/g, '**Reason:** Document unclear').split('\n')" :key="Math.random()">
                                            <p class="text-gray-600" x-show="line.trim()" x-html="line.trim().replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')"></p>
                                        </template>
                                        <div x-show="tpl.action_text && tpl.action_url" class="pt-2">
                                            <span class="inline-block bg-blue-600 text-white px-4 py-2 rounded text-xs font-medium" x-text="tpl.action_text"></span>
                                        </div>
                                        <div class="text-gray-500 text-xs pt-2" x-text="tpl.footer"></div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-3 pt-2 flex-wrap gap-y-2">
                                    <button @click="saveEmailTemplate(tpl)" :disabled="tplSaving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50">
                                        <span x-show="!tplSaving">Save Template</span>
                                        <span x-show="tplSaving">Saving...</span>
                                    </button>
                                    <button @click="if(confirm('Reset this template to its default content?')) resetEmailTemplate(tpl)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                                        Reset to Default
                                    </button>
                                    <button @click="openSendModal(tpl)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                        &#9993; Send
                                    </button>
                                    <template x-if="!['welcome','password_reset','kyc_approved','kyc_rejected'].includes(tpl.key)">
                                        <button @click="if(confirm('Delete this custom template permanently?')) deleteEmailTemplate(tpl)" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                            Delete
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ===== Send Template Modal ===== -->
        <div x-show="showSendModal" x-cloak class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" @click.self="showSendModal = false">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Send Email Notification</h3>
                <p class="text-sm text-gray-500 mb-4" x-text="'Template: ' + (sendTplName || '')"></p>

                <!-- Recipient Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Send To</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" x-model="sendTo" value="emails" class="text-blue-600">
                            <span class="text-sm">Specific email addresses</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" x-model="sendTo" value="all_users" class="text-blue-600">
                            <span class="text-sm">All registered users</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" x-model="sendTo" value="all_owners" class="text-blue-600">
                            <span class="text-sm">All account owners</span>
                        </label>
                    </div>
                </div>

                <!-- Specific Emails Input -->
                <div x-show="sendTo === 'emails'" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Addresses</label>
                    <textarea x-model="sendEmails" rows="3" placeholder="Enter emails separated by commas..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- Warning for broadcast -->
                <div x-show="sendTo !== 'emails'" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">&#9888; This will send the email to <strong x-text="sendTo === 'all_users' ? 'ALL registered users' : 'ALL account owners'"></strong>. Please confirm before sending.</p>
                </div>

                <!-- Result Message -->
                <div x-show="sendResult" class="mb-4 p-3 rounded-lg text-sm" :class="sendResultType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="sendResult"></div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <button @click="showSendModal = false; sendResult = '';" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">Cancel</button>
                    <button @click="sendTemplateNotification()" :disabled="sendLoading" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50">
                        <span x-show="!sendLoading">Send Now</span>
                        <span x-show="sendLoading">Sending...</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== ADD BUSINESS MODAL ==================== -->
    <div x-show="showAddBusinessModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showAddBusinessModal = false">
        <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
            <div class="fixed inset-0 bg-black/50" @click="showAddBusinessModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl z-10">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b bg-green-600 rounded-t-xl">
                    <h3 class="text-lg font-bold text-white">Add New Business</h3>
                    <button @click="showAddBusinessModal = false" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Messages -->
                <div x-show="addBizMsg" x-cloak class="px-6 pt-4">
                    <div class="p-3 rounded-lg text-sm" :class="addBizMsgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="addBizMsg"></div>
                </div>

                <!-- Form -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-5">
                    <!-- Owner Info -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Account Owner</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addBizForm.firstname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addBizForm.lastname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs text-gray-500 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" x-model="addBizForm.email" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="Owner email — login credentials will be sent here">
                        </div>
                    </div>

                    <!-- Business Info -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Business Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Business Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addBizForm.business_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Business Type</label>
                                <select x-model="addBizForm.business_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
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
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Registration Number</label>
                                <input type="text" x-model="addBizForm.registration_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">TIN Number</label>
                                <input type="text" x-model="addBizForm.tin_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Phone</label>
                                <input type="text" x-model="addBizForm.phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="+255...">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Paybill</label>
                                <input type="text" x-model="addBizForm.paybill" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs text-gray-500 mb-1">Address</label>
                            <input type="text" x-model="addBizForm.address" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="Street / P.O. Box">
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">City</label>
                                <input type="text" x-model="addBizForm.city" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Country</label>
                                <select x-model="addBizForm.country" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
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
                        </div>
                    </div>

                    <!-- Identity -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Identity Verification</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ID Type</label>
                                <select x-model="addBizForm.id_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                                    <option value="">Select...</option>
                                    <option value="national_id">National ID (NIDA)</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers_license">Driver's License</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ID Number</label>
                                <input type="text" x-model="addBizForm.id_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Bank Settlement</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Bank Name</label>
                                <input type="text" x-model="addBizForm.bank_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Account Name</label>
                                <input type="text" x-model="addBizForm.bank_account_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Account Number</label>
                                <input type="text" x-model="addBizForm.bank_account_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">SWIFT Code</label>
                                <input type="text" x-model="addBizForm.bank_swift" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Branch</label>
                                <input type="text" x-model="addBizForm.bank_branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Documents (JPG, PNG, PDF — max 5MB each)</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ID Document</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizIdDocFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Certificate of Incorporation</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizIncorpFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Business License</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizLicFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tax Clearance</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizTaxFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">TIN Certificate</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizTinCertFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Company Memorandum</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizMemoFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Company Resolution</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="addBizResolutionFile = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3 border-b pb-2">Account Status</h4>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="addBizForm.status" value="pending" class="text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700">Pending (requires KYC review)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="addBizForm.status" value="active" class="text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700">Active (pre-approved)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex items-center justify-between">
                    <p class="text-xs text-gray-500">Login credentials will be emailed to the business owner.</p>
                    <div class="flex items-center gap-3">
                        <button @click="showAddBusinessModal = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-100">Cancel</button>
                        <button @click="submitAddBusiness()" :disabled="addBizSaving"
                            class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="addBizSaving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="addBizSaving ? 'Creating...' : 'Create Business'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function adminPanel() {
    return {
        user: null,
        activeTab: '',
        stats: { total_accounts: 0, active_accounts: 0, suspended_accounts: 0, pending_accounts: 0, total_users: 0 },

        // Accounts
        accounts: [], accLoading: false, accSearch: '', accStatusFilter: '', accPage: 1, accPagination: {},

        // Users
        adminUsers: [], usrLoading: false, usrSearch: '', usrRoleFilter: '', usrPage: 1, usrPagination: {},

        // Transactions (admin)
        adminTransactions: [], txnLoading: false, txnSearch: '', txnStatusFilter: '', txnTypeFilter: '', txnOperatorFilter: '', txnPage: 1, txnPagination: {},

        // Wallets (admin)
        walletData: {}, wltLoading: false,

        // Settlements (admin)
        adminSettlements: [], stlLoading: false, stlSearch: '', stlStatusFilter: '', stlPage: 1, stlPagination: {},
        stlActionLoading: false, stlMsg: '', stlMsgType: 'success', pendingSettlementsCount: 0,

        // KYC Modal
        showKycModal: false, kycAccount: null, kycLoading: false,
        kycNotesText: '', kycNoteSaving: false, kycNoteMsg: '',
        kycPaybill: '', kycPaybillSaving: false, kycPaybillMsg: '',
        kycRateLimit: 60, kycRateLimitSaving: false, kycRateLimitMsg: '', kycRateLimitMsgType: 'success',
        kycActionLoading: false,
        // KYC Edit
        kycEditing: false, kycEditSaving: false, kycEditMsg: '', kycEditMsgType: 'success',
        kycIdDocFile: null, kycBizLicFile: null, kycIncorpFile: null, kycTaxFile: null,
        kycTinCertFile: null, kycMemoFile: null, kycResolutionFile: null,
        kycEditForm: {
            business_name: '', business_type: '', registration_number: '', tin_number: '',
            email: '', phone: '', address: '', city: '', country: '',
            id_type: '', id_number: '',
            bank_name: '', bank_account_name: '', bank_account_number: '', bank_swift: '', bank_branch: ''
        },

        // Account name lookup
        accountMap: {},

        // Add Business
        showAddBusinessModal: false, addBizSaving: false, addBizMsg: '', addBizMsgType: 'success',
        addBizIdDocFile: null, addBizIncorpFile: null, addBizLicFile: null, addBizTaxFile: null,
        addBizTinCertFile: null, addBizMemoFile: null, addBizResolutionFile: null,
        addBizForm: {
            firstname: '', lastname: '', email: '', business_name: '', business_type: '',
            registration_number: '', tin_number: '', phone: '', paybill: '',
            address: '', city: '', country: 'Tanzania',
            id_type: '', id_number: '',
            bank_name: '', bank_account_name: '', bank_account_number: '', bank_swift: '', bank_branch: '',
            status: 'active'
        },

        // Charges
        charges: [], chargesLoading: false, chargeLoading: false, chargeMsg: '', chargeMsgType: 'success',
        chargeOperatorFilter: '', chargeStatusFilter: '',
        chargeForm: { name: '', account_id: '', operator: 'all', transaction_type: 'all', charge_type: 'fixed', charge_value: '', min_amount: 0, max_amount: 0, applies_to: 'platform' },

        // Charge filters
        chargeAccountFilter: '',

        // Charge Revenue
        chargeRevenue: {},

        // IP Whitelist (admin)
        adminIpList: [], ipListLoading: false, ipSearch: '', ipStatusFilter: '', pendingIpCount: 0,

        // Internal Transfers (admin)
        adminInternalTransfers: [], trfLoading: false, trfStatusFilter: '', trfAccountFilter: '',
        trfActionLoading: false, trfMsg: '', trfMsgType: 'success', pendingTransferCount: 0,

        // Reversals (admin)
        adminReversals: [], revLoading: false, revStatusFilter: '', revPage: 1, revPagination: {},
        revActionLoading: false, pendingReversalCount: 0,

        // Direct reversal
        showDirectRevModal: false, directRevTxn: null, directRevReason: '', directRevLoading: false, directRevError: '', directRevSuccess: '',

        // Operators (admin)
        operatorsList: [], opLoading: false,
        showOperatorModal: false, editingOperator: null,
        opForm: { name: '', code: '', api_url: '', sp_id: '', merchant_code: '', sp_password: '', collection_path: '/collection', disbursement_path: '/disbursement', callback_url: '', api_version: '5.0', status: 'active' },
        opSaving: false, opError: '', opSuccess: '',

        // Payment Requests (admin)
        paymentRequests: [], payLoading: false, paySearch: '', payStatusFilter: '', payTypeFilter: '', payOperatorFilter: '', payPage: 1, payPagination: {},

        // Admin Users management (super_admin only)
        adminUsersList: [], adminUsersLoading: false,
        showAdminUserModal: false, editingAdminUser: null,
        adminUserForm: { firstname: '', lastname: '', email: '', password: '', permissions: [] },
        adminUserSaving: false, adminUserError: '', adminUserSuccess: '',
        adminPermLabels: {
            admin_overview: 'Overview & Stats',
            admin_accounts: 'Accounts & KYC',
            admin_transactions: 'View Transactions',
            admin_wallets: 'View Wallets',
            admin_settlements: 'Approve Settlements',
            admin_charges: 'Manage Charges',
            admin_ip_whitelist: 'IP Whitelist',
            admin_transfers: 'Approve Transfers',
            admin_users: 'Users & Reset Password',
            admin_reversals: 'Reversals',
            admin_operators: 'Operators & API',
            admin_payments: 'Payment Requests',
        },

        // Error Logs (super_admin only)
        logEntries: [], logLoading: false, logService: 'auth', logLevel: '', logSearch: '', logLines: '200',
        logFileSize: '', logTotalEntries: 0, logError: '', logAutoRefresh: false, logAutoRefreshTimer: null,

        // Mail Config (super_admin only)
        mailForm: { MAIL_MAILER: 'smtp', MAIL_HOST: '', MAIL_PORT: '587', MAIL_USERNAME: '', MAIL_PASSWORD: '', MAIL_ENCRYPTION: 'tls', MAIL_FROM_ADDRESS: '', MAIL_FROM_NAME: 'Payin' },
        mailLoading: false, mailSaving: false, mailMsg: '', mailMsgType: 'success',
        testMailAddress: '', testMailSending: false, testMailMsg: '', testMailMsgType: 'success',

        // Email Templates
        emailTemplates: [], tplLoading: false, tplSaving: false, tplMsg: '', tplMsgType: 'success',
        showNewTplForm: false,
        newTplForm: { key: '', name: '', subject: '', greeting: 'Hello @{{name}},', body: '', action_text: '', action_url: '', footer: '— Payin Team' },
        // Send modal
        showSendModal: false, sendTplId: null, sendTplName: '', sendTo: 'emails', sendEmails: '', sendLoading: false, sendResult: '', sendResultType: 'success',
        logServiceUrls: {
            auth: '{{ config("services.auth_service.url") }}/api/admin/logs',
            payment: '/api/admin/logs',
            transaction: '{{ config("services.transaction_service.url") }}/api/admin/logs',
            wallet: '{{ config("services.wallet_service.url") }}/api/admin/logs',
            settlement: '{{ config("services.settlement_service.url") }}/api/admin/logs',
        },

        appReady: false,

        init() {
            const token = localStorage.getItem('auth_token');
            if (!token) { window.location.href = '/login'; return; }
            this.user = JSON.parse(localStorage.getItem('auth_user') || 'null');
            if (!this.user || !['super_admin', 'admin_user'].includes(this.user.role)) {
                window.location.href = '/dashboard';
                return;
            }
            this.adminPerms = this.user.admin_permissions || [];
            // Set default tab to first permitted tab
            const tabOrder = ['overview', 'accounts', 'transactions', 'wallets', 'settlements', 'charges', 'ipwhitelist', 'transfers', 'operators', 'payments', 'users', 'reversals'];
            const permMap = { overview: 'admin_overview', accounts: 'admin_accounts', transactions: 'admin_transactions', wallets: 'admin_wallets', settlements: 'admin_settlements', charges: 'admin_charges', ipwhitelist: 'admin_ip_whitelist', transfers: 'admin_transfers', operators: 'admin_operators', payments: 'admin_payments', users: 'admin_users', reversals: 'admin_reversals' };
            this.activeTab = tabOrder.find(t => this.hasPerm(permMap[t])) || 'overview';
            if (this.hasPerm('admin_overview')) this.fetchStats();
            this.fetchAccountMap();
            if (this.hasPerm('admin_settlements')) this.fetchPendingSettlementsCount();
            if (this.hasPerm('admin_overview')) this.fetchChargeRevenue();
            if (this.hasPerm('admin_ip_whitelist')) this.fetchPendingIpCount();
            if (this.hasPerm('admin_transfers')) this.fetchPendingTransferCount();
            if (this.hasPerm('admin_reversals')) this.fetchPendingReversalCount();
            this.appReady = true;
            this.$nextTick(() => document.dispatchEvent(new Event('alpine:initialized')));
        },

        // Permission helper
        adminPerms: [],
        hasPerm(p) { return this.user?.role === 'super_admin' || this.adminPerms.includes(p); },

        getHeaders() {
            return { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },

        handleUnauth(res) {
            if (res.status === 401) { localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); window.location.href = '/login'; return true; }
            return false;
        },

        formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); },

        accountName(id) { return this.accountMap[id] || ('Account #' + id); },

        async fetchAccountMap() {
            try {
                let page = 1, hasMore = true;
                while (hasMore) {
                    const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts?page=${page}&per_page=100`, { headers: this.getHeaders() });
                    if (!res.ok) break;
                    const data = await res.json();
                    (data.data || []).forEach(a => { this.accountMap[a.id] = a.business_name; });
                    hasMore = data.next_page_url !== null;
                    page++;
                }
            } catch (e) { console.error(e); }
        },

        async fetchStats() {
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/stats', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) this.stats = await res.json();
            } catch (e) { console.error(e); }
        },

        async fetchChargeRevenue() {
            try {
                const res = await fetch('{{ config("services.transaction_service.url") }}/api/admin/charge-revenue', { headers: this.getHeaders() });
                if (res.ok) this.chargeRevenue = await res.json();
            } catch (e) { console.error(e); }
        },

        async fetchAccounts() {
            this.accLoading = true;
            try {
                let url = `{{ config("services.auth_service.url") }}/api/admin/accounts?page=${this.accPage}`;
                if (this.accSearch) url += `&search=${encodeURIComponent(this.accSearch)}`;
                if (this.accStatusFilter) url += `&status=${this.accStatusFilter}`;

                // Fetch accounts (required) and wallets (optional) separately
                const accRes = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(accRes)) return;
                const data = await accRes.json();

                // Wallet fetch is optional — don't let it break the accounts list
                let walletMap = {};
                try {
                    const walRes = await fetch(`{{ config("services.wallet_service.url") }}/api/admin/wallets`, { headers: this.getHeaders() });
                    if (walRes.ok) {
                        const walData = await walRes.json();
                        (walData.accounts || []).forEach(w => {
                            walletMap[w.account_id] = { collection: w.collection_total, disbursement: w.disbursement_total };
                        });
                    }
                } catch (walErr) { console.warn('Wallet service unavailable:', walErr); }

                this.accounts = (data.data || []).map(acc => ({
                    ...acc,
                    collection_balance: walletMap[acc.id]?.collection || '0.00',
                    disbursement_balance: walletMap[acc.id]?.disbursement || '0.00'
                }));
                this.accPagination = { current_page: data.current_page, last_page: data.last_page, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) { console.error(e); }
            this.accLoading = false;
        },

        async updateAccountStatus(id, status) {
            const label = status === 'active' ? 'approve/activate' : status;
            if (!confirm(`Are you sure you want to ${label} this account?`)) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${id}/status`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ status })
                });
                if (res.ok) { this.fetchAccounts(); this.fetchStats(); }
            } catch (e) { console.error(e); }
        },

        async fetchUsers() {
            this.usrLoading = true;
            try {
                let url = `{{ config("services.auth_service.url") }}/api/admin/users?page=${this.usrPage}`;
                if (this.usrSearch) url += `&search=${encodeURIComponent(this.usrSearch)}`;
                if (this.usrRoleFilter) url += `&role=${this.usrRoleFilter}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminUsers = data.data || [];
                this.usrPagination = { current_page: data.current_page, last_page: data.last_page, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) { console.error(e); }
            this.usrLoading = false;
        },

        async adminResetPassword(userId, userName) {
            if (!confirm(`Reset password for ${userName}? A new random password will be generated.`)) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/users/${userId}/reset-password`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (res.ok) {
                    alert(`Password reset!\n\nNew password for ${userName}:\n${data.new_password}\n\nPlease share this securely with the user.`);
                } else {
                    alert(data.message || 'Failed to reset password.');
                }
            } catch (e) { console.error(e); alert('Error resetting password.'); }
        },

        // ---- Admin Transactions ----
        async fetchAdminTransactions() {
            this.txnLoading = true;
            try {
                let url = `{{ config("services.transaction_service.url") }}/api/admin/transactions?page=${this.txnPage}`;
                if (this.txnSearch) url += `&search=${encodeURIComponent(this.txnSearch)}`;
                if (this.txnStatusFilter) url += `&status=${this.txnStatusFilter}`;
                if (this.txnTypeFilter) url += `&type=${this.txnTypeFilter}`;
                if (this.txnOperatorFilter) url += `&operator=${encodeURIComponent(this.txnOperatorFilter)}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminTransactions = data.data || [];
                this.txnPagination = { current_page: data.current_page, last_page: data.last_page, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) { console.error(e); }
            this.txnLoading = false;
        },

        // ---- Admin Wallets ----
        async fetchAdminWallets() {
            this.wltLoading = true;
            try {
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/admin/wallets', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) this.walletData = await res.json();
            } catch (e) { console.error(e); }
            this.wltLoading = false;
        },

        // ---- Admin Settlements ----
        async fetchAdminSettlements() {
            this.stlLoading = true;
            try {
                let url = `{{ config("services.settlement_service.url") }}/api/admin/settlements?page=${this.stlPage}`;
                if (this.stlSearch) url += `&search=${encodeURIComponent(this.stlSearch)}`;
                if (this.stlStatusFilter) url += `&status=${this.stlStatusFilter}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminSettlements = data.data || [];
                this.stlPagination = { current_page: data.current_page, last_page: data.last_page, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
            } catch (e) { console.error(e); }
            this.stlLoading = false;
        },

        async fetchPendingSettlementsCount() {
            try {
                const res = await fetch('{{ config("services.settlement_service.url") }}/api/admin/settlements?status=pending&page=1', { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.pendingSettlementsCount = data.total || 0;
                }
            } catch (e) { /* silent */ }
        },

        async approveSettlement(id) {
            if (!confirm('Approve this settlement for bank transfer?')) return;
            this.stlActionLoading = true; this.stlMsg = '';
            try {
                const res = await fetch(`{{ config("services.settlement_service.url") }}/api/admin/settlements/${id}/approve`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                const data = await res.json();
                if (res.ok) {
                    this.stlMsg = data.message; this.stlMsgType = 'success';
                    this.fetchAdminSettlements();
                    this.fetchPendingSettlementsCount();
                } else {
                    this.stlMsg = data.message || 'Failed to approve.'; this.stlMsgType = 'error';
                }
            } catch (e) { this.stlMsg = 'Service unavailable.'; this.stlMsgType = 'error'; }
            this.stlActionLoading = false;
            setTimeout(() => { this.stlMsg = ''; }, 5000);
        },

        async rejectSettlement(id) {
            if (!confirm('Reject this settlement? The wallet will be refunded.')) return;
            this.stlActionLoading = true; this.stlMsg = '';
            try {
                const res = await fetch(`{{ config("services.settlement_service.url") }}/api/admin/settlements/${id}/reject`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                const data = await res.json();
                if (res.ok) {
                    this.stlMsg = data.message; this.stlMsgType = 'success';
                    this.fetchAdminSettlements();
                    this.fetchPendingSettlementsCount();
                } else {
                    this.stlMsg = data.message || 'Failed to reject.'; this.stlMsgType = 'error';
                }
            } catch (e) { this.stlMsg = 'Service unavailable.'; this.stlMsgType = 'error'; }
            this.stlActionLoading = false;
            setTimeout(() => { this.stlMsg = ''; }, 5000);
        },

        // ---- Add Business Functions ----
        resetAddBusinessForm() {
            this.addBizForm = {
                firstname: '', lastname: '', email: '', business_name: '', business_type: '',
                registration_number: '', tin_number: '', phone: '', paybill: '',
                address: '', city: '', country: 'Tanzania',
                id_type: '', id_number: '',
                bank_name: '', bank_account_name: '', bank_account_number: '', bank_swift: '', bank_branch: '',
                status: 'active'
            };
            this.addBizIdDocFile = null;
            this.addBizIncorpFile = null;
            this.addBizLicFile = null;
            this.addBizTaxFile = null;
            this.addBizTinCertFile = null;
            this.addBizMemoFile = null;
            this.addBizResolutionFile = null;
            this.addBizMsg = '';
            this.addBizSaving = false;
        },

        async submitAddBusiness() {
            if (!this.addBizForm.firstname || !this.addBizForm.lastname || !this.addBizForm.email || !this.addBizForm.business_name) {
                this.addBizMsg = 'First name, last name, email, and business name are required.';
                this.addBizMsgType = 'error';
                return;
            }
            this.addBizSaving = true;
            this.addBizMsg = '';
            try {
                const fd = new FormData();
                Object.entries(this.addBizForm).forEach(([k, v]) => { if (v) fd.append(k, v); });
                if (this.addBizIdDocFile) fd.append('id_document', this.addBizIdDocFile);
                if (this.addBizIncorpFile) fd.append('certificate_of_incorporation', this.addBizIncorpFile);
                if (this.addBizLicFile) fd.append('business_license', this.addBizLicFile);
                if (this.addBizTaxFile) fd.append('tax_clearance', this.addBizTaxFile);
                if (this.addBizTinCertFile) fd.append('tin_certificate', this.addBizTinCertFile);
                if (this.addBizMemoFile) fd.append('company_memorandum', this.addBizMemoFile);
                if (this.addBizResolutionFile) fd.append('company_resolution', this.addBizResolutionFile);

                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/create-business`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json' },
                    body: fd
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (res.ok) {
                    this.addBizMsg = data.message || 'Business created successfully!';
                    this.addBizMsgType = 'success';
                    this.fetchAccounts();
                    this.fetchStats();
                    setTimeout(() => { this.showAddBusinessModal = false; this.addBizMsg = ''; }, 3000);
                } else {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.addBizMsg = errors || 'Failed to create business.';
                    this.addBizMsgType = 'error';
                }
            } catch (e) {
                console.error(e);
                this.addBizMsg = 'Network error. Please try again.';
                this.addBizMsgType = 'error';
            }
            this.addBizSaving = false;
        },

        // ---- KYC Functions ----
        async viewKycDetails(accountId, scrollToApprove = false) {
            this.showKycModal = true;
            this.kycLoading = true;
            this.kycAccount = null;
            this.kycNoteMsg = '';
            this.kycPaybillMsg = '';
            this.kycRateLimitMsg = '';
            this.kycEditing = false;
            this.kycEditMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${accountId}`, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.kycAccount = data.account;
                    this.kycNotesText = this.kycAccount.kyc_notes || '';
                    this.kycPaybill = this.kycAccount.paybill || '';
                    this.kycRateLimit = this.kycAccount.rate_limit ?? 60;
                }
            } catch (e) { console.error(e); }
            this.kycLoading = false;
        },

        toggleKycEdit() {
            if (this.kycEditing) {
                this.kycEditing = false;
                this.kycEditMsg = '';
                this.kycIdDocFile = null;
                this.kycBizLicFile = null;
                this.kycIncorpFile = null;
                this.kycTaxFile = null;
                this.kycTinCertFile = null;
                this.kycMemoFile = null;
                this.kycResolutionFile = null;
                return;
            }
            if (!this.kycAccount) return;
            const a = this.kycAccount;
            this.kycEditForm = {
                business_name: a.business_name || '', business_type: a.business_type || '',
                registration_number: a.registration_number || '', tin_number: a.tin_number || '',
                email: a.email || '', phone: a.phone || '',
                address: a.address || '', city: a.city || '', country: a.country || '',
                id_type: a.id_type || '', id_number: a.id_number || '',
                bank_name: a.bank_name || '', bank_account_name: a.bank_account_name || '',
                bank_account_number: a.bank_account_number || '', bank_swift: a.bank_swift || '', bank_branch: a.bank_branch || ''
            };
            this.kycIdDocFile = null;
            this.kycBizLicFile = null;
            this.kycIncorpFile = null;
            this.kycTaxFile = null;
            this.kycTinCertFile = null;
            this.kycMemoFile = null;
            this.kycResolutionFile = null;
            this.kycEditMsg = '';
            this.kycEditing = true;
        },

        async saveKycEdit() {
            if (!this.kycAccount) return;
            this.kycEditSaving = true;
            this.kycEditMsg = '';
            try {
                const fd = new FormData();
                Object.entries(this.kycEditForm).forEach(([k, v]) => { if (v) fd.append(k, v); });
                if (this.kycIdDocFile) fd.append('id_document', this.kycIdDocFile);
                if (this.kycIncorpFile) fd.append('certificate_of_incorporation', this.kycIncorpFile);
                if (this.kycBizLicFile) fd.append('business_license', this.kycBizLicFile);
                if (this.kycTaxFile) fd.append('tax_clearance', this.kycTaxFile);
                if (this.kycTinCertFile) fd.append('tin_certificate', this.kycTinCertFile);
                if (this.kycMemoFile) fd.append('company_memorandum', this.kycMemoFile);
                if (this.kycResolutionFile) fd.append('company_resolution', this.kycResolutionFile);
                const token = document.cookie.split('; ').find(c => c.startsWith('admin_token='))?.split('=')[1];
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/kyc`, {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
                    body: fd
                });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.kycAccount = { ...this.kycAccount, ...data.account };
                    this.kycEditMsg = 'KYC updated successfully!';
                    this.kycEditMsgType = 'success';
                    this.kycEditing = false;
                    this.kycIdDocFile = null;
                    this.kycBizLicFile = null;
                    this.kycIncorpFile = null;
                    this.kycTaxFile = null;
                    this.kycTinCertFile = null;
                    this.kycMemoFile = null;
                    this.kycResolutionFile = null;
                    setTimeout(() => { this.kycEditMsg = ''; }, 5000);
                } else {
                    const err = await res.json();
                    this.kycEditMsg = err.message || 'Failed to update KYC.';
                    this.kycEditMsgType = 'error';
                }
            } catch (e) {
                console.error(e);
                this.kycEditMsg = 'Network error. Please try again.';
                this.kycEditMsgType = 'error';
            }
            this.kycEditSaving = false;
        },

        async saveKycNotes() {
            if (!this.kycAccount) return;
            this.kycNoteSaving = true; this.kycNoteMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/kyc-notes`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ kyc_notes: this.kycNotesText })
                });
                if (res.ok) {
                    this.kycNoteMsg = 'Notes saved!';
                    setTimeout(() => { this.kycNoteMsg = ''; }, 3000);
                }
            } catch (e) { console.error(e); }
            this.kycNoteSaving = false;
        },

        async savePaybill() {
            if (!this.kycPaybill?.trim()) { alert('Please enter a paybill number.'); return; }
            this.kycPaybillSaving = true;
            this.kycPaybillMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/status`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ status: this.kycAccount.status, paybill: this.kycPaybill.trim() })
                });
                if (res.ok) {
                    this.kycAccount.paybill = this.kycPaybill.trim();
                    this.kycPaybillMsg = 'Paybill saved!';
                    this.fetchAccounts();
                    setTimeout(() => this.kycPaybillMsg = '', 3000);
                }
            } catch (e) { console.error(e); }
            this.kycPaybillSaving = false;
        },

        async saveRateLimit() {
            if (!this.kycAccount) return;
            const val = parseInt(this.kycRateLimit);
            if (!val || val < 1 || val > 10000) { this.kycRateLimitMsg = 'Enter a value between 1 and 10,000.'; this.kycRateLimitMsgType = 'error'; return; }
            this.kycRateLimitSaving = true;
            this.kycRateLimitMsg = '';
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/rate-limit`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ rate_limit: val })
                });
                if (res.ok) {
                    this.kycAccount.rate_limit = val;
                    this.kycRateLimitMsg = `Rate limit set to ${val} req/min.`;
                    this.kycRateLimitMsgType = 'success';
                    setTimeout(() => this.kycRateLimitMsg = '', 3000);
                } else {
                    const data = await res.json();
                    this.kycRateLimitMsg = data.message || 'Failed to update rate limit.';
                    this.kycRateLimitMsgType = 'error';
                }
            } catch (e) { this.kycRateLimitMsg = 'Service unavailable.'; this.kycRateLimitMsgType = 'error'; }
            this.kycRateLimitSaving = false;
        },

        async kycApprove() {
            const paybill = this.kycPaybill?.trim();
            if (!paybill) { alert('Please enter a paybill number before approving.'); return; }
            if (!confirm('Approve this account KYC? The account will become active with paybill: ' + paybill)) return;
            this.kycActionLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/status`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ status: 'active', paybill: paybill })
                });
                if (res.ok) {
                    this.kycAccount.status = 'active';
                    this.kycAccount.paybill = paybill;
                    this.kycAccount.kyc_approved_at = new Date().toISOString();
                    this.fetchAccounts();
                    this.fetchStats();
                }
            } catch (e) { console.error(e); }
            this.kycActionLoading = false;
        },

        async kycReject() {
            const reason = prompt('Enter reason for rejection (will be saved as KYC notes):');
            if (reason === null) return;
            this.kycActionLoading = true;
            try {
                // Save rejection note
                if (reason) {
                    await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/kyc-notes`, {
                        method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ kyc_notes: 'REJECTED: ' + reason })
                    });
                }
                // Set status to suspended (rejected)
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/accounts/${this.kycAccount.id}/status`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ status: 'suspended' })
                });
                if (res.ok) {
                    this.kycAccount.status = 'suspended';
                    this.fetchAccounts();
                    this.fetchStats();
                }
            } catch (e) { console.error(e); }
            this.kycActionLoading = false;
        },

        // ---- Charges ----
        async fetchCharges() {
            this.chargesLoading = true;
            try {
                let url = '{{ config("services.transaction_service.url") }}/api/charges';
                const params = [];
                if (this.chargeOperatorFilter) params.push(`operator=${encodeURIComponent(this.chargeOperatorFilter)}`);
                if (this.chargeStatusFilter) params.push(`status=${this.chargeStatusFilter}`);
                if (this.chargeAccountFilter) params.push(`account_id=${this.chargeAccountFilter}`);
                if (params.length) url += '?' + params.join('&');
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    const data = await res.json();
                    this.charges = data.charges || [];
                }
            } catch (e) { console.error(e); }
            this.chargesLoading = false;
        },

        async addCharge() {
            if (!this.chargeForm.name || !this.chargeForm.charge_value) {
                this.chargeMsg = 'Please fill in name and charge value.'; this.chargeMsgType = 'error'; return;
            }
            this.chargeLoading = true; this.chargeMsg = '';
            try {
                const res = await fetch('{{ config("services.transaction_service.url") }}/api/charges', {
                    method: 'POST', headers: this.getHeaders(), body: JSON.stringify(this.chargeForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.chargeMsg = errors || 'Failed.'; this.chargeMsgType = 'error'; return;
                }
                this.chargeMsg = data.message; this.chargeMsgType = 'success';
                this.chargeForm = { name: '', account_id: '', operator: 'all', transaction_type: 'all', charge_type: 'fixed', charge_value: '', min_amount: 0, max_amount: 0, applies_to: 'platform' };
                this.fetchCharges();
                setTimeout(() => { this.chargeMsg = ''; }, 3000);
            } catch (e) { this.chargeMsg = 'Service unavailable.'; this.chargeMsgType = 'error'; }
            finally { this.chargeLoading = false; }
        },

        async toggleChargeStatus(ch) {
            const newStatus = ch.status === 'active' ? 'inactive' : 'active';
            try {
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/charges/${ch.id}`, {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify({ status: newStatus })
                });
                if (res.ok) this.fetchCharges();
            } catch (e) { console.error(e); }
        },

        async deleteCharge(id) {
            if (!confirm('Delete this charge configuration?')) return;
            try {
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/charges/${id}`, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (res.ok) this.fetchCharges();
            } catch (e) { console.error(e); }
        },

        /* ==================== IP WHITELIST (ADMIN) ==================== */
        async fetchAdminIps() {
            this.ipListLoading = true;
            try {
                let url = `{{ config("services.auth_service.url") }}/api/admin/ip-whitelist?`;
                if (this.ipSearch) url += `search=${encodeURIComponent(this.ipSearch)}&`;
                if (this.ipStatusFilter) url += `status=${this.ipStatusFilter}&`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminIpList = data.ips || [];
                this.pendingIpCount = data.pending_count ?? 0;
            } catch (e) { console.error(e); }
            this.ipListLoading = false;
        },

        async fetchPendingIpCount() {
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/ip-whitelist?status=pending`, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.pendingIpCount = data.pending_count ?? 0;
            } catch (e) { console.error(e); }
        },

        async approveIp(id) {
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/ip-whitelist/${id}/approve`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    this.fetchAdminIps();
                }
            } catch (e) { console.error(e); }
        },

        async rejectIp(id) {
            const notes = prompt('Rejection reason (optional):');
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/ip-whitelist/${id}/reject`, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ admin_notes: notes || '' })
                });
                if (this.handleUnauth(res)) return;
                if (res.ok) {
                    this.fetchAdminIps();
                }
            } catch (e) { console.error(e); }
        },

        /* ==================== INTERNAL TRANSFERS (ADMIN) ==================== */
        async fetchAdminInternalTransfers() {
            this.trfLoading = true;
            try {
                let url = '{{ config("services.wallet_service.url") }}/api/admin/internal-transfers?';
                if (this.trfStatusFilter) url += `status=${this.trfStatusFilter}&`;
                if (this.trfAccountFilter) url += `account_id=${encodeURIComponent(this.trfAccountFilter)}&`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminInternalTransfers = data.transfers || data.data || [];
                this.pendingTransferCount = data.pending_count ?? 0;
            } catch (e) { console.error(e); }
            this.trfLoading = false;
        },

        async fetchPendingTransferCount() {
            try {
                const res = await fetch('{{ config("services.wallet_service.url") }}/api/admin/internal-transfers?status=pending', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.pendingTransferCount = data.pending_count ?? 0;
            } catch (e) { console.error(e); }
        },

        async approveInternalTransfer(id) {
            if (!confirm('Approve this transfer? Funds will be moved from collection to disbursement.')) return;
            this.trfActionLoading = true; this.trfMsg = '';
            try {
                const res = await fetch(`{{ config("services.wallet_service.url") }}/api/admin/internal-transfers/${id}/approve`, {
                    method: 'PUT', headers: this.getHeaders()
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) { this.trfMsg = data.message || 'Approval failed.'; this.trfMsgType = 'error'; return; }
                this.trfMsg = data.message; this.trfMsgType = 'success';
                this.fetchAdminInternalTransfers();
                this.fetchPendingTransferCount();
                setTimeout(() => { this.trfMsg = ''; }, 4000);
            } catch (e) { this.trfMsg = 'Service unavailable.'; this.trfMsgType = 'error'; }
            finally { this.trfActionLoading = false; }
        },

        async rejectInternalTransfer(id) {
            const notes = prompt('Rejection reason (optional):');
            if (notes === null) return;
            this.trfActionLoading = true; this.trfMsg = '';
            try {
                const res = await fetch(`{{ config("services.wallet_service.url") }}/api/admin/internal-transfers/${id}/reject`, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ admin_notes: notes || '' })
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) { this.trfMsg = data.message || 'Rejection failed.'; this.trfMsgType = 'error'; return; }
                this.trfMsg = data.message; this.trfMsgType = 'success';
                this.fetchAdminInternalTransfers();
                this.fetchPendingTransferCount();
                setTimeout(() => { this.trfMsg = ''; }, 4000);
            } catch (e) { this.trfMsg = 'Service unavailable.'; this.trfMsgType = 'error'; }
            finally { this.trfActionLoading = false; }
        },

        formatAmount(a) { return Number(a).toLocaleString('en-US', { minimumFractionDigits: 2 }); },

        // ---- Reversals ----
        async fetchAdminReversals() {
            this.revLoading = true;
            try {
                let url = `{{ config("services.transaction_service.url") }}/api/admin/reversals?page=${this.revPage}`;
                if (this.revStatusFilter) url += `&status=${this.revStatusFilter}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.adminReversals = data.data || [];
                this.revPagination = { current_page: data.current_page, last_page: data.last_page, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url, total: data.total };
            } catch (e) { console.error(e); }
            finally { this.revLoading = false; }
        },

        async fetchPendingReversalCount() {
            try {
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/admin/reversals?status=pending`, { headers: this.getHeaders() });
                if (res.ok) {
                    const data = await res.json();
                    this.pendingReversalCount = data.total || (data.data || []).length || 0;
                }
            } catch (e) {}
        },

        async approveReversal(rev) {
            if (!confirm(`Approve reversal for ${rev.original_ref}? This will:\n\n• ${rev.type === 'collection' ? 'DEBIT collection wallet' : 'CREDIT disbursement wallet'} by the net amount\n• Mark original transaction as reversed`)) return;
            this.revActionLoading = true;
            try {
                // 1. Approve in transaction-service
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/admin/reversals/${rev.id}/approve`, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ admin_notes: 'Approved' })
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) { alert(data.message || 'Approval failed.'); return; }

                // 2. Reverse wallet balance in wallet-service
                try {
                    const walletRes = await fetch(`{{ config("services.wallet_service.url") }}/api/admin/wallet/reverse`, {
                        method: 'POST', headers: this.getHeaders(),
                        body: JSON.stringify({
                            amount: rev.amount,
                            operator: rev.operator,
                            account_id: String(rev.account_id),
                            type: rev.type,
                            reversal_ref: rev.reversal_ref,
                            original_ref: rev.original_ref,
                            platform_charge: rev.platform_charge,
                            operator_charge: rev.operator_charge,
                        })
                    });
                    const walletData = await walletRes.json();
                    if (!walletRes.ok) {
                        alert('Reversal approved but wallet adjustment failed: ' + (walletData.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Reversal approved but wallet service unavailable. Please adjust wallet manually.');
                }

                this.fetchAdminReversals();
                this.fetchPendingReversalCount();
            } catch (e) { alert('Service unavailable.'); }
            finally { this.revActionLoading = false; }
        },

        async rejectReversal(id) {
            const notes = prompt('Rejection reason (optional):');
            if (notes === null) return;
            this.revActionLoading = true;
            try {
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/admin/reversals/${id}/reject`, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ admin_notes: notes || 'Rejected' })
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) { alert(data.message || 'Rejection failed.'); return; }
                this.fetchAdminReversals();
                this.fetchPendingReversalCount();
            } catch (e) { alert('Service unavailable.'); }
            finally { this.revActionLoading = false; }
        },

        openDirectReversal(txn) {
            this.directRevTxn = txn;
            this.directRevReason = '';
            this.directRevError = '';
            this.directRevSuccess = '';
            this.showDirectRevModal = true;
        },

        async submitDirectReversal() {
            this.directRevLoading = true;
            this.directRevError = '';
            this.directRevSuccess = '';
            try {
                // 1. Create reversal record (auto-approved)
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/admin/reversals/direct`, {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify({
                        transaction_id: this.directRevTxn.id,
                        reason: this.directRevReason,
                    })
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) {
                    this.directRevError = data.message || 'Reversal failed.';
                    return;
                }

                // 2. Adjust wallet balance
                const rev = data.reversal;
                try {
                    const walletRes = await fetch(`{{ config("services.wallet_service.url") }}/api/admin/wallet/reverse`, {
                        method: 'POST', headers: this.getHeaders(),
                        body: JSON.stringify({
                            amount: rev.amount,
                            operator: rev.operator,
                            account_id: String(rev.account_id),
                            type: rev.type,
                            reversal_ref: rev.reversal_ref,
                            original_ref: rev.original_ref,
                            platform_charge: rev.platform_charge,
                            operator_charge: rev.operator_charge,
                        })
                    });
                    const walletData = await walletRes.json();
                    if (!walletRes.ok) {
                        this.directRevError = 'Transaction reversed but wallet adjustment failed: ' + (walletData.message || 'Unknown error');
                        return;
                    }
                } catch (e) {
                    this.directRevError = 'Transaction reversed but wallet service unavailable. Please adjust wallet manually.';
                    return;
                }

                this.directRevSuccess = 'Transaction reversed successfully!';
                // Update the transaction in the table without full reload
                const idx = this.adminTransactions.findIndex(t => t.id === this.directRevTxn.id);
                if (idx !== -1) this.adminTransactions[idx].status = 'reversed';
                this.fetchPendingReversalCount();

                setTimeout(() => { this.showDirectRevModal = false; }, 1500);
            } catch (e) {
                this.directRevError = 'Service unavailable. Please try again.';
            } finally {
                this.directRevLoading = false;
            }
        },

        // ==================== OPERATOR MANAGEMENT METHODS ====================

        async fetchOperators() {
            this.opLoading = true;
            try {
                const res = await fetch('/api/admin/operators', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.operatorsList = data.operators || [];
            } catch (e) { console.error(e); }
            finally { this.opLoading = false; }
        },

        openOperatorModal(op = null) {
            this.editingOperator = op;
            this.opError = '';
            this.opSuccess = '';
            if (op) {
                this.opForm = {
                    name: op.name || '',
                    code: op.code || '',
                    api_url: op.api_url || '',
                    sp_id: op.sp_id || '',
                    merchant_code: op.merchant_code || '',
                    sp_password: '',
                    collection_path: op.collection_path || '/collection',
                    disbursement_path: op.disbursement_path || '/disbursement',
                    callback_url: op.callback_url || '',
                    api_version: op.api_version || '5.0',
                    status: op.status || 'active',
                };
            } else {
                this.opForm = { name: '', code: '', api_url: '', sp_id: '', merchant_code: '', sp_password: '', collection_path: '/collection', disbursement_path: '/disbursement', callback_url: '', api_version: '5.0', status: 'active' };
            }
            this.showOperatorModal = true;
        },

        async saveOperator() {
            this.opSaving = true;
            this.opError = '';
            this.opSuccess = '';
            try {
                const url = this.editingOperator ? `/api/admin/operators/${this.editingOperator.id}` : '/api/admin/operators';
                const method = this.editingOperator ? 'PUT' : 'POST';
                const body = { ...this.opForm };
                if (this.editingOperator && !body.sp_password) delete body.sp_password;
                const res = await fetch(url, { method, headers: this.getHeaders(), body: JSON.stringify(body) });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (!res.ok) { this.opError = data.message || 'Failed to save operator'; return; }
                this.opSuccess = this.editingOperator ? 'Operator updated' : 'Operator created';
                this.showOperatorModal = false;
                this.fetchOperators();
            } catch (e) { this.opError = 'Network error'; console.error(e); }
            finally { this.opSaving = false; }
        },

        async deleteOperator(op) {
            if (!confirm(`Delete operator "${op.name}"? This cannot be undone.`)) return;
            try {
                const res = await fetch(`/api/admin/operators/${op.id}`, { method: 'DELETE', headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) { const d = await res.json(); alert(d.message || 'Failed to delete'); return; }
                this.fetchOperators();
            } catch (e) { console.error(e); }
        },

        async testOperator(op) {
            try {
                const res = await fetch(`/api/admin/operators/${op.id}/test`, { method: 'POST', headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                alert(data.success ? `Connection OK: ${data.message}` : `FAILED: ${data.message}`);
            } catch (e) { alert('Network error'); console.error(e); }
        },

        // ==================== PAYMENT REQUESTS METHODS ====================

        async fetchPaymentRequests() {
            this.payLoading = true;
            try {
                let url = `/api/admin/payment-requests?page=${this.payPage}`;
                if (this.paySearch) url += `&search=${encodeURIComponent(this.paySearch)}`;
                if (this.payStatusFilter) url += `&status=${this.payStatusFilter}`;
                if (this.payTypeFilter) url += `&type=${this.payTypeFilter}`;
                if (this.payOperatorFilter) url += `&operator=${encodeURIComponent(this.payOperatorFilter)}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.paymentRequests = data.data || [];
                this.payPagination = { current_page: data.current_page, last_page: data.last_page, total: data.total };
            } catch (e) { console.error(e); }
            finally { this.payLoading = false; }
        },

        payPrevPage() { if (this.payPage > 1) { this.payPage--; this.fetchPaymentRequests(); } },
        payNextPage() { if (this.payPage < (this.payPagination.last_page || 1)) { this.payPage++; this.fetchPaymentRequests(); } },

        payStatusColor(s) {
            const c = { completed: 'bg-green-100 text-green-800', pending: 'bg-yellow-100 text-yellow-800', processing: 'bg-blue-100 text-blue-800', failed: 'bg-red-100 text-red-800', cancelled: 'bg-gray-100 text-gray-800', timeout: 'bg-orange-100 text-orange-800' };
            return c[s] || 'bg-gray-100 text-gray-800';
        },

        // ==================== ADMIN USER MANAGEMENT METHODS ====================

        async fetchAdminUsers() {
            this.adminUsersLoading = true;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/admin-users`, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.adminUsersList = data.admin_users || [];
            } catch (e) { console.error(e); }
            finally { this.adminUsersLoading = false; }
        },

        openAdminUserModal(au = null) {
            this.editingAdminUser = au;
            this.adminUserError = '';
            this.adminUserSuccess = '';
            if (au) {
                this.adminUserForm = { firstname: au.firstname || '', lastname: au.lastname || '', email: au.email, password: '', permissions: [...(au.permissions || [])] };
            } else {
                this.adminUserForm = { firstname: '', lastname: '', email: '', password: '', permissions: [] };
            }
            this.showAdminUserModal = true;
        },

        toggleAdminPerm(key) {
            const idx = this.adminUserForm.permissions.indexOf(key);
            if (idx > -1) {
                this.adminUserForm.permissions.splice(idx, 1);
            } else {
                this.adminUserForm.permissions.push(key);
            }
        },

        async saveAdminUser() {
            this.adminUserSaving = true;
            this.adminUserError = '';
            this.adminUserSuccess = '';
            try {
                const isEdit = !!this.editingAdminUser;
                const url = isEdit
                    ? `{{ config("services.auth_service.url") }}/api/admin/admin-users/${this.editingAdminUser.id}`
                    : `{{ config("services.auth_service.url") }}/api/admin/admin-users`;
                const method = isEdit ? 'PUT' : 'POST';

                const body = {
                    firstname: this.adminUserForm.firstname,
                    lastname: this.adminUserForm.lastname,
                    email: this.adminUserForm.email,
                    permissions: this.adminUserForm.permissions,
                };
                if (this.adminUserForm.password) {
                    body.password = this.adminUserForm.password;
                }
                if (!isEdit && !body.password) {
                    this.adminUserError = 'Password is required for new admin users.';
                    return;
                }
                if (body.permissions.length === 0) {
                    this.adminUserError = 'At least one permission must be selected.';
                    return;
                }

                const res = await fetch(url, {
                    method, headers: this.getHeaders(),
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) {
                    this.adminUserError = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Failed to save.';
                    return;
                }
                this.adminUserSuccess = data.message || 'Saved successfully!';
                this.fetchAdminUsers();
                setTimeout(() => { this.showAdminUserModal = false; }, 1200);
            } catch (e) {
                this.adminUserError = 'Service unavailable.';
            } finally {
                this.adminUserSaving = false;
            }
        },

        async deleteAdminUser(au) {
            if (!confirm(`Delete admin user "${(au.firstname && au.lastname) ? (au.firstname + ' ' + au.lastname) : au.name}"? This cannot be undone.`)) return;
            try {
                const res = await fetch(`{{ config("services.auth_service.url") }}/api/admin/admin-users/${au.id}`, {
                    method: 'DELETE', headers: this.getHeaders(),
                });
                const data = await res.json();
                if (this.handleUnauth(res)) return;
                if (!res.ok) { alert(data.message || 'Delete failed.'); return; }
                alert(data.message || 'Admin user deleted.');
                this.fetchAdminUsers();
            } catch (e) { alert('Service unavailable.'); }
        },

        logout() {
            fetch('{{ config("services.auth_service.url") }}/api/logout', { method: 'POST', headers: this.getHeaders() }).finally(() => {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('auth_user');
                window.location.href = '/login';
            });
        },

        // ---- Error Logs ----
        async fetchLogs() {
            this.logLoading = true;
            this.logError = '';
            try {
                let url = this.logServiceUrls[this.logService] + `?lines=${this.logLines}`;
                if (this.logLevel) url += `&level=${this.logLevel}`;
                if (this.logSearch) url += `&search=${encodeURIComponent(this.logSearch)}`;
                const res = await fetch(url, { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    this.logError = errData.message || `Service returned ${res.status}`;
                    this.logEntries = [];
                    this.logLoading = false;
                    return;
                }
                const data = await res.json();
                this.logEntries = (data.entries || []).reverse().map(e => ({ ...e, _open: false }));
                this.logFileSize = data.file_size_human || '';
                this.logTotalEntries = data.total_entries || 0;
            } catch (e) {
                this.logError = 'Service unavailable. Make sure the service is running.';
                this.logEntries = [];
            }
            this.logLoading = false;
        },

        async clearLogs() {
            if (!confirm(`Clear ALL logs for ${this.logService}-service? This cannot be undone.`)) return;
            try {
                const url = this.logServiceUrls[this.logService];
                const res = await fetch(url, { method: 'DELETE', headers: this.getHeaders() });
                if (res.ok) {
                    this.logEntries = [];
                    this.logFileSize = '0 B';
                    this.logTotalEntries = 0;
                } else {
                    alert('Failed to clear logs.');
                }
            } catch (e) { alert('Service unavailable.'); }
        },

        toggleLogAutoRefresh() {
            if (this.logAutoRefresh) {
                this.logAutoRefreshTimer = setInterval(() => this.fetchLogs(), 30000);
            } else {
                if (this.logAutoRefreshTimer) clearInterval(this.logAutoRefreshTimer);
                this.logAutoRefreshTimer = null;
            }
        },

        // ==================== MAIL CONFIG ====================
        async fetchMailConfig() {
            this.mailLoading = true;
            this.mailMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/mail-config', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (res.ok && data.config) {
                    this.mailForm = { ...this.mailForm, ...data.config };
                }
            } catch (e) { this.mailMsg = 'Failed to load mail config.'; this.mailMsgType = 'error'; }
            this.mailLoading = false;
            // Also load templates
            this.fetchEmailTemplates();
        },

        async saveMailConfig() {
            this.mailSaving = true;
            this.mailMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/mail-config', {
                    method: 'PUT', headers: this.getHeaders(), body: JSON.stringify(this.mailForm)
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.mailMsg = data.message || (res.ok ? 'Saved.' : 'Failed.');
                this.mailMsgType = res.ok ? 'success' : 'error';
            } catch (e) { this.mailMsg = 'Network error.'; this.mailMsgType = 'error'; }
            this.mailSaving = false;
        },

        async sendTestEmail() {
            this.testMailSending = true;
            this.testMailMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/mail-config/test', {
                    method: 'POST', headers: this.getHeaders(), body: JSON.stringify({ email: this.testMailAddress })
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.testMailMsg = data.message || (res.ok ? 'Sent.' : 'Failed.');
                this.testMailMsgType = res.ok ? 'success' : 'error';
            } catch (e) { this.testMailMsg = 'Network error.'; this.testMailMsgType = 'error'; }
            this.testMailSending = false;
        },

        // ==================== EMAIL TEMPLATES ====================
        async fetchEmailTemplates() {
            this.tplLoading = true;
            this.tplMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates', { headers: this.getHeaders() });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (res.ok && data.templates) {
                    this.emailTemplates = data.templates.map(t => ({ ...t, _open: false }));
                }
            } catch (e) { this.tplMsg = 'Failed to load templates.'; this.tplMsgType = 'error'; }
            this.tplLoading = false;
        },

        async saveEmailTemplate(tpl) {
            this.tplSaving = true;
            this.tplMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates/' + tpl.id, {
                    method: 'PUT', headers: this.getHeaders(),
                    body: JSON.stringify({ subject: tpl.subject, greeting: tpl.greeting, body: tpl.body, action_text: tpl.action_text || null, action_url: tpl.action_url || null, footer: tpl.footer, is_active: tpl.is_active })
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.tplMsg = data.message || (res.ok ? 'Saved.' : 'Failed.');
                this.tplMsgType = res.ok ? 'success' : 'error';
                if (res.ok && data.template) {
                    const idx = this.emailTemplates.findIndex(t => t.id === tpl.id);
                    if (idx !== -1) this.emailTemplates[idx] = { ...data.template, _open: true };
                }
            } catch (e) { this.tplMsg = 'Network error.'; this.tplMsgType = 'error'; }
            this.tplSaving = false;
        },

        async resetEmailTemplate(tpl) {
            this.tplSaving = true;
            this.tplMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates/' + tpl.id + '/reset', {
                    method: 'POST', headers: this.getHeaders()
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.tplMsg = data.message || (res.ok ? 'Reset.' : 'Failed.');
                this.tplMsgType = res.ok ? 'success' : 'error';
                if (res.ok && data.template) {
                    const idx = this.emailTemplates.findIndex(t => t.id === tpl.id);
                    if (idx !== -1) this.emailTemplates[idx] = { ...data.template, _open: true };
                }
            } catch (e) { this.tplMsg = 'Network error.'; this.tplMsgType = 'error'; }
            this.tplSaving = false;
        },

        async createEmailTemplate() {
            this.tplSaving = true;
            this.tplMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(this.newTplForm)
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.tplMsg = data.message || (res.ok ? 'Created.' : 'Failed.');
                this.tplMsgType = res.ok ? 'success' : 'error';
                if (res.ok) {
                    this.showNewTplForm = false;
                    this.newTplForm = { key: '', name: '', subject: '', greeting: 'Hello @{{name}},', body: '', action_text: '', action_url: '', footer: '— Payin Team' };
                    await this.fetchEmailTemplates();
                }
            } catch (e) { this.tplMsg = 'Network error.'; this.tplMsgType = 'error'; }
            this.tplSaving = false;
        },

        async deleteEmailTemplate(tpl) {
            this.tplSaving = true;
            this.tplMsg = '';
            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates/' + tpl.id, {
                    method: 'DELETE', headers: this.getHeaders()
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                this.tplMsg = data.message || (res.ok ? 'Deleted.' : 'Failed.');
                this.tplMsgType = res.ok ? 'success' : 'error';
                if (res.ok) {
                    this.emailTemplates = this.emailTemplates.filter(t => t.id !== tpl.id);
                }
            } catch (e) { this.tplMsg = 'Network error.'; this.tplMsgType = 'error'; }
            this.tplSaving = false;
        },

        openSendModal(tpl) {
            this.sendTplId = tpl.id;
            this.sendTplName = tpl.name;
            this.sendTo = 'emails';
            this.sendEmails = '';
            this.sendResult = '';
            this.sendResultType = 'success';
            this.showSendModal = true;
        },

        async sendTemplateNotification() {
            this.sendLoading = true;
            this.sendResult = '';
            try {
                const payload = { template_id: this.sendTplId, send_to: this.sendTo };
                if (this.sendTo === 'emails') {
                    payload.emails = this.sendEmails.split(',').map(e => e.trim()).filter(e => e);
                    if (!payload.emails.length) {
                        this.sendResult = 'Please enter at least one email address.';
                        this.sendResultType = 'error';
                        this.sendLoading = false;
                        return;
                    }
                }
                const res = await fetch('{{ config("services.auth_service.url") }}/api/admin/email-templates/send', {
                    method: 'POST', headers: this.getHeaders(),
                    body: JSON.stringify(payload)
                });
                if (this.handleUnauth(res)) return;
                const data = await res.json();
                if (res.ok) {
                    this.sendResult = `Successfully sent to ${data.sent || 0} recipient(s)` + (data.failed ? `, ${data.failed} failed.` : '.');
                    this.sendResultType = 'success';
                } else {
                    this.sendResult = data.message || 'Failed to send.';
                    this.sendResultType = 'error';
                }
            } catch (e) { this.sendResult = 'Network error.'; this.sendResultType = 'error'; }
            this.sendLoading = false;
        },
    }
}
</script>
@endsection
