@extends('layouts.app')

@section('title', 'Trapix - Advanced Security Analyzer')

@section('content')
<!-- Hero Section with Terminal Aesthetic -->
<section class="relative min-h-[90vh] flex items-center justify-center px-6 py-12 overflow-hidden matrix-bg">

    <!-- Ambient glow orbs (Dark mode only) -->
    <div class="dark:absolute hidden dark:block top-20 left-10 w-72 h-72 bg-cyan-500 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-pulse"></div>
    <div class="dark:absolute hidden dark:block bottom-20 right-10 w-96 h-96 bg-emerald-500 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="max-w-6xl w-full relative z-10">
        <!-- Badge -->
        <div class="flex justify-center mb-8 fade-in-up">
            <span class="badge px-4 py-2 text-xs tracking-wider uppercase text-body-contrast">
                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse mr-2"></span>
                AI-Powered Threat Detection
            </span>
        </div>

        <!-- Main Heading with Terminal Typing Effect -->
        <div class="text-center mb-12 fade-in-up stagger-delay-1">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                <span class="text-heading-1">TRAPIX</span><br>
                <span class="typewriter-text typing-cursor text-primary dark:text-cyan-400 neon-text font-bold"
                      style="display: inline-block; overflow: hidden; white-space: nowrap;">
                    Security Analyzer
                </span>
            </h1>

            <p class="text-lg md:text-xl text-body max-w-2xl mx-auto leading-relaxed">
                Upload any file for instant malware detection, behavior analysis & AI-powered risk scoring.
                <span class="text-primary dark:text-cyan-400 font-semibold">Enterprise-grade security</span> in one click.
            </p>
        </div>

        <!-- Main Scanner Panel -->
        <div class="glass-panel p-8 md:p-12 neon-glow mb-12 fade-in-up stagger-delay-2 relative overflow-hidden">

            <!-- Scan line animation (active during scan) -->
            <div id="scanLine" class="scan-line hidden absolute inset-0 pointer-events-none"></div>

            <!-- Corner brackets -->
            <div class="corner-bracket corner-bracket--tl"></div>
            <div class="corner-bracket corner-bracket--tr"></div>
            <div class="corner-bracket corner-bracket--bl"></div>
            <div class="corner-bracket corner-bracket--br"></div>

            <!-- File Upload Area -->
            <div id="dropZone"
                 class="scanner-zone cursor-pointer transition-all duration-300"
                 style="min-height: 220px;">

                <!-- Upload Icon -->
                <svg class="scanner-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>

                <!-- Upload Text -->
                <h3 class="text-2xl font-bold text-heading-1 mb-2">
                    <span class="text-primary dark:text-cyan-400">Drop file</span> to scan
                </h3>
                <p class="text-body mb-6">or click to browse • Supports EXE, PDF, DOC, ZIP (max 100MB)</p>

                <!-- Hidden file input -->
                <input type="file" id="fileInput" class="hidden" multiple>

                <!-- Scanning Progress (initially hidden) -->
                <div id="scanProgress" class="hidden max-w-md mx-auto">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-cyan-400 font-mono" id="scanStatus">Initializing scan...</span>
                        <span class="text-sm text-cyan-400 font-mono" id="scanPercent">0%</span>
                    </div>
                    <div class="scan-progress h-2 rounded-full overflow-hidden bg-gray-800">
                        <div id="progressBar" class="scan-progress-bar h-full rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Terminal Output (during scan) -->
                <div id="terminalOutput" class="terminal-output hidden max-w-2xl mx-auto text-left font-mono text-xs">
                    <!-- Dynamic terminal lines will be inserted here -->
                </div>

            </div>
        </div>

        <!-- Quick Stats / Feature Cards -->
        <div class="grid md:grid-cols-3 gap-6 fade-in-up stagger-delay-3">

            <!-- Card 1 -->
            <div class="glass-card p-6 relative group cursor-pointer">
                <div class="absolute -top-2 -right-2 w-4 h-4 border-t-2 border-r-2 border-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-gradient-to-br from-cyan-500 to-blue-600 shadow-lg shadow-cyan-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-heading-2 mb-1">Static Analysis</h3>
                         <p class="text-sm text-body/80">Signature detection</p>
                    </div>
                </div>
                <p class="text-body-contrast text-sm leading-relaxed">
                    Deep inspection of file signatures, embedded scripts, and known threat patterns against 10M+ malware database.
                </p>
                <div class="mt-4 flex items-center gap-2">
                    <span class="status-safe text-sm">● 98.7% accuracy</span>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="glass-card p-6 relative group cursor-pointer">
                <div class="absolute -top-2 -right-2 w-4 h-4 border-t-2 border-r-2 border-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg shadow-emerald-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-heading-2 mb-1">Behavior Analysis</h3>
                         <p class="text-sm text-body/80">Runtime simulation</p>
                    </div>
                </div>
                <p class="text-body-contrast text-sm leading-relaxed">
                    Execute files in isolated sandbox to monitor system calls, network activity, and suspicious behavior patterns.
                </p>
                <div class="mt-4 flex items-center gap-2">
                    <span class="status-safe text-sm">● Active monitoring</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="glass-card p-6 relative group cursor-pointer">
                <div class="absolute -top-2 -right-2 w-4 h-4 border-t-2 border-r-2 border-purple-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-gradient-to-br from-purple-500 to-pink-600 shadow-lg shadow-purple-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-heading-2 mb-1">AI Risk Score</h3>
                         <p class="text-sm text-body/80">Machine learning</p>
                    </div>
                </div>
                <p class="text-body-contrast text-sm leading-relaxed">
                    Advanced ML models analyze threats, assign risk scores from 0-100, and provide actionable remediation steps.
                </p>
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-purple-400 text-sm">● Neural network active</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Decorative scan line across page (Dark mode only) -->
    <div class="dark:absolute hidden dark:block inset-0 pointer-events-none overflow-hidden opacity-10">
        <div class="w-full h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent animate-pulse"
             style="position: absolute; top: 30%;"></div>
        <div class="w-full h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-pulse"
             style="position: absolute; top: 70%; animation-delay: 1.5s;"></div>
    </div>
