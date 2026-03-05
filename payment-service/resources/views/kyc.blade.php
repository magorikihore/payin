@extends('layouts.app')

@section('title', 'Complete KYC - Payin')

@section('content')
<div x-data="kycPage()" x-init="init()" x-cloak>
    <!-- Top Navbar (matches dashboard) -->
    <nav class="bg-gray-900 shadow-lg border-b border-gray-800 fixed top-0 left-0 right-0 z-30">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center">
                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="ml-2 text-lg font-bold text-white">Payin</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-400 hidden sm:inline">Welcome, <span class="font-medium text-white" x-text="user?.name || 'User'"></span></span>
                    <button @click="logout()" class="text-xs text-red-400 hover:text-red-300 font-medium transition">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div class="h-14"></div>

    <!-- KYC Required Banner -->
    <div class="bg-gyellow-50 border-b border-gyellow-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-10 h-10 text-gyellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gyellow-800">Complete Your KYC</h3>
                    <p class="text-sm text-gyellow-700 mt-1">Please provide your business details and identity verification to activate your account. This is required before you can use the platform.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-12">
        <div class="bg-white rounded-xl shadow-sm border p-6">

            <!-- Stepper Progress -->
            <div class="flex items-center mb-8">
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

            <!-- Messages -->
            <div x-show="msg" x-cloak class="mb-4 p-3 rounded-lg text-sm"
                :class="msgType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
                x-text="msg"></div>

            <form @submit.prevent="saveKyc()" enctype="multipart/form-data">

                <!-- ===== STEP 1: Business Information ===== -->
                <div x-show="kycStep === 1">
                    <h4 class="text-md font-semibold text-gray-800 mb-1">Business Information</h4>
                    <p class="text-sm text-gray-500 mb-5">Provide your company or business details.</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Business Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.business_name" required
                                    :class="errors.business_name ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                <p x-show="errors.business_name" x-text="errors.business_name" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Business Type <span class="text-red-500">*</span></label>
                                <select x-model="form.business_type" required
                                    :class="errors.business_type ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
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
                                <p x-show="errors.business_type" x-text="errors.business_type" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Registration Number <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.registration_number" required placeholder="Business registration no."
                                    :class="errors.registration_number ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                <p x-show="errors.registration_number" x-text="errors.registration_number" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">TIN Number <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.tin_number" required placeholder="Tax Identification Number"
                                    :class="errors.tin_number ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                <p x-show="errors.tin_number" x-text="errors.tin_number" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.phone" required placeholder="+255..."
                                :class="errors.phone ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            <p x-show="errors.phone" x-text="errors.phone" class="text-xs text-red-500 mt-1"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Street Address</label>
                            <input type="text" x-model="form.address" placeholder="Street / P.O. Box"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">City</label>
                                <input type="text" x-model="form.city" placeholder="City"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Country</label>
                                <select x-model="form.country"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
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

                        <!-- Bank Settlement Details -->
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <h5 class="text-sm font-semibold text-gray-700 mb-3">Bank Settlement Accounts</h5>
                            <p class="text-sm text-gray-500 mb-3">Add at least one bank account for settlements. You can add more later from your dashboard.</p>

                            <!-- Added bank accounts list -->
                            <div x-show="bankAccounts.length > 0" class="space-y-2 mb-3">
                                <template x-for="(ba, idx) in bankAccounts" :key="idx">
                                    <div class="bg-gray-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800" x-text="ba.bank_name + ' — ' + ba.account_number"></p>
                                            <p class="text-xs text-gray-500" x-text="ba.account_name"></p>
                                        </div>
                                        <button type="button" @click="bankAccounts.splice(idx, 1)" class="text-xs text-red-500 hover:text-red-700 px-2 py-1">✕</button>
                                    </div>
                                </template>
                            </div>

                            <!-- Add bank account inline form -->
                            <div class="border border-dashed border-gray-300 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Bank Name *</label>
                                        <input type="text" x-model="bankForm.bank_name" placeholder="e.g. CRDB Bank"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Name *</label>
                                        <input type="text" x-model="bankForm.account_name" placeholder="Account holder name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Number *</label>
                                        <input type="text" x-model="bankForm.account_number" placeholder="Bank account number"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">SWIFT Code</label>
                                        <input type="text" x-model="bankForm.swift_code" placeholder="e.g. COLOTZTZ"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                                        <input type="text" x-model="bankForm.branch" placeholder="Branch name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    </div>
                                </div>
                                <button type="button" @click="addBankToList()"
                                    :disabled="!bankForm.bank_name || !bankForm.account_name || !bankForm.account_number"
                                    class="mt-3 text-sm bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 disabled:opacity-40 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    Add Account
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="button" @click="validateStep1() && (kycStep = 2)"
                            class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                            Next: ID Verification &rarr;
                        </button>
                    </div>
                </div>

                <!-- ===== STEP 2: ID Verification ===== -->
                <div x-show="kycStep === 2">
                    <h4 class="text-md font-semibold text-gray-800 mb-1">Identity Verification</h4>
                    <p class="text-sm text-gray-500 mb-5">Provide your personal identification details.</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">ID Type <span class="text-red-500">*</span></label>
                                <select x-model="form.id_type" required
                                    :class="errors.id_type ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    <option value="">Select...</option>
                                    <option value="national_id">National ID (NIDA)</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers_license">Driver's License</option>
                                </select>
                                <p x-show="errors.id_type" x-text="errors.id_type" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">ID Number <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.id_number" required placeholder="ID number"
                                    :class="errors.id_number ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                <p x-show="errors.id_number" x-text="errors.id_number" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="kycStep = 1"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                            &larr; Back
                        </button>
                        <button type="button" @click="validateStep2() && (kycStep = 3)"
                            class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                            Next: Documents &rarr;
                        </button>
                    </div>
                </div>

                <!-- ===== STEP 3: Documents ===== -->
                <div x-show="kycStep === 3">
                    <h4 class="text-md font-semibold text-gray-800 mb-1">Upload Documents</h4>
                    <p class="text-sm text-gray-500 mb-5">Upload the required documents for verification. Accepted formats: JPG, PNG or PDF (max 5MB each).</p>

                    <!-- Required Documents -->
                    <div class="mb-6">
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 text-gred-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <h5 class="text-sm font-semibold text-gray-700">Required Documents</h5>
                        </div>
                        <div class="space-y-4">
                            <!-- ID Document -->
                            <div class="border rounded-lg p-4 transition" :class="idFile ? 'border-ggreen-300 bg-ggreen-50/30' : (errors.id_document ? 'border-red-300 bg-red-50/30' : 'border-gray-200')">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">ID Document <span class="text-red-500">*</span></label>
                                        <p class="text-xs text-gray-400 mb-2">Passport, National ID (NIDA) or Driver's License</p>
                                    </div>
                                    <div x-show="idFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="idFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="idFile = $event.target.files[0]; delete errors.id_document" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 file:cursor-pointer">
                                <p x-show="errors.id_document" x-text="errors.id_document" class="text-xs text-red-500 mt-1"></p>
                            </div>

                            <!-- Certificate of Incorporation -->
                            <div class="border rounded-lg p-4 transition" :class="incorporationFile ? 'border-ggreen-300 bg-ggreen-50/30' : (errors.certificate_of_incorporation ? 'border-red-300 bg-red-50/30' : 'border-gray-200')">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">Certificate of Incorporation / Company Registration <span class="text-red-500">*</span></label>
                                        <p class="text-xs text-gray-400 mb-2">Official company registration certificate</p>
                                    </div>
                                    <div x-show="incorporationFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="incorporationFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="incorporationFile = $event.target.files[0]; delete errors.certificate_of_incorporation" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 file:cursor-pointer">
                                <p x-show="errors.certificate_of_incorporation" x-text="errors.certificate_of_incorporation" class="text-xs text-red-500 mt-1"></p>
                            </div>

                            <!-- Business License -->
                            <div class="border rounded-lg p-4 transition" :class="licenseFile ? 'border-ggreen-300 bg-ggreen-50/30' : (errors.business_license ? 'border-red-300 bg-red-50/30' : 'border-gray-200')">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">Business License <span class="text-red-500">*</span></label>
                                        <p class="text-xs text-gray-400 mb-2">Valid business license issued by relevant authority</p>
                                    </div>
                                    <div x-show="licenseFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="licenseFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="licenseFile = $event.target.files[0]; delete errors.business_license" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 file:cursor-pointer">
                                <p x-show="errors.business_license" x-text="errors.business_license" class="text-xs text-red-500 mt-1"></p>
                            </div>

                            <!-- TIN Certificate -->
                            <div class="border rounded-lg p-4 transition" :class="tinCertificateFile ? 'border-ggreen-300 bg-ggreen-50/30' : (errors.tin_certificate ? 'border-red-300 bg-red-50/30' : 'border-gray-200')">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">TIN Certificate <span class="text-red-500">*</span></label>
                                        <p class="text-xs text-gray-400 mb-2">Tax Identification Number (TIN) certificate from TRA</p>
                                    </div>
                                    <div x-show="tinCertificateFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="tinCertificateFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="tinCertificateFile = $event.target.files[0]; delete errors.tin_certificate" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gblue-50 file:text-gblue-700 hover:file:bg-gblue-100 file:cursor-pointer">
                                <p x-show="errors.tin_certificate" x-text="errors.tin_certificate" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Documents -->
                    <div>
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <h5 class="text-sm font-semibold text-gray-700">Additional Documents</h5>
                            <span class="ml-2 text-xs text-gray-400 font-normal">(Optional — submit if available)</span>
                        </div>
                        <div class="space-y-4">
                            <!-- Tax Clearance -->
                            <div class="border border-dashed rounded-lg p-4 transition" :class="taxClearanceFile ? 'border-ggreen-300 bg-ggreen-50/30' : 'border-gray-200'">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">Tax Clearance Certificate</label>
                                        <p class="text-xs text-gray-400 mb-2">Tax compliance certificate from TRA</p>
                                    </div>
                                    <div x-show="taxClearanceFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="taxClearanceFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="taxClearanceFile = $event.target.files[0]; delete errors.tax_clearance" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600 hover:file:bg-gray-100 file:cursor-pointer">
                                <p x-show="errors.tax_clearance" x-text="errors.tax_clearance" class="text-xs text-red-500 mt-1"></p>
                            </div>

                            <!-- Company Memorandum -->
                            <div class="border border-dashed rounded-lg p-4 transition" :class="memorandumFile ? 'border-ggreen-300 bg-ggreen-50/30' : 'border-gray-200'">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">Memorandum of Association</label>
                                        <p class="text-xs text-gray-400 mb-2">Company memorandum and articles of association</p>
                                    </div>
                                    <div x-show="memorandumFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="memorandumFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="memorandumFile = $event.target.files[0]; delete errors.company_memorandum" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600 hover:file:bg-gray-100 file:cursor-pointer">
                                <p x-show="errors.company_memorandum" x-text="errors.company_memorandum" class="text-xs text-red-500 mt-1"></p>
                            </div>

                            <!-- Company Resolution -->
                            <div class="border border-dashed rounded-lg p-4 transition" :class="resolutionFile ? 'border-ggreen-300 bg-ggreen-50/30' : 'border-gray-200'">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-0.5">Board Resolution</label>
                                        <p class="text-xs text-gray-400 mb-2">Resolution authorizing use of payment services</p>
                                    </div>
                                    <div x-show="resolutionFile" class="flex items-center text-ggreen-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-xs ml-1" x-text="resolutionFile?.name"></span>
                                    </div>
                                </div>
                                <input type="file" @change="resolutionFile = $event.target.files[0]; delete errors.company_resolution" accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600 hover:file:bg-gray-100 file:cursor-pointer">
                                <p x-show="errors.company_resolution" x-text="errors.company_resolution" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="kycStep = 2"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                            &larr; Back
                        </button>
                        <button type="button" @click="validateStep3() && (kycStep = 4)"
                            class="px-6 py-2 bg-gblue-500 text-white rounded-lg hover:bg-gblue-600 text-sm font-medium transition">
                            Next: Crypto Wallet &rarr;
                        </button>
                    </div>
                </div>

                <!-- ===== STEP 4: Crypto Wallet (Optional) + Submit ===== -->
                <div x-show="kycStep === 4">
                    <h4 class="text-md font-semibold text-gray-800 mb-1">Crypto Wallet (Optional)</h4>
                    <p class="text-sm text-gray-500 mb-5">For crypto settlements. You can skip this and add it later.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Wallet Address</label>
                            <input type="text" x-model="form.crypto_wallet_address" placeholder="e.g. 0x..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Network</label>
                                <select x-model="form.crypto_network"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    <option value="">Select...</option>
                                    <option value="ethereum">Ethereum (ERC-20)</option>
                                    <option value="bsc">BSC (BEP-20)</option>
                                    <option value="tron">Tron (TRC-20)</option>
                                    <option value="solana">Solana</option>
                                    <option value="bitcoin">Bitcoin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Currency</label>
                                <select x-model="form.crypto_currency"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gblue-500 outline-none">
                                    <option value="">Select...</option>
                                    <option value="USDT">USDT</option>
                                    <option value="USDC">USDC</option>
                                    <option value="BTC">BTC</option>
                                    <option value="ETH">ETH</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gblue-50 border border-gblue-200 rounded-lg p-4 mt-6 text-sm text-gblue-800">
                        <strong>Almost done!</strong> Click "Submit KYC" to send your details for admin review. Your account will be activated once verified.
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="kycStep = 3"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">
                            &larr; Back
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-8 py-2 bg-ggreen-500 text-white rounded-lg hover:bg-ggreen-600 text-sm font-bold transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!saving">Submit KYC</span>
                            <span x-show="saving">Submitting...</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function kycPage() {
    return {
        user: null,
        kycStep: 1,
        saving: false,
        msg: '', msgType: '',
        idFile: null, licenseFile: null, incorporationFile: null, taxClearanceFile: null,
        tinCertificateFile: null, memorandumFile: null, resolutionFile: null,
        form: {
            business_name: '', business_type: '', registration_number: '', tin_number: '',
            phone: '', address: '', city: '', country: 'Tanzania',
            id_type: '', id_number: '',
            crypto_wallet_address: '', crypto_network: '', crypto_currency: ''
        },
        bankAccounts: [],
        bankForm: { bank_name: '', account_name: '', account_number: '', swift_code: '', branch: '' },
        errors: {},

        appReady: false,

        init() {
            const token = localStorage.getItem('auth_token');
            if (!token) { window.location.href = '/login'; return; }
            this.user = JSON.parse(localStorage.getItem('auth_user') || 'null');
            if (!this.user) { window.location.href = '/login'; return; }
            if (this.user.role === 'super_admin') { window.location.href = '/admin'; return; }
            // Pre-fill business name from registration
            if (this.user.account) {
                this.form.business_name = this.user.account.business_name || '';
            }
            // Check if KYC is already submitted — redirect to dashboard
            this.checkKycStatus();
            this.appReady = true;
            this.$nextTick(() => document.dispatchEvent(new Event('alpine:initialized')));
        },

        async checkKycStatus() {
            try {
                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/kyc', {
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.kyc && data.kyc.kyc_submitted_at) {
                        // KYC already submitted, go to dashboard
                        localStorage.removeItem('kyc_required');
                        localStorage.setItem('account_pending', 'true');
                        window.location.href = '/dashboard';
                    }
                }
            } catch (e) { console.error(e); }
        },

        validateStep1() {
            this.errors = {};
            if (!this.form.business_name.trim()) this.errors.business_name = 'Business name is required.';
            if (!this.form.business_type) this.errors.business_type = 'Business type is required.';
            if (!this.form.registration_number.trim()) this.errors.registration_number = 'Registration number is required.';
            if (!this.form.tin_number.trim()) this.errors.tin_number = 'TIN number is required.';
            if (!this.form.phone.trim()) this.errors.phone = 'Phone number is required.';
            if (this.form.phone.trim() && !/^\+?[0-9]{9,15}$/.test(this.form.phone.trim())) this.errors.phone = 'Enter a valid phone number (e.g. +255...).';
            if (Object.keys(this.errors).length) { this.msg = 'Please fix the errors below before continuing.'; this.msgType = 'error'; return false; }
            this.msg = ''; return true;
        },

        validateStep2() {
            this.errors = {};
            if (!this.form.id_type) this.errors.id_type = 'ID type is required.';
            if (!this.form.id_number.trim()) this.errors.id_number = 'ID number is required.';
            if (Object.keys(this.errors).length) { this.msg = 'Please fix the errors below before continuing.'; this.msgType = 'error'; return false; }
            this.msg = ''; return true;
        },

        validateStep3() {
            this.errors = {};
            // Required documents
            if (!this.idFile) this.errors.id_document = 'Please upload your ID document.';
            if (!this.incorporationFile) this.errors.certificate_of_incorporation = 'Please upload your Certificate of Incorporation.';
            if (!this.licenseFile) this.errors.business_license = 'Please upload your business license.';
            if (!this.tinCertificateFile) this.errors.tin_certificate = 'Please upload your TIN Certificate.';
            // File size checks (all uploads)
            if (this.idFile && this.idFile.size > 5 * 1024 * 1024) this.errors.id_document = 'ID document must be under 5MB.';
            if (this.incorporationFile && this.incorporationFile.size > 5 * 1024 * 1024) this.errors.certificate_of_incorporation = 'Certificate must be under 5MB.';
            if (this.licenseFile && this.licenseFile.size > 5 * 1024 * 1024) this.errors.business_license = 'Business license must be under 5MB.';
            if (this.tinCertificateFile && this.tinCertificateFile.size > 5 * 1024 * 1024) this.errors.tin_certificate = 'TIN certificate must be under 5MB.';
            if (this.taxClearanceFile && this.taxClearanceFile.size > 5 * 1024 * 1024) this.errors.tax_clearance = 'Tax clearance must be under 5MB.';
            if (this.memorandumFile && this.memorandumFile.size > 5 * 1024 * 1024) this.errors.company_memorandum = 'Memorandum must be under 5MB.';
            if (this.resolutionFile && this.resolutionFile.size > 5 * 1024 * 1024) this.errors.company_resolution = 'Resolution must be under 5MB.';
            if (Object.keys(this.errors).length) { this.msg = 'Please upload all required documents before continuing.'; this.msgType = 'error'; return false; }
            this.msg = ''; return true;
        },

        validateAll() {
            if (!this.validateStep1()) { this.kycStep = 1; return false; }
            if (!this.validateStep2()) { this.kycStep = 2; return false; }
            if (!this.validateStep3()) { this.kycStep = 3; return false; }
            return true;
        },

        addBankToList() {
            if (!this.bankForm.bank_name || !this.bankForm.account_name || !this.bankForm.account_number) return;
            this.bankAccounts.push({ ...this.bankForm });
            this.bankForm = { bank_name: '', account_name: '', account_number: '', swift_code: '', branch: '' };
        },

        async saveKyc() {
            if (!this.validateAll()) return;
            this.saving = true;
            this.msg = '';
            try {
                const formData = new FormData();
                Object.keys(this.form).forEach(k => {
                    if (this.form[k] !== null && this.form[k] !== '') formData.append(k, this.form[k]);
                });
                if (this.idFile) formData.append('id_document', this.idFile);
                if (this.incorporationFile) formData.append('certificate_of_incorporation', this.incorporationFile);
                if (this.licenseFile) formData.append('business_license', this.licenseFile);
                if (this.taxClearanceFile) formData.append('tax_clearance', this.taxClearanceFile);
                if (this.tinCertificateFile) formData.append('tin_certificate', this.tinCertificateFile);
                if (this.memorandumFile) formData.append('company_memorandum', this.memorandumFile);
                if (this.resolutionFile) formData.append('company_resolution', this.resolutionFile);

                const authHeaders = { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}`, 'Accept': 'application/json' };

                const res = await fetch('{{ config("services.auth_service.public_url") }}/api/account/kyc', {
                    method: 'POST',
                    headers: authHeaders,
                    body: formData
                });
                const data = await res.json();
                if (!res.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    this.msg = errors || 'Failed to submit KYC.';
                    this.msgType = 'error';
                    return;
                }

                // Save bank accounts
                for (let i = 0; i < this.bankAccounts.length; i++) {
                    try {
                        await fetch('{{ config("services.auth_service.public_url") }}/api/account/bank-accounts', {
                            method: 'POST',
                            headers: { ...authHeaders, 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ...this.bankAccounts[i], is_default: i === 0 })
                        });
                    } catch (e) { console.error('Failed to save bank account', e); }
                }

                // KYC submitted successfully — redirect to dashboard
                localStorage.removeItem('kyc_required');
                localStorage.setItem('account_pending', 'true');
                window.location.href = '/dashboard';
            } catch (e) {
                console.error('KYC submission error:', e);
                this.msg = e.message && e.message !== 'Failed to fetch'
                    ? e.message
                    : 'Unable to connect to authentication service. Please check your internet connection and try again.';
                this.msgType = 'error';
            } finally {
                this.saving = false;
            }
        },

        logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            localStorage.removeItem('kyc_required');
            localStorage.removeItem('account_pending');
            window.location.href = '/login';
        }
    }
}
</script>
@endsection
