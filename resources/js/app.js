import './../css/app.css';

const VERDICT_THEME = {
    safe: {
        label: 'Aman',
        badge: 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
        card: 'bg-emerald-50 ring-1 ring-emerald-200',
        dot: 'bg-emerald-500',
    },
    suspicious: {
        label: 'Mencurigakan',
        badge: 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
        card: 'bg-amber-50 ring-1 ring-amber-200',
        dot: 'bg-amber-500',
    },
    malicious: {
        label: 'Berbahaya',
        badge: 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
        card: 'bg-rose-50 ring-1 ring-rose-200',
        dot: 'bg-rose-500',
    },
};

const STATUS_BADGE = {
    pending:   { label: 'Menunggu',  cls: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' },
    scanning:  { label: 'Memindai…', cls: 'bg-blue-100 text-blue-800 ring-1 ring-blue-200' },
    completed: { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' },
    failed:    { label: 'Gagal',     cls: 'bg-rose-100 text-rose-800 ring-1 ring-rose-200' },
};

const POLL_INTERVAL_MS = 1500;
const POLL_MAX_ATTEMPTS = 200;

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const state = {
    lastScanId: null,
    pollHandle: null,
    pollAttempts: 0,
};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function setProgress(percent) {
    const bar = $('#progressBar');
    const wrap = $('#progressWrap');
    if (!bar || !wrap) return;
    wrap.classList.remove('hidden');
    bar.style.width = `${Math.min(100, Math.max(0, percent))}%`;
}

function hideProgress() {
    const bar = $('#progressBar');
    const wrap = $('#progressWrap');
    if (bar) bar.style.width = '0%';
    if (wrap) wrap.classList.add('hidden');
}

function showLoading(message = 'Mengirim permintaan…') {
    const overlay = $('#loadingOverlay');
    const text = $('#loadingText');
    if (text) text.textContent = message;
    if (overlay) overlay.classList.remove('hidden');
}

function hideLoading() {
    const overlay = $('#loadingOverlay');
    if (overlay) overlay.classList.add('hidden');
}

function escapeHtml(value) {
    if (value == null) return '';
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderError(message) {
    const result = $('#result');
    if (!result) return;
    result.innerHTML = `
        <div class="animate-fade-in rounded-xl bg-rose-50 p-5 ring-1 ring-rose-200">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></div>
                <div>
                    <p class="font-semibold text-rose-800">Terjadi kesalahan</p>
                    <p class="mt-1 text-sm text-rose-700">${escapeHtml(message)}</p>
                </div>
            </div>
        </div>
    `;
    result.classList.remove('hidden');
}

function renderStatus(payload) {
    const result = $('#result');
    if (!result) return;
    const statusKey = String(payload.status ?? 'pending').toLowerCase();
    const statusMeta = STATUS_BADGE[statusKey] ?? STATUS_BADGE.pending;
    const verdictKey = payload.verdict ? String(payload.verdict).toLowerCase() : null;
    const verdictMeta = verdictKey ? VERDICT_THEME[verdictKey] : null;

    const malicious = Number(payload.malicious_count ?? 0);
    const total = Number(payload.total_engines ?? 0);
    const safeCount = Math.max(0, total - malicious);

    const aiBlock = statusKey === 'completed' && payload.ai_explanation
        ? `<div class="mt-4 rounded-lg bg-white p-4 ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penjelasan AI</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-800">${escapeHtml(payload.ai_explanation)}</p>
           </div>`
        : '';

    const errorBlock = statusKey === 'failed' && payload.error
        ? `<div class="mt-4 rounded-lg bg-rose-100 p-4 ring-1 ring-rose-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Detail error</p>
                <p class="mt-2 text-sm text-rose-800">${escapeHtml(payload.error)}</p>
           </div>`
        : '';

    const verdictBlock = verdictMeta
        ? `<div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${verdictMeta.badge}">
                    <span class="h-1.5 w-1.5 rounded-full ${verdictMeta.dot}"></span>
                    ${escapeHtml(verdictMeta.label)}
                </span>
                <span class="text-xs text-slate-500">berdasarkan ${total} mesin pemindai</span>
           </div>`
        : '';

    const statsBlock = total > 0
        ? `<div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-lg bg-rose-50 p-2 ring-1 ring-rose-100">
                    <p class="text-lg font-bold text-rose-700">${malicious}</p>
                    <p class="text-rose-600">Berbahaya</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-2 ring-1 ring-amber-100">
                    <p class="text-lg font-bold text-amber-700">${Number(payload.suspicious_count ?? 0)}</p>
                    <p class="text-amber-600">Mencurigakan</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-2 ring-1 ring-emerald-100">
                    <p class="text-lg font-bold text-emerald-700">${safeCount}</p>
                    <p class="text-emerald-600">Aman</p>
                </div>
           </div>`
        : '';

    result.innerHTML = `
        <div class="animate-fade-in rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${statusMeta.cls}">
                        <span class="h-1.5 w-1.5 rounded-full ${statusMeta.dot ?? 'bg-slate-500'}"></span>
                        ${escapeHtml(statusMeta.label)}
                    </span>
                    <span class="text-xs text-slate-400">ID #${escapeHtml(payload.id ?? state.lastScanId ?? '')}</span>
                </div>
                <button type="button" data-copy-target="result" class="copy-btn rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">Salin hasil</button>
            </div>
            <div class="mt-3 break-all text-sm font-medium text-slate-800">${escapeHtml(payload.input_value ?? '')}</div>
            ${verdictBlock}
            ${statsBlock}
            ${aiBlock}
            ${errorBlock}
        </div>
    `;
    result.classList.remove('hidden');

    const copyBtn = $('.copy-btn', result);
    if (copyBtn) {
        copyBtn.addEventListener('click', () => copyResult(copyBtn));
    }
}

function copyResult(button) {
    const result = $('#result');
    if (!result) return;
    const text = result.innerText;
    const onOk = () => flashCopyState(button, true);
    const onFail = () => flashCopyState(button, false);

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(onOk).catch(() => {
            fallbackCopy(text) ? onOk() : onFail();
        });
    } else {
        fallbackCopy(text) ? onOk() : onFail();
    }
}

function fallbackCopy(text) {
    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
    } catch {
        return false;
    }
}

function flashCopyState(button, ok) {
    if (!button) return;
    const original = button.textContent;
    button.textContent = ok ? 'Tersalin ✓' : 'Gagal menyalin';
    button.classList.add(ok ? 'text-emerald-600' : 'text-rose-600');
    setTimeout(() => {
        button.textContent = original;
        button.classList.remove('text-emerald-600', 'text-rose-600');
    }, 1500);
}

async function pollStatus(scanId) {
    state.lastScanId = scanId;
    state.pollAttempts = 0;
    stopPolling();
    const statusUrl = `/api/scans/${scanId}/status`;

    const tick = async () => {
        state.pollAttempts += 1;
        try {
            const res = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
            });
            if (res.status === 404) {
                stopPolling();
                hideProgress();
                renderError('Catatan pemindaian sudah kedaluwarsa atau tidak ditemukan.');
                return;
            }
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            const json = await res.json();
            const payload = json.data ?? json;
            renderStatus(payload);

            if (payload.progress != null) {
                setProgress(payload.progress);
            } else if (payload.status === 'pending') {
                setProgress(15);
            } else if (payload.status === 'scanning') {
                setProgress(60);
            } else if (payload.status === 'completed' || payload.status === 'failed') {
                setProgress(100);
            }

            const terminal = payload.status === 'completed' || payload.status === 'failed';
            if (terminal || state.pollAttempts >= POLL_MAX_ATTEMPTS) {
                stopPolling();
                setTimeout(hideProgress, 600);
            }
        } catch (err) {
            if (state.pollAttempts >= POLL_MAX_ATTEMPTS) {
                stopPolling();
                hideProgress();
                renderError(`Pemeriksaan timeout setelah ${POLL_MAX_ATTEMPTS} percobaan.`);
                return;
            }
        }
    };

    await tick();
    if (!state.pollHandle) {
        state.pollHandle = setInterval(tick, POLL_INTERVAL_MS);
    }
}

