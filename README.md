<h2>Review Aplikasi</h2>

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_4.jpg)

Aplikasi ini menggunakan sistem generator angka secara berurutan, hanya perlu mengatur angka mulai dan angka akhir. Saat angka di generate oleh generator, selanjutnya tempel baris nomor pada box area pemeriksa ataupun anda bisa mendownloadnya sebagai txt, lalu upload dengan menekan choose file. Anda juga bisa memeriksa baris yang anda miliki dengan mengupload file txt milik anda.

<h2>Konfigurasi Awal</h2>

* Server untuk program ini menggunakan dua yaitu PHP dan NodeJS. Pastikan anda memiliki php8.1 atau lebih tinggi dan NodeJS terinstall di linux anda
* Untuk melakukan instalasi program letakkan program di /var/www/html/wa-sortir-generator
* Instal requirement dengan menjalanakan
  - cd /path/to/your/server/directory # Contoh: /var/www/html/wa-sortir-generator
  - npm install (jika terjadi error, hapus package-lock.json lalu mulai npm install lagi)
* Program ini membutuhkan pustaka node js seperti :
  - whatsapp-web.js, express, socket.io, qrcode-terminal dan cors.
* install juga paket di whatsapp-backend.
  - cd whatsapp-backend
  - npm install (jika terjadi error, hapus package-lock.json lalu mulai npm install lagi)

<h2>Konfigurasi backend</h2>
* Buka file "server.js", lalu cari baris berikut :

```
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "http://yourdomainoriphere.com", // <--- Change This / Ubah ini
        methods: ["GET", "POST"]
    }
});
```

* Jika frontend dan backend berjalan di server yang SAMA: Anda bisa menggunakan http://localhost:3000 (jika port backend 3000) atau alamat IP server itu sendiri.
* Jika frontend diakses dari domain/IP lain: Gunakan http://your_frontend_domain_or_ip atau * (untuk mengizinkan semua origin, tapi ini tidak direkomendasikan untuk produksi karena masalah keamanan CORS).

  Juga, perhatikan port yang digunakan backend:

```
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Server berjalan di http://localhost:${PORT}`);
    console.log('Menunggu koneksi WhatsApp...');
});
```

* Secara default, ini adalah 3000. Pastikan port ini terbuka di firewall server Anda (lihat Langkah sebelumnya).

<h2>Konfigurasi Front End</h2>

* Buka file html frontend anda, index.php :

```
  const backendUrl = 'http://192.168.100.107:3000'; // <--- Change it with your domain / IP
