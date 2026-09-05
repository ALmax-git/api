<!doctype html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>X-Engine Gateway :: Core-X</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Space+Mono:wght@400;700&amp;display=swap" rel="stylesheet">
        <style>
            :root {
                --ink: #05080d;
                --panel: rgba(9, 16, 25, .82);
                --line: rgba(134, 205, 237, .16);
                --muted: #8e9dab;
                --cyan: #57d8ff;
                --cyan-soft: #aeeeff;
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
                background: #05080d;
            }

            body {
                width: 100%;
                margin: 0;
                overflow-x: hidden;
                color: #eff8ff;
                font-family: "DM Sans", sans-serif;
            }

            .mono {
                font-family: "Space Mono", monospace;
            }

            .page-shell {
                position: relative;
                isolation: isolate;
                width: 100%;
                min-height: 100%;
                background: radial-gradient(circle at 50% 0%, rgba(17, 77, 113, .18), transparent 34rem), #05080d;
            }

            #world-canvas {
                position: fixed;
                inset: 0;
                z-index: -1;
                width: 100%;
                height: 100%;
                pointer-events: none;
            }

            .atmosphere {
                position: fixed;
                inset: 0;
                z-index: -1;
                pointer-events: none;
                background:
                    radial-gradient(circle at 50% 28%, rgba(21, 116, 163, .1), transparent 30rem),
                    linear-gradient(180deg, rgba(5, 8, 13, .16), rgba(5, 8, 13, .8) 76%, #05080d);
            }

            .grid-overlay {
                position: fixed;
                inset: 0;
                z-index: -1;
                pointer-events: none;
                opacity: .32;
                background-image: linear-gradient(rgba(114, 188, 220, .045) 1px, transparent 1px), linear-gradient(90deg, rgba(114, 188, 220, .045) 1px, transparent 1px);
                background-size: 58px 58px;
                mask-image: linear-gradient(to bottom, black, transparent 80%);
            }

            .top-nav {
                background: rgba(5, 8, 13, .72);
                border: 1px solid var(--line);
                backdrop-filter: blur(20px);
                box-shadow: 0 16px 50px rgba(0, 0, 0, .18);
            }

            .nav-link {
                color: #8b9bad;
                transition: color .2s ease, text-shadow .2s ease;
            }

            .nav-link:hover,
            .nav-link:focus-visible {
                color: #eaf9ff;
                text-shadow: 0 0 14px rgba(87, 216, 255, .8);
                outline: none;
            }

            .hero-stage {
                min-height: calc(100 * min(var(--vh, 1vh), 1vh));
                display: flex;
                align-items: center;
                position: relative;
                overflow: hidden;
            }

            .micro-label {
                letter-spacing: .16em;
                text-transform: uppercase;
                font: 10px "Space Mono", monospace;
            }

            .eyebrow-line {
                width: 36px;
                height: 1px;
                background: #57d8ff;
                box-shadow: 0 0 12px rgba(87, 216, 255, .85);
            }

            .glass-panel {
                background: linear-gradient(135deg, rgba(13, 28, 40, .82), rgba(5, 10, 16, .72));
                border: 1px solid var(--line);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .035), 0 22px 58px rgba(0, 0, 0, .13);
                backdrop-filter: blur(16px);
            }

            .status-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #5dffbd;
                box-shadow: 0 0 12px #5dffbd;
                animation: pulse 2.2s ease-in-out infinite;
            }

            .section-rule {
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(87, 216, 255, .38), transparent);
            }

            .depth-card {
                transform-style: preserve-3d;
                transition: transform .2s ease, border-color .2s ease, background .2s ease, box-shadow .2s ease;
                will-change: transform;
            }

            .depth-card:hover,
            .depth-card.is-selected {
                border-color: rgba(87, 216, 255, .8);
                background: rgba(12, 32, 46, .94);
                box-shadow: 0 18px 45px rgba(1, 31, 45, .36), inset 0 1px 0 rgba(180, 245, 255, .08);
            }

            .architecture-orbit {
                position: relative;
                overflow: hidden;
                background: radial-gradient(circle at 50% 50%, rgba(13, 79, 112, .24), transparent 30%), rgba(6, 13, 20, .76);
            }

            .architecture-orbit::before {
                content: "";
                position: absolute;
                width: 310px;
                height: 310px;
                left: 50%;
                top: 50%;
                border: 1px solid rgba(87, 216, 255, .15);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                box-shadow: 0 0 70px rgba(48, 186, 240, .08), inset 0 0 45px rgba(48, 186, 240, .05);
                pointer-events: none;
            }

            .hub-core {
                position: relative;
                z-index: 1;
                background: radial-gradient(circle at 38% 30%, #21789d, #07111b 66%);
                border: 1px solid rgba(96, 220, 255, .75);
                box-shadow: 0 0 55px rgba(17, 144, 202, .27), inset 0 0 32px rgba(82, 214, 255, .14);
            }

            .route-card {
                border: 1px solid rgba(120, 181, 210, .18);
                background: rgba(4, 10, 16, .62);
            }

            .route-card.is-selected {
                border-color: #57d8ff;
                color: #d9f9ff;
                box-shadow: 0 0 22px rgba(87, 216, 255, .16);
            }

            .endpoint-card {
                min-height: 150px;
            }

            .journey-path {
                height: 1px;
                flex: 1;
                min-width: 24px;
                position: relative;
                background: linear-gradient(90deg, #21485e, #6ce6ff);
                overflow: hidden;
            }

            .journey-path::after {
                content: "";
                position: absolute;
                top: -2px;
                left: -20%;
                width: 18%;
                height: 5px;
                background: #d5f9ff;
                box-shadow: 0 0 12px #57d8ff;
                animation: travel 2.4s linear infinite;
            }

            .hero-orbit {
                width: min(74vw, 660px);
                height: min(74vw, 660px);
                position: absolute;
                left: 50%;
                top: 48%;
                border: 1px solid rgba(87, 216, 255, .09);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                box-shadow: inset 0 0 80px rgba(38, 176, 232, .05);
                pointer-events: none;
            }

            .hero-orbit::before,
            .hero-orbit::after {
                content: "";
                position: absolute;
                border: 1px solid rgba(87, 216, 255, .08);
                border-radius: 50%;
                inset: 13%;
            }

            .hero-orbit::after {
                inset: 27%;
            }

            .fade-up {
                animation: fadeUp .8s both;
            }

            .delay-1 {
                animation-delay: .12s;
            }

            .delay-2 {
                animation-delay: .24s;
            }

            .delay-3 {
                animation-delay: .36s;
            }

            @keyframes fadeUp {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes pulse {
                50% {
                    opacity: .4;
                    transform: scale(.8);
                }
            }

            @keyframes travel {
                to {
                    left: 110%;
                }
            }

            @media (max-width: 767px) {
                .hero-stage {
                    min-height: calc(92 * min(var(--vh, 1vh), 1vh));
                }

                .architecture-orbit::before {
                    width: 220px;
                    height: 220px;
                }

                .journey-path {
                    width: 1px;
                    min-width: 1px;
                    height: 26px;
                    flex: none;
                    background: linear-gradient(#21485e, #6ce6ff);
                }

                .journey-path::after {
                    left: -2px;
                    top: -20%;
                    width: 5px;
                    height: 18%;
                    animation-name: travelVertical;
                }

                @keyframes travelVertical {
                    to {
                        top: 110%;
                    }
                }
            }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>

    <body data-template-id="__page-root">
        <div class="page-shell">
            <canvas id="world-canvas" aria-label="Interactive three-dimensional X-Engine network"></canvas>
            <div class="atmosphere"></div>
            <div class="grid-overlay"></div>
            <header class="fixed left-0 top-0 z-30 w-full px-4 pt-4 sm:px-7">
                <nav class="canva-menu top-nav mx-auto flex max-w-7xl items-center justify-between rounded-2xl px-4 py-3 sm:px-5" data-template-id="navigation-bar" aria-label="Main navigation"><a class="flex items-center gap-3 no-underline" href="#top" aria-label="X-Engine home"> <span class="mono flex h-7 w-7 items-center justify-center rounded-md border border-cyan-300/70 bg-cyan-300/10 text-xs text-cyan-200">X</span> <span class="canva-text font-semibold tracking-tight" data-template-id="brand-name"></span> </a>
                    <div class="mono hidden items-center gap-5 text-[10px] lg:flex"><a class="canva-link nav-link" data-template-id="nav-architecture" href="#architecture"></a> <a class="canva-link nav-link" data-template-id="nav-endpoints" href="#endpoints"></a> <a class="canva-link nav-link" data-template-id="nav-telemetry" href="#telemetry"></a> <a class="canva-link nav-link" data-template-id="nav-github" href="https://github.com" target="_blank" rel="noopener noreferrer"></a>
                    </div><a class="canva-button mono rounded-lg border border-cyan-300/35 px-3 py-2 text-[10px] transition hover:bg-cyan-300/20" data-template-id="nav-connect" href="#architecture"></a>
                </nav>
            </header>
            <main>
                <section class="hero-stage px-5 pt-24" id="top">
                    <div class="hero-orbit"></div>
                    <div class="relative z-10 mx-auto flex w-full max-w-6xl flex-col items-center pt-16 text-center">
                        <div class="fade-up flex items-center gap-3"><span class="eyebrow-line"></span> <span class="canva-text micro-label text-cyan-200" data-template-id="hero-kicker"></span> <span class="eyebrow-line"></span>
                        </div>
                        <h1 class="canva-text fade-up delay-1 mt-5 max-w-3xl font-bold leading-[.94] tracking-[-.055em]" data-template-id="hero-title"></h1>
                        <p class="canva-text fade-up delay-2 mt-6 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base" data-template-id="hero-copy"></p>
                        <div class="fade-up delay-3 mt-8 flex flex-col items-center gap-3 sm:flex-row"><a class="canva-button mono inline-flex items-center gap-2 rounded-lg px-5 py-3 text-[11px] font-bold transition hover:brightness-110" data-template-id="hero-primary-cta" href="#architecture"> <i class="h-4 w-4" data-lucide="orbit"></i> </a> <a class="canva-button mono inline-flex items-center gap-2 rounded-lg border border-slate-500/40 px-5 py-3 text-[11px] transition hover:border-cyan-300/60" data-template-id="hero-secondary-cta" href="#endpoints"> <i class="h-4 w-4" data-lucide="activity"></i> </a>
                        </div>
                        <aside class="canva-panel glass-panel fade-up delay-3 mt-9 flex flex-wrap items-center justify-center gap-x-7 gap-y-3 rounded-xl px-5 py-3" data-template-id="hero-status-panel" aria-label="X-Engine live status">
                            <div class="flex items-center gap-2"><span class="status-dot"></span> <span class="canva-text micro-label text-emerald-200" data-template-id="status-online"></span>
                            </div><span class="hidden h-4 w-px bg-slate-600 sm:block"></span> <span class="canva-text micro-label text-slate-300" data-template-id="status-nodes"></span> <span class="hidden h-4 w-px bg-slate-600 sm:block"></span> <span class="canva-text micro-label text-slate-300" data-template-id="status-endpoints"></span>
                        </aside>
                        <div class="canva-panel glass-panel fade-up delay-3 mt-5 flex flex-wrap items-center justify-center gap-2 rounded-xl p-2" data-template-id="scene-control-panel"><button class="canva-button mono rounded-lg px-3 py-2 text-[10px] transition hover:bg-cyan-300/15" id="scene-pause" data-template-id="scene-pause-button" type="button"> <i class="mr-1 inline h-3.5 w-3.5" data-lucide="pause"></i> </button> <button class="canva-button mono rounded-lg border border-slate-600/60 px-3 py-2 text-[10px] transition hover:border-cyan-300/60" id="scene-reset" data-template-id="scene-reset-button" type="button"> <i class="mr-1 inline h-3.5 w-3.5" data-lucide="rotate-ccw"></i> </button> <span class="mono px-2 text-[9px] tracking-[.13em] text-cyan-100/65" id="focus-readout" aria-live="polite">TOPOLOGY / GLOBAL MESH</span>
                        </div>
                    </div>
                </section>
                <section class="relative px-5 py-24 sm:py-32" id="architecture">
                    <div class="mx-auto max-w-6xl">
                        <div class="section-rule"></div>
                        <div class="mt-12 flex flex-col justify-between gap-8 md:flex-row md:items-end">
                            <div>
                                <p class="canva-text micro-label text-cyan-300" data-template-id="architecture-kicker"></p>
                                <h2 class="canva-text mt-3 max-w-xl font-bold tracking-[-.045em]" data-template-id="architecture-title"></h2>
                            </div>
                            <p class="canva-text max-w-md text-sm leading-6 text-slate-400" data-template-id="architecture-copy"></p>
                        </div>
                        <div class="canva-panel glass-panel architecture-orbit mt-12 rounded-2xl p-5 sm:p-10" data-template-id="architecture-map">
                            <div class="relative z-10 grid gap-4 md:grid-cols-[1fr_auto_1fr] md:items-center md:gap-10">
                                <div class="space-y-3 text-center md:text-right"><button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-right" data-focus="apps" type="button"><span class="canva-text" data-template-id="arch-apps"></span></button> <button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-right" data-focus="hub" type="button"><span class="canva-text" data-template-id="arch-hub"></span></button> <button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-right" data-focus="guard" type="button"><span class="canva-text" data-template-id="arch-guard"></span></button>
                                </div>
                                <div class="hub-core mx-auto flex h-36 w-36 flex-col items-center justify-center rounded-full text-center"><span class="mono text-[10px] tracking-[.2em] text-cyan-200">CORE-X</span> <span class="canva-text mono mt-1 text-sm font-bold" data-template-id="architecture-engine"></span> <span class="mt-2 h-1.5 w-1.5 rounded-full bg-cyan-200 shadow-[0_0_12px_#57d8ff]"></span>
                                </div>
                                <div class="space-y-3 text-center md:text-left"><button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-left" data-focus="database" type="button"><span class="canva-text" data-template-id="arch-database"></span></button> <button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-left" data-focus="api" type="button"><span class="canva-text" data-template-id="arch-api"></span></button> <button class="route-card depth-card mono w-full rounded-lg px-4 py-3 text-center text-xs text-slate-200 md:text-left" data-focus="nodes" type="button"><span class="canva-text" data-template-id="arch-nodes"></span></button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-7 grid gap-4 md:grid-cols-4">
                            <article class="canva-card glass-panel depth-card rounded-xl p-5" data-template-id="cap-route-card">
                                <p class="canva-text mono text-xs font-bold text-cyan-200" data-template-id="cap-route-title"></p>
                                <p class="canva-text mt-3 text-sm leading-6 text-slate-400" data-template-id="cap-route-copy"></p>
                            </article>
                            <article class="canva-card glass-panel depth-card rounded-xl p-5" data-template-id="cap-validate-card">
                                <p class="canva-text mono text-xs font-bold text-cyan-200" data-template-id="cap-validate-title"></p>
                                <p class="canva-text mt-3 text-sm leading-6 text-slate-400" data-template-id="cap-validate-copy"></p>
                            </article>
                            <article class="canva-card glass-panel depth-card rounded-xl p-5" data-template-id="cap-sync-card">
                                <p class="canva-text mono text-xs font-bold text-cyan-200" data-template-id="cap-sync-title"></p>
                                <p class="canva-text mt-3 text-sm leading-6 text-slate-400" data-template-id="cap-sync-copy"></p>
                            </article>
                            <article class="canva-card glass-panel depth-card rounded-xl p-5" data-template-id="cap-observe-card">
                                <p class="canva-text mono text-xs font-bold text-cyan-200" data-template-id="cap-observe-title"></p>
                                <p class="canva-text mt-3 text-sm leading-6 text-slate-400" data-template-id="cap-observe-copy"></p>
                            </article>
                        </div>
                    </div>
                </section>
                <section class="relative border-t border-slate-800/80 px-5 py-24 sm:py-32" id="endpoints">
                    <div class="mx-auto max-w-6xl">
                        <div class="grid gap-10 lg:grid-cols-[.78fr_1.22fr] lg:items-end">
                            <div>
                                <p class="canva-text micro-label text-cyan-300" data-template-id="endpoint-kicker"></p>
                                <h2 class="canva-text mt-3 font-bold tracking-[-.045em]" data-template-id="endpoint-title"></h2>
                                <p class="canva-text mt-4 max-w-sm text-sm leading-6 text-slate-400" data-template-id="endpoint-copy"></p>
                            </div>
                            <aside class="canva-panel glass-panel rounded-xl p-5" data-template-id="endpoint-detail-panel">
                                <div class="flex items-center justify-between border-b border-slate-700/70 pb-3"><span class="canva-text mono text-[10px] text-slate-500" data-template-id="request-label"></span> <span class="mono flex items-center gap-2 text-[10px] text-emerald-300"><span class="status-dot"></span><span id="request-state">CONNECTED</span></span>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><span class="mono text-sm text-cyan-200" id="active-endpoint">GET /api/v1/nodes</span> <span class="mono text-[10px] text-slate-400" id="active-latency">14ms P95</span>
                                </div>
                                <div class="mono mt-4 grid grid-cols-3 gap-3 text-[9px] text-slate-500">
                                    <div>
                                        <span class="block text-slate-200" id="active-requests">42.8K</span>REQUESTS
                                    </div>
                                    <div>
                                        <span class="block text-slate-200" id="active-target">NODEPULSE</span>TARGET
                                    </div>
                                    <div>
                                        <span class="block text-emerald-300">200 OK</span>RESPONSE
                                    </div>
                                </div>
                            </aside>
                        </div>
                        <div class="mt-9 grid gap-3 sm:grid-cols-2 md:grid-cols-5"><button class="endpoint-card depth-card is-selected glass-panel rounded-xl p-4 text-left focus:outline-none focus:ring-2 focus:ring-cyan-300" data-endpoint="0" type="button"><span class="mono text-[10px] text-cyan-200">GET</span><span class="mono mt-2 block text-[10px] text-slate-200">/api/v1/nodes</span><span class="mono mt-5 flex justify-between text-[9px] text-slate-500"><span>LIVE</span><span>14ms</span></span></button> <button class="endpoint-card depth-card glass-panel rounded-xl p-4 text-left focus:outline-none focus:ring-2 focus:ring-cyan-300" data-endpoint="1" type="button"><span class="mono text-[10px] text-blue-300">POST</span><span class="mono mt-2 block text-[10px] text-slate-200">/api/v1/route</span><span class="mono mt-5 flex justify-between text-[9px] text-slate-500"><span>LIVE</span><span>22ms</span></span></button> <button class="endpoint-card depth-card glass-panel rounded-xl p-4 text-left focus:outline-none focus:ring-2 focus:ring-cyan-300" data-endpoint="2" type="button"><span class="mono text-[10px] text-violet-300">AUTH</span><span class="mono mt-2 block text-[10px] text-slate-200">/api/v1/verify</span><span class="mono mt-5 flex justify-between text-[9px] text-slate-500"><span>SECURE</span><span>18ms</span></span></button> <button class="endpoint-card depth-card glass-panel rounded-xl p-4 text-left focus:outline-none focus:ring-2 focus:ring-cyan-300" data-endpoint="3" type="button"><span class="mono text-[10px] text-emerald-300">SYNC</span><span class="mono mt-2 block text-[10px] text-slate-200">/api/v1/mesh</span><span class="mono mt-5 flex justify-between text-[9px] text-slate-500"><span>SYNCED</span><span>31ms</span></span></button> <button class="endpoint-card depth-card glass-panel rounded-xl p-4 text-left focus:outline-none focus:ring-2 focus:ring-cyan-300" data-endpoint="4" type="button"><span class="mono text-[10px] text-amber-200">NODE</span><span class="mono mt-2 block text-[10px] text-slate-200">/api/v1/pulse</span><span class="mono mt-5 flex justify-between text-[9px] text-slate-500"><span>READY</span><span>11ms</span></span></button>
                        </div>
                    </div>
                </section>
                <section class="relative px-5 pb-24 sm:pb-32" id="telemetry">
                    <div class="canva-panel glass-panel mx-auto max-w-6xl rounded-2xl p-6 sm:p-9" data-template-id="journey-panel">
                        <div class="flex items-center justify-between gap-4">
                            <p class="canva-text mono text-xs font-bold text-cyan-100" data-template-id="journey-title"></p><span class="canva-text mono text-[9px] text-slate-500" data-template-id="journey-status"></span>
                        </div>
                        <div class="mt-8 flex flex-col items-center justify-between gap-3 md:flex-row md:gap-0">
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-slate-300" data-lucide="monitor-up"></i><span class="canva-text mono mt-2 block text-[9px] text-slate-400" data-template-id="journey-client"></span>
                            </div><span class="journey-path"></span>
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-slate-300" data-lucide="brackets"></i><span class="canva-text mono mt-2 block text-[9px] text-slate-400" data-template-id="journey-endpoint"></span>
                            </div><span class="journey-path"></span>
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-cyan-200" data-lucide="hexagon"></i><span class="canva-text mono mt-2 block text-[9px] text-cyan-200" data-template-id="journey-engine"></span>
                            </div><span class="journey-path"></span>
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-slate-300" data-lucide="shield-check"></i><span class="canva-text mono mt-2 block text-[9px] text-slate-400" data-template-id="journey-validator"></span>
                            </div><span class="journey-path"></span>
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-slate-300" data-lucide="git-fork"></i><span class="canva-text mono mt-2 block text-[9px] text-slate-400" data-template-id="journey-router"></span>
                            </div><span class="journey-path"></span>
                            <div class="text-center">
                                <i class="mx-auto h-5 w-5 text-slate-300" data-lucide="server"></i><span class="canva-text mono mt-2 block text-[9px] text-slate-400" data-template-id="journey-node"></span>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
            <footer class="canva-footer border-t border-slate-800/80 px-5 py-8" data-template-id="footer-panel">
                <div class="mx-auto flex max-w-6xl flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <p class="canva-text mono text-[10px] text-slate-500" data-template-id="footer-copy"></p>
                    <p class="canva-text mono text-[10px] text-cyan-200" data-template-id="footer-status"></p>
                </div>
            </footer>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                lucide.createIcons();
                initEndpointExplorer();
                initTiltCards();
                initWorld();
            });

            function initEndpointExplorer() {
                const records = [{
                        method: 'GET',
                        path: '/api/v1/nodes',
                        latency: '14ms P95',
                        requests: '42.8K',
                        target: 'NODEPULSE'
                    },
                    {
                        method: 'POST',
                        path: '/api/v1/route',
                        latency: '22ms P95',
                        requests: '18.4K',
                        target: 'X-HUB'
                    },
                    {
                        method: 'AUTH',
                        path: '/api/v1/verify',
                        latency: '18ms P95',
                        requests: '9.7K',
                        target: 'X-GUARD'
                    },
                    {
                        method: 'SYNC',
                        path: '/api/v1/mesh',
                        latency: '31ms P95',
                        requests: '6.2K',
                        target: 'SQLITE'
                    },
                    {
                        method: 'NODE',
                        path: '/api/v1/pulse',
                        latency: '11ms P95',
                        requests: '51.1K',
                        target: 'LOCAL-07'
                    }
                ];
                const cards = document.querySelectorAll('[data-endpoint]');
                cards.forEach(card => {
                    card.addEventListener('click', () => {
                        const record = records[Number(card.dataset.endpoint)];
                        cards.forEach(item => item.classList.remove('is-selected'));
                        card.classList.add('is-selected');
                        document.getElementById('active-endpoint').textContent = record.method + '  ' + record.path;
                        document.getElementById('active-latency').textContent = record.latency;
                        document.getElementById('active-requests').textContent = record.requests;
                        document.getElementById('active-target').textContent = record.target;
                    });
                });
            }

            function initTiltCards() {
                document.querySelectorAll('.depth-card').forEach(card => {
                    card.addEventListener('pointermove', event => {
                        const rect = card.getBoundingClientRect();
                        const x = (event.clientX - rect.left) / rect.width - .5;
                        const y = (event.clientY - rect.top) / rect.height - .5;
                        card.style.transform = 'perspective(700px) rotateX(' + (-y * 5) + 'deg) rotateY(' + (x * 6) + 'deg) translateY(-3px)';
                    });
                    card.addEventListener('pointerleave', () => {
                        card.style.transform = '';
                    });
                });
            }

            function initWorld() {
                const canvas = document.getElementById('world-canvas');
                const renderer = new THREE.WebGLRenderer({
                    canvas,
                    antialias: true,
                    alpha: true
                });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(46, 1, .1, 100);
                const mouse = new THREE.Vector2();
                const targetCamera = new THREE.Vector3(0, 0, 11);
                const neutralCamera = new THREE.Vector3(0, 0, 11);
                const coreGroup = new THREE.Group();
                const networkGroup = new THREE.Group();
                scene.add(coreGroup, networkGroup);

                const core = new THREE.Mesh(
                    new THREE.IcosahedronGeometry(1.16, 2),
                    new THREE.MeshStandardMaterial({
                        color: 0x0c3d56,
                        emissive: 0x087faf,
                        emissiveIntensity: 1.8,
                        metalness: .8,
                        roughness: .23
                    })
                );
                const wire = new THREE.Mesh(
                    new THREE.IcosahedronGeometry(1.42, 2),
                    new THREE.MeshBasicMaterial({
                        color: 0x78e7ff,
                        wireframe: true,
                        transparent: true,
                        opacity: .24
                    })
                );
                const ring1 = new THREE.Mesh(new THREE.TorusGeometry(1.78, .012, 6, 90), new THREE.MeshBasicMaterial({
                    color: 0x4edbff,
                    transparent: true,
                    opacity: .34
                }));
                const ring2 = ring1.clone();
                ring2.rotation.x = Math.PI * .48;
                ring2.rotation.y = .5;
                coreGroup.add(core, wire, ring1, ring2);

                scene.add(new THREE.AmbientLight(0x8bc8ff, .55));
                const light = new THREE.PointLight(0x4ddcff, 2.7, 19);
                light.position.set(0, 1, 4);
                scene.add(light);

                const services = {
                    apps: new THREE.Vector3(-4.2, 2.2, -1),
                    api: new THREE.Vector3(3.9, 2.4, -.4),
                    nodes: new THREE.Vector3(-4.5, -.9, 0),
                    database: new THREE.Vector3(4.3, -1.5, -.7),
                    guard: new THREE.Vector3(2.3, -3, -1),
                    hub: new THREE.Vector3(0, 0, 0)
                };
                const colours = [0x5cddff, 0x488dff, 0x58ddc9, 0x67caff, 0x9fc8ff];
                const names = ['CORE-X APPS', 'API SERVICES', 'LOCAL NODES', 'DATABASES', 'X-GUARD'];
                const nodes = [];
                const packetGroup = new THREE.Group();
                scene.add(packetGroup);

                Object.keys(services).filter(key => key !== 'hub').forEach((key, index) => {
                    const position = services[key];
                    const node = new THREE.Mesh(
                        new THREE.OctahedronGeometry(.2 + (index % 2) * .05, 1),
                        new THREE.MeshStandardMaterial({
                            color: 0x153d55,
                            emissive: colours[index],
                            emissiveIntensity: 1.8,
                            metalness: .65,
                            roughness: .28
                        })
                    );
                    node.position.copy(position);
                    node.userData = {
                        key,
                        label: names[index]
                    };
                    networkGroup.add(node);
                    nodes.push(node);

                    const line = new THREE.Line(
                        new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(), position]),
                        new THREE.LineBasicMaterial({
                            color: 0x2c99bd,
                            transparent: true,
                            opacity: .38
                        })
                    );
                    networkGroup.add(line);

                    const packet = new THREE.Mesh(new THREE.SphereGeometry(.042, 8, 8), new THREE.MeshBasicMaterial({
                        color: 0xd2f9ff
                    }));
                    packet.userData = {
                        endpoint: position.clone(),
                        offset: index / 5
                    };
                    packetGroup.add(packet);
                });

                const particlePositions = [];
                for (let i = 0; i < 440; i++) particlePositions.push((Math.random() - .5) * 20, (Math.random() - .5) * 15, (Math.random() - .5) * 9);
                const particleGeometry = new THREE.BufferGeometry();
                particleGeometry.setAttribute('position', new THREE.Float32BufferAttribute(particlePositions, 3));
                scene.add(new THREE.Points(particleGeometry, new THREE.PointsMaterial({
                    color: 0x4baeca,
                    size: .024,
                    transparent: true,
                    opacity: .72
                })));

                const raycaster = new THREE.Raycaster();
                let paused = false;
                let hovered = null;

                function focus(key) {
                    const point = services[key] || services.hub;
                    targetCamera.set(point.x * .3, point.y * .3, key === 'hub' ? 8.2 : 7.7);
                    const readable = key === 'hub' ? 'CORE-X HUB' : (nodes.find(node => node.userData.key === key) || {}).userData?.label || 'GLOBAL MESH';
                    document.getElementById('focus-readout').textContent = 'FOCUS / ' + readable + ' / LINK STABLE';
                    document.querySelectorAll('[data-focus]').forEach(button => button.classList.toggle('is-selected', button.dataset.focus === key));
                }

                document.querySelectorAll('[data-focus]').forEach(button => button.addEventListener('click', () => focus(button.dataset.focus)));
                document.getElementById('scene-pause').addEventListener('click', () => {
                    paused = !paused;
                    document.getElementById('scene-pause').setAttribute('aria-pressed', String(paused));
                });
                document.getElementById('scene-reset').addEventListener('click', () => {
                    targetCamera.copy(neutralCamera);
                    document.getElementById('focus-readout').textContent = 'TOPOLOGY / GLOBAL MESH';
                    document.querySelectorAll('[data-focus]').forEach(button => button.classList.remove('is-selected'));
                });

                window.addEventListener('pointermove', event => {
                    mouse.x = event.clientX / window.innerWidth * 2 - 1;
                    mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
                });
                window.addEventListener('click', event => {
                    if (event.target.closest('button, a')) return;
                    if (hovered) focus(hovered.userData.key);
                });
                window.addEventListener('scroll', () => {
                    const ratio = Math.min(window.scrollY / Math.max(document.body.scrollHeight - window.innerHeight, 1), 1);
                    networkGroup.rotation.z = ratio * .18;
                }, {
                    passive: true
                });

                function resize() {
                    renderer.setSize(window.innerWidth, window.innerHeight, false);
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                }
                window.addEventListener('resize', resize);
                resize();

                const clock = new THREE.Clock();

                function animate() {
                    const time = clock.getElapsedTime();
                    if (!paused) {
                        coreGroup.rotation.y += .003;
                        coreGroup.rotation.x = Math.sin(time * .5) * .12;
                        ring1.rotation.z += .006;
                        ring2.rotation.z -= .004;
                        packetGroup.children.forEach(packet => {
                            const progress = (time * .18 + packet.userData.offset) % 1;
                            packet.position.lerpVectors(new THREE.Vector3(), packet.userData.endpoint, progress);
                        });
                    }
                    raycaster.setFromCamera(mouse, camera);
                    const hits = raycaster.intersectObjects(nodes);
                    hovered = hits.length ? hits[0].object : null;
                    nodes.forEach(node => node.scale.setScalar(node === hovered ? 1.65 : 1));
                    camera.position.lerp(targetCamera, .035);
                    camera.position.x += mouse.x * .1;
                    camera.position.y += mouse.y * .07;
                    camera.lookAt(0, 0, 0);
                    renderer.render(scene, camera);
                    requestAnimationFrame(animate);
                }
                animate();
            }
        </script>
        @livewireScripts
    </body>

</html>
