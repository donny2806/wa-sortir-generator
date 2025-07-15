// server.js
// Import modul yang diperlukan
const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js'); // Menggunakan LocalAuth untuk sesi persisten
const qrcode = require('qrcode-terminal');
const cors = require('cors'); // Import modul CORS
const http = require('http');
const { Server } = require('socket.io');
const fs = require('fs'); // Untuk operasi sistem file
const path = require('path'); // Untuk jalur file

// Inisialisasi aplikasi Express
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "http://192.168.100.107", // Mengubah ini agar sesuai dengan alamat IP frontend Anda
        methods: ["GET", "POST"]
    }
});

// Gunakan middleware CORS
app.use(cors());
// Tingkatkan batas ukuran body untuk JSON
app.use(express.json({ limit: '50mb' })); // Mengizinkan payload hingga 50MB
app.use(express.urlencoded({ limit: '50mb', extended: true })); // Juga untuk URL-encoded bodies jika digunakan

// Path untuk menyimpan sesi WhatsApp
const SESSION_FILE_PATH = './.wwebjs_auth';

// Inisialisasi klien WhatsApp
let client; // Deklarasi variabel client di luar agar bisa diakses di mana saja
let isClientReady = false; // Status kesiapan klien WhatsApp
let isInitializingClient = false; // Flag baru untuk mencegah inisialisasi ganda

// Global state untuk pemindaian aktif tunggal (baik massal maupun instan)
let currentScan = {
    isActive: false,      // Apakah ada pemindaian yang sedang berjalan atau dijeda?
    type: null,           // 'mass' atau 'instant'
    numbers: [],          // Array nomor yang akan dipindai
    index: 0,             // Indeks saat ini dalam array nomor
    socket: null,         // Klien Socket.IO yang memulai pemindaian ini
    isPaused: false,      // Apakah pemindaian saat ini dijeda?
    resumePromiseResolve: null, // Fungsi untuk resolve ketika pemindaian dilanjutkan dari jeda
    cancelRequested: false // Flag untuk memberi sinyal pembatalan pemindaian
};

// Objek untuk melacak klien yang terhubung (berdasarkan socket.id)
const onlineClients = {};

