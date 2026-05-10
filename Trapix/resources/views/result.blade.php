@extends('layouts.app')

@section('title', 'Analysis Result | Trapix')

@section('content')
<section class="relative min-h-[90vh] py-10 px-4 md:px-6 overflow-hidden">
    <div class="max-w-7xl mx-auto relative z-10" x-data="resultApp()" x-init="init()">

        {{-- ── Header ── --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('analyze') }}" class="text-body hover:text-green-400 transition-colors text-sm">← New Scan</a>
                    @auth <span class="text-body/40">|</span>
                    <a href="{{ route('dashboard') }}" class="text-body hover:text-green-400 transition-colors text-sm">Dashboard</a>
                    @endauth
                </div>
                <h1 class="text-3xl font-bold text-heading-1">Analysis <span class="text-green-400 neon-text">Result</span></h1>
                <p class="text-body text-xs font-mono mt-1">Job ID: <span class="text-emerald-400">{{ $job->id }}</span></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('analysis.report.export-zip', $job->id) }}" target="_blank"
                   class="border border-green-500/50 hover:bg-green-500/10 text-green-400 font-bold flex items-center gap-2 text-sm px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Export ZIP
                </a>
                <a href="{{ route('analysis.report', $job->id) }}" target="_blank"
                   class="btn-primary flex items-center gap-2 text-sm px-4 py-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
                <span id="riskBadge" class="px-4 py-1.5 rounded-full text-sm font-bold border" :class="riskClass">
                    <span x-text="riskLevel">—</span>
                </span>
            </div>
        </div>

        {{-- ── Loading state ── --}}
        <div x-show="loading" class="flex flex-col items-center justify-center py-24">
            <div class="w-12 h-12 border-4 border-green-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-body font-mono text-sm">Loading analysis data...</p>
        </div>

        {{-- ── Error state ── --}}
        <div x-show="!loading && error" class="glass-card p-8 text-center">
            <p class="text-red-400 font-mono" x-text="error"></p>
        </div>

        {{-- ── Main Content ── --}}
        <div x-show="!loading && !error" class="flex flex-col lg:flex-row gap-6">
            
            {{-- ── File Selector (Sidebar) ── --}}
            <template x-if="allResults.length > 1">
                <div class="lg:w-72 shrink-0 space-y-4">
                    <h3 class="text-xs font-bold text-body uppercase tracking-widest px-2">Analyzed Files</h3>
                    <div class="glass-panel max-h-[600px] overflow-y-auto">
                        <template x-for="(res, idx) in allResults" :key="idx">
                            <button @click="currentFileIndex = idx; result = res" 
                                    class="w-full text-left p-4 border-b border-box-border/30 hover:bg-white/5 transition-colors group"
                                    :class="currentFileIndex === idx ? 'bg-green-500/10 border-l-2 border-l-green-400' : ''">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-sm font-semibold text-heading-2 truncate" :class="currentFileIndex === idx ? 'text-green-400' : ''" x-text="res.file_name"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded uppercase font-bold shrink-0" 
                                          :class="{
                                              'bg-red-500/20 text-red-400': res.risk_level === 'CRITICAL' || res.risk_level === 'HIGH',
                                              'bg-amber-500/20 text-amber-400': res.risk_level === 'MEDIUM',
                                              'bg-blue-500/20 text-blue-400': res.risk_level === 'LOW',
                                              'bg-gray-500/20 text-gray-400': !res.risk_level || res.risk_level === 'Unknown'
                                          }" x-text="res.risk_level || '??'"></span>
                                </div>
                                <p class="text-[10px] text-body mt-1 truncate" x-text="res.file_type"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <div class="flex-grow">

            {{-- ── Overview Cards ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="glass-card p-4">
                    <p class="text-xs text-body uppercase tracking-wider mb-1">File</p>
                    <p class="text-sm font-bold text-heading-2 truncate" x-text="result?.file_name || 'N/A'"></p>
                    <p class="text-xs text-body mt-1" x-text="result?.file_size || ''"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-xs text-body uppercase tracking-wider mb-1">File Type</p>
                    <p class="text-sm font-bold text-heading-2 truncate" x-text="result?.file_type || 'N/A'"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-xs text-body uppercase tracking-wider mb-1">VT Detection</p>
                    <p class="text-sm font-bold" :class="vtColor" 
                       x-text="result?.virustotal?.found ? result.virustotal.detection_ratio : (result?.virustotal?.queried ? 'Not Found' : 'Skipped')"></p>
                    <p class="text-xs text-body mt-1" x-text="result?.virustotal?.threat_label || result?.virustotal?.error || ''"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-xs text-body uppercase tracking-wider mb-1">Analysis Time</p>
                    <p class="text-xs font-mono text-heading-2" x-text="result?.analysis_time || '—'"></p>
                </div>
            </div>

            {{-- ── Tabs ── --}}
            <div class="glass-panel">
                <div class="flex gap-0 border-b border-box-border overflow-x-auto">
                    <template x-for="(tab, idx) in tabs" :key="idx">
                        <button @click="activeTab = idx"
                                class="px-5 py-3 text-sm font-semibold whitespace-nowrap transition-colors border-b-2 -mb-px"
                                :class="activeTab === idx
                                    ? 'border-green-400 text-green-400'
                                    : 'border-transparent text-body hover:text-heading-2'">
                            <span x-text="tab.label"></span>
                            <span x-show="tab.count !== null" class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] bg-white/10" x-text="tab.count"></span>
                        </button>
                    </template>
                </div>

                <div class="p-6">

                    {{-- Tab 0: Identification --}}
                    <div x-show="activeTab === 0">
                        <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4">Hashes & Identification</h3>
                        <div class="space-y-2 font-mono text-sm">
                            <template x-for="[k,v] in Object.entries(result?.hashes || {})" :key="k">
                                <div class="flex gap-3 p-3 rounded-lg bg-black/30 border border-box-border/40 hover:border-green-500/30 transition-colors">
                                    <span class="text-body uppercase text-xs w-20 flex-shrink-0 pt-0.5" x-text="k"></span>
                                    <span class="text-emerald-400 break-all text-xs" x-text="v"></span>
                                </div>
                            </template>
                        </div>

                        <template x-if="result?.virustotal?.raw_stats && Object.keys(result.virustotal.raw_stats).length > 0">
                            <div class="mt-8">
                                <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4">VirusTotal Engine Stats</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <template x-for="[stat, count] in Object.entries(result.virustotal.raw_stats)" :key="stat">
                                        <div class="p-3 rounded-lg bg-black/20 border border-box-border/20">
                                            <p class="text-[10px] text-body uppercase tracking-widest mb-1" x-text="stat"></p>
                                            <p class="text-sm font-bold" :class="count > 0 ? (stat === 'malicious' ? 'text-red-400' : 'text-amber-400') : 'text-heading-3'" x-text="count"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tab 1: PE Info --}}
                    <div x-show="activeTab === 1">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4">PE Header</h3>
                                <div class="space-y-2 text-sm">
                                    <template x-for="[k,v] in peRows" :key="k">
                                        <div class="flex justify-between p-2 rounded border-b border-box-border/30">
                                            <span class="text-body" x-text="k"></span>
                                            <span class="text-heading-2 font-mono text-xs" x-text="v"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4">Sections</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs font-mono">
                                        <thead><tr class="text-body border-b border-box-border">
                                            <th class="p-2 text-left">Name</th><th class="p-2 text-right">VAddr</th><th class="p-2 text-right">RSize</th><th class="p-2 text-right">Entropy</th>
                                        </tr></thead>
                                        <tbody>
                                            <template x-for="s in (result?.pe_info?.sections || [])" :key="s.name">
                                                <tr class="border-b border-box-border/20 hover:bg-white/5">
                                                    <td class="p-2 text-green-400" x-text="s.name"></td>
                                                    <td class="p-2 text-right text-body" x-text="s.virtual_address || s.vaddr || '—'"></td>
                                                    <td class="p-2 text-right text-body" x-text="s.raw_size || s.rsize || '—'"></td>
                                                    <td class="p-2 text-right" :class="(s.entropy||0)>7?'text-red-400':(s.entropy||0)>6?'text-amber-400':'text-emerald-400'" x-text="(s.entropy||0).toFixed(4)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Imports --}}
                        <div class="mt-6" x-show="Object.keys(result?.pe_info?.imports || {}).length > 0">
                            <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-3">Imports</h3>
                            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                                <template x-for="[dll, funcs] in Object.entries(result?.pe_info?.imports || {})" :key="dll">
                                    <details class="border border-box-border/40 rounded-lg">
                                        <summary class="px-4 py-2 cursor-pointer text-sm font-mono text-green-400 hover:bg-white/5 flex justify-between items-center">
                                            <span x-text="dll"></span>
                                            <span class="text-body text-xs" x-text="funcs.length + ' functions'"></span>
                                        </summary>
                                        <div class="p-3 overflow-x-auto">
                                            <table class="w-full text-xs font-mono"><thead><tr class="text-body"><th class="p-1 text-left">Name</th><th class="p-1 text-right">Ordinal</th><th class="p-1 text-right">Hint</th></tr></thead>
                                            <tbody><template x-for="f in funcs.slice(0,50)" :key="f.name||f.ordinal"><tr class="border-b border-box-border/20"><td class="p-1 text-heading-3" x-text="f.name||'(ordinal)'"></td><td class="p-1 text-right text-body" x-text="f.ordinal||'—'"></td><td class="p-1 text-right text-body" x-text="f.hint||'—'"></td></tr></template></tbody>
                                            </table>
                                        </div>
                                    </details>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Behavior --}}
                    <div x-show="activeTab === 2">
                        {{-- Suspicious APIs --}}
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4 flex items-center gap-2">
                                Suspicious APIs
                                <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs" x-text="Object.keys(result?.suspicious_apis||{}).length"></span>
                            </h3>
                            <template x-if="Object.keys(result?.suspicious_apis||{}).length === 0">
                                <p class="text-emerald-400 text-sm flex items-center gap-2"><span>✅</span> No suspicious APIs detected</p>
                            </template>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="[api, reason] in Object.entries(result?.suspicious_apis||{})" :key="api">
                                    <div class="flex gap-3 p-3 rounded-lg border border-red-500/20 bg-red-500/5">
                                        <span class="text-red-400 font-mono text-xs w-40 flex-shrink-0" x-text="api"></span>
                                        <span class="text-body text-xs" x-text="reason"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        {{-- IoCs --}}
                        <div>
                            <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-4">Indicators of Compromise</h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <template x-for="[cat, items] in Object.entries(result?.iocs||{})" :key="cat">
                                    <div x-show="items && items.length > 0" class="glass-card p-4">
                                        <h4 class="text-xs text-green-400 font-bold uppercase tracking-wider mb-3 flex justify-between">
                                            <span x-text="cat"></span>
                                            <span class="text-body" x-text="items.length + ' items'"></span>
                                        </h4>
                                        <div class="space-y-1 max-h-32 overflow-y-auto">
                                            <template x-for="item in items.slice(0,20)" :key="item">
                                                <p class="text-xs font-mono text-heading-3 break-all" x-text="item"></p>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: AI Insights --}}
                    <div x-show="activeTab === 3">
                        <template x-if="!aiData">
                            <div class="text-center py-12" x-data="{ running: false, aiError: null }">
                                <div class="w-16 h-16 rounded-full border-2 border-purple-500/30 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-purple-400/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-body text-sm mb-1">AI Expert System analysis not yet run.</p>
                                <p class="text-body/50 text-xs mb-6">Click below to analyze this file with AI and get behavioral insights.</p>
                                <p x-show="aiError" class="text-red-400 text-xs mb-4" x-text="aiError"></p>
                                @auth
                                <button @click="
                                    running = true; aiError = null;
                                    fetch('/api/analysis/{{ $job->id }}/run-ai', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                    })
                                    .then(r => r.json())
                                    .then(d => {
                                        if (d.insights) {
                                            $root.aiData = d.insights;
                                        } else {
                                            aiError = d.error || 'AI analysis failed.';
                                        }
                                    })
                                    .catch(e => { aiError = 'Network error: ' + e.message; })
                                    .finally(() => { running = false; })
                                " :disabled="running"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold transition-colors disabled:opacity-50">
                                    <div x-show="running" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    <svg x-show="!running" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-text="running ? 'Analyzing...' : 'Run AI Analysis'"></span>
                                </button>
                                @else
                                <p class="text-xs text-body/40">Login to use AI-assisted analysis.</p>
                                @endauth
                            </div>
                        </template>
                        <template x-if="aiData">
                            <div class="space-y-6">
                                <div x-show="aiData.summary" class="glass-card p-4 border-l-2 border-purple-500">
                                    <p class="text-xs text-purple-400 uppercase tracking-wider mb-2">Summary</p>
                                    <p class="text-sm text-heading-2" x-text="aiData.summary"></p>
                                </div>
                                <div x-show="(aiData.insights||[]).length > 0">
                                    <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-3">Key Insights</h3>
                                    <div class="space-y-2">
                                        <template x-for="(ins, i) in (aiData.insights||[])" :key="i">
                                            <div class="p-3 rounded-lg border border-box-border/40 bg-black/20">
                                                <span class="text-xs text-purple-400 uppercase" x-text="ins.category"></span>
                                                <p class="text-sm text-heading-3 mt-1" x-text="ins.detail"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="(aiData.mitre_techniques||[]).length > 0">
                                    <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-3">MITRE ATT&CK Techniques</h3>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="t in (aiData.mitre_techniques||[])" :key="t">
                                            <span class="px-3 py-1 rounded border border-amber-500/30 bg-amber-500/10 text-amber-400 text-xs font-mono" x-text="t"></span>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="(aiData.recommendations||[]).length > 0">
                                    <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider mb-3">Recommendations</h3>
                                    <ul class="space-y-1">
                                        <template x-for="(r, i) in (aiData.recommendations||[])" :key="i">
                                            <li class="flex gap-2 text-sm text-body">
                                                <span class="text-green-400 flex-shrink-0">→</span>
                                                <span x-text="r"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tab 4: Strings --}}
                    <div x-show="activeTab === 4">
                        <div class="mb-4 flex items-center gap-4">
                            <span class="text-sm text-body">Total: <strong class="text-heading-2" x-text="result?.strings?.total || 0"></strong></span>
                            <span class="text-sm text-body">ASCII: <strong class="text-green-400" x-text="(result?.strings?.ascii||[]).length"></strong></span>
                            <span class="text-sm text-body">Unicode: <strong class="text-purple-400" x-text="(result?.strings?.unicode||[]).length"></strong></span>
                        </div>
                        <div class="max-h-96 overflow-y-auto rounded-lg border border-box-border/40 bg-black/30 p-4 font-mono text-xs space-y-1">
                            <template x-for="s in (result?.strings?.ascii||[]).slice(0,500)" :key="s">
                                <div class="text-heading-3 break-all hover:text-green-400 transition-colors" x-text="s"></div>
                            </template>
                            <template x-for="s in (result?.strings?.unicode||[]).slice(0,200)" :key="s">
                                <div class="text-purple-400/80 break-all" x-text="s"></div>
                            </template>
                        </div>
                    </div>

                    {{-- Tab 5: Entropy --}}
                    <div x-show="activeTab === 5">
                        <div class="mb-4 glass-card p-4 flex items-center gap-4">
                            <div>
                                <p class="text-xs text-body uppercase tracking-wider">Overall File Entropy</p>
                                <p class="text-3xl font-bold font-mono" :class="(result?.entropy?.file_entropy||0)>7?'text-red-400':(result?.entropy?.file_entropy||0)>6?'text-amber-400':'text-emerald-400'" x-text="(result?.entropy?.file_entropy||0).toFixed(4)"></p>
                            </div>
                            <div class="text-xs text-body">
                                <p x-show="(result?.entropy?.file_entropy||0) > 7" class="text-red-400 font-semibold">⚠ Highly suspicious — likely packed or encrypted</p>
                                <p x-show="(result?.entropy?.file_entropy||0) <= 7 && (result?.entropy?.file_entropy||0) > 6" class="text-amber-400">Elevated entropy — possibly obfuscated</p>
                                <p x-show="(result?.entropy?.file_entropy||0) <= 6" class="text-emerald-400">Normal entropy range</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <template x-for="[sec, ent] in Object.entries(result?.entropy?.section_entropies||{})" :key="sec">
                                <div class="flex items-center gap-4">
                                    <span class="text-green-400 font-mono text-sm w-24 flex-shrink-0" x-text="sec"></span>
                                    <div class="flex-1 h-3 bg-black/40 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all" :style="'width:'+((ent/8)*100)+'%'" :class="ent>7?'bg-red-500':ent>6?'bg-amber-500':'bg-emerald-500'"></div>
                                    </div>
                                    <span class="font-mono text-sm w-12 text-right" :class="ent>7?'text-red-400':ent>6?'text-amber-400':'text-emerald-400'" x-text="ent.toFixed(4)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Tab 6: Collaboration --}}
                    <div x-show="activeTab === 6">
                        @auth
                            <div class="grid md:grid-cols-3 gap-6">
                                <div class="md:col-span-2 space-y-4">
                                    <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider">Analyst Notes</h3>
                                    <textarea x-model="notes" rows="10" 
                                              class="w-full bg-black/40 border border-box-border rounded-lg p-4 text-sm text-body font-mono focus:border-green-500 focus:ring-1 focus:ring-green-500 transition-colors"
                                              placeholder="Write your analysis notes here... (Markdown supported)"></textarea>
                                </div>
                                <div class="space-y-4">
                                    <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider">Tags</h3>
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <template x-for="(tag, idx) in tags" :key="idx">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded bg-green-500/10 border border-green-500/30 text-green-400 text-xs font-mono">
                                                <span x-text="tag"></span>
                                                <button @click="tags.splice(idx, 1)" class="hover:text-red-400 focus:outline-none">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="text" x-model="newTag" @keydown.enter="if(newTag.trim()){ tags.push(newTag.trim()); newTag=''; }"
                                               class="w-full bg-black/40 border border-box-border rounded-lg px-3 py-1.5 text-sm text-body focus:border-green-500 transition-colors"
                                               placeholder="Add a tag...">
                                        <button @click="if(newTag.trim()){ tags.push(newTag.trim()); newTag=''; }" class="btn-secondary px-3 py-1.5 text-xs">Add</button>
                                    </div>
                                    
                                    <div class="pt-6 border-t border-box-border/50">
                                        <button @click="saveCollaboration()" :disabled="savingCollab" 
                                                class="btn-primary w-full py-2.5 flex items-center justify-center gap-2">
                                            <svg x-show="savingCollab" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="savingCollab ? 'Saving...' : 'Save Changes'"></span>
                                        </button>
                                        <p x-show="collabMessage" class="text-emerald-400 text-xs text-center mt-2 font-mono" x-transition x-text="collabMessage"></p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-16">
                                <div class="w-16 h-16 rounded-full border-2 border-green-500/30 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-green-400/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <p class="text-body text-sm">Collaboration tools are only available to authenticated users.</p>
                                <div class="mt-4">
                                    <a href="{{ route('login') }}" class="btn-primary px-6 py-2 text-sm">Login to Annotate</a>
                                </div>
                            </div>
                        @endauth
                    </div>

                    {{-- Tab 7: Technical View (Raw JSON) --}}
                    <div x-show="activeTab === 7">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-heading-2 uppercase tracking-wider">Raw Analysis Payload</h3>
                            <button @click="navigator.clipboard.writeText(JSON.stringify(result, null, 2)); collabMessage = 'JSON copied to clipboard!'" class="text-xs text-green-400 hover:underline">Copy JSON</button>
                        </div>
                        <div class="bg-black/40 rounded-lg border border-box-border/30 p-4 font-mono text-[11px] text-emerald-400/80 overflow-auto max-h-[600px]">
                            <pre x-text="JSON.stringify(result, null, 4)"></pre>
                        </div>
                    </div>

                </div>{{-- /p-6 --}}
            </div>{{-- /glass-panel --}}
        </div>{{-- /main content --}}
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('resultApp', () => ({
        loading: true,
        error: null,
        result: null,
        allResults: [],
        currentFileIndex: 0,
        aiData: null,
        activeTab: 0,
        riskLevel: '—',
        riskClass: 'border-gray-500/30 text-gray-400',
        vtColor: 'text-body',
        
        // Collaboration state
        notes: @json($job->notes ?? ''),
        tags: @json($job->tags ?? []),
        newTag: '',
        savingCollab: false,
        collabMessage: '',

        get tabs() {
            return [
                { label: 'Identification', count: Object.keys(this.result?.hashes||{}).length || null },
                { label: 'PE Analysis',    count: this.result?.pe_info?.section_count ?? null },
                { label: 'Behavior',       count: (Object.keys(this.result?.suspicious_apis||{}).length + Object.values(this.result?.iocs||{}).reduce((a,v)=>a+(v?.length||0),0)) || null },
                { label: 'AI Insights',    count: (this.aiData?.insights||[]).length || null },
                { label: 'Strings',        count: this.result?.strings?.total || null },
                { label: 'Entropy',        count: Object.keys(this.result?.entropy?.section_entropies||{}).length || null },
                { label: 'Collaboration',  count: this.tags?.length || null },
                { label: 'Technical View', count: null },
            ];
        },

        get peRows() {
            const p = this.result?.pe_info || {};
            if (!p.is_pe) return [['Is PE', 'No']];
            return [
                ['Is PE',      p.is_pe ? 'Yes' : 'No'],
                ['Machine',    p.machine_type || '—'],
                ['Timestamp',  p.timestamp || '—'],
                ['Entry Point',p.entry_point || '—'],
                ['Subsystem',  p.subsystem || '—'],
                ['Is DLL',     p.is_dll ? 'Yes' : 'No'],
                ['Imphash',    p.imphash || '—'],
                ['Sections',   p.section_count ?? '—'],
                ['Import DLLs',p.import_dll_count ?? '—'],
                ['Import Fns', p.import_func_count ?? '—'],
            ];
        },

        async init() {
            try {
                const jobId = '{{ $job->id }}';
                const res = await fetch(`/api/analysis/${jobId}/result`);
                const data = await res.json();

                if (!res.ok) {
                    this.error = data.error || 'Failed to load result.';
                    return;
                }

                // The result may be wrapped in a 'results' array (bridge.py format)
                const raw = data.result || {};
                
                if (raw.results && Array.isArray(raw.results)) {
                    this.allResults = raw.results;
                    
                    // Try to find the most "interesting" file to show first (e.g. .exe, .dll, or highest risk)
                    let bestIdx = 0;
                    for (let i = 0; i < this.allResults.length; i++) {
                        const r = this.allResults[i];
                        const name = (r.file_name || "").toLowerCase();
                        if (name.endsWith(".exe") || name.endsWith(".dll") || r.risk_level === 'CRITICAL' || r.risk_level === 'HIGH') {
                            bestIdx = i;
                            break;
                        }
                    }
                    this.currentFileIndex = bestIdx;
                    this.result = this.allResults[bestIdx];
                } else {
                    this.result = raw;
                    this.allResults = [raw];
                }

                // AI data from aiResponse relation
                @if($job->aiResponse)
                @php
                    $aiDataArray = [
                        'summary'          => $job->aiResponse->insights['summary'] ?? '',
                        'insights'         => $job->aiResponse->insights['insights'] ?? [],
                        'recommendations'  => $job->aiResponse->insights['recommendations'] ?? [],
                        'mitre_techniques' => $job->aiResponse->insights['mitre_techniques'] ?? [],
                        'severity_score'   => $job->aiResponse->insights['severity_score'] ?? 0,
                    ];
                @endphp
                this.aiData = @json($aiDataArray);
                @endif

                this.setRiskDisplay();
            } catch(e) {
                this.error = 'Network error loading result: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        setRiskDisplay() {
            const risk = this.result?.risk_level || '{{ $job->report?->risk_level ?? '' }}';
            this.riskLevel = risk || '—';
            const map = {
                'CRITICAL':   'border-red-500/60 text-red-400 bg-red-500/10',
                'HIGH':       'border-red-400/50 text-red-400 bg-red-500/10',
                'SUSPICIOUS': 'border-amber-500/50 text-amber-400 bg-amber-500/10',
                'MEDIUM':     'border-amber-500/50 text-amber-400 bg-amber-500/10',
                'LOW':        'border-emerald-500/50 text-emerald-400 bg-emerald-500/10',
                'SAFE':       'border-emerald-500/50 text-emerald-400 bg-emerald-500/10',
                'CLEAN':      'border-emerald-500/50 text-emerald-400 bg-emerald-500/10',
            };
            this.riskClass = map[risk] || 'border-gray-500/30 text-gray-400';

            const vt = this.result?.virustotal || {};
            const mal = vt.malicious || 0;
            this.vtColor = mal >= 10 ? 'text-red-400' : mal > 0 ? 'text-amber-400' : 'text-emerald-400';
        },
        
        async saveCollaboration() {
            this.savingCollab = true;
            this.collabMessage = '';
            
            try {
                const res = await fetch(`/api/analysis/{{ $job->id }}/collaboration`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        notes: this.notes,
                        tags: this.tags
                    })
                });
                
                if (res.ok) {
                    this.collabMessage = 'Saved successfully!';
                    setTimeout(() => { this.collabMessage = ''; }, 3000);
                } else {
                    const data = await res.json();
                    this.collabMessage = data.error || 'Failed to save.';
                }
            } catch (err) {
                this.collabMessage = 'Network error.';
            } finally {
                this.savingCollab = false;
            }
        }
    }));
});
</script>
@endpush