function stopPolling() {
    if (state.pollHandle) {
        clearInterval(state.pollHandle);
        state.pollHandle = null;
    }
}

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = data?.message || data?.errors?.url?.[0] || `HTTP ${res.status}`;
        throw new Error(msg);
    }
    return data;
}

async function postForm(url, formData) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = data?.message || data?.errors?.file?.[0] || `HTTP ${res.status}`;
        throw new Error(msg);
    }
    return data;
}

async function handleScanUrl(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const input = form.querySelector('input[name="url"]');
    const url = input?.value?.trim();
    if (!url) return;

    try {
        showLoading('Mengirim URL…');
        setProgress(10);
        const data = await postJson('/api/scan-url', { url });
        hideLoading();
        await pollStatus(data.scan_id);
    } catch (err) {
        hideLoading();
        hideProgress();
        renderError(err.message || 'Gagal memindai URL.');
    }
}

async function handleScanFile(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const fileInput = form.querySelector('input[name="file"]');
    const file = fileInput?.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        showLoading('Mengunggah berkas…');
        setProgress(15);
        const data = await postForm('/api/scan-file', formData);
        hideLoading();
        await pollStatus(data.scan_id);
    } catch (err) {
        hideLoading();
        hideProgress();
        renderError(err.message || 'Gagal memindai berkas.');
    }
}

function switchTab(target) {
    const tabs = $$('[data-tab]');
    const panels = $$('[data-panel]');
    tabs.forEach((btn) => {
        const isActive = btn.dataset.tab === target;
        btn.classList.toggle('border-brand-500', isActive);
        btn.classList.toggle('text-brand-600', isActive);
        btn.classList.toggle('border-transparent', !isActive);
        btn.classList.toggle('text-slate-500', !isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    panels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.panel !== target);
    });
}

function setupDropZone() {
    const drop = $('#dropZone');
    const input = drop?.querySelector('input[type="file"]');
    if (!drop || !input) return;

    ['dragenter', 'dragover'].forEach((evt) => {
        drop.addEventListener(evt, (e) => {
            e.preventDefault();
            drop.classList.add('ring-2', 'ring-brand-400', 'bg-brand-50');
        });
    });
    ['dragleave', 'drop'].forEach((evt) => {
        drop.addEventListener(evt, (e) => {
            e.preventDefault();
            drop.classList.remove('ring-2', 'ring-brand-400', 'bg-brand-50');
        });
    });
    
    // Allow clicking the dropzone to open file dialog
    drop.addEventListener('click', () => input.click());

    // Update UI when file is selected
    input.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        const textElement = $('#dropText', drop);
        if (textElement) {
            textElement.textContent = file ? file.name : 'Tekan di sini untuk memilih file';
            if (file) {
                textElement.classList.add('text-brand-600');
            } else {
                textElement.classList.remove('text-brand-600');
            }
        }
    });

    drop.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}

function init() {
    const urlForm = $('#urlForm');
    const fileForm = $('#fileForm');
    if (urlForm) urlForm.addEventListener('submit', handleScanUrl);
    if (fileForm) fileForm.addEventListener('submit', handleScanFile);

    $$('[data-tab]').forEach((btn) => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    setupDropZone();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