// Fungsi untuk menginisialisasi atau memuat ulang klien WhatsApp
async function initializeWhatsAppClient() { // Dibuat async
    // Mencegah inisialisasi ganda jika sudah dalam proses
    if (isInitializingClient) {
        console.log('Inisialisasi klien sudah berjalan, mengabaikan permintaan baru.');
        return;
    }
    isInitializingClient = true;
    console.log('Menginisialisasi klien WhatsApp...');

    // Pastikan klien sebelumnya benar-benar null sebelum membuat yang baru
    if (client) {
        console.warn('Klien WhatsApp sudah ada saat initializeWhatsAppClient dipanggil. Mencoba menghancurkannya.');
        try {
            if (client.pupBrowser && typeof client.pupBrowser.close === 'function') {
                await client.destroy();
                console.log('Klien WhatsApp yang ada dihancurkan secara paksa.');
            } else {
                console.log('Klien yang ada ditemukan, tetapi pupBrowser tidak aktif atau sudah ditutup. Melewatkan destroy().');
            }
        } catch (err) {
            console.error('Gagal menghancurkan klien yang ada secara paksa:', err);
        } finally {
            client = null;
        }
    }

    client = new Client({
        authStrategy: new LocalAuth({
            clientId: "whatsapp-checker", // ID unik untuk sesi ini
            dataPath: SESSION_FILE_PATH // Path untuk menyimpan data sesi
        }),
        puppeteer: {
            // Menggunakan 'new' untuk headless mode yang lebih modern dan stabil
            headless: 'new',
            args: [
                // Set argumen yang paling dasar dan stabil untuk Puppeteer
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage', // Penting untuk lingkungan Docker/Linux
                // Argumen lain yang sering menyebabkan masalah dihilangkan
            ],
        }
    });

    client.on('qr', (qr) => {
        console.log('QR RECEIVED', qr);
        qrcode.generate(qr, { small: true });
        // Kirim QR code ke semua klien frontend yang terhubung
        io.emit('qr_code', { qrCodeUrl: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qr)}` });
        isClientReady = false;
    });

    client.on('ready', () => {
        console.log('Client is ready!');
        isClientReady = true;
        isInitializingClient = false; // Inisialisasi selesai
        io.emit('whatsapp_ready', { message: 'WhatsApp client terhubung dan siap!' });
        console.log('WhatsApp client siap. Memperbarui status tombol.');
    });

    client.on('authenticated', (session) => {
        console.log('AUTHENTICATED', session);
    });

    client.on('auth_failure', async (msg) => { // Dibuat async
        console.error('AUTHENTICATION FAILURE', msg);
        io.emit('auth_failure', { message: msg });
        isClientReady = false;
        isInitializingClient = false; // Inisialisasi gagal
        // Coba hancurkan klien yang gagal autentikasi sebelum inisialisasi ulang
        if (client) {
            try {
                if (client.pupBrowser && typeof client.pupBrowser.close === 'function') {
                    await client.destroy();
                    console.log('Klien yang gagal autentikasi dihancurkan.');
                }
            } catch (destroyError) {
                console.error('Error saat menghancurkan klien yang gagal autentikasi:', destroyError);
            } finally {
                client = null;
            }
        }
        console.log('Mencoba inisialisasi ulang klien setelah kegagalan autentikasi...');
        setTimeout(async () => { // Gunakan async callback untuk setTimeout
            await initializeWhatsAppClient();
        }, 5000);
    });

    client.on('disconnected', async (reason) => { // Dibuat async
        console.log('Client was disconnected', reason);
        isClientReady = false;
        isInitializingClient = false; // Inisialisasi gagal
        io.emit('whatsapp_disconnected', { reason: reason });
        // Coba hancurkan klien yang terputus sebelum inisialisasi ulang
        if (client) {
            try {
                if (client.pupBrowser && typeof client.pupBrowser.close === 'function') {
                    await client.destroy();
                    console.log('Klien yang terputus dihancurkan.');
                }
            } catch (destroyError) {
                console.error('Error saat menghancurkan klien yang terputus:', destroyError);
            } finally {
                client = null;
            }
        }
        console.log('Mencoba inisialisasi ulang klien setelah terputus...');
        // Panggil initializeWhatsAppClient secara langsung dan tunggu
        setTimeout(async () => { // Gunakan async callback untuk setTimeout
            await initializeWhatsAppClient();
        }, 5000); // Penundaan 5 detik sebelum mencoba inisialisasi ulang
    });

    try {
        await client.initialize(); // Await inisialisasi
    } catch (initError) {
        console.error('Error during WhatsApp client initialization:', initError);
        isClientReady = false;
        isInitializingClient = false; // Inisialisasi gagal
        io.emit('whatsapp_disconnected', { reason: `Initialization failed: ${initError.message}` });
        // Logika retry sudah ada di disconnected/auth_failure handler, jadi ini mungkin redundant
        // tetapi tetap dipertahankan sebagai fallback jika error terjadi sebelum event listener terpicu.
        if (client) { // Coba hancurkan jika ada error inisialisasi
            try {
                if (client.pupBrowser && typeof client.pupBrowser.close === 'function') {
                    await client.destroy();
                }
            } catch (err) {
                console.warn('Gagal menghancurkan klien setelah error inisialisasi:', err);
            } finally {
                client = null;
            }
        }
        setTimeout(async () => {
            await initializeWhatsAppClient();
        }, 5000);
    }
}

// Panggil inisialisasi saat server dimulai menggunakan IIFE (Immediately Invoked Function Expression)
(async () => {
    await initializeWhatsAppClient();
})();

// Endpoint untuk mendapatkan status QR code atau kesiapan
app.get('/get-qr', (req, res) => {
    if (isClientReady) {
        res.json({ status: 'ready', message: 'WhatsApp client is ready.' });
    } else {
        // Jika klien belum siap, kita bisa mengirim status inisialisasi
        res.json({ status: 'initializing', message: 'WhatsApp client is initializing or waiting for QR scan.' });
    }
});

/**
 * Fungsi utilitas untuk membersihkan dan memformat nomor telepon untuk penggunaan WhatsApp.
 * Mengembalikan nomor dalam format E.164 tanpa '+' di depan (misal: 6281234567890 atau 12125550100).
 *
 * @param {string} rawNumber Nomor telepon mentah dari input.
 * @returns {string} Nomor telepon yang diformat.
 */
function cleanAndFormatNumber(rawNumber) {
    let cleaned = rawNumber.replace(/[^0-9+]/g, ''); // Hapus semua non-digit kecuali '+'

    // Jika dimulai dengan '+', hapus '+' saja, pertahankan sisanya (kode negara + nomor)
    if (cleaned.startsWith('+')) {
        return cleaned.substring(1);
    }

    // Jika nomor dimulai dengan '0' dan bukan '00' (untuk nomor internasional),
    // asumsikan itu nomor lokal Indonesia dan ganti '0' dengan '62'.
    if (cleaned.startsWith('0') && !cleaned.startsWith('00') && cleaned.length >= 8) {
        return `62${cleaned.substring(1)}`;
    }
    // Jika nomor dimulai dengan '8' (dan tidak diawali dengan '+' atau '62'),
    // asumsikan itu nomor seluler Indonesia tanpa '0' atau '62' di depan, tambahkan '62'.
    else if (cleaned.startsWith('8') && cleaned.length >= 8 && !cleaned.startsWith('62')) {
        return `62${cleaned}`;
    }
    // Untuk format lain (misalnya sudah 62xxxx, atau nomor internasional tanpa '+'),
    // biarkan apa adanya. whatsapp-web.js akan menanganinya atau gagal.
    return cleaned;
}

/**
 * Fungsi untuk memulai pemindaian nomor telepon (baik massal maupun instan).
 * Ini adalah loop rekursif yang memproses satu nomor pada satu waktu.
 */
async function startScan() {
    // Periksa apakah pemindaian dibatalkan
    if (currentScan.cancelRequested) {
        console.log('Permintaan pembatalan pemindaian, menghentikan pemindaian.');
        if (currentScan.socket) {
            currentScan.socket.emit('scan_status', { status: 'cancelled', message: 'Pemindaian dibatalkan oleh pengguna.', type: currentScan.type });
        }
        resetScanState();
        return;
    }

    // Periksa apakah klien WhatsApp siap
    if (!client || !isClientReady) {
        console.error('Klien tidak siap untuk pemindaian. Menghentikan pemindaian.');
        if (currentScan.socket) {
            currentScan.socket.emit('scan_error', { message: 'Klien WhatsApp tidak siap untuk pemindaian.', type: currentScan.type });
        }
        resetScanState();
        return;
    }

    // Mekanisme jeda: jika dijeda, tunggu hingga dilanjutkan
    if (currentScan.isPaused) {
        console.log('Pemindaian dijeda. Menunggu untuk dilanjutkan.');
        if (currentScan.socket) {
            // Emit status jeda ke klien yang memulai pemindaian
            currentScan.socket.emit('scan_status', { status: 'paused', message: `Pemindaian dijeda di nomor ${currentScan.numbers[currentScan.index] || 'terakhir'}.`, type: currentScan.type });
        }
        await new Promise(resolve => {
            currentScan.resumePromiseResolve = resolve;
        });
        currentScan.resumePromiseResolve = null; // Hapus resolver setelah melanjutkan
        // Periksa kembali pembatalan segera setelah melanjutkan
        if (currentScan.cancelRequested) {
            console.log('Pemindaian dibatalkan saat dijeda, menghentikan loop.');
            if (currentScan.socket) {
                currentScan.socket.emit('scan_status', { status: 'cancelled', message: 'Pemindaian dibatalkan oleh pengguna.', type: currentScan.type });
            }
            resetScanState();
            return;
        }
        console.log(`Pemindaian dilanjutkan dari indeks ${currentScan.index}`);
        if (currentScan.socket) {
            currentScan.socket.emit('scan_status', { status: 'resumed', message: `Pemindaian dilanjutkan dari nomor ${currentScan.numbers[currentScan.index]}.`, type: currentScan.type });
        }
    }

    // Periksa apakah semua nomor telah dipindai
    if (currentScan.index >= currentScan.numbers.length) {
        console.log('Pemindaian selesai.');
        if (currentScan.socket) {
            currentScan.socket.emit('scan_complete', { message: 'Pemindaian selesai.', results: 'Semua nomor telah diperiksa.', type: currentScan.type });
        }
        resetScanState();
        return;
    }

    const originalNumber = currentScan.numbers[currentScan.index];
    const formattedNumberForWaJs = cleanAndFormatNumber(originalNumber);
    // Untuk hyperlink wa.me, kita akan menggunakan nomor asli
    const formattedNumberForLink = originalNumber.replace(/[^0-9+]/g, '');

    let debug_info = 'OK';
    let isRegistered = false;
    let profilePicUrl = null; // Inisialisasi null

    // Validasi format nomor setelah pembersihan
    if (!formattedNumberForWaJs || formattedNumberForWaJs.length < 5 || !/^[0-9]+$/.test(formattedNumberForWaJs)) {
        debug_info = `Error: Nomor '${originalNumber}' diformat menjadi '${formattedNumberForWaJs}' tidak valid/terlalu pendek atau mengandung karakter non-digit.`;
        isRegistered = false;
    } else {
        try {
            console.log(`Memeriksa nomor: Original='${originalNumber}', Formatted='${formattedNumberForWaJs}@c.us'`);
            isRegistered = await client.isRegisteredUser(`${formattedNumberForWaJs}@c.us`);

            // Ambil foto profil jika nomor terdaftar, TERLEPAS DARI TIPE SCAN
            if (isRegistered) {
                try {
                    const contact = await client.getContactById(`${formattedNumberForWaJs}@c.us`);
                    if (contact) {
                        profilePicUrl = await contact.getProfilePicUrl();
                        console.log(`Foto profil ditemukan untuk ${originalNumber}: ${profilePicUrl}`);
                    } else {
                        console.warn(`Objek kontak null untuk pengguna terdaftar ${formattedNumberForWaJs}`);
                    }
                } catch (contactError) {
                    console.warn(`Gagal mengambil foto profil untuk ${originalNumber}:`, contactError.message);
                }
            }
        } catch (error) {
            console.error(`Error checking number ${originalNumber} (formatted: ${formattedNumberForWaJs}):`, error);
            isRegistered = false;
            debug_info = `Error API: ${error.message.substring(0, 50)}...`;
            if (error.message.includes('Invalid number format')) {
                debug_info = `Error: Format nomor tidak valid untuk WhatsApp.`;
            }
        }
    }

    const percentage = ((currentScan.index + 1) / currentScan.numbers.length) * 100;

    // Emit event yang berbeda berdasarkan tipe pemindaian
    if (currentScan.type === 'mass') {
        currentScan.socket.emit('scan_progress', {
            currentNumber: originalNumber,
            formattedNumber: formattedNumberForLink,
            isRegistered: isRegistered,
            whatsappName: 'N/A', // WhatsApp-web.js tidak langsung menyediakan nama di isRegisteredUser
            profilePicUrl: profilePicUrl, // <--- SEKARANG MENGIRIMKAN URL FOTO PROFIL
            status: 'N/A',
            percentage: percentage,
            statusText: `Memeriksa ${originalNumber}...`,
            debug_info: debug_info
        });
    } else if (currentScan.type === 'instant') {
        currentScan.socket.emit('single_whatsapp_number_result', {
            originalNumber: originalNumber,
            isRegistered: isRegistered,
            profilePicUrl: profilePicUrl,
            formattedNumberForLink: formattedNumberForLink,
            error: debug_info !== 'OK' ? debug_info : null // Kirim error jika debug_info menunjukkan ada error
        });
    }

    currentScan.index++;
    setTimeout(startScan, 100); // Penundaan singkat antar cek
}

// Fungsi untuk mereset status pemindaian
function resetScanState() {
    currentScan.isActive = false;
    currentScan.type = null;
    currentScan.numbers = [];
    currentScan.index = 0;
    currentScan.socket = null;
    currentScan.isPaused = false;
    currentScan.resumePromiseResolve = null;
    currentScan.cancelRequested = false;
    console.log('Status pemindaian direset.');
}

// Endpoint untuk memeriksa nomor WhatsApp secara massal
app.post('/check-whatsapp-numbers', async (req, res) => {
    const { numbers, socketId } = req.body;
    const clientSocket = io.sockets.sockets.get(socketId);

    if (!clientSocket) {
        return res.status(400).json({ error: 'ID Soket hilang atau tidak valid. Tidak dapat mengirim pembaruan real-time.' });
    }

    if (!client || !isClientReady) {
        clientSocket.emit('scan_error', { message: 'Klien WhatsApp belum siap. Harap pindai kode QR terlebih dahulu.', type: 'mass' });
        return res.status(503).json({ error: 'Klien WhatsApp belum siap. Harap pindai kode QR terlebih dahulu.' });
    }
    if (!numbers || !Array.isArray(numbers) || numbers.length === 0) {
        clientSocket.emit('scan_error', { message: 'Daftar nomor tidak valid.', type: 'mass' });
        return res.status(400).json({ error: 'Daftar nomor tidak valid.' });
    }

    if (currentScan.isActive) {
        clientSocket.emit('scan_error', { message: 'Pemindaian sedang berlangsung. Harap tunggu atau batalkan pemindaian saat ini.', type: currentScan.type });
        return res.status(409).json({ error: 'Pemindaian sedang berlangsung.' });
    }

    // Inisialisasi currentScan untuk pemindaian massal
    currentScan.isActive = true;
    currentScan.type = 'mass';
    currentScan.numbers = numbers;
    currentScan.index = 0;
    currentScan.socket = clientSocket;
    currentScan.isPaused = false;
    currentScan.cancelRequested = false;

    // Bersihkan hasil sebelumnya di frontend (melalui soket)
    // Ini akan memicu frontend untuk mereset tabel massal
    currentScan.socket.emit('scan_complete', { results: [], type: 'mass' });

    // Mulai pemindaian
    startScan();

    res.json({ message: 'Pemindaian nomor WhatsApp dimulai.', total: numbers.length });
});

// --- Socket.IO Event Handlers ---
io.on('connection', (socket) => {
    console.log('Klien terhubung:', socket.id);
    onlineClients[socket.id] = true; // Tambahkan klien ke daftar online
    io.emit('client_count_update', { count: Object.keys(onlineClients).length }); // Perbarui semua klien

    // Kirim status klien WhatsApp saat ini ke klien yang baru terhubung
    if (isClientReady) {
        socket.emit('whatsapp_ready', { message: 'Klien WhatsApp terhubung dan siap!' });
    } else {
        // Jika klien belum siap, coba kirim QR code jika tersedia
        socket.emit('whatsapp_disconnected', { reason: 'Klien belum siap, menunggu QR atau inisialisasi.' });
    }

    // Tangani permintaan QR code baru (dari frontend)
    socket.on('request_new_qr', async () => { // Dibuat async
        console.log('Menerima permintaan QR baru dari klien:', socket.id);
        // Panggil inisialisasi ulang untuk memicu QR baru jika diperlukan
        if (!isClientReady) {
            await initializeWhatsAppClient(); // Await panggilan
        } else {
            // Jika klien sudah siap, kirim status ready saja
            socket.emit('whatsapp_ready', { message: 'Klien WhatsApp terhubung dan siap!' });
        }
    });

    socket.on('disconnect', () => {
        console.log('Klien terputus:', socket.id);
        delete onlineClients[socket.id]; // Hapus klien dari daftar online
        io.emit('client_count_update', { count: Object.keys(onlineClients).length }); // Perbarui semua klien

        // Jika klien yang terputus adalah yang memulai pemindaian, batalkan pemindaian
        if (currentScan.isActive && currentScan.socket && currentScan.socket.id === socket.id) {
            console.log('Klien yang memulai pemindaian terputus. Membatalkan pemindaian.');
            currentScan.cancelRequested = true; // Set flag pembatalan
            if (currentScan.isPaused && currentScan.resumePromiseResolve) {
                currentScan.resumePromiseResolve(); // Lanjutkan jika dijeda untuk memicu pembatalan
            }
            // startScan loop akan menangani pengiriman status 'cancelled' dan mereset state
        }
    });

    socket.on('pause_scan', () => {
        console.log('Event pause_scan diterima.');
        if (currentScan.isActive && !currentScan.isPaused) {
            currentScan.isPaused = true;
            console.log('Pemindaian dijeda oleh klien:', socket.id);
            // Status akan di-emit oleh startScan ketika mencapai titik jeda
        } else {
            console.log('Tidak ada pemindaian aktif atau sudah dijeda.');
        }
    });

    socket.on('resume_scan', () => {
        console.log('Event resume_scan diterima.');
        if (currentScan.isActive && currentScan.isPaused) {
            currentScan.isPaused = false;
            console.log('Pemindaian dilanjutkan oleh klien:', socket.id);
            if (currentScan.resumePromiseResolve) {
                currentScan.resumePromiseResolve(); // Resolve promise untuk melanjutkan loop
            }
            // Status akan di-emit oleh startScan setelah melanjutkan
        } else {
            console.log('Pemindaian tidak dalam keadaan dijeda untuk dilanjutkan.');
        }
    });

    socket.on('cancel_scan', () => {
        console.log('Event cancel_scan diterima.');
        if (currentScan.isActive) {
            currentScan.cancelRequested = true;
            // Jika dijeda, resolve promise untuk membiarkan loop memeriksa cancelRequested
            if (currentScan.isPaused && currentScan.resumePromiseResolve) {
                currentScan.resumePromiseResolve();
            }
            console.log('Pemindaian dibatalkan oleh klien:', socket.id);
            // Loop startScan akan menangani pengiriman status 'cancelled' dan mereset state
        } else {
            console.log('Tidak ada pemindaian aktif atau dijeda untuk dibatalkan.');
        }
    });

    socket.on('logout_whatsapp', async () => {
        try {
            if (client && isClientReady) {
                await client.logout();
                console.log('Logout WhatsApp berhasil.');
                io.to(socket.id).emit('whatsapp_disconnected', { reason: 'Logged out by user.' });
                isClientReady = false;
                // 'disconnected' event akan memicu inisialisasi ulang
            } else {
                io.to(socket.id).emit('whatsapp_disconnected', { reason: 'Klien tidak siap atau sudah logout.' });
            }
        } catch (error) {
            console.error('Error selama logout WhatsApp:', error);
            io.to(socket.id).emit('whatsapp_disconnected', { reason: `Logout gagal: ${error.message}` });
        }
    });

    socket.on('destroy_session_and_clear_cache', async () => {
        try {
            if (client) {
                // Periksa apakah pupBrowser ada dan memiliki metode close sebelum memanggil destroy
                if (client.pupBrowser && typeof client.pupBrowser.close === 'function') {
                    await client.destroy();
                    console.log('Sesi WhatsApp dihancurkan.');
                } else {
                    console.log('Klien ditemukan, tetapi pupBrowser tidak aktif atau sudah ditutup. Melewatkan destroy().');
                }
            }

            const sessionDirPath = path.join(__dirname, SESSION_FILE_PATH);
            if (fs.existsSync(sessionDirPath)) {
                fs.rmSync(sessionDirPath, { recursive: true, force: true });
                console.log('Cache sesi dihapus.');
                io.to(socket.id).emit('scan_status', { status: 'cache_cleared', message: 'Sesi dan cache berhasil dihapus.' });
            } else {
                console.log('Tidak ada cache sesi untuk dihapus.');
                io.to(socket.id).emit('scan_status', { status: 'cache_cleared', message: 'Tidak ada cache sesi ditemukan.' });
            }
            isClientReady = false;
            io.to(socket.id).emit('whatsapp_disconnected', { reason: 'Sesi dihancurkan dan cache dibersihkan oleh pengguna.' });
            // Inisialisasi ulang klien setelah penghancuran sesi
            setTimeout(async () => { // Gunakan async callback untuk setTimeout
                await initializeWhatsAppClient();
            }, 1000);
        } catch (error) {
            console.error('Error menghancurkan sesi dan membersihkan cache:', error);
            io.to(socket.id).emit('scan_status', { status: 'error', message: `Gagal menghancurkan sesi atau membersihkan cache: ${error.message}` });
        }
    });

    socket.on('clear_cache', async () => {
        try {
            const sessionDirPath = path.join(__dirname, SESSION_FILE_PATH);
            if (fs.existsSync(sessionDirPath)) {
                fs.rmSync(sessionDirPath, { recursive: true, force: true });
                console.log('Cache sesi dihapus.');
                io.to(socket.id).emit('scan_status', { status: 'cache_cleared', message: 'Cache berhasil dibersihkan.' });
            } else {
                console.log('Tidak ada cache sesi untuk dihapus.');
                io.to(socket.id).emit('scan_status', { status: 'cache_cleared', message: 'Tidak ada cache sesi ditemukan.' });
            }
        } catch (error) {
            console.error('Error membersihkan cache:', error);
            io.to(socket.id).emit('scan_status', { status: 'error', message: `Gagal membersihkan cache: ${error.message}` });
        }
    });

    // Tangani permintaan pencarian profil WhatsApp (untuk pencarian nomor tunggal dengan info profil)
    socket.on('search_whatsapp_profile', async ({ phoneNumber }) => {
        console.log(`Menerima permintaan pencarian profil untuk: ${phoneNumber}`);
        if (!client || !isClientReady) {
            socket.emit('whatsapp_profile_result', { error: 'Klien WhatsApp belum siap. Harap pindai kode QR terlebih dahulu.' });
            return;
        }

        const formattedNumberForWaJs = cleanAndFormatNumber(phoneNumber); // Gunakan fungsi utilitas baru

        let profileData = {
            isRegistered: false,
            profilePicUrl: null
        };

        // Validasi format nomor setelah pembersihan
        if (!formattedNumberForWaJs || formattedNumberForWaJs.length < 5 || !/^[0-9]+$/.test(formattedNumberForWaJs)) {
            profileData.error = `Nomor '${phoneNumber}' diformat menjadi '${formattedNumberForWaJs}' tidak valid/terlalu pendek atau mengandung karakter non-digit.`;
        } else {
            try {
                profileData.isRegistered = await client.isRegisteredUser(`${formattedNumberForWaJs}@c.us`);

                if (profileData.isRegistered) {
                    try {
                        const contact = await client.getContactById(`${formattedNumberForWaJs}@c.us`);
                        if (contact) {
                            profileData.profilePicUrl = await contact.getProfilePicUrl();
                            console.log(`Foto profil ditemukan untuk ${formattedNumberForWaJs}: ${profileData.profilePicUrl}`);
                        } else {
                            console.warn(`Objek kontak null untuk pengguna terdaftar ${formattedNumberForWaJs}`);
                        }
                    } catch (contactError) {
                        console.warn(`Gagal mengambil objek kontak atau foto profil untuk ${formattedNumberForWaJs}:`, contactError.message);
                    }
                }
            } catch (error) {
                console.error(`Error saat mencari profil WhatsApp untuk ${phoneNumber} (formatted: ${formattedNumberForWaJs}):`, error);
                profileData.error = `Gagal mencari profil: ${error.message}`;
                if (error.message.includes('Invalid number format')) {
                    profileData.error = `Error: Format nomor tidak valid untuk WhatsApp.`;
                }
            }
        }

        socket.emit('whatsapp_profile_result', profileData);
        console.log(`Hasil pencarian profil untuk ${phoneNumber}:`, profileData);
    });

    // BARU: Event listener untuk memulai pemindaian instan (sekarang memulai loop pemindaian)
    socket.on('start_instant_scan', async ({ numbers, socketId }) => {
        console.log(`Menerima permintaan pemeriksaan instan untuk ${numbers.length} nomor dari soket: ${socketId}`);
        const clientSocket = io.sockets.sockets.get(socketId);

        if (!clientSocket) {
            console.error(`Soket dengan ID ${socketId} tidak ditemukan untuk pemeriksaan instan.`);
            return;
        }

        if (!client || !isClientReady) {
            clientSocket.emit('scan_error', { message: 'Klien WhatsApp belum siap. Harap pindai kode QR terlebih dahulu.', type: 'instant' });
            return;
        }

        if (!Array.isArray(numbers) || numbers.length === 0) {
            clientSocket.emit('scan_error', { message: 'Daftar nomor instan tidak valid.', type: 'instant' });
            return;
        }

        if (currentScan.isActive) {
            clientSocket.emit('scan_error', { message: 'Pemindaian sedang berlangsung. Harap tunggu atau batalkan pemindaian saat ini.', type: currentScan.type });
            return;
        }

        // Inisialisasi currentScan untuk pemindaian instan
        currentScan.isActive = true;
        currentScan.type = 'instant';
        currentScan.numbers = numbers;
        currentScan.index = 0;
        currentScan.socket = clientSocket;
        currentScan.isPaused = false;
        currentScan.cancelRequested = false;

        // Mulai pemindaian
        startScan();

        // Kirim respons langsung ke frontend bahwa pemindaian telah dimulai
        // Hasil sebenarnya akan datang melalui event 'single_whatsapp_number_result'
        clientSocket.emit('instant_scan_started', { message: 'Pemeriksaan instan dimulai.' });
    });
});


// Port tempat server akan berjalan
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Server berjalan di http://localhost:${PORT}`);
    console.log('Menunggu koneksi WhatsApp...');
});
