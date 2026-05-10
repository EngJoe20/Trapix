@extends('layouts.app')

@section('title', 'AI Integrations')

@section('extra_css')
<style>
    .glass-card {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(59, 130, 246, 0.1);
        border-radius: 1rem;
    }
    .neon-border-emerald { border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 0 20px rgba(16, 185, 129, 0.1); }
    .neon-border-blue { border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 0 20px rgba(59, 130, 246, 0.1); }
    .neon-border-purple { border-color: rgba(168, 85, 247, 0.4); box-shadow: 0 0 20px rgba(168, 85, 247, 0.1); }
    .neon-border-orange { border-color: rgba(249, 115, 22, 0.4); box-shadow: 0 0 20px rgba(249, 115, 22, 0.1); }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="aiIntegrationsApp()">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-heading-1">AI Integrations</h1>
        <p class="text-body mt-2">Configure your own API keys for AI-assisted malware analysis. Keys are encrypted and securely stored.</p>
    </div>

    <!-- Provider Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- OpenAI Card -->
        <template x-data="{ p: providers['openai'] }">
            <div class="glass-card p-6 relative transition-all duration-300 group" 
                 :class="p.isEnabled ? 'neon-border-emerald border-emerald-500/50' : 'hover:border-emerald-500/30'">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center">
                            <span class="text-emerald-400 font-bold">O</span>
                        </div>
                        <h2 class="text-xl font-bold text-heading-2">OpenAI</h2>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="p.isEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
                
                <div class="space-y-4" x-show="p.isEnabled" x-collapse>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">API Key</label>
                        <div class="relative">
                            <input :type="p.showKey ? 'text' : 'password'" x-model="p.apiKey" placeholder="sk-..." class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 pr-10" />
                            <button @click="p.showKey = !p.showKey" class="absolute right-3 top-2.5 text-body hover:text-white">
                                <svg x-show="!p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Base URL <span class="text-xs text-body/50">(Optional)</span></label>
                        <input type="text" x-model="p.baseUrl" placeholder="https://api.openai.com/v1" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Default Model</label>
                        <input type="text" x-model="p.model" placeholder="gpt-4o" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button @click="saveProvider('openai')" :disabled="p.saving" class="flex-1 btn-primary bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center">
                            <span x-show="!p.saving">Save</span>
                            <div x-show="p.saving" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="testProvider('openai')" :disabled="p.testing" class="flex-1 border border-emerald-500/50 hover:bg-emerald-500/10 text-emerald-400 font-bold py-2 px-4 rounded-lg flex justify-center items-center transition-colors">
                            <span x-show="!p.testing">Test</span>
                            <div x-show="p.testing" class="w-5 h-5 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="deleteProvider('openai')" class="p-2 text-body/50 hover:text-red-500 transition-colors" title="Remove Configuration">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Gemini Card -->
        <template x-data="{ p: providers['gemini'] }">
            <div class="glass-card p-6 relative transition-all duration-300 group" 
                 :class="p.isEnabled ? 'neon-border-blue border-blue-500/50' : 'hover:border-blue-500/30'">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center">
                            <span class="text-blue-400 font-bold">G</span>
                        </div>
                        <h2 class="text-xl font-bold text-heading-2">Google Gemini</h2>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="p.isEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                    </label>
                </div>
                
                <div class="space-y-4" x-show="p.isEnabled" x-collapse>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">API Key</label>
                        <div class="relative">
                            <input :type="p.showKey ? 'text' : 'password'" x-model="p.apiKey" placeholder="AIzaSy..." class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-blue-500 focus:ring-1 focus:ring-blue-500 pr-10" />
                            <button @click="p.showKey = !p.showKey" class="absolute right-3 top-2.5 text-body hover:text-white">
                                <svg x-show="!p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Default Model</label>
                        <input type="text" x-model="p.model" placeholder="gemini-1.5-pro" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button @click="saveProvider('gemini')" :disabled="p.saving" class="flex-1 btn-primary bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center">
                            <span x-show="!p.saving">Save</span>
                            <div x-show="p.saving" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="testProvider('gemini')" :disabled="p.testing" class="flex-1 border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 font-bold py-2 px-4 rounded-lg flex justify-center items-center transition-colors">
                            <span x-show="!p.testing">Test</span>
                            <div x-show="p.testing" class="w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="deleteProvider('gemini')" class="p-2 text-body/50 hover:text-red-500 transition-colors" title="Remove Configuration">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Claude Card -->
        <template x-data="{ p: providers['claude'] }">
            <div class="glass-card p-6 relative transition-all duration-300 group" 
                 :class="p.isEnabled ? 'neon-border-purple border-purple-500/50' : 'hover:border-purple-500/30'">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center">
                            <span class="text-purple-400 font-bold">C</span>
                        </div>
                        <h2 class="text-xl font-bold text-heading-2">Anthropic Claude</h2>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="p.isEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>
                
                <div class="space-y-4" x-show="p.isEnabled" x-collapse>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">API Key</label>
                        <div class="relative">
                            <input :type="p.showKey ? 'text' : 'password'" x-model="p.apiKey" placeholder="sk-ant-..." class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-purple-500 focus:ring-1 focus:ring-purple-500 pr-10" />
                            <button @click="p.showKey = !p.showKey" class="absolute right-3 top-2.5 text-body hover:text-white">
                                <svg x-show="!p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="p.showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Default Model</label>
                        <input type="text" x-model="p.model" placeholder="claude-3-5-sonnet-20240620" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-purple-500 focus:ring-1 focus:ring-purple-500" />
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button @click="saveProvider('claude')" :disabled="p.saving" class="flex-1 btn-primary bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center">
                            <span x-show="!p.saving">Save</span>
                            <div x-show="p.saving" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="testProvider('claude')" :disabled="p.testing" class="flex-1 border border-purple-500/50 hover:bg-purple-500/10 text-purple-400 font-bold py-2 px-4 rounded-lg flex justify-center items-center transition-colors">
                            <span x-show="!p.testing">Test</span>
                            <div x-show="p.testing" class="w-5 h-5 border-2 border-purple-400 border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="deleteProvider('claude')" class="p-2 text-body/50 hover:text-red-500 transition-colors" title="Remove Configuration">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Ollama Card -->
        <template x-data="{ p: providers['ollama'] }">
            <div class="glass-card p-6 relative transition-all duration-300 group" 
                 :class="p.isEnabled ? 'neon-border-orange border-orange-500/50' : 'hover:border-orange-500/30'">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-orange-500/10 flex items-center justify-center">
                            <span class="text-orange-400 font-bold">O</span>
                        </div>
                        <h2 class="text-xl font-bold text-heading-2">Ollama (Local)</h2>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="p.isEnabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                    </label>
                </div>
                
                <div class="space-y-4" x-show="p.isEnabled" x-collapse>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Base URL</label>
                        <input type="text" x-model="p.baseUrl" placeholder="http://localhost:11434" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-orange-500 focus:ring-1 focus:ring-orange-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading-3 mb-1">Local Model</label>
                        <input type="text" x-model="p.model" placeholder="llama3" class="w-full bg-black/50 border border-box-border rounded-lg px-4 py-2 text-body focus:border-orange-500 focus:ring-1 focus:ring-orange-500" />
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button @click="saveProvider('ollama')" :disabled="p.saving" class="flex-1 btn-primary bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center">
                            <span x-show="!p.saving">Save</span>
                            <div x-show="p.saving" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="testProvider('ollama')" :disabled="p.testing" class="flex-1 border border-orange-500/50 hover:bg-orange-500/10 text-orange-400 font-bold py-2 px-4 rounded-lg flex justify-center items-center transition-colors">
                            <span x-show="!p.testing">Test</span>
                            <div x-show="p.testing" class="w-5 h-5 border-2 border-orange-400 border-t-transparent rounded-full animate-spin"></div>
                        </button>
                        <button @click="deleteProvider('ollama')" class="p-2 text-body/50 hover:text-red-500 transition-colors" title="Remove Configuration">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed bottom-4 right-4 z-50 flex items-center p-4 mb-4 text-sm rounded-lg shadow"
         :class="toast.type === 'success' ? 'bg-emerald-800/80 text-emerald-100 border border-emerald-700' : 'bg-red-800/80 text-red-100 border border-red-700'"
         role="alert">
        <svg x-show="toast.type === 'success'" class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>
        <svg x-show="toast.type === 'error'" class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/></svg>
        <span class="sr-only">Info</span>
        <div>
            <span class="font-medium" x-text="toast.title"></span> <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function aiIntegrationsApp() {
        const serverIntegrations = @json($integrations);
        
        return {
            toast: { show: false, type: 'success', title: '', message: '' },
            providers: {
                openai: {
                    isEnabled: serverIntegrations.openai ? serverIntegrations.openai.is_enabled : false,
                    apiKey: '', // Never populate plaintext API key from server
                    baseUrl: serverIntegrations.openai?.base_url || '',
                    model: serverIntegrations.openai?.default_model || '',
                    showKey: false,
                    saving: false,
                    testing: false
                },
                gemini: {
                    isEnabled: serverIntegrations.gemini ? serverIntegrations.gemini.is_enabled : false,
                    apiKey: '',
                    baseUrl: serverIntegrations.gemini?.base_url || '',
                    model: serverIntegrations.gemini?.default_model || '',
                    showKey: false,
                    saving: false,
                    testing: false
                },
                claude: {
                    isEnabled: serverIntegrations.claude ? serverIntegrations.claude.is_enabled : false,
                    apiKey: '',
                    baseUrl: serverIntegrations.claude?.base_url || '',
                    model: serverIntegrations.claude?.default_model || '',
                    showKey: false,
                    saving: false,
                    testing: false
                },
                ollama: {
                    isEnabled: serverIntegrations.ollama ? serverIntegrations.ollama.is_enabled : false,
                    apiKey: '',
                    baseUrl: serverIntegrations.ollama?.base_url || 'http://localhost:11434',
                    model: serverIntegrations.ollama?.default_model || 'llama3',
                    showKey: false,
                    saving: false,
                    testing: false
                }
            },

            showToast(type, title, message) {
                this.toast = { show: true, type, title, message };
                setTimeout(() => this.toast.show = false, 5000);
            },

            async saveProvider(provider) {
                const p = this.providers[provider];
                p.saving = true;
                
                try {
                    const response = await fetch('{{ route("settings.ai-integrations.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            provider: provider,
                            api_key: p.apiKey,
                            base_url: p.baseUrl,
                            model: p.model,
                            is_enabled: p.isEnabled
                        })
                    });
                    
                    const data = await response.json();
                    if (response.ok) {
                        this.showToast('success', 'Saved!', data.message);
                        // Clear api key input since it is saved securely
                        p.apiKey = '';
                    } else {
                        this.showToast('error', 'Error!', data.message || 'Failed to save configuration.');
                    }
                } catch (error) {
                    this.showToast('error', 'Error!', 'An unexpected error occurred.');
                } finally {
                    p.saving = false;
                }
            },

            async testProvider(provider) {
                const p = this.providers[provider];
                p.testing = true;
                
                try {
                    const response = await fetch('{{ route("settings.ai-integrations.test") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            provider: provider,
                            api_key: p.apiKey,
                            base_url: p.baseUrl,
                            model: p.model
                        })
                    });
                    
                    const data = await response.json();
                    if (response.ok) {
                        this.showToast('success', 'Connection OK!', data.message);
                    } else {
                        this.showToast('error', 'Connection Failed!', data.message);
                    }
                } catch (error) {
                    this.showToast('error', 'Error!', 'Network error or provider is unreachable.');
                } finally {
                    p.testing = false;
                }
            },

            async deleteProvider(provider) {
                if (!confirm(`Are you sure you want to remove the ${provider} configuration?`)) return;
                
                try {
                    const response = await fetch('{{ route("settings.ai-integrations.delete") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ provider: provider })
                    });
                    
                    const data = await response.json();
                    if (response.ok) {
                        this.showToast('success', 'Deleted!', data.message);
                        // Reset local state
                        this.providers[provider].isEnabled = false;
                        this.providers[provider].apiKey = '';
                        this.providers[provider].baseUrl = '';
                        this.providers[provider].model = '';
                    }
                } catch (error) {
                    this.showToast('error', 'Error!', 'Failed to delete configuration.');
                }
            }
        };
    }
</script>
@endsection