```

* Ubah http://192.168.100.107:3000 menjadi URL atau alamat IP publik dari server tempat backend Anda berjalan, diikuti dengan portnya.
* Penting: Jika Anda menggunakan nama domain (misalnya api.example.com), gunakan itu: http://api.example.com:3000.

  <h2> Jalankan Backend Node.js</h2>

* Masuk ke directori
  - cd /path/to/your/server/directory

* Jalankan Backends
  - node server.js

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_7.jpg)
![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_8.jpg)
![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_9.jpg)
 
Untuk penggunaan produksi, sangat disarankan untuk menggunakan manajer proses seperti PM2 agar aplikasi Anda tetap berjalan di latar belakang dan secara otomatis dimulai ulang jika terjadi crash atau restart server:

<h3>Khusus untuk penggunaan script otomatis (menjalankan script dengan pm, jika sudah menjalankan dengan node server.js maka tidak perlu)</h3>

* Install pm2

```
npm install -g pm2
```

* Mulai aplikasi Anda dengan PM2

```
pm2 start server.js --name "whatsapp-checker-backend"
```

* Simpan konfigurasi PM2 agar otomatis dimulai saat server reboot
```
pm2 save
pm2 startup
```
Anda dapat melihat log dengan pm2 logs dan mengelola aplikasi dengan pm2 list, pm2 stop <name>, pm2 restart <name>.

Langkah 5: Sajikan Frontend (dan Konfigurasi Firewall)
Frontend Anda adalah file HTML, CSS, dan JavaScript statis. Anda dapat menyajikannya dengan beberapa cara:

<h2>Menggunakan Server Web (Nginx/Apache - Direkomendasikan)</h2>
Ini adalah cara paling umum dan efisien untuk menyajikan file statis.

Nginx: Instal Nginx, lalu buat konfigurasi server block baru untuk mengarahkan ke direktori frontend Anda.

# Contoh konfigurasi Nginx (biasanya di /etc/nginx/sites-available/your_domain.conf)

```
server {
    listen 80;
    server_name your_frontend_domain_or_ip; # Ganti dengan domain/IP frontend

    root /path/to/your/frontend/directory; # Contoh: /var/www/whatsapp-checker/frontend
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Setelah itu, aktifkan konfigurasi dan muat ulang Nginx.

Apache: Instal Apache dan konfigurasikan VirtualHost untuk mengarahkan ke direktori frontend Anda.

Menggunakan Server Node.js yang Sama (Kurang Direkomendasikan untuk Produksi):
Anda bisa menambahkan Express.js di server.js untuk menyajikan file statis frontend:

```
// Di server.js, setelah app = express();
app.use(express.static(path.join(__dirname, 'nama_folder_frontend_anda')));
// Contoh: app.use(express.static(path.join(__dirname, 'public')));
// Pastikan folder frontend Anda berada di satu level dengan server.js,
// atau sesuaikan path.
```

Jika Anda melakukan ini, pastikan backendUrl di frontend menunjuk ke port yang sama dengan server Node.js (misalnya http://your_server_ip:3000).

Konfigurasi Firewall:
Pastikan port yang digunakan oleh backend Node.js Anda (misalnya 3000) dan port yang digunakan oleh server web Anda (misalnya 80 untuk HTTP, 443 untuk HTTPS) terbuka di firewall server Anda.

Untuk Ubuntu/Debian (UFW):
```
sudo ufw allow 3000/tcp # Untuk port backend
sudo ufw allow 'Nginx HTTP' # Atau 'Apache' jika Anda menggunakannya
sudo ufw enable
```
Untuk CentOS/RHEL (firewalld):
```
sudo firewall-cmd --permanent --add-port=3000/tcp
sudo firewall-cmd --permanent --add-service=http # Atau https
sudo firewall-cmd --reload
```
Pengujian
Setelah semua langkah di atas selesai:

Akses frontend Anda melalui browser web menggunakan alamat IP atau nama domain server Anda (misalnya http://your_frontend_domain_or_ip).

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_10.jpg)
![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_5.jpg)

Periksa konsol browser (F12) untuk melihat apakah ada kesalahan koneksi Socket.IO atau kesalahan lainnya.

Periksa log backend Anda (dengan pm2 logs atau melihat output terminal jika Anda menjalankan node server.js secara langsung) untuk memastikan klien WhatsApp memulai dan menghasilkan QR Code.

Pindai QR Code dengan ponsel Anda.

Coba fitur generator dan pemeriksa nomor.

Dengan mengikuti langkah-langkah ini, Anda seharusnya dapat menginstal dan menjalankan aplikasi Pemeriksa WhatsApp Anda di server Node.js lain.

# Cara penggunaan / How to use
* Masukkan deretan angka di form input nomor seperti gambar di bawah, anda bisa memilih untuk nomor telepon lokal khusus Indonesia atau nomor telepon internasional (pastikan anda sudah menautkan perangkat anda terlebih dahulu)
  
![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_6.jpg)

* Setelah anda memasukkan opsi nomer silahkan tekan generate, maka angka akan di generate secara tersusun. Anda juga bisa mendownload deretan angka sebagai txt.

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_11.jpg)

* Paste nomor di text area untuk di sortir atau diperiksa oleh mesin

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_12.jpg)

* Anda juga bisa memeriksa deretan / database nomor telepon yang anda miliki dengan forman tersusun vertikal ke bawah, alat secara otomatis membacanya sebagai baris baru.

![alt text](https://github.com/donny2806/wa-sortir-generator/blob/main/whatsapp-backend/Screenshot_13.jpg)

Alat ini berguna untuk membantu promosi sebuah situs atau melakukan kampanye di daerah - daerah tertentu. Bisa digunakan sebagai alat strategis untuk menjalankan politik juga. Untuk penggunaan illegal diluar tanggung jawab saya, tool ini diciptakan sebagai edukasi dan alat bantu marketing semata.
