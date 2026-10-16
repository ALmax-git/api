<!DOCTYPE html>
<html class="dark" lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Core-X Developer Documentation | ALmax Cloud</title>

        <!-- SEO Meta Tags -->
        <meta name="description" content="Core-X Developer Documentation — comprehensive guides, API references, and examples for building on the ALmax Cloud platform.">
        <meta name="keywords" content="ALmax Cloud, Core-X, developer documentation, API, SDK, guides, tutorials, cloud platform">
        <meta name="author" content="ALmax Cloud">
        <meta name="robots" content="index, follow">
        <link href="https://api.almaxcloud.com/" rel="canonical" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="Core-X Developer Documentation | ALmax Cloud">
        <meta property="og:description" content="Comprehensive developer documentation for Core-X on ALmax Cloud — API references, quickstart guides, and examples.">
        <meta property="og:url" content="https://api.almaxcloud.com/">
        <meta property="og:site_name" content="ALmax Cloud">
        <meta property="og:image" content="https://api.almaxcloud.com/og-image.png">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:site" content="@almaxcloud">
        <meta name="twitter:title" content="Core-X Developer Documentation | ALmax Cloud">
        <meta name="twitter:description" content="API references, guides and examples for building on ALmax Cloud's Core-X platform.">
        <meta name="twitter:image" content="https://api.almaxcloud.com/og-image.png">

        <style>
            body {
                background-color: #020408;
                color: #f8fafc;
                font-family: 'Inter', sans-serif;
            }

            .bg-grid {
                background-size: 40px 40px;
                background-image:
                    linear-gradient(to right, rgba(0, 240, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(0, 240, 255, 0.03) 1px, transparent 1px);
            }

            .glow-cyan {
                box-shadow: 0 0 25px rgba(0, 240, 255, 0.15);
            }

            .glow-cyan-lg {
                box-shadow: 0 0 50px rgba(0, 240, 255, 0.25);
            }

            .glass-panel {
                background: rgba(13, 19, 34, 0.75);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(0, 240, 255, 0.15);
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            ::-webkit-scrollbar-track {
                background: #020408;
            }

            ::-webkit-scrollbar-thumb {
                background: #1e293b;
                border-radius: 3px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #00f0ff;
            }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>

    <body class="bg-grid bg-core-bg selection:bg-core-cyan min-h-screen overflow-x-hidden selection:text-black" x-data="coreDocsApp()" @keydown.window.prevent.ctrl.k="searchOpen = true" @keydown.window.prevent.meta.k="searchOpen = true" @keydown.escape.window="searchOpen = false">

        <div class="fixed bottom-6 right-6 z-50 transform transition-all duration-300" x-show="toastVisible" x-transition:enter="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="opacity-0">
            <div class="glass-panel glow-cyan border-core-cyan/40 text-core-cyan flex items-center space-x-3 rounded-lg border px-4 py-3">
                <svg class="text-core-cyan h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium text-white" x-text="toastMessage"></span>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/80 px-4 pt-20 backdrop-blur-md" x-show="searchOpen" x-transition.opacity @click.self="searchOpen = false">
            <div class="glass-panel glow-cyan-lg border-core-cyan/40 w-full max-w-2xl overflow-hidden rounded-xl border shadow-2xl" @click.stop>
                <div class="border-core-border flex items-center border-b px-4 py-3">
                    <svg class="text-core-cyan mr-3 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input class="w-full bg-transparent text-sm font-medium text-white placeholder-slate-500 focus:outline-none" type="text" x-model="searchQuery" placeholder="Search documentation, APIs, SDKs, or error codes..." autofocus x-ref="searchInput">
                    <span class="border-core-border rounded border bg-slate-900 px-2 py-1 text-xs text-slate-500">ESC</span>
                </div>
                <div class="max-h-96 space-y-1 overflow-y-auto p-2">
                    <template x-for="result in filteredSearchResults" :key="result.id">
                        <button class="group flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-cyan-500/10" @click="navigateTo(result.id); searchOpen = false; searchQuery=''">
                            <div>
                                <div class="group-hover:text-core-cyan flex items-center space-x-2 text-sm font-medium text-white">
                                    <span class="border-core-border rounded border bg-slate-800 px-1.5 py-0.5 text-xs text-slate-400" x-text="result.category"></span>
                                    <span x-text="result.title"></span>
                                </div>
                                <p class="mt-0.5 max-w-lg truncate text-xs text-slate-400" x-text="result.excerpt"></p>
                            </div>
                            <svg class="group-hover:text-core-cyan h-4 w-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </template>
                    <div class="py-8 text-center text-sm text-slate-500" x-show="filteredSearchResults.length === 0">
                        No matching documentation found for "<span x-text="searchQuery"></span>"
                    </div>
                </div>
                <div class="border-core-border flex items-center justify-between border-t bg-slate-950/80 px-4 py-2 text-xs text-slate-500">
                    <span>Tip: Use <kbd class="rounded bg-slate-800 px-1 py-0.5 text-slate-300">↑</kbd> <kbd class="rounded bg-slate-800 px-1 py-0.5 text-slate-300">↓</kbd> to navigate</span>
                    <span class="text-core-cyan font-mono">X-Engine v1.0</span>
                </div>
            </div>
        </div>

        <header class="glass-panel border-core-border sticky top-0 z-40 w-full border-b backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-4">
                    <button class="hover:text-core-cyan p-1 text-slate-400 md:hidden" @click="mobileSidebarOpen = !mobileSidebarOpen">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex cursor-pointer items-center space-x-3" @click="navigateTo('overview')">
                        <div class="glow-cyan flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600" style="background-image: url('/logo.png'); background-size: cover; background-repeat: no-repeat;">
                            {{-- <span class="text-lg font-extrabold text-black">CX</span> --}}
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-base font-bold tracking-wider text-white">X-Engine v1.0</span>
                                <span class="border-core-cyan/30 text-core-cyan rounded border bg-cyan-500/10 px-1.5 py-0.5 font-mono text-[10px] uppercase">Docs</span>
                            </div>
                            <p class="font-mono text-[10px] tracking-tight text-slate-400">ALmax Cloud Web OS Engine</p>
                        </div>
                    </div>
                </div>

                <!-- Search bar trigger & Version selector -->
                <div class="flex items-center space-x-4">
                    <button class="border-core-border hover:border-core-cyan/50 hidden w-64 items-center space-x-3 rounded-lg border bg-slate-900/80 px-3 py-1.5 text-xs text-slate-400 transition-all sm:flex" @click="searchOpen = true">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span class="flex-1 text-left">Search docs...</span>
                        <kbd class="rounded border border-slate-700 bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400">⌘K</kbd>
                    </button>

                    <!-- Version Selector -->
                    <div class="relative" x-data="{ open: false }">
                        <button class="border-core-border hover:border-core-cyan/40 flex items-center space-x-2 rounded-lg border bg-slate-900/80 px-3 py-1.5 font-mono text-xs text-slate-300" @click="open = !open">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                            <span>v1.0</span>
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="glass-panel border-core-border absolute right-0 z-50 mt-2 w-48 rounded-lg border py-1 text-xs shadow-xl" x-show="open" @click.outside="open = false">
                            <a class="text-core-cyan block px-4 py-2 font-mono hover:bg-cyan-500/10" href="#" @click.prevent="open = false; showToast('Switched to v1.0')">v1.0 (Stable)</a>
                            {{-- <a class="block px-4 py-2 font-mono text-slate-300 hover:bg-cyan-500/10" href="#" @click.prevent="open = false; showToast('Switched to v2.6.0-BETA')">v2.6.0-BETA</a> --}}
                            {{-- <a class="block px-4 py-2 font-mono text-slate-300 hover:bg-cyan-500/10" href="#" @click.prevent="open = false; showToast('Switched to v2.4.2')">v2.4.2 (Legacy)</a> --}}
                        </div>
                    </div>

                    <a class="bg-core-cyan hidden items-center rounded-lg px-3 py-1.5 text-xs font-semibold text-black shadow-[0_0_15px_rgba(0,240,255,0.3)] transition-colors hover:bg-cyan-400 md:inline-flex" href="#playground" @click.prevent="navigateTo('api-playground')">
                        API Playground
                    </a>
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <!-- Mobile Sidebar Backdrop -->
            <div class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm md:hidden" x-show="mobileSidebarOpen" @click="mobileSidebarOpen = false"></div>

            <aside class="border-core-border bg-core-bg fixed bottom-0 left-0 top-16 z-50 w-72 shrink-0 space-y-6 overflow-y-auto border-r p-6 transition-transform duration-300 md:top-auto md:z-0 md:translate-x-0 md:border-0 md:bg-transparent" :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'">

                <!-- Quick Navigation Menu -->
                <div class="space-y-6">
                    <template x-for="category in categories" :key="category.name">
                        <div>
                            <h3 class="mb-2 flex items-center justify-between font-mono text-xs uppercase tracking-wider text-slate-400">
                                <span x-text="category.name"></span>
                                <span class="bg-core-cyan/40 h-1.5 w-1.5 rounded-full"></span>
                            </h3>
                            <ul class="border-core-border/60 ml-1 space-y-1 border-l pl-3">
                                <template x-for="item in category.items" :key="item.id">
                                    <li>
                                        <button class="group flex w-full items-center justify-between rounded px-2 py-1.5 text-left text-xs transition-all" @click="navigateTo(item.id); mobileSidebarOpen = false" :class="activeDocId === item.id ? 'text-core-cyan font-semibold bg-cyan-500/10 border-l-2 border-core-cyan -ml-[13px] pl-[11px]' : 'text-slate-400 hover:text-white hover:bg-slate-900/50'">
                                            <span x-text="item.title"></span>
                                            <span class="py-0.2 rounded border border-slate-700 bg-slate-800 px-1 font-mono text-[9px] text-slate-300" x-show="item.badge" x-text="item.badge"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>

                <!-- SDK Download Banner in Sidebar -->
                <div class="glass-panel border-core-cyan/30 relative overflow-hidden rounded-xl border p-4 text-left">
                    <div class="absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-cyan-500/10 blur-xl"></div>
                    <h4 class="mb-1 text-xs font-semibold text-white">Core-X CLI & SDK v1.0</h4>
                    <p class="mb-3 text-[11px] text-slate-400">Install our enterprise developer toolkit via npm or pip.</p>
                    <div class="border-core-border text-core-cyan flex items-center justify-between rounded border bg-black/80 p-2 font-mono text-[10px]">
                        <span>npm i -g @almaxcloud/core-x</span>
                        <button class="text-slate-400 hover:text-white" @click="copyText('npm i -g @almaxcloud/core-x')">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            <main class="min-w-0 flex-1 md:ml-8 lg:ml-12">

                <!-- Breadcrumbs -->
                <div class="mb-6 flex items-center space-x-2 font-mono text-xs text-slate-500">
                    <span class="hover:text-core-cyan cursor-pointer" @click="navigateTo('overview')">Docs</span>
                    <span>/</span>
                    <span class="text-slate-400" x-text="currentCategoryName"></span>
                    <span>/</span>
                    <span class="text-core-cyan" x-text="currentDocTitle"></span>
                </div>

                <div class="animate-fadeIn space-y-8" x-show="activeDocId === 'overview'">
                    <div class="border-core-border border-b pb-6">
                        <div class="border-core-cyan/30 text-core-cyan mb-4 inline-flex items-center space-x-2 rounded-full border bg-cyan-500/10 px-2.5 py-1 font-mono text-xs">
                            <span class="bg-core-cyan h-1.5 w-1.5 animate-ping rounded-full"></span>
                            <span>Core-X OS Engine v2.5</span>
                        </div>
                        <h1 class="mb-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Core-X Developer Documentation</h1>
                        <p class="text-sm leading-relaxed text-slate-400 sm:text-base">
                            Welcome to the official developer portal for <strong>Core-X</strong>, ALmax Cloud's proprietary Web OS for business operations. Build immersive business applications, daemon services, windowed applets, and real-time state synchronization modules on top of our secure kernel architecture.
                        </p>
                    </div>

                    <!-- Feature Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="glass-panel border-core-border hover:border-core-cyan/50 group cursor-pointer rounded-xl border p-5 transition-all" @click="navigateTo('quickstart')">
                            <div class="border-core-cyan/30 text-core-cyan mb-4 flex h-10 w-10 items-center justify-center rounded-lg border bg-cyan-500/10 transition-transform group-hover:scale-110">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="group-hover:text-core-cyan mb-1 text-base font-bold text-white transition-colors">5-Minute Quickstart</h3>
                            <p class="text-xs text-slate-400">Initialize your first Core-X Web OS container, authenticate via X-Engine, and launch a window applet.</p>
                        </div>
                        <div class="glass-panel border-core-border hover:border-core-cyan/50 group cursor-pointer rounded-xl border p-5 transition-all" @click="navigateTo('web-api')">
                            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg border border-blue-500/30 bg-blue-500/10 text-blue-400 transition-transform group-hover:scale-110">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="group-hover:text-core-cyan mb-1 text-base font-bold text-white transition-colors">Web OS & Applets API</h3>
                            <p class="text-xs text-slate-400">Control desktop hierarchy, tiling layouts, virtual viewports, and inter-process messaging.</p>
                        </div>
                    </div>

                    <!-- Code Example Box -->
                    <div class="glass-panel border-core-border overflow-hidden rounded-xl border">
                        <div class="border-core-border flex items-center justify-between border-b bg-slate-950 px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 rounded-full bg-rose-500/80"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-500/80"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-500/80"></span>
                                <span class="ml-2 font-mono text-xs text-slate-400">initialize-kernel.js</span>
                            </div>
                            <!-- Language Tabs -->
                            <div class="flex space-x-1">
                                <button class="rounded px-2.5 py-1 font-mono text-[11px] transition-colors" @click="selectedLanguage = 'javascript'" :class="selectedLanguage === 'javascript' ? 'bg-core-cyan text-black font-semibold' : 'bg-slate-900 text-slate-400 hover:text-white'">JS/TS</button>
                                <button class="rounded px-2.5 py-1 font-mono text-[11px] transition-colors" @click="selectedLanguage = 'curl'" :class="selectedLanguage === 'curl' ? 'bg-core-cyan text-black font-semibold' : 'bg-slate-900 text-slate-400 hover:text-white'">cURL</button>
                            </div>
                        </div>
                        <div class="group relative overflow-x-auto bg-black/90 p-4 font-mono text-xs text-slate-300">
                            <button class="absolute right-3 top-3 flex items-center space-x-1.5 rounded border border-slate-700 bg-slate-800 px-2.5 py-1 text-[10px] text-slate-300 transition-colors hover:bg-slate-700" @click="copySnippet()">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                </svg>
                                <span>Copy</span>
                            </button>
                            <pre x-show="selectedLanguage === 'javascript'">
<span class="text-purple-400">import</span> { Helpers, Payments } <span class="text-purple-400">from</span> <span class="text-emerald-300">'@almax/core-x'</span>;
<span class="text-slate-500">// Initialize secure Core-X session</span>
<span class="text-blue-400">import </span>XEngine <span class="text-purple-400">from </span> <span class="text-yellow-300">"@almaxcloud/core-x";</span>
<span class="text-blue-400">XEngine</span>.<span class="text-yellow-300">boot({ public: 'App Public key' })</span> 
console.<span class="text-yellow-300">log</span>(<span class="text-emerald-300">`Core-X OS booted successfully. ${Helpers.copy('my text')}`</span>);
//                          </pre>

                            <pre style="display: none;" x-show="selectedLanguage === 'curl'">
<span class="text-slate-500"># Authenticate and initialize Core-X session</span>
curl -X POST https://api.almaxcloud.com/api/v1/app/boot \
  -H <span class="text-emerald-300">"Authorization: Bearer App_Public_key"</span> \
  -H <span class="text-emerald-300">"Content-Type: application/json"</span> \
  -d <span class="text-emerald-300">'{"cluster": "dev_server", "client_version": "2.5.0"}'</span>
                            </pre>
                        </div>
                    </div>

                    <!-- Next Steps Footer -->
                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <span class="text-xs text-slate-500">Last updated: October 2026</span>
                        <button class="text-core-cyan flex items-center space-x-2 text-xs font-semibold hover:underline" @click="navigateTo('quickstart')">
                            <span>Next: 5-Minute Quickstart Guide</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" style="display: none;" x-show="activeDocId === 'quickstart'">
                    <div class="border-core-border border-b pb-6">
                        <span class="text-core-cyan font-mono text-xs uppercase tracking-wider">Getting Started</span>
                        <h1 class="mb-3 mt-1 text-3xl font-extrabold text-white">5-Minute Quickstart Guide</h1>
                        <p class="text-sm text-slate-400">Follow this step-by-step guide to integrate Core-X into your enterprise workflow.</p>
                    </div>

                    <div class="space-y-6 text-sm text-slate-300">
                        <div class="flex items-start space-x-4">
                            <div class="border-core-cyan/40 text-core-cyan flex h-8 w-8 shrink-0 items-center justify-center rounded-full border bg-cyan-500/10 font-mono font-bold">1</div>
                            <div>
                                <h3 class="mb-1 font-bold text-white">Obtain API Credentials</h3>
                                <p class="text-xs leading-relaxed text-slate-400">Log into your <a class="text-core-cyan underline" href="https://almaxcloud.com" target="_blank">ALmax Cloud Dashboard</a>, navigate to Core-X Settings->Accounts->API and generate a Personal Access Token (PAT) with <code class="text-core-cyan font-mono">READ:UPDATE:CREATE:DELETE</code> scope.</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-400">After obtaining your API token, email <a class="text-core-cyan underline" href="mailto:support@almaxcloud.com">support@almaxcloud.com</a> requesting developer activation and include: Developer Name, Developer Website, Support Email, Core-X user_id, Business Documents, and Relevant Docs.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="border-core-cyan/40 text-core-cyan flex h-8 w-8 shrink-0 items-center justify-center rounded-full border bg-cyan-500/10 font-mono font-bold">2</div>
                            <div>
                                <h3 class="mb-1 font-bold text-white">Install the Core-X SDK</h3>
                                <p class="mb-3 text-xs text-slate-400">Install the official Node.js client package in your terminal:</p>
                                <div class="border-core-border text-core-cyan flex items-center justify-between rounded-lg border bg-black/90 p-3 font-mono text-xs">
                                    <span>npm install @almax/core-x</span>
                                    <button class="text-xs text-slate-400 hover:text-white" @click="copyText('npm install @almax/core-x')">Copy</button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="border-core-cyan/40 text-core-cyan flex h-8 w-8 shrink-0 items-center justify-center rounded-full border bg-cyan-500/10 font-mono font-bold">3</div>
                            <div>
                                <h3 class="mb-1 font-bold text-white">Spawn Your First Window Applet</h3>
                                <p class="mb-3 text-xs text-slate-400">Create a script to render a managed business window inside the Core-X workspace:</p>
                                <div class="border-core-border rounded-lg border bg-black/90 p-4 font-mono text-xs text-slate-300">
                                    <span class="text-purple-400">const</span> applet = <span class="text-blue-400">await</span> Launcher.<span class="text-yellow-300">create</span>({<br>
                                    &nbsp;&nbsp;container: <span class="text-emerald-300">'#my_div_element'</span>,<br>
                                    &nbsp;&nbsp;width: <span class="text-orange-400">1200</span>,<br>
                                    &nbsp;&nbsp;height: <span class="text-orange-400">800</span>,<br>
                                    &nbsp;&nbsp;name_space: <span class="text-emerald-300">'com.my_app.namespace'</span><br>
                                    });<br>
                                    applet.<span class="text-yellow-300">mount</span>();
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer navigation -->
                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <button class="flex items-center space-x-1 text-xs text-slate-400 hover:text-white" @click="navigateTo('overview')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Previous: Overview</span>
                        </button>
                        <button class="text-core-cyan flex items-center space-x-1 text-xs font-semibold hover:underline" @click="navigateTo('auth')">
                            <span>Next: Authentication & Security</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" style="display: none;" x-show="activeDocId === 'auth'">
                    <div class="border-core-border border-b pb-6">
                        <span class="text-core-cyan font-mono text-xs uppercase tracking-wider">Security</span>
                        <h1 class="mb-3 mt-1 text-3xl font-extrabold text-white">Authentication & X-Engine</h1>
                        <p class="text-sm text-slate-400">Core-X enforces strict cryptographic verification and token-based authentication for all daemon requests.</p>
                    </div>

                    <div class="space-y-4 text-sm text-slate-300">
                        <h3 class="text-lg font-bold text-white">Bearer Token Header</h3>
                        <p class="text-xs text-slate-400">Every API request directed at Core-X must include the Authorization header with an active Personal Access Token (PAT) or Session JWT.</p>

                        <div class="border-core-border rounded-lg border bg-black/90 p-4 font-mono text-xs text-slate-300">
                            Authorization: Bearer public_key...
                        </div>

                        <div class="glass-panel space-y-1 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-xs text-amber-200">
                            <div class="flex items-center space-x-2 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span>Security Warning</span>
                            </div>
                            <p class="text-slate-300">Never expose your master secret keys or service tokens in client-side Single Page Applications. Use ALmax Secure Proxy or Server-Side Rendering (SSR) for sensitive operations.</p>
                        </div>
                    </div>

                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <button class="flex items-center space-x-1 text-xs text-slate-400 hover:text-white" @click="navigateTo('quickstart')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Previous: Quickstart</span>
                        </button>
                        <button class="text-core-cyan flex items-center space-x-1 text-xs font-semibold hover:underline" @click="navigateTo('windows-api')">
                            <span>Next: Windows API</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" style="display: none;" x-show="activeDocId === 'windows-api'">
                    <div class="border-core-border border-b pb-6">
                        <div class="mb-2 flex items-center space-x-3">
                            <span class="rounded border border-emerald-500/30 bg-emerald-500/20 px-2 py-0.5 font-mono text-xs font-bold text-emerald-400">POST</span>
                            <span class="font-mono text-xs text-slate-400">/v1/os/windows</span>
                        </div>
                        <h1 class="mb-2 text-3xl font-extrabold text-white">Create Window Applet</h1>
                        <p class="text-sm text-slate-400">Spawns a new secure window frame inside the Core-X Web OS workspace grid.</p>
                    </div>

                    <!-- Endpoint Parameters Table -->
                    <div class="space-y-4">
                        <h3 class="font-mono text-sm font-bold uppercase tracking-wider text-white">Request Parameters</h3>
                        <div class="glass-panel border-core-border overflow-hidden rounded-xl border">
                            <table class="w-full text-left text-xs">
                                <thead class="border-core-border border-b bg-slate-950 font-mono text-slate-400">
                                    <tr>
                                        <th class="p-3">Parameter</th>
                                        <th class="p-3">Type</th>
                                        <th class="p-3">Required</th>
                                        <th class="p-3">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-core-border divide-y text-slate-300">
                                    <tr>
                                        <td class="text-core-cyan p-3 font-mono">title</td>
                                        <td class="p-3 font-mono text-slate-400">string</td>
                                        <td class="p-3"><span class="text-emerald-400">Yes</span></td>
                                        <td class="p-3">The window title displayed in the titlebar.</td>
                                    </tr>
                                    <tr>
                                        <td class="text-core-cyan p-3 font-mono">width</td>
                                        <td class="p-3 font-mono text-slate-400">integer</td>
                                        <td class="p-3"><span class="text-slate-500">No</span></td>
                                        <td class="p-3">Initial window width in pixels (Default: 960).</td>
                                    </tr>
                                    <tr>
                                        <td class="text-core-cyan p-3 font-mono">height</td>
                                        <td class="p-3 font-mono text-slate-400">integer</td>
                                        <td class="p-3"><span class="text-slate-500">No</span></td>
                                        <td class="p-3">Initial window height in pixels (Default: 640).</td>
                                    </tr>
                                    <tr>
                                        <td class="text-core-cyan p-3 font-mono">url</td>
                                        <td class="p-3 font-mono text-slate-400">string</td>
                                        <td class="p-3"><span class="text-emerald-400">Yes</span></td>
                                        <td class="p-3">Secure iframe source URL for the applet content.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <button class="flex items-center space-x-1 text-xs text-slate-400 hover:text-white" @click="navigateTo('auth')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Previous: Authentication</span>
                        </button>
                        <button class="text-core-cyan flex items-center space-x-1 text-xs font-semibold hover:underline" @click="navigateTo('api-playground')">
                            <span>Next: Interactive API Playground</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" id="playground" style="display: none;" x-show="activeDocId === 'api-playground'">
                    <div class="border-core-border border-b pb-6">
                        <span class="text-core-cyan font-mono text-xs uppercase tracking-wider">Interactive Sandbox</span>
                        <h1 class="mb-2 mt-1 text-3xl font-extrabold text-white">Core-X API Playground</h1>
                        <p class="text-sm text-slate-400">Test Core-X OS endpoints in real-time. Simulated responses replicate live ALmax Cloud daemon behavior.</p>
                    </div>

                    <!-- Playground Interface Grid -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Request Panel -->
                        <div class="glass-panel border-core-border space-y-4 rounded-xl border p-5">
                            <div class="flex items-center justify-between">
                                <h3 class="font-mono text-sm font-bold uppercase tracking-wider text-white">Request Configuration</h3>
                                <span class="rounded border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] text-emerald-400">Mock Sandbox Active</span>
                            </div>

                            <!-- Method & URL Selector -->
                            <div class="flex space-x-2">
                                <select class="border-core-border text-core-cyan rounded-lg border bg-slate-900 px-3 py-2 font-mono text-xs focus:outline-none" x-model="playgroundMethod">
                                    <option>GET</option>
                                    <option>POST</option>
                                    <option>PUT</option>
                                    <option>DELETE</option>
                                </select>
                                <input class="border-core-border flex-1 rounded-lg border bg-slate-900 px-3 py-2 font-mono text-xs text-slate-200 focus:outline-none" type="text" x-model="playgroundUrl">
                            </div>

                            <!-- Payload JSON Editor -->
                            <div>
                                <label class="mb-1.5 block font-mono text-xs text-slate-400">Request Payload (JSON)</label>
                                <textarea class="border-core-border focus:border-core-cyan/50 w-full rounded-lg border bg-slate-950 p-3 font-mono text-xs text-slate-200 focus:outline-none" x-model="playgroundParams" rows="6"></textarea>
                            </div>

                            <!-- Send Request Button -->
                            <button class="bg-core-cyan flex w-full items-center justify-center space-x-2 rounded-lg py-2.5 text-xs font-semibold text-black shadow-[0_0_15px_rgba(0,240,255,0.3)] transition-colors hover:bg-cyan-400" @click="sendPlaygroundRequest()">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                                <span x-text="playgroundLoading ? 'Executing Daemon Request...' : 'Send API Request'"></span>
                            </button>
                        </div>

                        <!-- Response Panel -->
                        <div class="glass-panel border-core-border flex flex-col space-y-4 rounded-xl border p-5">
                            <div class="flex items-center justify-between">
                                <h3 class="font-mono text-sm font-bold uppercase tracking-wider text-white">Response Payload</h3>
                                <div class="flex items-center space-x-2 font-mono text-xs" x-show="playgroundResponse">
                                    <span class="text-emerald-400">200 OK</span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-slate-400">142ms</span>
                                </div>
                            </div>

                            <div class="border-core-border max-h-72 flex-1 overflow-y-auto rounded-lg border bg-slate-950 p-4 font-mono text-xs text-slate-300">
                                <template x-if="playgroundLoading">
                                    <div class="text-core-cyan flex items-center justify-center space-x-3 py-16">
                                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>Communicating with Core-X gateway...</span>
                                    </div>
                                </template>
                                <template x-if="!playgroundLoading && playgroundResponse">
                                    <pre class="text-core-cyan" x-text="JSON.stringify(playgroundResponse, null, 2)"></pre>
                                </template>
                                <template x-if="!playgroundLoading && !playgroundResponse">
                                    <div class="py-16 text-center text-slate-600">
                                        Click "Send API Request" to test endpoint execution.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Control Widget -->
                    <div class="glass-panel border-core-border mt-8 flex flex-col items-center justify-between gap-4 rounded-xl border p-6 sm:flex-row">
                        <div>
                            <h4 class="mb-1 text-sm font-bold text-white">Was this documentation helpful?</h4>
                            <p class="text-xs text-slate-400">Help us improve the Core-X developer portal experience.</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button class="border-core-border hover:border-core-cyan flex items-center space-x-2 rounded-lg border bg-slate-900 px-4 py-2 text-xs text-slate-300 transition-colors" @click="submitFeedback('positive')">
                                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                </svg>
                                <span>Yes, extremely</span>
                            </button>
                            <button class="border-core-border flex items-center space-x-2 rounded-lg border bg-slate-900 px-4 py-2 text-xs text-slate-300 transition-colors hover:border-rose-500/50" @click="submitFeedback('negative')">
                                <svg class="h-4 w-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path>
                                </svg>
                                <span>Needs improvement</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" style="display: none;" x-show="activeDocId === 'webhooks'">
                    <div class="border-core-border border-b pb-6">
                        <span class="text-core-cyan font-mono text-xs uppercase tracking-wider">Real-Time</span>
                        <h1 class="mb-3 mt-1 text-3xl font-extrabold text-white">Webhooks & Event Subscriptions</h1>
                        <p class="text-sm text-slate-400">Listen to real-time kernel events, applet state transitions, and user telemetry via secured HTTPS webhooks.</p>
                    </div>

                    <div class="space-y-4 text-sm text-slate-300">
                        <h3 class="text-lg font-bold text-white">Event Payload Structure</h3>
                        <p class="text-xs text-slate-400">All outbound webhook deliveries are signed using HMAC-SHA256 in the <code class="text-core-cyan font-mono">X-CoreX-Signature</code> header.</p>

                        <div class="border-core-border rounded-lg border bg-black/90 p-4 font-mono text-xs text-slate-300">
                            {<br>
                            &nbsp;&nbsp;<span class="text-purple-400">"event_id"</span>: <span class="text-emerald-300">"evt_99812739102"</span>,<br>
                            &nbsp;&nbsp;<span class="text-purple-400">"event_type"</span>: <span class="text-emerald-300">"applet.spawned"</span>,<br>
                            &nbsp;&nbsp;<span class="text-purple-400">"timestamp"</span>: <span class="text-orange-400">1760601513</span>,<br>
                            &nbsp;&nbsp;<span class="text-purple-400">"data"</span>: {<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-purple-400">"window_id"</span>: <span class="text-emerald-300">"win_x_8871"</span>,<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-purple-400">"owner"</span>: <span class="text-emerald-300">"usr_almax_admin"</span><br>
                            &nbsp;&nbsp;}<br>
                            }
                        </div>
                    </div>

                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <button class="flex items-center space-x-1 text-xs text-slate-400 hover:text-white" @click="navigateTo('api-playground')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Previous: API Playground</span>
                        </button>
                        <button class="text-core-cyan flex items-center space-x-1 text-xs font-semibold hover:underline" @click="navigateTo('errors')">
                            <span>Next: Error Codes & Limits</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="animate-fadeIn space-y-8" style="display: none;" x-show="activeDocId === 'errors'">
                    <div class="border-core-border border-b pb-6">
                        <span class="text-core-cyan font-mono text-xs uppercase tracking-wider">Troubleshooting</span>
                        <h1 class="mb-3 mt-1 text-3xl font-extrabold text-white">Error Codes & Rate Limits</h1>
                        <p class="text-sm text-slate-400">Understand Core-X API status codes, rate limit headers, and troubleshooting protocols.</p>
                    </div>

                    <div class="space-y-6">
                        <div class="glass-panel border-core-border overflow-hidden rounded-xl border">
                            <table class="w-full text-left text-xs">
                                <thead class="border-core-border border-b bg-slate-950 font-mono text-slate-400">
                                    <tr>
                                        <th class="p-3">Status Code</th>
                                        <th class="p-3">Error Slug</th>
                                        <th class="p-3">Description & Resolution</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-core-border divide-y text-slate-300">
                                    <tr>
                                        <td class="p-3 font-mono text-amber-400">429 Too Many Requests</td>
                                        <td class="p-3 font-mono text-slate-300">rate_limit_exceeded</td>
                                        <td class="p-3 text-slate-400">Exceeded 1,000 req/min threshold. Implement exponential backoff.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-mono text-rose-400">401 Unauthorized</td>
                                        <td class="p-3 font-mono text-slate-300">invalid_token</td>
                                        <td class="p-3 text-slate-400">Provided PAT or JWT has expired or been revoked in dashboard.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-mono text-rose-400">503 Service Unavailable</td>
                                        <td class="p-3 font-mono text-slate-300">kernel_restart</td>
                                        <td class="p-3 text-slate-400">Target Core-X daemon container is undergoing rolling cluster update.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-core-border flex items-center justify-between border-t pt-6">
                        <button class="flex items-center space-x-1 text-xs text-slate-400 hover:text-white" @click="navigateTo('webhooks')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Previous: Webhooks</span>
                        </button>
                        <button class="text-core-cyan flex items-center space-x-1 text-xs font-semibold hover:underline" @click="navigateTo('overview')">
                            <span>Back to Overview</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

            </main>
        </div>
        @livewireScripts
        <script>
            function coreDocsApp() {
                return {
                    activeDocId: 'overview',
                    searchOpen: false,
                    searchQuery: '',
                    mobileSidebarOpen: false,
                    selectedLanguage: 'javascript',
                    toastMessage: '',
                    toastVisible: false,

                    // Playground State
                    playgroundMethod: 'POST',
                    playgroundUrl: 'https://testnet.almaxcloud.com/api/v1/',
                    playgroundParams: '{\n  "title": "X-Engine v1.0",\n  "width": 1280,\n  "height": 800,\n  "url": "https://api.almaxcloud.com"\n}',
                    playgroundResponse: null,
                    playgroundLoading: false,

                    categories: [{
                            name: 'Getting Started',
                            items: [{
                                    id: 'overview',
                                    title: 'Overview',
                                    badge: 'v2.5'
                                },
                                {
                                    id: 'quickstart',
                                    title: '5-Minute Quickstart'
                                },
                                {
                                    id: 'auth',
                                    title: 'Authentication & Security'
                                }
                            ]
                        },
                        {
                            name: 'Core-X APIs',
                            items: [{
                                    id: 'windows-api',
                                    title: 'Windows & Applets API',
                                    badge: 'POST'
                                },
                                {
                                    id: 'api-playground',
                                    title: 'Interactive API Playground',
                                    badge: 'Sandbox'
                                }
                            ]
                        },
                        {
                            name: 'Real-Time & Ops',
                            items: [{
                                    id: 'webhooks',
                                    title: 'Webhooks & Events'
                                },
                                {
                                    id: 'errors',
                                    title: 'Error Codes & Limits'
                                }
                            ]
                        }
                    ],

                    get currentDocTitle() {
                        for (const cat of this.categories) {
                            for (const item of cat.items) {
                                if (item.id === this.activeDocId) return item.title;
                            }
                        }
                        return 'Overview';
                    },

                    get currentCategoryName() {
                        for (const cat of this.categories) {
                            for (const item of cat.items) {
                                if (item.id === this.activeDocId) return cat.name;
                            }
                        }
                        return 'Getting Started';
                    },

                    get filteredSearchResults() {
                        if (!this.searchQuery.trim()) {
                            return [{
                                    id: 'overview',
                                    category: 'Getting Started',
                                    title: 'Core-X Developer Overview',
                                    excerpt: 'Official developer portal for Core-X Web OS by ALmax Cloud.'
                                },
                                {
                                    id: 'quickstart',
                                    category: 'Getting Started',
                                    title: '5-Minute Quickstart Guide',
                                    excerpt: 'Initialize your first Core-X Web OS container and authenticate.'
                                },
                                {
                                    id: 'windows-api',
                                    category: 'Core-X APIs',
                                    title: 'Create Window Applet',
                                    excerpt: 'Control desktop hierarchy, tiling layouts, and virtual viewports.'
                                },
                                {
                                    id: 'api-playground',
                                    category: 'Sandbox',
                                    title: 'Interactive API Playground',
                                    excerpt: 'Test Core-X OS endpoints in real-time with simulated responses.'
                                }
                            ];
                        }
                        const q = this.searchQuery.toLowerCase();
                        const allDocs = [{
                                id: 'overview',
                                category: 'Getting Started',
                                title: 'Core-X Developer Overview',
                                excerpt: 'Official developer portal for Core-X Web OS by ALmax Cloud.'
                            },
                            {
                                id: 'quickstart',
                                category: 'Getting Started',
                                title: '5-Minute Quickstart Guide',
                                excerpt: 'Initialize your first Core-X Web OS container and authenticate.'
                            },
                            {
                                id: 'auth',
                                category: 'Security',
                                title: 'Authentication & X-Engine',
                                excerpt: 'Cryptographic verification and token-based authentication for daemon requests.'
                            },
                            {
                                id: 'web-api',
                                category: 'Core-X APIs',
                                title: 'Create Window Applet',
                                excerpt: 'Control desktop hierarchy, tiling layouts, and virtual viewports.'
                            },
                            {
                                id: 'api-playground',
                                category: 'Sandbox',
                                title: 'Interactive API Playground',
                                excerpt: 'Test Core-X OS endpoints in real-time with simulated responses.'
                            },
                            {
                                id: 'webhooks',
                                category: 'Real-Time',
                                title: 'Webhooks & Event Subscriptions',
                                excerpt: 'Listen to real-time kernel events and applet state transitions.'
                            },
                            {
                                id: 'errors',
                                category: 'Troubleshooting',
                                title: 'Error Codes & Rate Limits',
                                excerpt: 'Understand Core-X API status codes and rate limit headers.'
                            }
                        ];
                        return allDocs.filter(d => d.title.toLowerCase().includes(q) || d.excerpt.toLowerCase().includes(q) || d.category.toLowerCase().includes(q));
                    },

                    navigateTo(id) {
                        this.activeDocId = id;
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    },

                    showToast(msg) {
                        this.toastMessage = msg;
                        this.toastVisible = true;
                        setTimeout(() => {
                            this.toastVisible = false;
                        }, 3000);
                    },

                    copyText(text) {
                        const el = document.createElement('textarea');
                        el.value = text;
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        this.showToast('Copied to clipboard successfully!');
                    },

                    copySnippet() {
                        let code = "";
                        if (this.selectedLanguage === 'javascript') {
                            code = "import { XEngine } from '@almax/core-x';\nXEngine.boot({ public: 'App Public key' });";
                        } else {
                            code = "curl -X POST https://api.almaxcloud.com/api/v1/app/boot -H 'Authorization: Bearer App Public key'";
                        }
                        this.copyText(code);
                    },

                    sendPlaygroundRequest() {
                        this.playgroundLoading = true;
                        this.playgroundResponse = null;
                        setTimeout(() => {
                            this.playgroundLoading = false;
                            this.playgroundResponse = {
                                status: "success",
                                code: 200,
                                timestamp: Date.now(),
                                region: "us-east-almax",
                                data: {
                                    window_id: "win_cx_" + Math.floor(Math.random() * 90000 + 10000),
                                    status: "active",
                                    title: "Enterprise Dashboard v2",
                                    viewport: {
                                        width: 1280,
                                        height: 800
                                    },
                                    pid: 4892
                                }
                            };
                            this.showToast('API daemon response received (200 OK)');
                        }, 650);
                    },

                    submitFeedback(rating) {
                        this.showToast(rating === 'positive' ? 'Thank you! Your feedback has been recorded.' : 'Thank you! Feedback sent to the ALmax docs team.');
                    }
                }
            }
        </script>
    </body>

</html>
