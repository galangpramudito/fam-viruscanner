<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CekSiber - Pemindai Link &amp; File Berbahaya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    <nav class="border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
            <div class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-600 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                </span>
                <span class="text-xl font-bold tracking-tight text-slate-900">Cek<span class="text-brand-600">Siber</span></span>
            </div>
            <span class="rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">Gratis &amp; Aman</span>
        </div>
    </nav>

    <main class="mx-auto max-w-3xl px-4 py-8 md:py-12">

        <div class="mb-8 text-center">
            <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Cek Sebelum Klik!</h1>
            <p class="mx-auto max-w-xl text-base leading-relaxed text-slate-600 md:text-lg">
                Punya link mencurigakan dari WhatsApp? Atau disuruh install file APK aneh? <br class="hidden md:block">
                Cek dulu di sini supaya HP dan rekening Anda aman dari penipu.
            </p>
        </div>

        <div role="tablist" class="mx-auto mb-6 flex max-w-md rounded-xl bg-slate-200 p-1.5 shadow-inner">
            <button type="button" data-tab="url" aria-selected="true" class="w-full rounded-lg border-b-2 border-brand-500 py-3 text-sm font-bold text-brand-600 transition">
                Cek Link / Web
            </button>
            <button type="button" data-tab="file" aria-selected="false" class="w-full rounded-lg border-b-2 border-transparent py-3 text-sm font-bold text-slate-500 transition hover:text-slate-700">
                Cek Dokumen / APK
            </button>
        </div>

        <div class="mb-8 rounded-3xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/50 md:p-8">

            <form id="urlForm" data-panel="url" class="space-y-5">
                <div>
                    <label for="inputUrl" class="mb-2 block text-base font-bold text-slate-800">Tempel Link dari WhatsApp / SMS di sini:</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <input type="url" id="inputUrl" name="url" required maxlength="2048"
                               class="block w-full rounded-xl border-2 border-slate-200 py-4 pl-12 pr-4 text-lg text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:outline-none focus:ring-0"
                               placeholder="Contoh: https://undian-hadiah-palsu.com">
                    </div>
                </div>
                <button type="submit" class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-4 text-lg font-bold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 active:bg-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span>Mulai Pindai Sekarang</span>
                </button>
            </form>

            <form id="fileForm" data-panel="file" class="hidden space-y-5" enctype="multipart/form-data">
                <div>
                    <label class="mb-2 block text-base font-bold text-slate-800">Pilih file yang ingin diperiksa:</label>
                    <div id="dropZone" class="group cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-10 text-center transition hover:border-brand-500 hover:bg-brand-50/50">
                        <input type="file" id="inputFile" name="file" required accept=".apk,.pdf,.doc,.docx,.zip" class="hidden">
                        <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full border border-slate-100 bg-white shadow-sm transition group-hover:border-brand-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7 text-brand-500"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                        </div>
                        <p id="dropText" class="text-base font-bold text-slate-700">Tekan di sini untuk memilih file</p>
                        <p class="mt-2 text-sm text-slate-500">Mendukung format APK, PDF, DOC/DOCX, ZIP (Maksimal 20MB)</p>
                    </div>
                </div>
                <button type="submit" class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-4 text-lg font-bold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700 active:bg-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    <span>Periksa File Sekarang</span>
                </button>
            </form>

            <div id="loadingOverlay" class="hidden py-12 text-center">
                <div class="mx-auto mb-4 h-14 w-14 animate-spin rounded-full border-4 border-slate-100 border-t-brand-600"></div>
                <p id="loadingText" class="text-lg font-bold text-slate-800">Sedang memeriksa keamanan…</p>
                <p class="mt-1 text-sm text-slate-500">Sistem sedang mencocokkan data dengan puluhan antivirus dunia. Mohon tunggu sebentar.</p>
            </div>

            <div id="progressWrap" class="mt-6 hidden h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div id="progressBar" class="h-full w-0 rounded-full bg-brand-600 transition-all duration-300 ease-out"></div>
            </div>

            <div id="result" class="mt-6 hidden"></div>
        </div>

        <p class="text-center text-xs text-slate-400">
            Hasil pemeriksaan disimpan selama 24 jam dan otomatis terhapus setelahnya.
        </p>
    </main>
</body>
</html>