</section>

<!-- Security Features Banner -->
<section class="relative py-16 px-6 bg-gradient-to-b from-bg to-bg/50 overflow-hidden">

    <div class="max-w-6xl mx-auto">
        <div class="flex flex-wrap justify-center gap-8 md:gap-16 text-center">

            <div class="flex items-center gap-3 fade-in-up stagger-delay-1">
                <svg class="w-5 h-5 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-body-contrast text-sm">Real-time Protection</span>
            </div>

            <div class="flex items-center gap-3 fade-in-up stagger-delay-2">
                <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-body-contrast text-sm">10M+ Threat Database</span>
            </div>

            <div class="flex items-center gap-3 fade-in-up stagger-delay-3">
                <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z"/>
                    <path d="M10 4a1 1 0 011 1v4.586l2.707 2.707a1 1 0 01-1.414 1.414l-3-3A1 1 0 019 10V5a1 1 0 011-1z"/>
                </svg>
                <span class="text-body-contrast text-sm">Instant Reports</span>
            </div>

            <div class="flex items-center gap-3 fade-in-up stagger-delay-4">
                <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <span class="text-body-contrast text-sm">Zero False Positives</span>
            </div>

        </div>
    </div>
</section>

<!-- Scan Results Preview Section -->
<section class="relative py-20 px-6 bg-gradient-to-b from-bg/50 to-bg overflow-hidden">

    <div class="max-w-6xl mx-auto">

        <div class="grid md:grid-cols-2 gap-8 items-center">

            <!-- Terminal-like output -->
            <div class="fade-in-up stagger-delay-1">
                <div class="glass-card p-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-emerald-500"></div>

                <!-- Terminal header -->
                <div class="flex items-center gap-2 mb-4 pb-4 border-b border-box-border">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="ml-2 text-xs text-heading-3 font-mono">TRAPIX_SCAN.EXE</span>
                    </div>

                    <!-- Terminal content -->
                    <div class="font-mono text-sm space-y-2 text-heading-2" id="terminalDemo">
                        <div class="terminal-line" style="animation-delay: 0.1s">
                            <span class="text-primary dark:text-cyan-400">$</span> trapix scan --file="uploaded_document.exe"
                        </div>
                        <div class="terminal-line" style="animation-delay: 0.3s">
                            <span class="text-body/60">[→]</span> Initializing sandbox environment...
                        </div>
                        <div class="terminal-line" style="animation-delay: 0.5s">
                            <span class="text-emerald-600 dark:text-emerald-400">[OK]</span> Static analysis complete - 0 signatures found
                        </div>
                        <div class="terminal-line" style="animation-delay: 0.7s">
                            <span class="text-primary dark:text-cyan-400">[→]</span> Monitoring runtime behavior...
                        </div>
                        <div class="terminal-line" style="animation-delay: 0.9s">
                            <span class="text-amber-600 dark:text-amber-400">[WARN]</span> Detected 2 suspicious API calls
                        </div>
                        <div class="terminal-line" style="animation-delay: 1.1s">
                            <span class="text-purple-600 dark:text-purple-400">[AI]</span> Neural network evaluating risk...
                        </div>
                        <div class="terminal-line" style="animation-delay: 1.3s">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">[RESULT]</span> Risk Score: <span class="text-emerald-600 dark:text-emerald-400">12/100 (SAFE)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text content -->
            <div class="fade-in-up stagger-delay-2">
            <h2 class="text-4xl font-bold mb-6 leading-tight">
                <span class="text-heading-1">Advanced Threat</span><br>
                <span class="text-primary dark:text-cyan-400 neon-text">Detection Engine</span>
            </h2>
            <p class="text-body mb-8 leading-relaxed">
                Powered by machine learning and sandbox isolation, Trapix provides <span class="text-emerald-500 font-semibold">unmatched security</span>.
                Our system analyzes over 10 million threat signatures and monitors behavioral patterns in real-time.
            </p>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                             <h4 class="text-heading-2 font-semibold">Multi-layered Analysis</h4>
                             <p class="text-body/80 text-sm">Static, dynamic, and AI-powered heuristic scanning</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                             <h4 class="text-heading-2 font-semibold">Zero-Day Threat Detection</h4>
                             <p class="text-body/80 text-sm">Behavior-based analysis catches unknown malware</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                             <h4 class="text-heading-2 font-semibold">100% Private & Secure</h4>
                             <p class="text-body/80 text-sm">Files processed in isolated environment, never stored</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap gap-4">
                    <button class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Start Scanning
                    </button>
                    <button class="btn-secondary">
                        View Demo
                    </button>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- How It Works - Steps -->
