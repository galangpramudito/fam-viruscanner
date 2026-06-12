<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CekSiber - Pemindai Link & File Berbahaya</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased selection:bg-blue-200">

    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-shield-halved text-blue-600 text-2xl"></i>
                <span class="text-xl font-bold tracking-tight text-slate-900">Cek<span class="text-blue-600">Siber</span></span>
            </div>
            <span class="text-xs bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-full border border-blue-200">Gratis & Aman</span>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto px-4 py-8 md:py-12">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight sm:text-4xl mb-4">Cek Sebelum Klik!</h1>
            <p class="text-base md:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed">
                Punya link mencurigakan dari WA? Atau disuruh install file APK aneh? <br class="hidden md:block">
                Cek dulu di sini supaya HP dan rekening Anda aman dari penipu.
            </p>
        </div>

        <div class="flex bg-slate-200 p-1.5 rounded-xl mb-6 max-w-md mx-auto shadow-inner">
            <button id="tab-url-btn" onclick="switchTab('url')" class="w-full py-3 text-sm font-bold rounded-lg bg-white text-blue-700 shadow-sm transition">
                <i class="fa-solid fa-link mr-2"></i>Cek Link / Web
            </button>
            <button id="tab-file-btn" onclick="switchTab('file')" class="w-full py-3 text-sm font-bold rounded-lg text-slate-600 hover:text-slate-900 transition">
                <i class="fa-solid fa-file-shield mr-2"></i>Cek Dokumen / APK
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6 md:p-8 mb-8">
            
            <form id="form-url" onsubmit="handleScanUrl(event)" class="space-y-5">
                <div>
                    <label class="block text-base font-bold text-slate-800 mb-2">Tempel Link dari WhatsApp / SMS di sini:</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-globe text-slate-400 text-lg"></i>
                        </div>
                        <input type="url" id="input-url" required class="block w-full pl-12 pr-4 py-4 text-lg border-2 border-slate-200 rounded-xl focus:outline-hidden focus:ring-0 focus:border-blue-500 text-slate-900 placeholder-slate-400 transition" placeholder="Contoh: https://undian-hadiah-palsu.com">
                    </div>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center justify-center space-x-2 text-lg cursor-pointer">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Mulai Pindai Sekarang</span>
                </button>
            </form>

            <form id="form-file" onsubmit="handleScanFile(event)" class="hidden space-y-5" enctype="multipart/form-data">
                <div>
                    <label class="block text-base font-bold text-slate-800 mb-2">Pilih file yang ingin diperiksa:</label>
                    <div id="drop-zone" class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-10 text-center cursor-pointer bg-slate-50 hover:bg-blue-50/50 transition group">
                        <input type="file" id="input-file" required accept=".apk,.pdf,.doc,.docx" class="hidden" onchange="updateFileInfo()">
                        <div class="bg-white w-16 h-16 rounded-full shadow-sm flex items-center justify-center mx-auto mb-4 border border-slate-100 group-hover:border-blue-200 transition">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-blue-500"></i>
                        </div>
                        <p id="drop-text" class="text-base font-bold text-slate-700">Tekan di sini untuk memilih file</p>
                        <p class="text-sm text-slate-500 mt-2">Mendukung format APK, PDF, Word (Maksimal 20MB)</p>
                    </div>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center justify-center space-x-2 text-lg cursor-pointer">
                    <i class="fa-solid fa-shield-virus"></i>
                    <span>Periksa File Sekarang</span>
                </button>
            </form>

            <div id="loading-indicator" class="hidden text-center py-12 space-y-5">
                <div class="inline-block animate-spin rounded-full h-14 w-14 border-4 border-slate-100 border-t-blue-600"></div>
                <div>
                    <p id="loading-msg" class="text-lg font-bold text-slate-800">Sedang memeriksa keamanan...</p>
                    <p class="text-sm text-slate-500 mt-1">Sistem sedang mencocokkan data dengan puluhan antivirus dunia. Mohon tunggu sebentar.</p>
                </div>
            </div>

            <div id="result-box" class="hidden border-t-2 border-slate-100 pt-8 mt-8 animate-fade-in">
                
                <div id="status-banner" class="rounded-2xl p-6 mb-6 text-center shadow-sm border-2">
                    <i id="status-icon" class="fa-solid fa-circle-check text-6xl mb-3"></i>
                    <h2 id="status-title" class="text-2xl md:text-3xl font-black tracking-tight uppercase">STATUS</h2>
                    <p id="status-subtitle" class="mt-2 text-sm md:text-base font-medium opacity-90"></p>
                </div>
                
                <div class="bg-slate-50 rounded-xl p-4 mb-6 border border-slate-200">
                    <p class="text-sm text-slate-500 mb-1">Yang diperiksa:</p>
                    <p id="res-target" class="font-semibold text-slate-800 break-all mb-3 text-sm"></p>
                    
                    <p class="text-sm text-slate-500 mb-1">Hasil Deteksi Sistem:</p>
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-robot text-slate-400"></i>
                        <p id="res-score" class="font-bold text-sm text-slate-800"></p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-slate-800"><i class="fa-brands fa-whatsapp text-green-500 mr-1"></i> Penjelasan Lengkap (Bisa Dibagikan):</label>
                    </div>
                    <div class="relative bg-blue-50/50 rounded-2xl border border-blue-100 p-1">
                        <textarea id="res-ai" readonly rows="8" class="block w-full bg-transparent text-slate-700 font-medium text-base p-4 rounded-xl border-none focus:ring-0 resize-none leading-relaxed"></textarea>
                    </div>
                    
                    <button onclick="copyToClipboard()" class="mt-4 w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center space-x-2 cursor-pointer">
                        <i class="fa-regular fa-copy"></i>
                        <span id="copy-btn-text">Salin Pesan & Bagikan ke Grup Keluarga</span>
                    </button>
                </div>

            </div>

        </div>
    </main>

    <script>
        // Setup Drag and Drop Zone Interaction
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('input-file');
        
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-blue-500', 'bg-blue-50'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-500', 'bg-blue-50'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileInfo();
            }
        });

        function updateFileInfo() {
            const file = fileInput.files[0];
            if (file) {
                document.getElementById('drop-text').innerHTML = `File Siap: <span class="text-blue-600 font-bold">${file.name}</span>`;
            }
        }

        // Tab Switcher Logic
        function switchTab(type) {
            const urlBtn = document.getElementById('tab-url-btn');
            const fileBtn = document.getElementById('tab-file-btn');
            const formUrl = document.getElementById('form-url');
            const formFile = document.getElementById('form-file');
            document.getElementById('result-box').classList.add('hidden');

            if (type === 'url') {
                urlBtn.className = "w-full py-3 text-sm font-bold rounded-lg bg-white text-blue-700 shadow-sm transition";
                fileBtn.className = "w-full py-3 text-sm font-bold rounded-lg text-slate-600 hover:text-slate-900 transition";
                formUrl.classList.remove('hidden');
                formFile.classList.add('hidden');
            } else {
                fileBtn.className = "w-full py-3 text-sm font-bold rounded-lg bg-white text-blue-700 shadow-sm transition";
                urlBtn.className = "w-full py-3 text-sm font-bold rounded-lg text-slate-600 hover:text-slate-900 transition";
                formFile.classList.remove('hidden');
                formUrl.classList.add('hidden');
            }
        }

        // Action Trigger Utilities
        function showLoading() {
            document.getElementById('loading-indicator').classList.remove('hidden');
            document.getElementById('result-box').classList.add('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-indicator').classList.add('hidden');
        }

        // FETCH DATA URL
        async function handleScanUrl(e) {
            e.preventDefault();
            showLoading();
            try {
                const response = await fetch('/api/scan-url', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ url: document.getElementById('input-url').value })
                });
                const result = await response.json();
                hideLoading();

                if (result.status === 'success') {
                    displayResult(result.data.input_value, result.data.malicious_count, result.data.total_engines, result.data.ai_explanation);
                } else {
                    alert(result.message || "Terjadi kesalahan sistem.");
                }
            } catch (err) {
                hideLoading();
                alert("Gagal terhubung ke server. Pastikan koneksi internet lancar.");
            }
        }

        // FETCH DATA FILE
        async function handleScanFile(e) {
            e.preventDefault();
            const fileField = document.getElementById('input-file').files[0];
            if (!fileField) return alert("Pilih file terlebih dahulu!");

            const formData = new FormData();
            formData.append('file', fileField);
            showLoading();

            try {
                const response = await fetch('/api/scan-file', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const result = await response.json();
                hideLoading();

                if (result.status === 'success') {
                    displayResult(result.data.input_value, result.data.malicious_count, result.data.total_engines, result.data.ai_explanation);
                } else {
                    alert(result.message || "Terjadi kesalahan sistem.");
                }
            } catch (err) {
                hideLoading();
                alert("Gagal mengunggah berkas ke server.");
            }
        }

        // LOGIKA TAMPILAN UNTUK ORANG AWAM (DIPERBAIKI)
        function displayResult(target, malicious, total, explanation) {
            document.getElementById('res-target').innerText = target;
            document.getElementById('res-ai').value = explanation;
            
            const scoreElement = document.getElementById('res-score');
            scoreElement.innerText = `${malicious} dari ${total} mesin anti-virus menyatakan ini BERBAHAYA.`;
            // Reset class default untuk warna teks skor
            scoreElement.className = "font-bold text-sm";
            
            const banner = document.getElementById('status-banner');
            const icon = document.getElementById('status-icon');
            const title = document.getElementById('status-title');
            const subtitle = document.getElementById('status-subtitle');

            const textToAnalyze = explanation.toUpperCase();

            // BUG FIX: Hapus deteksi kata "BAHAYA" secara teks murni agar tidak bentrok dengan kata "TIDAK BERBAHAYA"
            if (malicious > 0 || textToAnalyze.includes('🔴')) {
                // TEMA MERAH (BAHAYA)
                banner.className = "rounded-2xl p-6 mb-6 text-center shadow-md border-2 text-red-900 bg-red-100 border-red-300 animate-pulse";
                icon.className = "fa-solid fa-triangle-exclamation text-6xl mb-3 text-red-600";
                title.innerText = "AWAS! SANGAT BERBAHAYA";
                subtitle.innerText = "Jangan diklik, jangan diisi data, dan segera hapus pesan tersebut!";
                scoreElement.classList.add('text-red-600');
            } 
            else if (textToAnalyze.includes('🟡') || textToAnalyze.includes('WASPADA')) {
                // TEMA KUNING (WASPADA)
                banner.className = "rounded-2xl p-6 mb-6 text-center shadow-md border-2 text-yellow-900 bg-yellow-100 border-yellow-300";
                icon.className = "fa-solid fa-shield-cat text-6xl mb-3 text-yellow-600";
                title.innerText = "WASPADA & HATI-HATI";
                subtitle.innerText = "Meski belum terdeteksi virus, link/file ini terlihat mencurigakan.";
                scoreElement.classList.add('text-yellow-700');
            } 
            else {
                // TEMA HIJAU (AMAN)
                banner.className = "rounded-2xl p-6 mb-6 text-center shadow-md border-2 text-green-900 bg-green-100 border-green-300";
                icon.className = "fa-solid fa-shield-check text-6xl mb-3 text-green-600";
                title.innerText = "KEMUNGKINAN BESAR AMAN";
                subtitle.innerText = "Sistem kami tidak menemukan ancaman virus pada saat ini.";
                scoreElement.classList.add('text-slate-800');
            }
            
            document.getElementById('result-box').classList.remove('hidden');
        }

        // Copy Text Functionality
        function copyToClipboard() {
            const textarea = document.getElementById('res-ai');
            textarea.select();
            document.execCommand('copy');
            
            const btnText = document.getElementById('copy-btn-text');
            btnText.innerText = "Teks Berhasil Disalin!";
            setTimeout(() => { btnText.innerText = "Salin Pesan & Bagikan ke Grup Keluarga"; }, 2500);
        }
    </script>
</body>
</html>