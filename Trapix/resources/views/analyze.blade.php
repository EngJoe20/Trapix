@extends('layouts.app')

@section('title', 'Scanner | Trapix')

@section('content')
<section class="relative min-h-[90vh] py-12 px-6 overflow-hidden">
    <!-- Ambient glow orbs -->
    <div class="dark:absolute hidden dark:block top-20 left-10 w-72 h-72 bg-cyan-500 rounded-full mix-blend-screen filter blur-[120px] opacity-20"></div>

    <div class="max-w-7xl mx-auto relative z-10" x-data="scannerApp()">
        
        <div class="mb-10 fade-in-up">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="text-heading-1">Threat</span>
                <span class="text-primary dark:text-cyan-400 neon-text">Scanner</span>
            </h1>
            <p class="text-body max-w-2xl">
                Upload files, select analysis tools, or use the command terminal. Trapix isolates your files in a secure sandbox.
            </p>
        </div>

        <div class="grid lg:grid-cols-12 gap-8">
            
            <!-- Left Panel: Upload & GUI Tools -->
            <div class="lg:col-span-5 flex flex-col gap-6 fade-in-up stagger-delay-1">
                
                <!-- Upload Dropzone -->
                <div class="glass-panel p-8 relative overflow-hidden group" 
                     id="dropZone"
                     @dragover.prevent="dragover = true"
                     @dragleave.prevent="dragover = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'border-cyan-400 shadow-[0_0_15px_rgba(34,211,238,0.5)]': dragover }">
                     
                    <input type="file" id="fileInput" class="hidden" multiple @change="handleFileSelect($event)">
                    
                    <!-- Dropzone Default -->
                    <div class="text-center cursor-pointer" @click="document.getElementById('fileInput').click()" x-show="!selectedFile && !isUploading && !isCompleted">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full border-2 border-primary/30 flex items-center justify-center group-hover:border-primary transition-colors">
                            <svg class="w-8 h-8 text-primary dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-heading-1 mb-2">Upload File or Folder</h3>
                        <p class="text-body text-sm">Drag & drop or click to browse (Max 100MB)</p>
                    </div>

                    <!-- File Selected UI -->
                    <div x-show="selectedFile && !isUploading && !isCompleted" style="display: none;" class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(34,211,238,0.1)]">
                            <svg class="w-8 h-8 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-heading-1 mb-1 truncate px-4" x-text="selectedFile ? selectedFile.name : ''"></h3>
                        <p class="text-body text-xs mb-6" x-text="selectedFile ? (selectedFile.size/1024/1024).toFixed(2) + ' MB' : ''"></p>
                        
                        <div class="flex flex-col gap-3 max-w-[200px] mx-auto">
                            <button @click="startUpload" class="btn-primary w-full py-2.5 flex items-center justify-center gap-2 group">
                                <span>START ANALYSIS</span>
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                            <button @click="resetScan" class="text-xs text-body hover:text-red-400 transition-colors uppercase tracking-wider font-semibold">Cancel</button>
                        </div>
                    </div>

                    <!-- Progress UI -->
                    <div x-show="isUploading" style="display: none;" class="text-center">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-cyan-400 font-mono" x-text="scanStatusText">Uploading...</span>
                            <span class="text-sm text-cyan-400 font-mono" x-text="progress + '%'">0%</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden bg-gray-800 w-full mb-4">
                            <div class="h-full rounded-full bg-cyan-400 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                        </div>
                        <p class="text-xs text-body font-mono">Job ID: <span x-text="jobId" class="text-emerald-400"></span></p>
                    </div>

                    <!-- Completed UI -->
                    <div x-show="isCompleted" style="display: none;" class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full border-2 border-emerald-400 flex items-center justify-center">
                            <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-emerald-400 mb-2">Analysis Complete</h3>
                        <div class="flex justify-center gap-4 mt-4">
                            <a :href="pdfUrl" x-show="hasPdf" class="btn-primary text-sm px-4 py-2" target="_blank">Download PDF Report</a>
                            <button @click="resetScan" class="btn-secondary text-sm px-4 py-2">Scan Another</button>
                        </div>
                    </div>
                </div>

                <!-- GUI Tool Selection -->
                <div class="glass-card p-6 relative">
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-box-border">
                        <h3 class="text-lg font-bold text-heading-2">Analysis Tools</h3>
                        <button @click="selectAllTools" class="text-xs text-cyan-400 hover:text-cyan-300">Select All</button>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="(tool, key) in tools" :key="key">
                            <label class="flex items-start gap-3 p-3 rounded-lg border border-box-border hover:border-cyan-500/50 cursor-pointer transition-colors"
                                   :class="{ 'bg-cyan-500/10 border-cyan-500/50': tool.enabled }">
                                <input type="checkbox" x-model="tool.enabled" class="mt-1 bg-transparent border-gray-500 text-cyan-500 focus:ring-cyan-500 rounded">
                                <div>
                                    <span class="text-sm font-semibold text-heading-3 block" x-text="tool.name"></span>
                                    <span class="text-xs text-body/70 block" x-text="tool.desc"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Right Panel: Terminal Command Mode -->
            <div class="lg:col-span-7 fade-in-up stagger-delay-2 h-full flex flex-col">
                <div class="glass-card flex-grow relative overflow-hidden flex flex-col h-[600px]">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-emerald-500 to-purple-500"></div>
                    
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
                            Example: <span class="text-cyan-400">Trapix analyze all</span>
                        </div>
                        
                        <template x-for="(log, index) in logs" :key="index">
                            <div class="terminal-line mb-1" :class="log.color">
                                <span x-html="log.prefix" class="mr-2"></span><span x-text="log.text"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Terminal Input -->
                    <div class="p-4 border-t border-box-border bg-black/40 flex items-center gap-2">
                        <span class="text-cyan-400 font-mono text-[12px]">$&gt;</span>
                        <input type="text" x-model="commandInput" @keydown.enter="executeCommand" 
                               class="w-full bg-transparent border-none outline-none text-heading-2 font-mono text-[12px] focus:ring-0 p-0"
                               placeholder="Enter command (e.g., Trapix run static)..."
                               :disabled="isUploading && !isCompleted">
                    </div>
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
        
        tools: {
            hash: { name: 'Hash Analysis', desc: 'SHA256, MD5 extraction', enabled: true },
            static: { name: 'Static Analysis', desc: 'PE Headers, Strings', enabled: true },
            meta: { name: 'Metadata', desc: 'Exif & File info', enabled: true },
            sig: { name: 'Signature', desc: 'Known malware signatures', enabled: true },
            ioc: { name: 'IOC Extraction', desc: 'IPs, URLs, Emails', enabled: true },
            python: { name: 'Custom Scripts', desc: 'Run custom Python modules', enabled: false },
            ai: { name: 'AI Expert System', desc: 'Machine Learning risk scoring', enabled: false },
        },
        
        logs: [],
        
        selectAllTools() {
            for (let key in this.tools) {
                this.tools[key].enabled = true;
            }
            this.addLog('System', 'All analysis tools enabled.', 'text-emerald-400');
        },
        
        addLog(type, text, color = 'text-body-contrast') {
            const prefixes = {
                'Command': '<span class="text-cyan-400">$</span>',
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
                    this.addLog('System', `Simulating run of module: ${parts[2]}...`, 'text-cyan-400');
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
                this.addLog('Info', `File selected: ${this.selectedFile.name} (${(this.selectedFile.size/1024).toFixed(2)} KB)`);
                this.startUpload();
            }
        },
        
        handleFileSelect(e) {
            if (e.target.files.length > 0) {
                this.selectedFile = e.target.files[0];
                this.addLog('Info', `File selected: ${this.selectedFile.name} (${(this.selectedFile.size/1024).toFixed(2)} KB)`);
                this.startUpload();
            }
        },
        
        async startUpload() {
            if (!this.selectedFile) return;
            
            this.isUploading = true;
            this.isCompleted = false;
            this.progress = 10;
            this.scanStatusText = 'Initializing upload...';
            this.addLog('System', 'Initializing API upload request...', 'text-cyan-400');
            
            // Build options from tools
            let selectedToolKeys = Object.keys(this.tools).filter(k => this.tools[k].enabled);
            let skipVt = !this.tools['sig'].enabled; // If signature disabled, skip VT
            
            const formData = new FormData();
            formData.append('files[]', this.selectedFile);
            formData.append('skip_vt', skipVt ? 1 : 0);
            formData.append('options', JSON.stringify({ tools: selectedToolKeys }));
            
            try {
                this.scanStatusText = 'Uploading...';
                this.addLog('System', 'Uploading file to secure sandbox...', 'text-cyan-400');
                
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
                this.addLog('Info', 'Dispatching tools to sandboxed environment...', 'text-cyan-400');
                
                // Start polling
                this.pollStatus();
                
            } catch (error) {
                console.error('Fetch Error:', error);
                this.addLog('Error', `Network Error: ${error.message}. See console for details.`, 'text-red-400');
                this.resetScan();
            }
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
                
                this.addLog('System', 'Fetching result JSON...', 'text-cyan-400');
                this.addLog('Info', `Risk Level: ${data.risk_level}`, data.risk_level === 'HIGH' ? 'text-red-400' : 'text-emerald-400');
                
                if (this.tools['ai'].enabled) {
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
            document.getElementById('fileInput').value = '';
        }
    }));
});
</script>
@endpush
