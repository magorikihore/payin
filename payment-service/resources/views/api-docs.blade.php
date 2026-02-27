@extends('layouts.app')

@section('title', 'API Documentation - Payin')

@section('content')
<div class="min-h-screen bg-gray-50" x-data="apiDocs()">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex h-1 w-16 rounded overflow-hidden">
                    <div class="flex-1 bg-gblue-500"></div>
                    <div class="flex-1 bg-gred-500"></div>
                    <div class="flex-1 bg-gyellow-500"></div>
                    <div class="flex-1 bg-ggreen-500"></div>
                </div>
                <h1 class="text-xl font-bold text-gray-800">Payin API</h1>
                <span class="bg-gblue-100 text-gblue-700 text-xs font-semibold px-2 py-0.5 rounded">v1</span>
            </div>
            <a href="/login" class="text-sm text-gblue-500 hover:underline">Sign In →</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex gap-8">
        <!-- Sidebar Navigation -->
        <nav class="hidden lg:block w-56 flex-shrink-0">
            <div class="sticky top-24 space-y-1">
                <a href="#overview" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Overview</a>
                <a href="#authentication" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Authentication</a>
                <a href="#collection" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Collection (Payin)</a>
                <a href="#disbursement" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Disbursement (Payout)</a>
                <a href="#status" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Transaction Status</a>
                <a href="#operators" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Active Operators</a>
                <a href="#callbacks" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Callbacks</a>
                <a href="#errors" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Error Handling</a>
                <a href="#environments" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded hover:bg-gray-100">Environments</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
            <!-- Overview -->
            <section id="overview" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Overview</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <p class="text-gray-600 mb-4">
                        Payin is a payment gateway that enables merchants to collect and disburse mobile money payments across Africa.
                        Our API uses a simple REST interface with JSON payloads.
                    </p>
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
            </section>

            <!-- Authentication -->
            <section id="authentication" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Authentication</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">
                        All API requests require two headers for authentication. Generate your API credentials from the
                        <strong>Dashboard → Settings → API Keys</strong> page.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Header</th>
                                    <th class="pb-2 font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">X-API-Key</code></td>
                                    <td class="py-2">Your API key (public identifier)</td>
                                </tr>
                                <tr>
                                    <td class="py-2"><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">X-API-Secret</code></td>
                                    <td class="py-2">Your API secret (keep it confidential)</td>
                                </tr>
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
                    <div class="bg-gyellow-50 border border-gyellow-200 rounded-lg p-4">
                        <p class="text-sm text-gyellow-800">
                            <strong>IP Whitelisting:</strong> For added security, whitelist your server IP addresses in
                            <strong>Dashboard → Settings → IP Whitelist</strong>. Requests from non-whitelisted IPs will be rejected once enabled.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Collection -->
            <section id="collection" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Collection (Payin)</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">Initiate a mobile money collection from a customer. The customer will receive a USSD prompt on their phone to confirm the payment.</p>
                    <div class="flex items-center space-x-2">
                        <span class="bg-ggreen-500 text-white text-xs font-bold px-2 py-1 rounded">POST</span>
                        <code class="text-sm text-gray-700">/v1/collection</code>
                    </div>

                    <h4 class="font-semibold text-gray-700">Request Body</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Field</th>
                                    <th class="pb-2 font-semibold text-gray-700">Type</th>
                                    <th class="pb-2 font-semibold text-gray-700">Required</th>
                                    <th class="pb-2 font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">phone</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Customer phone number (e.g. <code class="text-xs">255712345678</code>)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">amount</code></td>
                                    <td class="py-2">number</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Amount to collect (min: 100)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">operator</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Operator code (e.g. <code class="text-xs">mpesa</code>, <code class="text-xs">tigopesa</code>, <code class="text-xs">airtelmoney</code>)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">reference</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">No</td>
                                    <td class="py-2">Your internal reference (max 50 chars)</td>
                                </tr>
                                <tr>
                                    <td class="py-2"><code class="text-xs">callback_url</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">No</td>
                                    <td class="py-2">Override the default callback URL for this request</td>
                                </tr>
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

                    <h4 class="font-semibold text-gray-700 mt-4">Response</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <p class="text-gray-400 text-xs mb-2">Success (201)</p>
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
            </section>

            <!-- Disbursement -->
            <section id="disbursement" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Disbursement (Payout)</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">Send money from your wallet to a customer's mobile money account.</p>
                    <div class="flex items-center space-x-2">
                        <span class="bg-ggreen-500 text-white text-xs font-bold px-2 py-1 rounded">POST</span>
                        <code class="text-sm text-gray-700">/v1/disbursement</code>
                    </div>

                    <h4 class="font-semibold text-gray-700">Request Body</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Field</th>
                                    <th class="pb-2 font-semibold text-gray-700">Type</th>
                                    <th class="pb-2 font-semibold text-gray-700">Required</th>
                                    <th class="pb-2 font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">phone</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Recipient phone number (e.g. <code class="text-xs">255712345678</code>)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">amount</code></td>
                                    <td class="py-2">number</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Amount to send (min: 100)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">operator</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">Yes</td>
                                    <td class="py-2">Operator code</td>
                                </tr>
                                <tr>
                                    <td class="py-2"><code class="text-xs">reference</code></td>
                                    <td class="py-2">string</td>
                                    <td class="py-2">No</td>
                                    <td class="py-2">Your internal reference (max 50 chars)</td>
                                </tr>
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

                    <h4 class="font-semibold text-gray-700 mt-4">Response</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <p class="text-gray-400 text-xs mb-2">Success (201)</p>
                        <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "message": "Disbursement initiated",
  "request_ref": "PAY-X9Y8Z7W6V5U4",
  "status": "pending",
  "amount": 5000,
  "charge": 150,
  "operator": "mpesa"
}</pre>
                    </div>
                </div>
            </section>

            <!-- Transaction Status -->
            <section id="status" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Transaction Status</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">Check the current status of a payment request.</p>
                    <div class="flex items-center space-x-2">
                        <span class="bg-gblue-500 text-white text-xs font-bold px-2 py-1 rounded">GET</span>
                        <code class="text-sm text-gray-700">/v1/status/{request_ref}</code>
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4">Response</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-green-400 text-sm font-mono whitespace-pre">{
  "request_ref": "PAY-A1B2C3D4E5F6",
  "type": "collection",
  "amount": 10000,
  "charge": 200,
  "phone": "255712345678",
  "operator": "mpesa",
  "status": "completed",
  "operator_ref": "MPESA123456",
  "reference": "ORDER-001",
  "created_at": "2026-01-15T10:30:00.000000Z",
  "completed_at": "2026-01-15T10:30:45.000000Z"
}</pre>
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4">Status Values</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Status</th>
                                    <th class="pb-2 font-semibold text-gray-700">Description</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="bg-gyellow-100 text-gyellow-700 px-1.5 py-0.5 rounded text-xs">pending</code></td>
                                    <td class="py-2">Request submitted, waiting for operator/customer</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="bg-ggreen-100 text-ggreen-700 px-1.5 py-0.5 rounded text-xs">completed</code></td>
                                    <td class="py-2">Payment successful</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="bg-gred-100 text-gred-700 px-1.5 py-0.5 rounded text-xs">failed</code></td>
                                    <td class="py-2">Payment failed (insufficient funds, timeout, rejected)</td>
                                </tr>
                                <tr>
                                    <td class="py-2"><code class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-xs">reversed</code></td>
                                    <td class="py-2">Transaction was reversed</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Active Operators -->
            <section id="operators" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Active Operators</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">List available mobile money operators and their supported transaction types.</p>
                    <div class="flex items-center space-x-2">
                        <span class="bg-gblue-500 text-white text-xs font-bold px-2 py-1 rounded">GET</span>
                        <code class="text-sm text-gray-700">/v1/operators</code>
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4">Response</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-green-400 text-sm font-mono whitespace-pre">[
  {
    "name": "M-Pesa",
    "code": "mpesa",
    "country": "Tanzania",
    "currency": "TZS",
    "supports_collection": true,
    "supports_disbursement": true,
    "min_amount": 100,
    "max_amount": 10000000
  },
  {
    "name": "Tigo Pesa",
    "code": "tigopesa",
    "country": "Tanzania",
    "currency": "TZS",
    "supports_collection": true,
    "supports_disbursement": true,
    "min_amount": 100,
    "max_amount": 5000000
  }
]</pre>
                    </div>
                </div>
            </section>

            <!-- Callbacks -->
            <section id="callbacks" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Callbacks (Webhooks)</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">
                        When a payment is completed or fails, Payin sends a POST request to your configured callback URL.
                        Set your callback URL in <strong>Dashboard → Account Info → Callback URL</strong>.
                    </p>

                    <h4 class="font-semibold text-gray-700">Callback Payload</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
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

                    <div class="bg-gblue-50 border border-gblue-200 rounded-lg p-4">
                        <p class="text-sm text-gblue-800">
                            <strong>Important:</strong> Your callback endpoint must return HTTP <code class="text-xs">200</code> within 10 seconds.
                            Always verify the payment status by calling the <code class="text-xs">/v1/status/{request_ref}</code> endpoint
                            before fulfilling orders — never trust callback data alone.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Error Handling -->
            <section id="errors" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Error Handling</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <p class="text-gray-600">All errors follow a consistent JSON format.</p>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <p class="text-gray-400 text-xs mb-2">Error response</p>
                        <pre class="text-red-400 text-sm font-mono whitespace-pre">{
  "message": "Insufficient wallet balance.",
  "errors": {
    "amount": ["The amount must be at least 100."]
  }
}</pre>
                    </div>

                    <h4 class="font-semibold text-gray-700">HTTP Status Codes</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Code</th>
                                    <th class="pb-2 font-semibold text-gray-700">Meaning</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">200</code></td>
                                    <td class="py-2">Success</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">201</code></td>
                                    <td class="py-2">Created (payment initiated)</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">401</code></td>
                                    <td class="py-2">Unauthorized — invalid API key or secret</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">403</code></td>
                                    <td class="py-2">Forbidden — IP not whitelisted or account inactive</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">404</code></td>
                                    <td class="py-2">Resource not found</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><code class="text-xs">422</code></td>
                                    <td class="py-2">Validation error</td>
                                </tr>
                                <tr>
                                    <td class="py-2"><code class="text-xs">429</code></td>
                                    <td class="py-2">Rate limit exceeded</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Environments -->
            <section id="environments" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Environments</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-2 font-semibold text-gray-700">Environment</th>
                                    <th class="pb-2 font-semibold text-gray-700">Base URL</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-2">Production</td>
                                    <td class="py-2"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">https://api.payin.co.tz/api/v1</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Quick Start -->
            <section id="quickstart" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Quick Start</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <ol class="list-decimal list-inside text-gray-600 space-y-2">
                        <li><a href="/login" class="text-gblue-500 hover:underline">Create an account</a> and complete KYC verification</li>
                        <li>Wait for admin approval of your account</li>
                        <li>Go to <strong>Dashboard → Settings → API Keys</strong> and generate your keys</li>
                        <li>Set your callback URL in <strong>Dashboard → Account Info</strong></li>
                        <li>Optionally whitelist your server IPs in <strong>Dashboard → Settings → IP Whitelist</strong></li>
                        <li>Start making API calls using your <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">X-API-Key</code> and <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">X-API-Secret</code> headers</li>
                    </ol>

                    <h4 class="font-semibold text-gray-700 mt-4">Full Example — Collect Payment (PHP)</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-green-400 text-sm font-mono whitespace-pre">$ch = curl_init('https://api.payin.co.tz/api/v1/collection');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
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
echo $data['request_ref']; // PAY-A1B2C3D4E5F6</pre>
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4">Full Example — Collect Payment (Python)</h4>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
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

data = response.json()
print(data['request_ref'])  # PAY-A1B2C3D4E5F6</pre>
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4">Full Example — Collect Payment (JavaScript / Node.js)</h4>
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
console.log(data.request_ref); // PAY-A1B2C3D4E5F6</pre>
                    </div>
                </div>
            </section>

            <!-- Support -->
            <section class="mb-12">
                <div class="bg-gblue-50 border border-gblue-200 rounded-xl p-6 text-center">
                    <h3 class="text-lg font-semibold text-gblue-800 mb-2">Need Help?</h3>
                    <p class="text-gblue-600 text-sm">Contact us at <a href="mailto:support@payin.co.tz" class="underline font-medium">support@payin.co.tz</a> for integration support.</p>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
function apiDocs() {
    return {};
}
</script>
@endsection
