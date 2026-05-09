@extends('layouts.app')

@section('title', 'Dashboard - Trapix Security Analyzer')

@section('content')
<section class="relative min-h-[90vh] py-12 px-6 bg-bg overflow-hidden">
    <div class="max-w-7xl mx-auto relative z-10" x-data="dashboardApp()">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 fade-in-up">
            <div>
                <h1 class="text-3xl font-bold text-heading-1 mb-2">Security Dashboard</h1>
                <p class="text-body text-sm">Welcome back, {{ $user->name }} • Plan: <span class="text-cyan-400 font-semibold uppercase">{{ $plan?->name ?? 'Free' }}</span></p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('analyze') }}" class="btn-primary">New Analysis</a>
            </div>
        </div>

        <!-- Stats / Quota Grid -->
        <div class="grid md:grid-cols-3 gap-6 mb-10 fade-in-up stagger-delay-1">
            
            <!-- Quota Card -->
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-heading-2">Monthly Quota</h3>
                    <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                </div>
                
                @if($plan && $plan->hasUnlimitedAnalyses())
                    <div class="text-3xl font-bold text-emerald-400 mb-2">Unlimited</div>
                    <p class="text-body text-sm">Enterprise limit active</p>
                @else
                    @php
                        $used = $user->monthly_analysis_used;
                        $total = $plan?->monthly_analyses ?? 10;
                        $percent = min(100, ($used / max(1, $total)) * 100);
                        $color = $percent > 90 ? 'bg-red-500' : ($percent > 70 ? 'bg-amber-500' : 'bg-cyan-500');
                    @endphp
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-body-contrast">{{ $used }} used</span>
                        <span class="text-body">{{ $total }} total</span>
                    </div>
                    <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden mb-2">
                        <div class="h-full {{ $color }} rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-xs text-body">Resets on {{ $user->quota_reset_date ? $user->quota_reset_date->format('M j, Y') : '1st of month' }}</p>
                @endif
            </div>

            <!-- Features Card -->
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-heading-2">Plan Features</h3>
                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <ul class="space-y-2 text-sm text-body-contrast">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        Max Upload Size: {{ $plan?->maxUploadMb() ?? 10 }}MB
                    </li>
                    <li class="flex items-center gap-2">
                        @if($plan?->ai_access ?? false)
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            AI Expert System Enabled
                        @else
                            <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            <span class="text-body opacity-70">AI Expert System</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2 mt-3">
                        <a href="{{ route('pricing') }}" class="text-cyan-400 hover:text-cyan-300 underline text-xs">Upgrade Plan</a>
                    </li>
                </ul>
            </div>

            <!-- Download Stats Card -->
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-heading-2">Total Analyses</h3>
                    <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div class="text-4xl font-bold text-heading-1 mb-2">{{ $jobs->total() ?? 0 }}</div>
                <p class="text-body text-sm">Historical records safely stored</p>
            </div>

        </div>

        <!-- History Table -->
        <div class="glass-panel p-6 fade-in-up stagger-delay-2">
            
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 border-b border-box-border pb-4">
                <h3 class="text-xl font-bold text-heading-1">Analysis History</h3>
                
                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                    <input type="text" x-model="search" @input.debounce.500ms="fetchHistory(1)" placeholder="Search files..." class="bg-black/50 border border-box-border rounded px-3 py-1.5 text-sm text-body focus:border-cyan-500 outline-none w-full md:w-48">
                    
                    <select x-model="riskFilter" @change="fetchHistory(1)" class="bg-black/50 border border-box-border rounded px-3 py-1.5 text-sm text-body focus:border-cyan-500 outline-none">
                        <option value="">All Risks</option>
                        <option value="CRITICAL">Critical</option>
                        <option value="HIGH">High</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="LOW">Low</option>
                        <option value="SAFE">Safe</option>
                    </select>
                    
                    <button @click="fetchHistory(1)" class="btn-secondary text-sm px-3 py-1.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-box-border/50 text-body text-xs uppercase tracking-wider">
                            <th class="p-3">Job ID</th>
                            <th class="p-3">Files</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Risk</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <template x-if="isLoading">
                            <tr><td colspan="6" class="p-8 text-center text-body"><div class="w-6 h-6 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div> Loading...</td></tr>
                        </template>
                        
                        <template x-if="!isLoading && history.length === 0">
                            <tr><td colspan="6" class="p-8 text-center text-body opacity-60">No analysis jobs found.</td></tr>
                        </template>

                        <template x-for="job in history" :key="job.id">
                            <tr class="border-b border-box-border/30 hover:bg-white/5 transition-colors group">
                                <td class="p-3 font-mono text-xs text-body" x-text="job.id.substring(0,8) + '...'"></td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <span class="text-heading-3" x-text="job.file_count + ' File(s)'"></span>
                                    </div>
                                </td>
                                <td class="p-3 text-body" x-text="new Date(job.created_at).toLocaleDateString()"></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                          :class="{
                                            'bg-emerald-500/20 text-emerald-400': job.status === 'completed',
                                            'bg-amber-500/20 text-amber-400': job.status === 'pending' || job.status === 'processing',
                                            'bg-red-500/20 text-red-400': job.status === 'failed'
                                          }" x-text="job.status.toUpperCase()"></span>
                                </td>
                                <td class="p-3">
                                    <span x-show="job.report && job.report.risk_level" class="px-2 py-0.5 rounded text-xs font-bold"
                                          :class="{
                                            'bg-red-500/20 text-red-400 border border-red-500/30': job.report?.risk_level === 'CRITICAL' || job.report?.risk_level === 'HIGH',
                                            'bg-amber-500/20 text-amber-400 border border-amber-500/30': job.report?.risk_level === 'MEDIUM',
                                            'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': job.report?.risk_level === 'LOW' || job.report?.risk_level === 'SAFE'
                                          }" x-text="job.report?.risk_level"></span>
                                    <span x-show="!job.report" class="text-body/50 text-xs">-</span>
                                </td>
                                <td class="p-3 text-right">
                                    <a x-show="job.status === 'completed' && job.report?.pdf_path" 
                                       :href="'/api/analysis/' + job.id + '/report'" 
                                       target="_blank"
                                       class="text-cyan-400 hover:text-cyan-300 text-xs font-semibold flex items-center justify-end gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex items-center justify-between" x-show="lastPage > 1">
                <button @click="fetchHistory(currentPage - 1)" :disabled="currentPage === 1" class="btn-secondary text-xs px-3 py-1 disabled:opacity-50">Prev</button>
                <span class="text-xs text-body">Page <span x-text="currentPage"></span> of <span x-text="lastPage"></span></span>
                <button @click="fetchHistory(currentPage + 1)" :disabled="currentPage === lastPage" class="btn-secondary text-xs px-3 py-1 disabled:opacity-50">Next</button>
            </div>
            
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardApp', () => ({
        history: [],
        isLoading: true,
        search: '',
        riskFilter: '',
        currentPage: 1,
        lastPage: 1,
        
        init() {
            this.fetchHistory();
        },
        
        async fetchHistory(page = 1) {
            this.isLoading = true;
            this.currentPage = page;
            
            try {
                const url = new URL(window.location.origin + '/dashboard/history');
                url.searchParams.append('page', page);
                if (this.search) url.searchParams.append('search', this.search);
                if (this.riskFilter) url.searchParams.append('risk', this.riskFilter);
                
                const res = await fetch(url);
                const data = await res.json();
                
                this.history = data.data;
                this.lastPage = data.last_page;
            } catch (err) {
                console.error("Failed to fetch history", err);
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
@endpush