<section class="relative py-20 px-6 bg-bg overflow-hidden">

    <div class="max-w-6xl mx-auto">

        <!-- Section header -->
        <div class="text-center mb-16 fade-in-up">
            <h2 class="text-4xl font-bold mb-4 heading-glow">
                <span class="text-heading-1">How It</span>
                <span class="text-primary dark:text-cyan-400 neon-text"> Works</span>
            </h2>
            <p class="text-body max-w-2xl mx-auto">
                Three simple steps to comprehensive file analysis and threat detection
            </p>
        </div>

        <!-- Steps grid -->
        <div class="grid md:grid-cols-3 gap-8 relative">

            <!-- Step 1 -->
            <div class="glass-card p-8 text-center relative fade-in-up stagger-delay-1 group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold border-2 border-bg shadow-lg shadow-cyan-500/40">
                    1
                </div>
                <div class="pt-6">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full border-2 border-primary/30 flex items-center justify-center group-hover:border-primary transition-colors">
                        <svg class="w-7 h-7 text-primary dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                     <h3 class="text-xl font-bold text-heading-2 mb-3">Upload File</h3>
                     <p class="text-body text-sm leading-relaxed">
                         Drag & drop or browse. Supports executables, documents, archives, images, and scripts.
                     </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="glass-card p-8 text-center relative fade-in-up stagger-delay-2 group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-gradient-to-r from-emerald-500 to-green-500 flex items-center justify-center text-white font-bold border-2 border-bg shadow-lg shadow-emerald-500/40">
                    2
                </div>
                <div class="pt-6">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full border-2 border-emerald-400/30 flex items-center justify-center group-hover:border-emerald-400 transition-colors">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                     <h3 class="text-xl font-bold text-heading-2 mb-3">Analyze</h3>
                     <p class="text-body text-sm leading-relaxed">
                         Static signature scan, sandbox behavioral analysis, and AI risk assessment run simultaneously.
                     </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="glass-card p-8 text-center relative fade-in-up stagger-delay-3 group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold border-2 border-bg shadow-lg shadow-purple-500/40">
                    3
                </div>
                <div class="pt-6">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full border-2 border-purple-400/30 flex items-center justify-center group-hover:border-purple-400 transition-colors">
                        <svg class="w-7 h-7 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                     <h3 class="text-xl font-bold text-heading-2 mb-3">Get Report</h3>
                     <p class="text-body text-sm leading-relaxed">
                         Receive detailed PDF report with risk score, indicators of compromise, and remediation steps.
                     </p>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const scanProgress = document.getElementById('scanProgress');
    const progressBar = document.getElementById('progressBar');
    const scanStatus = document.getElementById('scanStatus');
    const scanPercent = document.getElementById('scanPercent');
    const terminalOutput = document.getElementById('terminalOutput');
    const scanLine = document.getElementById('scanLine');

    // Click to upload
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', handleFiles);

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) {
            startScan(files[0]);
        }
    });

    function handleFiles(e) {
        const file = e.target.files[0];
        if (file) {
            startScan(file);
        }
    }

    function startScan(file) {
        // Show progress UI
        dropZone.querySelector('.scanner-icon').classList.add('hidden');
        dropZone.querySelector('h3').classList.add('hidden');
        dropZone.querySelector('p').classList.add('hidden');
        scanProgress.classList.remove('hidden');
        scanLine.classList.remove('hidden');
        terminalOutput.classList.remove('hidden');
        terminalOutput.innerHTML = '';

        const messages = [
            { text: `$ trapix scan --file="${file.name}"`, color: 'text-cyan-400', delay: 0 },
            { text: `[→] File size: ${(file.size / 1024).toFixed(2)} KB`, color: 'text-gray-400', delay: 500 },
            { text: `[→] Calculating SHA-256 hash...`, color: 'text-cyan-400', delay: 1000 },
            { text: `[OK] Hash: ${generateFakeHash()}`, color: 'text-emerald-400', delay: 1500 },
            { text: `[→] Uploading to sandbox...`, color: 'text-cyan-400', delay: 2000 },
            { text: `[→] Extracting embedded resources...`, color: 'text-cyan-400', delay: 2800 },
            { text: `[OK] 3 resources found`, color: 'text-emerald-400', delay: 3500 },
            { text: `[→] Running static signature scan...`, color: 'text-cyan-400', delay: 4200 },
            { text: `[OK] 0 known malware signatures`, color: 'text-emerald-400', delay: 5000 },
            { text: `[→] Monitoring runtime behavior...`, color: 'text-cyan-400', delay: 5800 },
            { text: `[INFO] API calls monitored: 142`, color: 'text-gray-400', delay: 6500 },
            { text: `[WARN] Detected unusual registry access`, color: 'text-amber-400', delay: 7200 },
            { text: `[→] AI model evaluating threat level...`, color: 'text-purple-400', delay: 8000 },
            { text: `[AI] Risk assessment: LOW (12/100)`, color: 'text-emerald-400 font-bold', delay: 8800 },
            { text: `[OK] Scan complete. Generating report...`, color: 'text-emerald-400', delay: 9500 },
        ];

        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 1;
            progressBar.style.width = `${progress}%`;
            scanPercent.textContent = `${progress}%`;

            if (progress >= 100) {
                clearInterval(progressInterval);
                setTimeout(() => {
                    scanStatus.textContent = 'Scan Complete';
                    scanStatus.className = 'text-sm text-emerald-400 font-mono';
                }, 500);
            }
        }, 95); // ~9.5 seconds total

        // Add terminal lines with typing effect
        messages.forEach((msg) => {
            setTimeout(() => {
                const line = document.createElement('span');
                line.className = `terminal-line block ${msg.color}`;
                line.textContent = msg.text;
                terminalOutput.appendChild(line);
            }, msg.delay);
        });
    }

    function generateFakeHash() {
        const chars = '0123456789abcdef';
        let hash = '';
        for (let i = 0; i < 64; i++) {
            hash += chars[Math.floor(Math.random() * chars.length)];
        }
        return hash;
    }

    // Intersection Observer for fade-in animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));
});
</script>
@endpush
