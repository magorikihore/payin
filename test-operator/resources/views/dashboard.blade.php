<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Operator Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="dashboard()" x-init="init()">

    <!-- Header -->
    <nav class="bg-indigo-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 rounded-lg p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-white text-xl font-bold">Test Operator Simulator</h1>
                        <p class="text-indigo-200 text-sm">Simulates mobile money operator for Payin testing</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-white text-sm font-medium" x-text="stats.operator_name || 'Test Operator'"></p>
                        <p class="text-indigo-200 text-xs">SP ID: <span x-text="stats.sp_id || '-'"></span></p>
                    </div>
                    <div class="flex items-center space-x-2 bg-white/10 rounded-lg px-3 py-2">
                        <div class="w-2 h-2 rounded-full" :class="stats.auto_callback ? 'bg-green-400' : 'bg-yellow-400'"></div>
                        <span class="text-white text-xs" x-text="stats.auto_callback ? 'Auto-Callback ON' : 'Manual Mode'"></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <p class="text-gray-500 text-xs uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-gray-800" x-text="stats.total || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-green-100">
                <p class="text-green-600 text-xs uppercase tracking-wide">Collections</p>
                <p class="text-2xl font-bold text-green-700" x-text="stats.collections || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-blue-100">
                <p class="text-blue-600 text-xs uppercase tracking-wide">Disbursements</p>
                <p class="text-2xl font-bold text-blue-700" x-text="stats.disbursements || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-yellow-100">
                <p class="text-yellow-600 text-xs uppercase tracking-wide">Pending</p>
                <p class="text-2xl font-bold text-yellow-700" x-text="stats.pending || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-emerald-100">
                <p class="text-emerald-600 text-xs uppercase tracking-wide">Sent</p>
                <p class="text-2xl font-bold text-emerald-700" x-text="stats.sent || 0"></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-red-100">
                <p class="text-red-600 text-xs uppercase tracking-wide">Failed</p>
                <p class="text-2xl font-bold text-red-700" x-text="stats.failed || 0"></p>
            </div>
        </div>

        <!-- Auto-Callback Config -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h3 class="font-semibold text-gray-800">Auto-Callback Configuration</h3>
                    <p class="text-gray-500 text-sm">When enabled, callbacks are automatically sent to Payin after receiving a request</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Status:</span>
                        <span class="px-2 py-1 rounded text-xs font-medium"
                              :class="stats.auto_callback ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                              x-text="stats.auto_callback ? 'Enabled' : 'Disabled'"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Delay:</span>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700"
                              x-text="(stats.auto_delay || 3) + 's'"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Result:</span>
                        <span class="px-2 py-1 rounded text-xs font-medium"
                              :class="stats.auto_result === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                              x-text="(stats.auto_result || 'success').toUpperCase()"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Actions -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-3">
                    <input type="text" x-model="filters.search" @input.debounce.300ms="loadRequests()"
                           placeholder="Search reference, phone, gateway ID..."
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-64">
                    <select x-model="filters.type" @change="loadRequests()"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="collection">Collection</option>
                        <option value="disbursement">Disbursement</option>
                    </select>
                    <select x-model="filters.callback_status" @change="loadRequests()"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="loadRequests(); loadStats()"
                            class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    <button @click="clearAll()"
                            class="px-3 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600 transition">
                        Clear All
                    </button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auth</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Callback</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                    <svg class="animate-spin h-6 w-6 mx-auto text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="mt-2 block">Loading...</span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && requests.length === 0">
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="font-medium">No requests yet</p>
                                    <p class="text-sm mt-1">When Payin sends a collection or disbursement request, it will appear here.</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="req in requests" :key="req.id">
                            <tr class="hover:bg-gray-50 transition fade-in">
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs" x-text="'#' + req.id"></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-medium"
                                          :class="req.type === 'collection' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                          x-text="req.type"></span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700" x-text="req.reference"></td>
                                <td class="px-4 py-3 text-gray-700" x-text="req.msisdn"></td>
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    <span x-text="parseFloat(req.amount).toLocaleString()"></span>
                                    <span class="text-gray-400 text-xs" x-text="req.currency"></span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600" x-text="req.gateway_id"></td>
                                <td class="px-4 py-3">
                                    <span class="w-2 h-2 rounded-full inline-block"
                                          :class="req.auth_valid ? 'bg-green-500' : 'bg-red-500'"
                                          :title="req.auth_valid ? 'Valid' : 'Invalid'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-medium"
                                          :class="{
                                              'bg-yellow-100 text-yellow-700': req.callback_status === 'pending',
                                              'bg-green-100 text-green-700': req.callback_status === 'sent',
                                              'bg-red-100 text-red-700': req.callback_status === 'failed'
                                          }"
                                          x-text="req.callback_status"></span>
                                    <template x-if="req.callback_result">
                                        <span class="text-xs text-gray-400 ml-1" x-text="'(' + req.callback_result + ')'"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="formatTime(req.created_at)"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-1">
                                        <template x-if="req.callback_status === 'pending'">
                                            <div class="flex space-x-1">
                                                <button @click="triggerCallback(req.id, 'success')"
                                                        class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600 transition"
                                                        :disabled="sendingCallback === req.id">
                                                    <span x-show="sendingCallback !== req.id">Approve</span>
                                                    <span x-show="sendingCallback === req.id">...</span>
                                                </button>
                                                <button @click="triggerCallback(req.id, 'failed')"
                                                        class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600 transition"
                                                        :disabled="sendingCallback === req.id">
                                                    Reject
                                                </button>
                                            </div>
                                        </template>
                                        <button @click="showDetail(req)"
                                                class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs hover:bg-gray-300 transition">
                                            Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t bg-gray-50 flex items-center justify-between" x-show="pagination.last_page > 1">
                <p class="text-sm text-gray-500">
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                    (<span x-text="pagination.total"></span> total)
                </p>
                <div class="flex space-x-2">
                    <button @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 border rounded text-sm disabled:opacity-50 hover:bg-gray-100 transition">
                        Prev
                    </button>
                    <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 border rounded text-sm disabled:opacity-50 hover:bg-gray-100 transition">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Operator Config Info -->
        <div class="mt-6 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-3">Payin Admin Configuration</h3>
            <p class="text-gray-500 text-sm mb-4">Use these values when adding this test operator in the Payin admin panel:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Operator Name</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded" x-text="stats.operator_name || 'Test Operator'"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Operator Code</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded" x-text="stats.operator_code || 'testoperator'"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">SP ID</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded" x-text="stats.sp_id || '600100'"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Merchant Code</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">6001001</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">SP Password (plain)</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">TestOperator@2025</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">API URL</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">http://localhost:8006/api</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Collection Path</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">/ussd/collection</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Disbursement Path</label>
                        <p class="font-mono text-sm bg-gray-50 p-2 rounded">/ussd/disbursement</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="detailModal" x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         @click.self="detailModal = false">
        <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-lg font-semibold text-gray-800">Request Detail</h3>
                <button @click="detailModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-4" x-show="selectedRequest">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500">Type</label>
                        <p class="font-medium" x-text="selectedRequest?.type"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Command</label>
                        <p class="font-medium" x-text="selectedRequest?.command"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Reference</label>
                        <p class="font-mono text-sm" x-text="selectedRequest?.reference"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Gateway ID</label>
                        <p class="font-mono text-sm" x-text="selectedRequest?.gateway_id"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Phone (MSISDN)</label>
                        <p class="font-medium" x-text="selectedRequest?.msisdn"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Amount</label>
                        <p class="font-medium">
                            <span x-text="parseFloat(selectedRequest?.amount || 0).toLocaleString()"></span>
                            <span class="text-gray-400" x-text="selectedRequest?.currency"></span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Auth Valid</label>
                        <p>
                            <span class="px-2 py-1 rounded text-xs font-medium"
                                  :class="selectedRequest?.auth_valid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  x-text="selectedRequest?.auth_valid ? 'Yes' : 'No'"></span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Callback Status</label>
                        <p>
                            <span class="px-2 py-1 rounded text-xs font-medium"
                                  :class="{
                                      'bg-yellow-100 text-yellow-700': selectedRequest?.callback_status === 'pending',
                                      'bg-green-100 text-green-700': selectedRequest?.callback_status === 'sent',
                                      'bg-red-100 text-red-700': selectedRequest?.callback_status === 'failed'
                                  }"
                                  x-text="selectedRequest?.callback_status"></span>
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs text-gray-500">Callback URL</label>
                        <p class="font-mono text-sm break-all bg-gray-50 p-2 rounded" x-text="selectedRequest?.callback_url"></p>
                    </div>
                    <template x-if="selectedRequest?.receipt_number">
                        <div>
                            <label class="text-xs text-gray-500">Receipt Number</label>
                            <p class="font-mono text-sm" x-text="selectedRequest?.receipt_number"></p>
                        </div>
                    </template>
                    <template x-if="selectedRequest?.callback_sent_at">
                        <div>
                            <label class="text-xs text-gray-500">Callback Sent At</label>
                            <p class="text-sm" x-text="formatTime(selectedRequest?.callback_sent_at)"></p>
                        </div>
                    </template>
                </div>

                <!-- Raw Request -->
                <div>
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Raw Request (from Payin)</label>
                    <pre class="mt-1 bg-gray-900 text-green-400 text-xs p-4 rounded-lg overflow-x-auto max-h-48"
                         x-text="JSON.stringify(selectedRequest?.raw_request, null, 2)"></pre>
                </div>

                <!-- Raw Response -->
                <div>
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Response (sent to Payin)</label>
                    <pre class="mt-1 bg-gray-900 text-blue-400 text-xs p-4 rounded-lg overflow-x-auto max-h-48"
                         x-text="JSON.stringify(selectedRequest?.raw_response, null, 2)"></pre>
                </div>

                <!-- Callback Response -->
                <template x-if="selectedRequest?.callback_response">
                    <div>
                        <label class="text-xs text-gray-500 uppercase tracking-wide">Callback Response (from Payin)</label>
                        <pre class="mt-1 bg-gray-900 text-yellow-400 text-xs p-4 rounded-lg overflow-x-auto max-h-48"
                             x-text="JSON.stringify(selectedRequest?.callback_response, null, 2)"></pre>
                    </div>
                </template>

                <!-- Actions -->
                <template x-if="selectedRequest?.callback_status === 'pending'">
                    <div class="flex space-x-3 pt-4 border-t">
                        <button @click="triggerCallback(selectedRequest.id, 'success'); detailModal = false"
                                class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                            Approve (Send Success Callback)
                        </button>
                        <button @click="triggerCallback(selectedRequest.id, 'failed'); detailModal = false"
                                class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                            Reject (Send Failure Callback)
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         x-text="toast.message">
    </div>

    <script>
    function dashboard() {
        return {
            requests: [],
            stats: {},
            filters: { search: '', type: '', callback_status: '' },
            pagination: { current_page: 1, last_page: 1, total: 0 },
            loading: false,
            detailModal: false,
            selectedRequest: null,
            sendingCallback: null,
            toast: { show: false, message: '', type: 'success' },
            pollInterval: null,

            init() {
                this.loadStats();
                this.loadRequests();
                // Poll every 3 seconds
                this.pollInterval = setInterval(() => {
                    this.loadRequests();
                    this.loadStats();
                }, 3000);
            },

            async loadStats() {
                try {
                    const res = await fetch('/api/dashboard/stats');
                    this.stats = await res.json();
                } catch (e) { console.error('Stats error:', e); }
            },

            async loadRequests(page = 1) {
                this.loading = this.requests.length === 0;
                try {
                    const params = new URLSearchParams();
                    params.set('page', page);
                    if (this.filters.search) params.set('search', this.filters.search);
                    if (this.filters.type) params.set('type', this.filters.type);
                    if (this.filters.callback_status) params.set('callback_status', this.filters.callback_status);

                    const res = await fetch('/api/dashboard/requests?' + params.toString());
                    const data = await res.json();

                    this.requests = data.data || [];
                    this.pagination = {
                        current_page: data.current_page || 1,
                        last_page: data.last_page || 1,
                        total: data.total || 0
                    };
                } catch (e) { console.error('Requests error:', e); }
                this.loading = false;
            },

            goToPage(page) {
                if (page < 1 || page > this.pagination.last_page) return;
                this.loadRequests(page);
            },

            async triggerCallback(id, result) {
                this.sendingCallback = id;
                try {
                    const res = await fetch(`/api/dashboard/requests/${id}/callback`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ result })
                    });
                    const data = await res.json();
                    this.showToast(data.message, res.ok ? 'success' : 'error');
                    this.loadRequests(this.pagination.current_page);
                    this.loadStats();
                } catch (e) {
                    this.showToast('Failed to send callback', 'error');
                }
                this.sendingCallback = null;
            },

            async clearAll() {
                if (!confirm('Clear all requests? This cannot be undone.')) return;
                try {
                    await fetch('/api/dashboard/requests', { method: 'DELETE' });
                    this.showToast('All requests cleared', 'success');
                    this.loadRequests();
                    this.loadStats();
                } catch (e) {
                    this.showToast('Failed to clear requests', 'error');
                }
            },

            showDetail(req) {
                this.selectedRequest = req;
                this.detailModal = true;
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 3000);
            },

            formatTime(ts) {
                if (!ts) return '-';
                const d = new Date(ts);
                return d.toLocaleString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            }
        };
    }
    </script>
</body>
</html>
