@extends('layouts.app')

@section('title', 'Scanner | Trapix')

@section('content')
    <section class="relative min-h-[90vh] py-12 px-6 overflow-hidden">
        <!-- Ambient glow orbs -->
        <div
            class="dark:absolute hidden dark:block top-20 left-10 w-72 h-72 bg-green-500 rounded-full mix-blend-screen filter blur-[120px] opacity-20">
        </div>

        <div class="max-w-7xl mx-auto relative z-10" x-data="scannerApp()">

            <div class="mb-10 fade-in-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="text-heading-1">Threat</span>
                    <span class="text-primary dark:text-green-400 neon-text">Scanner</span>
                </h1>
                <p class="text-body max-w-2xl">
                    Upload files, select analysis tools, or use the command terminal. Trapix isolates your files in a secure
                    sandbox.
                </p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8">

                <!-- Left Panel: Upload & GUI Tools -->
                <div class="lg:col-span-5 flex flex-col gap-6 fade-in-up stagger-delay-1">

                    <!-- Upload Dropzone -->
                    <div class="glass-panel p-8 relative overflow-hidden group" id="dropZone"
                        @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false"
                        @drop.prevent="handleDrop($event)"
                        :class="{ 'border-green-400 shadow-[0_0_15px_rgba(34,211,238,0.5)]': dragover }">

                        <input type="file" id="fileInput" class="hidden" multiple @change="handleFileSelect($event)">

                        <!-- Dropzone Default -->
                        <div class="text-center cursor-pointer" @click="document.getElementById('fileInput').click()"
                            x-show="!selectedFile && !isUploading && !isCompleted">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full border-2 border-primary/30 flex items-center justify-center group-hover:border-primary transition-colors">
                                <svg class="w-8 h-8 text-primary dark:text-green-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-heading-1 mb-2">Upload File or Folder</h3>
                            <p class="text-body text-sm">Drag & drop or click to browse (Max 100MB)</p>
                        </div>

                        <!-- File Selected UI -->
                        <div x-show="selectedFile && !isUploading && !isCompleted" style="display: none;"
                            class="text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-xl bg-green-500/10 border border-green-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(34,211,238,0.1)]">
                                <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-heading-1 mb-1 truncate px-4"
                                x-text="selectedFile ? selectedFile.name : ''"></h3>
                            <p class="text-body text-xs mb-6"
                                x-text="selectedFile ? (selectedFile.size/1024/1024).toFixed(2) + ' MB' : ''"></p>

                            <div class="flex flex-col gap-3 max-w-[200px] mx-auto">
                                <button @click="startUpload"
                                    class="btn-primary w-full py-2.5 flex items-center justify-center gap-2 group">
                                    <span>START ANALYSIS</span>
                                    <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                                <button @click="resetScan"
                                    class="text-xs text-body hover:text-red-400 transition-colors uppercase tracking-wider font-semibold">Cancel</button>
                            </div>
                        </div>

                        <!-- Progress UI -->
                        <div x-show="isUploading" style="display: none;" class="text-center">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-green-400 font-mono" x-text="scanStatusText">Uploading...</span>
                                <span class="text-sm text-green-400 font-mono" x-text="progress + '%'">0%</span>
                            </div>
                            <div class="h-2 rounded-full overflow-hidden bg-gray-800 w-full mb-4">
                                <div class="h-full rounded-full bg-green-400 transition-all duration-300"
                                    :style="'width: ' + progress + '%'"></div>
                            </div>
                            <p class="text-xs text-body font-mono">Job ID: <span x-text="jobId"
                                    class="text-emerald-400"></span></p>
                        </div>

                        <!-- Completed UI -->
                        <div x-show="isCompleted" style="display: none;" class="text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-full border-2 border-emerald-400 flex items-center justify-center">
                                <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-emerald-400 mb-2">Analysis Complete</h3>
                            <div class="flex justify-center flex-wrap gap-3 mt-4">
                                <a :href="'/analysis/' + jobId" x-show="jobId" class="btn-primary text-sm px-4 py-2">View
                                    Full Result</a>
                                <a :href="pdfUrl" x-show="hasPdf" class="btn-secondary text-sm px-4 py-2"
                                    target="_blank">Download PDF</a>
                                <button @click="resetScan"
                                    class="text-sm text-body hover:text-red-400 transition-colors uppercase tracking-wider font-semibold">Scan
                                    Another</button>
                            </div>
                        </div>
                    </div>

                    <!-- GUI Tool Selection -->
                    <div class="glass-card p-6 relative">
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-box-border">
                            <h3 class="text-lg font-bold text-heading-2">Analysis Tools</h3>
                            <div class="flex gap-3">
                                <button @click="selectAllTools" class="text-xs text-green-400 hover:text-green-300">Select
                                    All</button>
                                <button @click="clearAllTools" class="text-xs text-body hover:text-red-400">Clear</button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="(tool, key) in tools" :key="key">
                                <!-- Skip algo sub-items — rendered inline below hashes -->
                                <template x-if="!tool.algo">
                                    <div>
                                        <label
                                            class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                            :class="tool.enabled
                                                   ? (key === 'ai' ? 'bg-purple-500/10 border-purple-500/50' : 'bg-green-500/10 border-green-500/50')
                                                   : 'border-box-border hover:border-green-500/30'">
                                            <input type="checkbox" x-model="tool.enabled"
                                                @change="key === 'hashes' && toggleHashChildren(tool.enabled)"
                                                class="mt-1 rounded border-gray-500 text-green-500 focus:ring-green-500">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-semibold block"
                                                    :class="key === 'ai' ? 'text-purple-300' : 'text-heading-3'"
                                                    x-text="tool.name"></span>
                                                <span x-show="tool.desc" class="text-xs text-body/60 block"
                                                    x-text="tool.desc"></span>
                                            </div>
                                        </label>

                                        <!-- Hash algorithm children (only under 'hashes') -->
                                        <div x-show="key === 'hashes' && tool.enabled"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0" class="ml-6 mt-1 space-y-1">
                                            <template x-for="algo in ['hash_sha256','hash_sha1','hash_md5']" :key="algo">
                                                <label
                                                    class="flex items-center gap-2 px-3 py-2 rounded border cursor-pointer transition-all"
                                                    :class="tools[algo].enabled ? 'bg-emerald-500/10 border-emerald-500/30' : 'border-box-border/40 hover:border-green-500/20'">
                                                    <input type="checkbox" x-model="tools[algo].enabled"
                                                        class="rounded border-gray-500 text-emerald-500 focus:ring-emerald-500">
                                                    <span class="text-xs font-mono text-heading-3"
                                                        x-text="tools[algo].name.trim()"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>

                </div>

                <!-- Right Panel: Terminal Command Mode -->
                <div class="lg:col-span-7 fade-in-up stagger-delay-2 h-full flex flex-col">
                    <div class="glass-card flex-grow relative overflow-hidden flex flex-col h-[600px]">
                        <div
                            class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-500 via-emerald-500 to-purple-500">
                        </div>

                        <!-- Terminal Header -->
                        <div class="flex items-center gap-2 p-4 border-b border-box-border bg-black/40">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span class="ml-2 text-xs text-heading-3 font-mono">TRAPIX_TERMINAL v1.0</span>
                        </div>

                        <!-- Terminal Output -->
                        <div class="flex-grow p-4 font-mono text-[12px] overflow-y-auto" id="terminalLog">
                            <div class="text-gray-400 mb-4">
                                Welcome to Trapix Command Mode. <br>
                                Type 'help' for a list of commands, or upload a file to begin.<br>
                                Example: <span class="text-green-400">Trapix analyze all</span>
                            </div>

                            <template x-for="(log, index) in logs" :key="index">
                                <div class="terminal-line mb-1" :class="log.color">
                                    <span x-html="log.prefix" class="mr-2"></span><span x-text="log.text"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Terminal Input -->
                        <div class="p-4 border-t border-box-border bg-black/40 flex items-center gap-2">
                            <span class="text-green-400 font-mono text-[12px]">$&gt;</span>
                            <input type="text" x-model="commandInput" @keydown.enter="executeCommand"
                                class="w-full bg-transparent border-none outline-none text-heading-2 font-mono text-[12px] focus:ring-0 p-0"
                                placeholder="Enter command (e.g., Trapix run static)..."
                                :disabled="isUploading && !isCompleted">
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Quota Modal (inside scannerApp scope) ── --}}
            <div x-show="quotaModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display:none">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="quotaModal.show = false"></div>
                <div class="relative glass-panel max-w-md w-full p-8 text-center animate-fade-in-up">
                    <div
                        class="w-16 h-16 mx-auto mb-5 rounded-full border-2 border-amber-500/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-heading-1 mb-3">Scan Limit Reached</h2>
                    <p class="text-sm text-body mb-8 leading-relaxed" x-text="quotaModal.message"></p>
                    <div class="flex flex-col gap-3">
                        <a :href="quotaModal.redirectTo" class="btn-primary w-full py-3 text-center font-semibold"
                            x-text="quotaModal.btnLabel">
                        </a>
                        <button @click="quotaModal.show = false"
                            class="text-sm text-body hover:text-heading-2 transition-colors">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('scannerApp', () => ({
                dragover: false,
                isUploading: false,
                isCompleted: false,
                progress: 0,
                jobId: null,
                pollUrl: null,
                scanStatusText: 'Ready',
                hasPdf: false,
                pdfUrl: '#',
                commandInput: '',
                selectedFile: null,
                quotaModal: { show: false, message: '', requireLogin: false, redirectTo: '/', btnLabel: 'Continue' },

                tools: {
                    // ── Hashes ────────────────────────────────────────────────────
                    hashes: { name: 'Hash Generation', desc: 'SHA256 / SHA1 / MD5', enabled: true, group: 'hashes' },
                    hash_sha256: { name: '  └ SHA-256', desc: '', enabled: true, algo: true },
                    hash_sha1: { name: '  └ SHA-1', desc: '', enabled: true, algo: true },
                    hash_md5: { name: '  └ MD5', desc: '', enabled: true, algo: true },
                    // ── File Info ─────────────────────────────────────────────────
                    file_info: { name: 'File Info', desc: 'Size, type detection', enabled: true },
                    // ── Network ───────────────────────────────────────────────────
                    vt: { name: 'VirusTotal Scan', desc: 'Hash-based lookup', enabled: true },
                    // ── Packing ───────────────────────────────────────────────────
                    packer: { name: 'Packer Detection', desc: 'Signatures + methods', enabled: true },
                    upx: { name: 'UPX Unpacking', desc: 'Auto-unpack UPX binaries', enabled: true },
                    entropy: { name: 'Entropy Analysis', desc: 'Per-section entropy score', enabled: true },
                    // ── Static ────────────────────────────────────────────────────
                    pe_info: { name: 'PE Information', desc: 'Headers, entry point, imphash', enabled: true },
                    imports: { name: '  └ Import Table', desc: 'DLLs + imported functions', enabled: true },
                    exports: { name: '  └ Export Table', desc: 'Exported functions', enabled: true },
                    suspicious_apis: { name: 'Suspicious APIs', desc: 'Flagged dangerous calls', enabled: true },
                    ioc: { name: 'IoC Extraction', desc: 'URLs, IPs, emails, domains', enabled: true },
                    strings: { name: 'Strings Analysis', desc: 'ASCII + Unicode strings', enabled: true },
                    risk_score: { name: 'Risk Scoring', desc: 'IAT + VT + packing score', enabled: true },
                    // ── AI ────────────────────────────────────────────────────────
                    ai: { name: 'AI Expert System', desc: 'Behavioral AI + Gemini', enabled: false },
                },

                logs: [],

                selectAllTools() {
                    for (let key in this.tools) {
                        this.tools[key].enabled = true;
                    }
                    this.addLog('System', 'All analysis tools enabled.', 'text-emerald-400');
                },

                clearAllTools() {
                    for (let key in this.tools) {
                        this.tools[key].enabled = false;
                    }
                    this.addLog('System', 'All tools cleared.', 'text-amber-400');
                },

                toggleHashChildren(enabled) {
                    this.tools.hash_sha256.enabled = enabled;
                    this.tools.hash_sha1.enabled = enabled;
                    this.tools.hash_md5.enabled = enabled;
                },

                addLog(type, text, color = 'text-body-contrast') {
                    const prefixes = {
                        'Command': '<span class="text-green-400">$</span>',
                        'System': '<span class="text-gray-400">[SYS]</span>',
                        'Info': '<span class="text-blue-400">[INFO]</span>',
                        'Success': '<span class="text-emerald-400">[OK]</span>',
                        'Warn': '<span class="text-amber-400">[WARN]</span>',
                        'Error': '<span class="text-red-400">[ERR]</span>',
                        'AI': '<span class="text-purple-400">[AI]</span>',
                    };
                    this.logs.push({ prefix: prefixes[type] || '', text, color });

                    this.$nextTick(() => {
                        const term = document.getElementById('terminalLog');
                        term.scrollTop = term.scrollHeight;
                    });
                },

                executeCommand() {
                    if (!this.commandInput.trim()) return;

                    const cmd = this.commandInput.trim();
                    this.addLog('Command', cmd, 'text-gray-300');
                    this.commandInput = '';

                    const parts = cmd.split(' ');

                    if (parts[0].toLowerCase() === 'help') {
                        this.addLog('System', 'Available commands:', 'text-gray-400');
                        this.addLog('System', '  Trapix analyze all   - Run all enabled tools on uploaded file', 'text-gray-400');
                        this.addLog('System', '  Trapix run <tool>    - Run specific tool (static, ioc, meta)', 'text-gray-400');
                        this.addLog('System', '  clear                - Clear terminal', 'text-gray-400');
                        return;
                    }

                    if (cmd.toLowerCase() === 'clear') {
                        this.logs = [];
                        return;
                    }

                    if (parts[0].toLowerCase() === 'trapix') {
                        if (!this.selectedFile) {
                            this.addLog('Error', 'No file uploaded. Please upload a file first.', 'text-red-400');
                            return;
                        }

                        if (parts[1] === 'analyze' && parts[2] === 'all') {
                            this.startUpload();
                        } else if (parts[1] === 'run') {
                            this.addLog('System', `Simulating run of module: ${parts[2]}...`, 'text-green-400');
                            this.startUpload();
                        } else {
                            this.addLog('Error', 'Unknown Trapix command syntax.', 'text-red-400');
                        }
                        return;
                    }

                    this.addLog('Error', `Command not found: ${parts[0]}`, 'text-red-400');
                },

                handleDrop(e) {
                    this.dragover = false;
                    if (e.dataTransfer.files.length > 0) {
                        this.selectedFile = e.dataTransfer.files[0];
                        this.addLog('Info', `File selected: ${this.selectedFile.name} (${(this.selectedFile.size / 1024).toFixed(2)} KB)`);
                        this.startUpload();
                    }
                },

                handleFileSelect(e) {
                    if (e.target.files.length > 0) {
                        this.selectedFile = e.target.files[0];
                        this.addLog('Info', `File selected: ${this.selectedFile.name} (${(this.selectedFile.size / 1024).toFixed(2)} KB)`);
                        this.startUpload();
                    }
                },

                async startUpload() {
                    if (!this.selectedFile) return;

                    this.isUploading = true;
                    this.isCompleted = false;
                    this.progress = 10;
                    this.scanStatusText = 'Initializing upload...';
                    this.addLog('System', 'Initializing API upload request...', 'text-green-400');

                    // Build options: separate tool keys from hash algorithm selections
                    const hashAlgos = ['hash_sha256', 'hash_sha1', 'hash_md5']
                        .filter(k => this.tools[k]?.enabled)
                        .map(k => k.replace('hash_', ''));
                    const selectedToolKeys = Object.keys(this.tools)
                        .filter(k => this.tools[k].enabled && !this.tools[k].algo)
                        .filter(k => k !== 'ai'); // AI is handled separately server-side
                    const aiEnabled = this.tools['ai'].enabled;
                    const skipVt = !this.tools['vt']?.enabled;

                    const formData = new FormData();
                    formData.append('files[]', this.selectedFile);
                    formData.append('skip_vt', skipVt ? 1 : 0);
                    formData.append('options', JSON.stringify({
                        tools: selectedToolKeys,
                        hash_algorithms: hashAlgos,
                        ai: aiEnabled,
                    }));

                    try {
                        this.scanStatusText = 'Uploading...';
                        this.addLog('System', 'Uploading file to secure sandbox...', 'text-green-400');

                        const response = await fetch('/api/analysis', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (response.status === 429) {
                            this.addLog('Error', `Quota Exceeded: ${data.error}`, 'text-red-400');
                            // Show quota modal
                            this.quotaModal = {
                                show: true,
                                message: data.error,
                                requireLogin: data.require_login || false,
                                redirectTo: data.redirect_to || (data.upgrade ? '/pricing' : '/register'),
                                btnLabel: data.upgrade ? 'Upgrade Plan' : 'Create Free Account',
                            };
                            this.resetScan();
                            return;
                        }

                        if (!response.ok) {
                            let errMsg = data.error || data.message || 'Unknown server error';
                            this.addLog('Error', `Upload Failed [${response.status}]: ${errMsg}`, 'text-red-400');
                            console.error('Detailed Error:', data);
                            this.resetScan();
                            return;
                        }

                        this.jobId = data.job_id;
                        this.pollUrl = data.poll_url;

                        this.progress = 30;
                        this.scanStatusText = 'Analysis running...';
                        this.addLog('Success', `Upload complete. Job ID: ${this.jobId}`, 'text-emerald-400');
                        this.addLog('Info', 'Dispatching tools to sandboxed environment...', 'text-green-400');

                        // Start listening to WebSocket
                        this.listenForProgress();

                    } catch (error) {
                        console.error('Fetch Error:', error);
                        this.addLog('Error', `Network Error: ${error.message}. See console for details.`, 'text-red-400');
                        this.resetScan();
                    }
                },

                listenForProgress() {
                    if (!window.Echo) {
                        this.addLog('Error', 'Real-time WebSocket connection not available. Falling back to polling.', 'text-red-400');
                        this.pollStatus(); // Fallback
                        return;
                    }

                    this.addLog('System', 'Subscribed to real-time progress updates...', 'text-green-400');

                    window.Echo.channel(`analysis.${this.jobId}`)
                        .listen('AnalysisProgressUpdated', (e) => {
                            this.progress = e.progress;
                            this.scanStatusText = e.message;
                            this.addLog('System', e.message, 'text-blue-400');

                            if (e.status === 'completed') {
                                this.addLog('Success', 'Analysis job marked as completed by worker.', 'text-emerald-400');
                                window.Echo.leaveChannel(`analysis.${this.jobId}`);
                                this.fetchResults();
                            } else if (e.status === 'failed') {
                                this.addLog('Error', e.message, 'text-red-400');
                                window.Echo.leaveChannel(`analysis.${this.jobId}`);
                                this.resetScan();
                            }
                        });
                },

                async pollStatus() {
                    try {
                        const res = await fetch(this.pollUrl);
                        const data = await res.json();

                        if (data.status === 'completed') {
                            this.progress = 100;
                            this.scanStatusText = 'Finalizing results...';
                            this.addLog('Success', 'Analysis job marked as completed by worker.', 'text-emerald-400');
                            this.fetchResults();
                        } else if (data.status === 'failed') {
                            this.addLog('Error', 'Analysis job failed during processing.', 'text-red-400');
                            this.resetScan();
                        } else {
                            // simulate progress increase for visual effect
                            if (this.progress < 90) this.progress += 5;
                            this.addLog('Info', 'Analysis running... please wait.', 'text-gray-400');
                            setTimeout(() => this.pollStatus(), 3000);
                        }
                    } catch (err) {
                        this.addLog('Error', 'Failed to poll status.', 'text-red-400');
                        setTimeout(() => this.pollStatus(), 5000);
                    }
                },

                async fetchResults() {
                    try {
                        const res = await fetch(`/api/analysis/${this.jobId}/result`);
                        const data = await res.json();

                        this.isUploading = false;
                        this.isCompleted = true;
                        this.hasPdf = data.has_pdf;
                        this.pdfUrl = data.pdf_url;

                        this.addLog('System', 'Fetching result JSON...', 'text-green-400');
                        this.addLog('Info', `Risk Level: ${data.risk_level}`, data.risk_level === 'HIGH' ? 'text-red-400' : 'text-emerald-400');

                        if (this.tools['ai']?.enabled) {
                            this.addLog('AI', 'Expert system analysis generated actionable insights.', 'text-purple-400');
                        }

                        this.addLog('Success', 'Process fully completed. PDF Report available.', 'text-emerald-400 font-bold');

                    } catch (err) {
                        this.addLog('Error', 'Failed to fetch final results.', 'text-red-400');
                    }
                },

                resetScan() {
                    this.isUploading = false;
                    this.isCompleted = false;
                    this.progress = 0;
                    this.jobId = null;
                    this.selectedFile = null;
                    const fi = document.getElementById('fileInput');
                    if (fi) fi.value = '';
                }
            }));
        });
    </script>
@endpush