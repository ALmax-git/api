<!DOCTYPE html>
<html lang="en" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ALmax API Engine | Developer Documentation</title>
        <meta name="description" content="Developer documentation for the ALmax API Engine, including authentication, device pairing, products, sales, staff, and app lifecycle endpoints.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --bg: #07111d;
                --bg-soft: #0d1b2a;
                --panel: rgba(15, 23, 42, 0.82);
                --panel-border: rgba(148, 163, 184, 0.18);
                --text: #e2e8f0;
                --muted: #94a3b8;
                --primary: #22d3ee;
                --primary-soft: rgba(34, 211, 238, 0.12);
                --success: #34d399;
                --warning: #fbbf24;
                --danger: #f87171;
            }

            body {
                background:
                    radial-gradient(circle at top, rgba(34, 211, 238, 0.12), transparent 30%),
                    linear-gradient(180deg, var(--bg) 0%, #020817 100%);
                color: var(--text);
                font-family: 'Inter', sans-serif;
            }

            .glass {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
            }

            .badge {
                border: 1px solid rgba(34, 211, 238, 0.35);
                background: rgba(34, 211, 238, 0.08);
                color: #a5f3fc;
            }

            code, pre {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            }

            .grid-pattern {
                background-image:
                    linear-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
                background-size: 26px 26px;
            }
        </style>
    </head>
    <body class="min-h-screen text-slate-100">
        <div class="grid-pattern min-h-screen">
            <header class="border-b border-slate-800/80 bg-slate-950/50 backdrop-blur-md">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-cyan-400/40 bg-cyan-500/10 text-lg font-bold text-cyan-300">
                            AX
                        </div>
                        <div>
                            <div class="text-base font-semibold tracking-[0.16em] text-white">ALMAX</div>
                            <div class="text-[10px] uppercase tracking-[0.28em] text-slate-400">API ENGINE</div>
                        </div>
                    </div>

                    <nav class="hidden items-center gap-6 text-sm text-slate-300 md:flex">
                        <a href="#overview" class="hover:text-cyan-300">Overview</a>
                        <a href="#auth" class="hover:text-cyan-300">Authentication</a>
                        <a href="#devices" class="hover:text-cyan-300">Devices</a>
                        <a href="#products" class="hover:text-cyan-300">Products</a>
                        <a href="#sales" class="hover:text-cyan-300">Sales</a>
                    </nav>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <section id="overview" class="pb-10">
                    <div class="badge inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium uppercase tracking-[0.2em]">
                        <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                        Business API Platform
                    </div>

                    <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                        ALmax API Engine documentation
                    </h1>

                    <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-300">
                        This service provides the backend engine behind ALmax Cloud operations: account authentication,
                        X-app bootstrapping, device registration, staff access, product catalog management, sales processing,
                        and client-store resolution. The routes are built for secure, token-based access and real-world retail and operations workflows.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3 text-sm text-slate-300">
                        <span class="rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1.5">Laravel 11</span>
                        <span class="rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1.5">Sanctum</span>
                        <span class="rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1.5">JSON API</span>
                        <span class="rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1.5">Client/Store/Branch model</span>
                    </div>
                </section>

                <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div class="glass rounded-2xl p-5">
                        <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">Auth</div>
                        <h2 class="text-xl font-semibold text-white">Login & session</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Login users, issue bearer tokens, fetch current user context, and revoke active sessions.
                        </p>
                    </div>

                    <div class="glass rounded-2xl p-5">
                        <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">Apps</div>
                        <h2 class="text-xl font-semibold text-white">X-App boot</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Validate a namespace, authorize the app, and return client and app metadata for app data requests.
                        </p>
                    </div>

                    <div class="glass rounded-2xl p-5">
                        <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">Devices</div>
                        <h2 class="text-xl font-semibold text-white">Terminal pairing</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Register device keys, activate pairing flow, and verify X-POS or terminal activity against a client.
                        </p>
                    </div>

                    <div class="glass rounded-2xl p-5">
                        <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">Business data</div>
                        <h2 class="text-xl font-semibold text-white">Products & sales</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            List products, review staff details, and create or resolve sales records for the authenticated client.
                        </p>
                    </div>
                </section>

                <section id="auth" class="mt-12 grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="glass rounded-2xl p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-emerald-300">POST</span>
                            <span class="font-mono text-sm text-slate-300">/api/v1/auth/login</span>
                        </div>

                        <h2 class="text-2xl font-bold text-white">Authentication</h2>
                        <p class="mt-3 text-slate-300">
                            User login is performed against the configured client namespace and returns a Sanctum token with a set of abilities.
                        </p>

                        <pre class="mt-5 overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/80 p-4 text-sm text-cyan-100"><code>curl -X POST https://api.almaxcloud.com/api/v1/auth/login \
  -H "Xsource: your_namespace" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret"
  }'</code></pre>

                        <div class="mt-5 rounded-xl border border-slate-700 bg-slate-900/60 p-4 text-sm text-slate-200">
                            Response includes:<br>
                            • <code class="text-cyan-300">success</code><br>
                            • <code class="text-cyan-300">data.user</code><br>
                            • <code class="text-cyan-300">data.token</code><br>
                            • <code class="text-cyan-300">data.abilities</code>
                        </div>
                    </div>

                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white">Core protected routes</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-300">
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">GET /api/v1/auth/me</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">GET /api/v1/auth/verify</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">POST /api/v1/auth/logout</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">POST /api/v1/auth/logout-all</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">GET /api/v1/auth/devices</li>
                        </ul>
                    </div>
                </section>

                <section id="devices" class="mt-12">
                    <div class="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.22em] text-cyan-300">Device flows</div>
                            <h2 class="mt-2 text-3xl font-bold text-white">Terminal and device lifecycle</h2>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="glass rounded-2xl p-5">
                            <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">register</div>
                            <div class="font-mono text-sm text-slate-200">POST /api/v1/auth/register-device</div>
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                Sends a pairing request for a known device key and moves it into the pairing state when it is inactive.
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-5">
                            <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">activate</div>
                            <div class="font-mono text-sm text-slate-200">POST /api/v1/auth/device-activation</div>
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                Verifies the terminal token and returns staff, client, store, and branch metadata for activation.
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-5">
                            <div class="mb-3 text-xs uppercase tracking-[0.2em] text-cyan-300">client devices</div>
                            <div class="font-mono text-sm text-slate-200">POST /api/v1/client/store/{uuid}/devices/create</div>
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                Creates or reuses a device record for the client and returns the generated device key and status.
                            </p>
                        </div>
                    </div>
                </section>

                <section id="products" class="mt-12 grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="glass rounded-2xl p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="rounded border border-violet-500/40 bg-violet-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-violet-300">GET/POST</span>
                            <span class="font-mono text-sm text-slate-300">Products & catalog</span>
                        </div>

                        <h2 class="text-2xl font-bold text-white">Products, staff and stores</h2>
                        <p class="mt-3 text-slate-300">
                            Customer-facing ALmax workflows are centered around the authenticated client, including their product catalog, staff records, stores, and branches.
                        </p>

                        <ul class="mt-5 space-y-3 text-sm text-slate-300">
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">POST /api/v1/products/get/all</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">POST /api/v1/products/save/{uuid}</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">POST /api/v1/client/staffs/{uuid}/all</li>
                            <li class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">GET /api/v1/resolve/{model}/{uuid}</li>
                        </ul>
                    </div>

                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white">App bootstrap</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            App namespaces and developer registration are exposed via X-app endpoints, allowing the engine to validate app ownership and return application metadata.
                        </p>

                        <div class="mt-5 rounded-lg border border-slate-800 bg-slate-950/80 p-4 font-mono text-sm text-cyan-200">
                            POST /api/v1/app/boot/{xnamespace}<br>
                            POST /api/v1/apps/data/{xnamespace}<br>
                            POST /api/v1/engine/resolve
                        </div>
                    </div>
                </section>

                <section id="sales" class="mt-12">
                    <div class="glass rounded-2xl p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="rounded border border-amber-500/40 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-amber-300">Sales</span>
                            <span class="font-mono text-sm text-slate-300">Order creation + client sales</span>
                        </div>

                        <h2 class="text-2xl font-bold text-white">Sales and operations workflows</h2>
                        <p class="mt-3 max-w-3xl text-slate-300">
                            The API supports product-driven sales workflows, storefront operations, and client-defined data retrieval for staff, branches, and stores. This page reflects the actual backend contract used by the project today.
                        </p>

                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">sales</div>
                                <div class="mt-2 font-mono text-sm text-cyan-200">POST /api/v1/new-sale</div>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">store data</div>
                                <div class="mt-2 font-mono text-sm text-cyan-200">POST /api/v1/client/store/sales/{uuid}/all</div>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">resolve</div>
                                <div class="mt-2 font-mono text-sm text-cyan-200">GET /api/v1/resolve/{model}/{uuid}</div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
