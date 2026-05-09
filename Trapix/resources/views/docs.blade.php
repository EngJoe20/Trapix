@extends('layouts.app')

@section('title', 'Documentation - Trapix')

@section('content')
<section class="relative min-h-screen py-12 px-6 overflow-hidden bg-bg">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-8">
        
        <!-- Sidebar Navigation -->
        <div class="md:w-64 flex-shrink-0">
            <div class="glass-card p-6 sticky top-24">
                <h3 class="text-lg font-bold text-heading-1 mb-4 border-b border-box-border pb-2">Contents</h3>
                <ul class="space-y-3 text-sm text-body">
                    <li><a href="#overview" class="hover:text-cyan-400 transition-colors">Overview</a></li>
                    <li><a href="#upload-flow" class="hover:text-cyan-400 transition-colors">Upload Workflow</a></li>
                    <li><a href="#tools" class="hover:text-cyan-400 transition-colors">Tool Selection</a></li>
                    <li><a href="#terminal" class="hover:text-cyan-400 transition-colors">Terminal Commands</a></li>
                    <li><a href="#subscriptions" class="hover:text-cyan-400 transition-colors">Subscriptions & Quotas</a></li>
                    <li><a href="#ai-system" class="hover:text-cyan-400 transition-colors">AI Expert System</a></li>
                </ul>
            </div>
        </div>

        <!-- Documentation Content -->
        <div class="flex-grow glass-panel p-8 md:p-12 prose prose-invert max-w-none">
            
            <h1 class="text-4xl font-bold text-heading-1 mb-8">Trapix Documentation</h1>

            <div id="overview" class="mb-12">
                <h2 class="text-2xl font-bold text-cyan-400 mb-4">Overview</h2>
                <p class="text-body-contrast">
                    Trapix is an advanced malware triage and analysis platform. It integrates a powerful Python-based static analysis engine with an isolated runtime sandbox to identify threats, extract Indicators of Compromise (IOCs), and provide an AI-powered risk assessment.
                </p>
            </div>

            <div id="upload-flow" class="mb-12">
                <h2 class="text-2xl font-bold text-cyan-400 mb-4">Upload Workflow</h2>
                <p class="text-body-contrast mb-4">The scanner pipeline processes your files securely through the following phases:</p>
                <div class="grid md:grid-cols-3 gap-4 mb-6 not-prose">
                    <div class="p-4 border border-box-border rounded-lg bg-black/20">
                        <h4 class="font-bold text-heading-2 mb-2">1. Ingestion</h4>
                        <p class="text-xs text-body">Files are securely uploaded, hashed (SHA256), and placed in a unique isolated workspace.</p>
                    </div>
                    <div class="p-4 border border-box-border rounded-lg bg-black/20">
                        <h4 class="font-bold text-heading-2 mb-2">2. Processing</h4>
                        <p class="text-xs text-body">Selected Python tools execute. PE headers, metadata, strings, and APIs are analyzed.</p>
                    </div>
                    <div class="p-4 border border-box-border rounded-lg bg-black/20">
                        <h4 class="font-bold text-heading-2 mb-2">3. Reporting</h4>
                        <p class="text-xs text-body">Results are aggregated. AI generates insights. A downloadable PDF report is produced.</p>
                    </div>
                </div>
            </div>

            <div id="tools" class="mb-12">
                <h2 class="text-2xl font-bold text-cyan-400 mb-4">GUI Tool Selection</h2>
                <p class="text-body-contrast mb-4">Before initiating an analysis, you can customize the toolchain via the GUI checkboxes:</p>
                <ul class="text-body-contrast">
                    <li><strong>Hash Analysis:</strong> Calculates MD5, SHA1, SHA256, and SSDEEP.</li>
                    <li><strong>Static Analysis:</strong> Parses PE structure, imports/exports, and section entropy.</li>
                    <li><strong>Signature Detection:</strong> Scans against YARA rules and VirusTotal definitions.</li>
                    <li><strong>IOC Extraction:</strong> Extracts hardcoded URLs, IP addresses, and email addresses.</li>
                </ul>
                <p class="text-sm text-gray-400 mt-2 bg-gray-900 p-3 rounded border border-gray-700">
                    <em>Tip: Disabling unnecessary tools speeds up analysis completion time.</em>
                </p>
            </div>

            <div id="terminal" class="mb-12">
                <h2 class="text-2xl font-bold text-cyan-400 mb-4">Terminal Command Mode</h2>
                <p class="text-body-contrast mb-4">For power users, Trapix includes an interactive web terminal. You must upload a file before executing specific tools.</p>
                <div class="bg-black p-4 rounded-lg font-mono text-sm border border-gray-800 text-cyan-400 mb-4 not-prose overflow-x-auto">
                    > Trapix help<br>
                    > Trapix run static<br>
                    > Trapix extractIOC<br>
                    > Trapix analyze all<br>
                    > clear
                </div>
            </div>

            <div id="subscriptions" class="mb-12">
                <h2 class="text-2xl font-bold text-cyan-400 mb-4">Subscriptions & Quotas</h2>
                <p class="text-body-contrast mb-4">Trapix implements strict quota management based on your subscription tier:</p>
                <div class="overflow-x-auto not-prose">
                    <table class="w-full text-left border-collapse border border-box-border text-sm">
                        <thead>
                            <tr class="bg-black/30">
                                <th class="p-3 border border-box-border text-heading-2">Plan</th>
                                <th class="p-3 border border-box-border text-heading-2">Analyses / Mo</th>
                                <th class="p-3 border border-box-border text-heading-2">Max Size</th>
                                <th class="p-3 border border-box-border text-heading-2">Report Downloads</th>
                                <th class="p-3 border border-box-border text-heading-2">AI Access</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-contrast">
                            <tr>
                                <td class="p-3 border border-box-border">Guest (No Login)</td>
                                <td class="p-3 border border-box-border">3 total</td>
                                <td class="p-3 border border-box-border">10 MB</td>
                                <td class="p-3 border border-box-border">1 per report</td>
                                <td class="p-3 border border-box-border">No</td>
                            </tr>
                            <tr>
                                <td class="p-3 border border-box-border">Free</td>
                                <td class="p-3 border border-box-border">10 / month</td>
                                <td class="p-3 border border-box-border">10 MB</td>
                                <td class="p-3 border border-box-border">2 per report</td>
                                <td class="p-3 border border-box-border">No</td>
                            </tr>
                            <tr>
                                <td class="p-3 border border-box-border">Professional</td>
                                <td class="p-3 border border-box-border">200 / month</td>
                                <td class="p-3 border border-box-border">100 MB</td>
                                <td class="p-3 border border-box-border">Unlimited</td>
                                <td class="p-3 border border-box-border">Yes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="ai-system" class="mb-12">
                <h2 class="text-2xl font-bold text-purple-400 mb-4">AI Expert System Integration</h2>
                <p class="text-body-contrast mb-4">
                    The platform architecture natively supports Large Language Models (LLMs) via an Adapter pattern. 
                    When enabled, analysis data is securely streamed to the AI provider to generate:
                </p>
                <ul class="text-body-contrast">
                    <li>Plain-english explanation of the malware's purpose.</li>
                    <li>MITRE ATT&CK technique mapping.</li>
                    <li>Remediation and response recommendations.</li>
                </ul>
                <p class="text-sm text-gray-400 mt-2 bg-gray-900 p-3 rounded border border-gray-700">
                    <em>Future Updates: We will integrate Google AI Studio for enhanced, faster risk evaluations directly into the pipeline.</em>
                </p>
            </div>

        </div>
    </div>
</section>
@endsection
