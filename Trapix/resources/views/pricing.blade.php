@extends('layouts.app')

@section('title', 'Pricing - Trapix Security Analyzer')

@section('content')
<section class="relative min-h-[80vh] py-16 px-6 overflow-hidden bg-bg">
    <div class="max-w-7xl mx-auto relative z-10">
        
        <div class="text-center mb-16 fade-in-up">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="text-heading-1">Choose Your</span>
                <span class="text-primary dark:text-cyan-400 neon-text">Arsenal</span>
            </h1>
            <p class="text-body max-w-2xl mx-auto">
                Transparent pricing for security professionals and enterprises. Scale your threat hunting with Trapix.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            
            <!-- Free Plan -->
            <div class="glass-card p-8 relative flex flex-col fade-in-up stagger-delay-1 border border-box-border hover:border-gray-500 transition-colors">
                <div class="mb-8">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-500/20 text-gray-400 mb-4 inline-block">GUEST / FREE</span>
                    <h3 class="text-3xl font-bold text-heading-1 mb-2">$0 <span class="text-sm font-normal text-body">/ month</span></h3>
                    <p class="text-body text-sm">Perfect for occasional analysis</p>
                </div>
                
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">10 analyses / month</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Max upload: 10 MB</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Static & Dynamic Analysis</span>
                    </li>
                    <li class="flex items-start gap-3 opacity-50">
                        <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span class="text-sm text-body-contrast">No AI Expert System</span>
                    </li>
                </ul>
                
                <a href="{{ route('register') }}" class="btn-secondary w-full text-center py-3">Get Started</a>
            </div>

            <!-- Pro Plan -->
            <div class="glass-card p-8 relative flex flex-col fade-in-up stagger-delay-2 border-2 border-cyan-500 shadow-[0_0_30px_rgba(34,211,238,0.2)] transform md:-translate-y-4">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    <span class="px-4 py-1 text-xs font-bold rounded-full bg-cyan-500 text-white shadow-lg">MOST POPULAR</span>
                </div>
                
                <div class="mb-8">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-cyan-500/20 text-cyan-400 mb-4 inline-block">PROFESSIONAL</span>
                    <h3 class="text-3xl font-bold text-heading-1 mb-2">$49 <span class="text-sm font-normal text-body">/ month</span></h3>
                    <p class="text-body text-sm">For independent researchers and SOC analysts</p>
                </div>
                
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">200 analyses / month</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Max upload: 100 MB</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">AI Expert System Integration</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Unlimited PDF Downloads</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Priority Queue Processing</span>
                    </li>
                </ul>
                
                <button class="btn-primary w-full text-center py-3">Upgrade to Pro</button>
            </div>

            <!-- Enterprise Plan -->
            <div class="glass-card p-8 relative flex flex-col fade-in-up stagger-delay-3 border border-box-border hover:border-purple-500 transition-colors">
                <div class="mb-8">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-500/20 text-purple-400 mb-4 inline-block">ENTERPRISE</span>
                    <h3 class="text-3xl font-bold text-heading-1 mb-2">Custom</h3>
                    <p class="text-body text-sm">For high-volume teams and MSSPs</p>
                </div>
                
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Unlimited analyses</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Max upload: 500 MB</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">API Access & Webhooks</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Custom AI Tuning</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm text-body-contrast">Dedicated Account Manager</span>
                    </li>
                </ul>
                
                <button class="btn-secondary w-full text-center py-3 border-purple-500/50 hover:bg-purple-500/10 text-purple-400">Contact Sales</button>
            </div>

        </div>
    </div>
</section>
@endsection
