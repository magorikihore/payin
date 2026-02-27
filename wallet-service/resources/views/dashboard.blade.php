@extends('layouts.app')

@section('title', 'Dashboard - Payment Dashboard')

@section('content')
<div x-data="dashboard()" x-init="init()" x-cloak>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-gray-800">Payment Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <span class="font-medium" x-text="user?.name || 'User'"></span></span>
                    <button @click="showPasswordModal = true" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Change Password</button>
                    <button @click="logout()" class="text-sm text-red-600 hover:text-red-800 font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Transactions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
                    </div>
                </div>
            </div>
            <!-- Completed -->
            <div class="bg-white rounded-xl shadow-sm p-6 border">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Completed</p>
                        <p class="text-2xl font-bold text-green-600" x-text="stats.completed"></p>
                    </div>
                </div>
            </div>
            <!-- Pending -->
            <div class="bg-white rounded-xl shadow-sm p-6 border">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600" x-text="stats.pending"></p>
                    </div>
                </div>
            </div>
            <!-- Failed -->
            <div class="bg-white rounded-xl shadow-sm p-6 border">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Failed</p>
                        <p class="text-2xl font-bold text-red-600" x-text="stats.failed"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 border mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" x-model="searchQuery" @input.debounce.400ms="currentPage = 1; fetchTransactions()"
                            placeholder="Search by reference, description, amount..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        <button x-show="searchQuery" @click="searchQuery = ''; fetchTransactions()" x-cloak
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select x-model="filterStatus" @change="fetchTransactions()"
                        class="ml-2 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Type</label>
                    <select x-model="filterType" @change="fetchTransactions()"
                        class="ml-2 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        <option value="deposit">Deposit</option>
                        <option value="withdrawal">Withdrawal</option>
                        <option value="payment">Payment</option>
                        <option value="transfer">Transfer</option>
                        <option value="refund">Refund</option>
                    </select>
                </div>
                <button @click="searchQuery = ''; filterStatus = ''; filterType = ''; currentPage = 1; fetchTransactions()"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Clear All</button>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3>
            </div>

            <!-- Loading -->
            <div x-show="loadingTxns" class="p-8 text-center text-gray-500">
                <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="mt-2">Loading transactions...</p>
            </div>

            <!-- Error -->
            <div x-show="txnError" x-cloak class="p-6 text-center">
                <p class="text-red-600" x-text="txnError"></p>
                <button @click="fetchTransactions()" class="mt-2 text-indigo-600 hover:underline text-sm">Retry</button>
            </div>

            <!-- Empty State -->
            <div x-show="!loadingTxns && !txnError && transactions.length === 0" x-cloak class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p>No transactions found.</p>
            </div>

            <!-- Table -->
            <div x-show="!loadingTxns && !txnError && transactions.length > 0" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="txn in transactions" :key="txn.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700" x-text="txn.transaction_ref"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="txn.description || '-'"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="{
                                                'bg-blue-100 text-blue-800': txn.type === 'deposit',
                                                'bg-orange-100 text-orange-800': txn.type === 'withdrawal',
                                                'bg-purple-100 text-purple-800': txn.type === 'payment',
                                                'bg-indigo-100 text-indigo-800': txn.type === 'transfer',
                                                'bg-pink-100 text-pink-800': txn.type === 'refund'
                                            }" x-text="txn.type"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold"
                                        :class="txn.type === 'deposit' || txn.type === 'refund' ? 'text-green-600' : 'text-gray-800'">
                                        <span x-text="(txn.type === 'deposit' || txn.type === 'refund' ? '+' : '-') + ' ' + formatAmount(txn.amount) + ' ' + txn.currency"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize" x-text="(txn.payment_method || '-').replace('_', ' ')"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                            :class="{
                                                'bg-green-100 text-green-800': txn.status === 'completed',
                                                'bg-yellow-100 text-yellow-800': txn.status === 'pending',
                                                'bg-red-100 text-red-800': txn.status === 'failed',
                                                'bg-gray-100 text-gray-800': txn.status === 'cancelled'
                                            }" x-text="txn.status"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(txn.created_at)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> transactions
                    </p>
                    <div class="flex space-x-2">
                        <button @click="goToPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                            class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                        <button @click="goToPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                            class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
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
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div x-show="pwError" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="pwError"></div>
            <div x-show="pwSuccess" x-cloak class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm" x-text="pwSuccess"></div>

            <form @submit.prevent="changePassword()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" x-model="currentPassword" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Enter current password">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" x-model="newPassword" required minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Enter new password">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" x-model="confirmPassword" required minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Confirm new password">
                </div>
                <div class="flex space-x-3">
                    <button type="button" @click="closePasswordModal()"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">Cancel</button>
                    <button type="submit" :disabled="pwLoading"
                        class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!pwLoading">Update Password</span>
                        <span x-show="pwLoading">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function dashboard() {
    return {
        user: null,
        transactions: [],
        loadingTxns: true,
        txnError: '',
        searchQuery: '',
        filterStatus: '',
        filterType: '',
        currentPage: 1,
        stats: { total: 0, completed: 0, pending: 0, failed: 0 },
        pagination: {},
        showPasswordModal: false,
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
        pwError: '',
        pwSuccess: '',
        pwLoading: false,

        init() {
            const token = localStorage.getItem('auth_token');
            const userData = localStorage.getItem('auth_user');

            if (!token) {
                window.location.href = '/login';
                return;
            }

            this.user = userData ? JSON.parse(userData) : null;
            this.fetchStats();
            this.fetchTransactions();
        },

        async fetchStats() {
            const token = localStorage.getItem('auth_token');
            try {
                const res = await fetch(`{{ config("services.transaction_service.url") }}/api/transactions/stats`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.stats = data;
                }
            } catch (e) {}
        },

        async fetchTransactions() {
            this.loadingTxns = true;
            this.txnError = '';
            const token = localStorage.getItem('auth_token');

            try {
                let url = `{{ config("services.transaction_service.url") }}/api/transactions?page=${this.currentPage}`;
                if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;
                if (this.filterStatus) url += `&status=${this.filterStatus}`;
                if (this.filterType) url += `&type=${this.filterType}`;

                const res = await fetch(url, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (res.status === 401) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('auth_user');
                    window.location.href = '/login';
                    return;
                }

                if (!res.ok) throw new Error('Failed to fetch transactions');

                const data = await res.json();
                this.transactions = data.data || [];
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    from: data.from,
                    to: data.to,
                    total: data.total,
                    prev_page_url: data.prev_page_url,
                    next_page_url: data.next_page_url,
                };


            } catch (e) {
                this.txnError = 'Failed to load transactions. Make sure the transaction service is running.';
            } finally {
                this.loadingTxns = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.currentPage = page;
            this.fetchTransactions();
        },

        formatAmount(amount) {
            return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        async logout() {
            const token = localStorage.getItem('auth_token');
            try {
                await fetch('{{ config("services.auth_service.url") }}/api/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
            } catch (e) {}
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            window.location.href = '/login';
        },

        async changePassword() {
            this.pwError = '';
            this.pwSuccess = '';
            this.pwLoading = true;
            const token = localStorage.getItem('auth_token');

            try {
                const res = await fetch('{{ config("services.auth_service.url") }}/api/change-password', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: this.currentPassword,
                        password: this.newPassword,
                        password_confirmation: this.confirmPassword
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.pwError = errors || 'Failed to change password.';
                    return;
                }
                this.pwSuccess = data.message || 'Password changed successfully.';
                this.currentPassword = '';
                this.newPassword = '';
                this.confirmPassword = '';
                setTimeout(() => this.closePasswordModal(), 2000);
            } catch (e) {
                this.pwError = 'Unable to connect to authentication service.';
            } finally {
                this.pwLoading = false;
            }
        },

        closePasswordModal() {
            this.showPasswordModal = false;
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';
            this.pwError = '';
            this.pwSuccess = '';
        }
    }
}
</script>
@endsection
