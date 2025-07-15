<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alat WhatsApp: Generator & Pemeriksa Nomor Telepon</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Socket.IO Client CDN -->
    <script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom Tailwind Colors */
        .bg-whatsapp-primary { background-color: #008069; } /* Dark green header */
        .bg-whatsapp-secondary { background-color: #128C7E; } /* Lighter green accent */
        .bg-whatsapp-chat-bg { background-color: #E5DDD5; } /* Chat background */
        .bg-whatsapp-light-bg { background-color: #F0F2F5; } /* Light gray general background */
        .text-whatsapp-dark { color: #333333; }
        .border-whatsapp-border { border-color: #D1D7DB; }

        /* General body and container styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #E0E0E0; /* WhatsApp-like background */
            display: flex;
            justify-content: center; /* Center content horizontally */
            align-items: flex-start; /* Align content to the top */
            min-height: 100vh; /* Full viewport height */
            padding: 1rem; /* Overall padding */
            box-sizing: border-box;
        }

        /* Main wrapper to hold all containers */
        .main-wrapper {
            display: flex;
            flex-direction: column; /* Default to column for small screens */
            gap: 1rem; /* Space between the main columns/containers */
            width: 100%; /* Take full width */
            max-width: 1400px; /* Max width for the entire application on large screens */
            height: calc(100vh - 2rem); /* Fill available height minus body padding */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Subtle overall shadow */
            border-radius: 0.5rem; /* Rounded corners for the whole app */
            overflow: hidden; /* Hide overflow for rounded corners */
            background-color: #ffffff; /* Main app background */
        }

        /* Responsive layout for large screens */
        @media (min-width: 1024px) { /* lg breakpoint */
            .main-wrapper {
                flex-direction: row; /* Change to row layout for large screens */
            }
        }

        /* Styles for individual content containers */
        .container {
            background-color: #ffffff;
            padding: 1.5rem; /* Slightly reduced padding for a tighter look */
            border-radius: 0.5rem; /* Consistent rounded corners */
            box-shadow: none; /* Remove individual container shadows, main-wrapper has one */
            width: 100%; /* Containers take full width of their parent column */
            display: flex; /* Make containers flex for internal layout */
            flex-direction: column; /* Default to column for internal content */
        }

        /* Left column specific styles (Generator + HLR Chart) */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Space between generator and HLR chart */
            width: 100%; /* Full width on small screens */
            background-color: #F0F2F5; /* Light gray background for left sidebar */
            padding: 1rem; /* Padding for the column itself */
            overflow-y: auto; /* Enable scrolling for the left column content */
        }
        @media (min-width: 1024px) { /* lg breakpoint */
            .left-column {
                width: 35%; /* Take 35% width on large screens (like WhatsApp chat list) */
                flex-shrink: 0; /* Prevent shrinking */
                border-right: 1px solid #D1D7DB; /* Separator line */
            }
        }

        /* Right column specific styles (WhatsApp Checker) */
        .right-column {
            display: flex;
            flex-direction: column;
            width: 100%; /* Full width on small screens */
            flex-grow: 1; /* Allow it to grow and take available space */
            background-color: #E5DDD5; /* WhatsApp chat background color */
        }
        @media (min-width: 1024px) { /* lg breakpoint */
            .right-column {
                width: 65%; /* Take 65% width on large screens */
            }
        }

        /* Specific styles for the WhatsApp Checker container to mimic chat window */
        #whatsapp-checker-container {
            padding: 0; /* Remove padding from container itself, content will have it */
            border-radius: 0; /* No individual border-radius for this main panel */
            background-color: transparent; /* Transparent, as right-column sets background */
            box-shadow: none; /* No shadow */
        }

        /* WhatsApp-like Header for Checker */
        .whatsapp-header {
            background-color: #008069; /* Dark green */
            color: white;
            padding: 1rem 1.5rem;
            border-top-left-radius: 0.5rem; /* Rounded only for top-left of the entire app */
            border-top-right-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Space out title and client count */
            flex-shrink: 0; /* Prevent header from shrinking */
        }
        @media (min-width: 1024px) {
            .whatsapp-header {
                border-top-left-radius: 0; /* No border radius on left for right column */
            }
        }

        /* WhatsApp-like Content Area for Checker (scrollable) */
        .whatsapp-content {
            flex-grow: 1; /* Takes all available vertical space */
            padding: 1.5rem; /* Padding for content inside */
            overflow-y: auto; /* Scrollable content */
            background-color: #E5DDD5; /* Chat background */
        }

        /* Input field styles */
        input[type="text"], textarea {
            border: 1px solid #D1D7DB; /* Lighter border */
            border-radius: 0.375rem; /* rounded-md */
            padding: 0.625rem 0.8rem; /* py-2.5 px-3 equivalent */
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
            background-color: #FFFFFF; /* White background */
        }
        input[type="file"] {
            padding: 0.5rem 0.75rem;
            background-color: #FFFFFF;
            border: 1px solid #D1D7DB;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: #128C7E; /* WhatsApp green on focus */
            box-shadow: 0 0 0 2px rgba(18, 140, 126, 0.2); /* Subtle green shadow */
        }
        
        /* Button styles (general for both sections) */
        button {
            background-color: #128C7E; /* WhatsApp green */
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.1s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
        button:hover {
            background-color: #075E54; /* Darker WhatsApp green */
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }
        button:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: translateY(0);
            box-shadow: none;
        }
        /* Specific button colors */
        #downloadBtn, #downloadIntBtn { background-color: #2563eb; } /* Blue for download */
        #downloadBtn:hover, #downloadIntBtn:hover { background-color: #1d4ed8; }
        #clearBtn, #clearIntBtn { background-color: #6b7280; } /* Gray for clear */
        #clearBtn:hover, #clearIntBtn:hover { background-color: #4b5563; }
        #pauseScanButton, #pauseInstantScanButton { background-color: #f59e0b; } /* Amber for pause */
        #pauseScanButton:hover, #pauseInstantScanButton:hover { background-color: #d97706; }
        #resumeScanButton, #resumeInstantScanButton { background-color: #22c55e; } /* Green for resume */
        #resumeScanButton:hover, #resumeInstantScanButton:hover { background-color: #16a34a; }
        #cancelScanButton, #cancelInstantScanButton { background-color: #ef4444; } /* Red for cancel */
        #cancelScanButton:hover, #cancelInstantScanButton:hover { background-color: #dc2626; }
        #logoutWhatsappButton { background-color: #ef4444; } /* Red for logout */
        #logoutWhatsappButton:hover { background-color: #dc2626; }
        #destroySessionButton { background-color: #b91c1c; } /* Darker red for destroy */
        #destroySessionButton:hover { background-color: #991b1b; }
        #clearCacheButton { background-color: #9ca3af; } /* Gray-400 for clear cache */
        #clearCacheButton:hover { background-color: #6b7280; } /* Gray-500 on hover */
        #searchWhatsappButton { background-color: #4CAF50; } /* Green for search */
        #searchWhatsappButton:hover { background-color: #075E54; }
        #startInstantScanButton { background-color: #007bff; } /* Blue for instant scan */
        #startInstantScanButton:hover { background-color: #0056b3; }


        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem; /* Reduced gap */
        }
        .input-grid {
            gap: 0.75rem; /* Reduced gap */
        }
        .error-message {
            color: #ef4444;
            font-size: 0.8rem; /* Smaller font size */
            margin-top: 0.1rem;
        }

        /* Message box styles */
        .message-box, .success-box, .error-box {
            padding: 0.8rem; /* Reduced padding */
            border-radius: 0.375rem; /* rounded-md */
            font-size: 0.9rem;
            margin-bottom: 1rem; /* Reduced margin */
        }
        
        /* Loading overlay and spinner - unchanged */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Province Bar Chart styles */
        #province-bars-container, #country-bars-container { /* Apply to both */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); /* Slightly smaller min-width for more columns */
            gap: 0.75rem; /* Reduced gap */
            padding: 0.75rem; /* Reduced padding */
            background-color: #e2e8f0; /* Light gray background for the bar chart area */
            border-radius: 0.5rem;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Inner shadow for depth */
            max-height: 400px; /* Adjusted height */
            overflow-y: auto; /* Enable vertical scrolling */
        }

        .province-bar, .country-bar { /* Apply to both */
            background-color: #cbd5e1; /* Default light gray for bars */
            color: #4a5568; /* Darker text for default bars */
            padding: 0.6rem 0.8rem; /* Reduced padding */
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
            display: flex;
            justify-content: center; /* Center text horizontally */
            align-items: center; /* Center text vertically */
            text-align: center;
            min-height: 35px; /* Ensure a minimum height for bars */
            font-size: 0.85rem; /* Smaller font size */
        }

        .province-bar.highlighted, .country-bar.highlighted { /* Apply to both */
            background-color: #22c55e; /* Green-500 for highlighted bar */
            color: white;
            font-weight: 600;
        }

        /* Table styling for checker results */
        #results-container table, #instant-results-container table {
            border-collapse: collapse; /* Remove space between borders */
            width: 100%;
        }
        #results-container th, #results-container td, #instant-results-container th, #instant-results-container td {
            border: 1px solid #D1D7DB; /* Light border */
            padding: 0.75rem;
            font-size: 0.875rem;
            text-align: left;
        }
        #results-container th, #instant-results-container th {
            background-color: #F0F2F5; /* Light gray header */
            color: #4A4A4A;
            font-weight: 600;
        }
        #results-container tr:nth-child(even), #instant-results-container tr:nth-child(even) {
            background-color: #F8F8F8; /* Subtle stripe for readability */
        }
        #results-container tr:hover, #instant-results-container tr:hover {
            background-color: #EBF7E9; /* Light green on hover */
        }
        #results-container .overflow-x-auto, #instant-results-container .overflow-x-auto {
            flex-grow: 1; /* Allow table to grow */
            overflow-y: auto; /* Enable vertical scrolling for table body */
        }
        #results-container table tbody, #instant-results-container table tbody {
            display: block;
            max-height: 300px; /* Limit height for scrollable body */
            overflow-y: auto;
        }
        #results-container table thead, #results-container table tbody tr,
        #instant-results-container table thead, #instant-results-container table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed; /* Ensures columns align */
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Left Column: Generator and HLR Chart -->
        <div class="left-column">
            <!-- New: WhatsApp Number Search Form -->
            <div id="whatsapp-search-container" class="container">
                <h2 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Pencarian Info Profil WhatsApp</h2>
                <p class="text-sm text-gray-600 text-center mb-4">
                    Masukkan nomor telepon lengkap dengan kode negara (contoh: +6281234567890).
                </p>

                <div class="input-group mb-4">
                    <label for="searchPhoneNumber" class="text-gray-700 font-medium">Nomor Telepon:</label>
                    <input type="text" id="searchPhoneNumber" class="w-full" placeholder="Contoh: +6281234567890"
                           oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                    <p id="searchError" class="error-message"></p>
                </div>

                <button id="searchWhatsappButton" class="w-full mb-4">Cari Info WhatsApp</button>

                <div id="search-results-display" class="hidden p-4 bg-whatsapp-light-bg rounded-lg border border-whatsapp-border shadow-sm text-center">
                    <img id="searchProfilePic" src="https://placehold.co/100x100/CCCCCC/000000?text=Foto" alt="Foto Profil" class="mx-auto rounded-full w-24 h-24 object-cover mb-3">
                    <p class="text-lg font-semibold text-whatsapp-dark mb-1">Terdaftar: <span id="searchRegisteredDisplay" class="font-bold text-red-500">Tidak</span></p>
                </div>
                <div id="search-message-box" class="hidden mt-4"></div>
            </div>


            <!-- Phone Number Generator Section (Lokal Indonesia) -->
            <div id="generator-container" class="container">
                <h1 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Generator Nomor Telepon (Lokal Indonesia)</h1>

                <div class="input-grid mb-4">
                    <!-- Text Field 1: 6 Characters (allows +) -->
                    <div class="input-group">
                        <label for="field1" class="text-gray-700 font-medium">Awalan (6 Karakter, termasuk + opsional):</label>
                        <input type="text" id="field1" maxlength="6" inputmode="text" pattern="[0-9+]*"
                               class="w-full" placeholder="Contoh: +6281"
                               oninput="validateInput(this, 6, 'field1')">
                        <p id="error1" class="error-message"></p>
                    </div>

                    <!-- Text Field 2: 2 Digits -->
                    <div class="input-group">
                        <label for="field2" class="text-gray-700 font-medium">Kode Area/Operator (2 Digit):</label>
                        <input type="text" id="field2" maxlength="2" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 23"
                               oninput="validateInput(this, 2, 'field2')">
                        <p id="error2" class="error-message"></p>
                    </div>

                    <!-- Text Field 3: Any Digits (Start) -->
                    <div class="input-group">
                        <label for="field3" class="text-gray-700 font-medium">Mulai Dari Angka Akhir:</label>
                        <input type="text" id="field3" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 1000000"
                               oninput="validateInput(this, null, 'field3')">
                        <p id="error3" class="error-message"></p>
                    </div>

                    <!-- Text Field 4: Any Digits (End) -->
                    <div class="input-group">
                        <label for="field4" class="text-gray-700 font-medium">Sampai Dengan Angka Akhir:</label>
                        <input type="text" id="field4" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 1000100"
                               oninput="validateInput(this, null, 'field4')">
                        <p id="error4" class="error-message"></p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mb-4">
                    <button id="generateBtn" class="flex-1">Generate Nomor Telepon</button>
                    <button id="downloadBtn" class="flex-1">Download Output (.txt)</button>
                </div>
                <button id="clearBtn" class="w-full bg-gray-500 hover:bg-gray-600 mb-4">Bersihkan Output</button>

                <div class="input-group">
                    <label for="outputArea" class="text-gray-700 font-medium">Output Nomor Telepon:</label>
                    <textarea id="outputArea" rows="10" class="w-full resize-y" readonly
                                placeholder="Nomor telepon yang dihasilkan akan muncul di sini..."></textarea>
                </div>
            </div>

            <!-- Indonesia Province Bar Chart Section -->
            <div id="indonesia-province-chart-container" class="container">
                <h2 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Perkiraan Lokasi HLR per Provinsi (Simulasi)</h2>
                <p class="text-sm text-gray-600 text-center mb-4">
                    Bar provinsi akan disorot berdasarkan "Kode Area/Operator" yang Anda masukkan.
                    <br>
                    <strong class="text-red-600">Catatan:</strong> Pemetaan ini adalah simulasi dan tidak akurat untuk semua HLR.
                </p>
                <div id="province-bars-container">
                    <!-- Provinces will be dynamically generated here by JavaScript -->
                </div>
            </div>

            <!-- New: International Phone Number Generator Section -->
            <div id="international-generator-container" class="container">
                <h2 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Generator Nomor Telepon Internasional</h2>
                <p class="text-sm text-gray-600 text-center mb-4">
                    Masukkan kode negara (tanpa '+') dan rentang angka akhir.
                </p>

                <div class="input-grid mb-4">
                    <!-- Text Field 5: Country Code -->
                    <div class="input-group">
                        <label for="field5" class="text-gray-700 font-medium">Kode Negara (tanpa '+'):</label>
                        <input type="text" id="field5" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 1 (untuk AS/Kanada), 44 (untuk UK)"
                               oninput="validateInput(this, null, 'field5')">
                        <p id="error5" class="error-message"></p>
                    </div>

                    <!-- Text Field 6: Any Digits (Start) -->
                    <div class="input-group">
                        <label for="field6" class="text-gray-700 font-medium">Mulai Dari Angka Akhir:</label>
                        <input type="text" id="field6" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 100000000"
                               oninput="validateInput(this, null, 'field6')">
                        <p id="error6" class="error-message"></p>
                    </div>

                    <!-- Text Field 7: Any Digits (End) -->
                    <div class="input-group">
                        <label for="field7" class="text-gray-700 font-medium">Sampai Dengan Angka Akhir:</label>
                        <input type="text" id="field7" inputmode="numeric" pattern="[0-9]*"
                               class="w-full" placeholder="Contoh: 100000100"
                               oninput="validateInput(this, null, 'field7')">
                        <p id="error7" class="error-message"></p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mb-4">
                    <button id="generateIntBtn" class="flex-1">Generate Nomor Internasional</button>
                    <button id="downloadIntBtn" class="flex-1">Download Output (.txt)</button>
                </div>
                <button id="clearIntBtn" class="w-full bg-gray-500 hover:bg-gray-600 mb-4">Bersihkan Output</button>

                <div class="input-group">
                    <label for="outputIntArea" class="text-gray-700 font-medium">Output Nomor Telepon Internasional:</label>
                    <textarea id="outputIntArea" rows="10" class="w-full resize-y" readonly
                                placeholder="Nomor telepon internasional yang dihasilkan akan muncul di sini..."></textarea>
                </div>
            </div>

            <!-- New: International Country Code Bar Chart Section -->
            <div id="international-country-chart-container" class="container">
                <h2 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Peta Kode Negara (Simulasi)</h2>
                <p class="text-sm text-gray-600 text-center mb-4">
                    Bar negara akan disorot berdasarkan "Kode Negara" yang Anda masukkan.
                    <br>
                    <strong class="text-red-600">Catatan:</strong> Tidak semua kode negara tercantum.
                </p>
                <div id="country-bars-container">
                    <!-- Countries will be dynamically generated here by JavaScript -->
                </div>
            </div>

        </div>

        <!-- Right Column: WhatsApp Checker -->
        <div class="right-column">
            <!-- WhatsApp Checker Section -->
            <div id="whatsapp-checker-container" class="container h-full">
                <!-- Header (like WhatsApp chat header) -->
                <div class="whatsapp-header">
                    <h1 class="text-2xl font-bold">Pemeriksa Nomor Telepon WhatsApp</h1>
                    <span id="online-clients-count" class="text-sm text-white-700 font-semibold">Online: 0</span>
                </div>

                <!-- Main content area (scrollable if needed) -->
                <div class="whatsapp-content">
                    <!-- New: Instant Scan Container -->
                    <div id="instant-scan-container" class="p-4 bg-whatsapp-light-bg rounded-lg border border-whatsapp-border shadow-sm mb-6">
                        <h2 class="text-2xl font-bold text-center text-whatsapp-dark mb-4">Pemeriksa Nomor Instan</h2>
                        <p class="text-sm text-gray-600 text-center mb-4">
                            Masukkan nomor telepon (satu per baris) untuk pemeriksaan cepat.
                            Pastikan format nomor lengkap dengan kode negara (contoh: +6281234567890).
                        </p>

                        <div class="input-group mb-4">
                            <label for="instantScanNumbersInput" class="text-gray-700 font-medium">Daftar Nomor Telepon:</label>
                            <textarea id="instantScanNumbersInput" rows="5" class="w-full resize-y" placeholder="Contoh:&#10;+6281234567890&#10;+12125550100&#10;08765432100"></textarea>
                        </div>

                        <button id="startInstantScanButton" class="w-full mb-4" disabled>Mulai Pemeriksaan Instan</button>
                        <p id="instantScanButtonHint" class="text-sm text-center text-gray-600 mb-4">
                            <!-- This text will be dynamically updated by JavaScript -->
                            Untuk mengaktifkan pemeriksaan instan, pastikan klien WhatsApp Anda terhubung.
                        </p>
                        <div id="instant-scan-message-box" class="hidden mt-4"></div>

                        <!-- Tombol Jeda/Lanjutkan/Batalkan untuk Pemindaian Instan -->
                        <div class="flex justify-center gap-4 mt-4 flex-wrap">
                            <button type="button" id="pauseInstantScanButton" disabled
                                    class="px-6 py-2">
                                Jeda
                            </button>
                            <button type="button" id="resumeInstantScanButton" disabled
                                    class="px-6 py-2">
                                Lanjutkan
                            </button>
                            <button type="button" id="cancelInstantScanButton" disabled
                                    class="px-6 py-2">
                                Berhenti
                            </button>
                        </div>

                        <!-- New: Textareas for Instant Scan Found/Not Found Numbers (Moved Here) -->
                        <div class="flex flex-col md:flex-row gap-4 mt-4">
                            <div class="flex-1 flex flex-col">
                                <label for="instantNumbersFound" class="text-gray-700 font-medium mb-2 block">Nomor WhatsApp Ditemukan (Instan):</label>
                                <textarea id="instantNumbersFound" rows="5" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-900 flex-grow" readonly placeholder="Nomor yang ditemukan secara instan akan muncul di sini..."></textarea>
                            </div>
                            <div class="flex-1 flex flex-col">
                                <label for="instantNumbersNotFound" class="text-gray-700 font-medium mb-2 block">Nomor WhatsApp Tidak Ditemukan (Instan):</label>
                                <textarea id="instantNumbersNotFound" rows="5" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-900 flex-grow" readonly placeholder="Nomor yang tidak ditemukan secara instan akan muncul di sini..."></textarea>
                            </div>
                        </div>

                        <div id="instant-results-container" class="mt-4 hidden flex-grow">
                            <h3 class="text-xl font-semibold text-whatsapp-dark mb-2">Hasil Pemeriksaan Instan:</h3>
                            <div class="overflow-x-auto rounded-lg shadow-sm flex-grow border border-whatsapp-border">
                                <table class="min-w-full bg-white rounded-lg">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-3 rounded-tl-lg text-xs">No.</th>
                                            <th class="py-2 px-3 text-xs">Nomor Telepon</th>
                                            <th class="py-2 px-3 text-xs">Status WA</th>
                                            <th class="py-2 px-3 rounded-tr-lg text-xs">Foto Profil</th>
                                        </tr>
                                    </thead>
                                    <tbody id="instantResultsTableBody">
                                        <!-- Results will be loaded here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian untuk menampilkan QR Code -->
                    <div id="qr-code-section" class="text-center mb-6 p-4 bg-whatsapp-light-bg rounded-lg border border-whatsapp-border shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-700 mb-3">Status Koneksi WhatsApp: <span id="connection-status" class="font-bold text-red-500">Menghubungkan...</span></h2>
                        <img id="whatsapp-qr-code" src="https://placehold.co/200x200/E0E0E0/000000?text=Memuat+QR" alt="QR Code WhatsApp" class="mx-auto my-4 rounded-lg shadow-md" onerror="console.error('Gagal memuat gambar QR dari:', this.src); this.src='https://placehold.co/200x200/FF0000/FFFFFF?text=Gagal+Memuat+QR'; this.alt='Gagal memuat QR Code';">
                        <p id="qr-instruction" class="text-gray-600">Buka WhatsApp di ponsel Anda, buka Pengaturan > Perangkat Tertaut > Tautkan Perangkat, lalu pindai QR Code ini.</p>
                        <!-- Existing Logout Button -->
                        <button type="button" id="logoutWhatsappButton" disabled
                                class="w-full mt-4 bg-red-500 hover:bg-red-600">
                            Logout Sesi WhatsApp
                        </button>
                        <!-- Existing Destroy Session Button -->
                        <button type="button" id="destroySessionButton" disabled
                                class="w-full mt-2">
                            Hancurkan Sesi & Bersihkan Cache
                        </button>
                        <!-- New Clear Cache Button -->
                        <button type="button" id="clearCacheButton"
                                class="w-full mt-2">
                            Bersihkan Cache
                        </button>
                    </div>

                    <form id="uploadForm" class="space-y-4 mb-6">
                        <div class="flex flex-col">
                            <label for="phone_file" class="text-gray-700 font-medium mb-2">Pilih File Teks (.txt):</label>
                            <input type="file" name="phone_file" id="phone_file" accept=".txt" required
                                   class="block w-full text-sm text-gray-900">
                        </div>
                        <button type="submit" id="submitButton" disabled
                                class="w-full">
                            Periksa Nomor Telepon
                        </button>
                    </form>

                    <!-- Kotak Teks untuk Nomor Ada/Tidak Ada -->
                    <div class="flex flex-col md:flex-row gap-4 mt-8 flex-grow">
                        <div class="flex-1 flex flex-col">
                            <label for="numbers-found" class="text-gray-700 font-medium mb-2 block">Nomor WhatsApp Ditemukan:</label>
                            <textarea id="numbers-found" rows="10" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-900 flex-grow" readonly placeholder="Nomor yang ditemukan akan muncul di sini..."></textarea>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label for="numbers-not-found" class="text-gray-700 font-medium mb-2 block">Nomor WhatsApp Tidak Ditemukan:</label>
                            <textarea id="numbers-not-found" rows="10" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-900 flex-grow" readonly placeholder="Nomor yang tidak ditemukan akan muncul di sini..."></textarea>
                        </div>
                    </div>

                    <!-- Progress Pengunggahan File -->
                    <div id="upload-progress-container" class="hidden mt-8 p-4 bg-whatsapp-light-bg rounded-lg border border-whatsapp-border shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">Proses Pengunggahan File:</h2>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div id="upload-progress-bar" class="bg-whatsapp-secondary h-4 rounded-full" style="width: 0%;"></div>
                        </div>
                        <p id="upload-progress-text" class="text-gray-700 text-sm mt-2">Menunggu pengunggahan...</p>
                    </div>

                    <!-- Progress Pemindaian Nomor -->
                    <div id="scan-progress-container" class="hidden mt-4 p-4 bg-whatsapp-light-bg rounded-lg border border-whatsapp-border shadow-sm">
                        <h2 class="text-xl font-semibold text-whatsapp-dark mb-3">Proses Pemindaian Nomor:</h2>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div id="scan-progress-bar" class="bg-whatsapp-secondary h-4 rounded-full" style="width: 0%;"></div>
                        </div>
                        <p id="scan-progress-text" class="text-whatsapp-dark text-sm mt-2">Menunggu pemindaian dimulai...</p>
                        <!-- Tombol Jeda/Lanjutkan/Batalkan -->
                        <div class="flex justify-center gap-4 mt-4 flex-wrap">
                            <button type="button" id="pauseScanButton" disabled
                                    class="px-6 py-2">
                                Jeda Pemindaian
                            </button>
                            <button type="button" id="resumeScanButton" disabled
                                    class="px-6 py-2">
                                Lanjutkan Pemindaian
                            </button>
                            <button type="button" id="cancelScanButton" disabled
                                    class="px-6 py-2">
                                Batalkan Pemindaian
                            </button>
                        </div>
                    </div>

                    <div id="results-container" class="mt-8 hidden flex-grow">
                        <h2 class="text-2xl font-bold text-whatsapp-dark mb-4">Hasil Pemeriksaan:</h2>
                        <div class="overflow-x-auto rounded-lg shadow-sm flex-grow border border-whatsapp-border">
                            <table class="min-w-full bg-white rounded-lg">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 rounded-tl-lg">No.</th>
                                        <th class="py-3 px-4">Nomor Telepon Asli</th>
                                        <th class="py-3 px-4">Status WhatsApp</th>
                                        <th class="py-3 px-4">Info Debug API</th>
                                        <th class="py-3 px-4 rounded-tr-lg">Foto Profil</th>
                                    </tr>
                                </thead>
                                <tbody id="results-table-body">
                                    <!-- Hasil akan dimuat di sini oleh JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Message Box for general messages -->
                    <div id="general-message-box" class="hidden mt-8"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Generator Section Variables (Local) ---
        const field1 = document.getElementById('field1');
        const field2 = document.getElementById('field2');
        const field3 = document.getElementById('field3');
        const field4 = document.getElementById('field4');
        const generateBtn = document.getElementById('generateBtn');
        const downloadBtn = document.getElementById('downloadBtn');
        const clearBtn = document.getElementById('clearBtn');
        const outputArea = document.getElementById('outputArea');

        // --- Province Bar Chart Section Variables ---
        const provinceBarsContainer = document.getElementById('province-bars-container');

        // List of all 38 Indonesian provinces with their IDs (kebab-case)
        const provinces = [
            { name: "Aceh", id: "aceh" },
            { name: "Sumatera Utara", id: "sumatera-utara" },
            { name: "Sumatera Barat", id: "sumatera-barat" },
            { name: "Riau", id: "riau" },
            { name: "Kepulauan Riau", id: "kepulauan-riau" },
            { name: "Jambi", id: "jambi" },
            { name: "Sumatera Selatan", id: "sumatera-selatan" },
            { name: "Bengkulu", id: "bengkulu" },
            { name: "Lampung", id: "lampung" },
            { name: "Kepulauan Bangka Belitung", id: "kepulauan-bangka-belitung" },
            { name: "Banten", id: "banten" },
            { name: "Jawa Barat", id: "jawa-barat" },
            { name: "DKI Jakarta", id: "dki-jakarta" },
            { name: "Jawa Tengah", id: "jawa-tengah" },
            { name: "DI Yogyakarta", id: "di-yogyakarta" },
            { name: "Jawa Timur", id: "jawa-timur" },
            { name: "Bali", id: "bali" },
            { name: "Nusa Tenggara Barat", id: "nusa-tenggara-barat" },
            { name: "Nusa Tenggara Timur", id: "nusa-tenggara-timur" },
            { name: "Kalimantan Barat", id: "kalimantan-barat" },
            { name: "Kalimantan Tengah", id: "kalimantan-tengah" },
            { name: "Kalimantan Selatan", id: "kalimantan-selatan" },
            { name: "Kalimantan Timur", id: "kalimantan-timur" },
            { name: "Kalimantan Utara", id: "kalimantan-utara" },
            { name: "Sulawesi Utara", id: "sulawesi-utara" },
            { name: "Gorontalo", id: "gorontalo" },
            { name: "Sulawesi Tengah", id: "sulawesi-tengah" },
            { name: "Sulawesi Barat", id: "sulawesi-barat" },
            { name: "Sulawesi Selatan", id: "sulawesi-selatan" },
            { name: "Sulawesi Tenggara", id: "sulawesi-tenggara" },
            { name: "Maluku Utara", id: "maluku-utara" },
            { name: "Maluku", id: "maluku" },
            { name: "Papua Barat Daya", id: "papua-barat-daya" },
            { name: "Papua Barat", id: "papua-barat" },
            { name: "Papua Tengah", id: "papua-tengah" },
            { name: "Papua Pegunungan", id: "papua-pegunungan" },
            { name: "Papua Selatan", id: "papua-selatan" },
            { name: "Papua", id: "papua" },
            { name: "Lainnya", id: "lainnya" } // Fallback for unmapped HLRs
        ];

        // Updated HLR (2-digit from field2) to Province ID mapping based on Telkomsel HLR areas
        const hlrProvinceMap = {
            // HLR area Jabodetabek (10-14)
            "10": "dki-jakarta",
            "11": "dki-jakarta",
            "12": "dki-jakarta",
            "13": "dki-jakarta",
            "14": "dki-jakarta",

            // HLR area Jawa Barat (15-32)
            "15": "jawa-barat",
            "16": "jawa-barat",
            "17": "jawa-barat",
            "18": "jawa-barat",
            "19": "jawa-barat",
            "20": "jawa-barat",
            "21": "jawa-barat",
            "22": "jawa-barat",
            "23": "jawa-barat",
            "24": "jawa-barat",
            "25": "jawa-barat",
            "26": "jawa-barat",
            "27": "jawa-barat",
            "28": "jawa-barat",
            "29": "jawa-barat",
            "30": "jawa-barat",
            "31": "jawa-barat",
            "32": "jawa-barat",

            // HLR area Jawa Tengah (33-38)
            "33": "jawa-tengah",
            "34": "jawa-tengah",
            "35": "jawa-tengah",
            "36": "jawa-tengah",
            "37": "jawa-tengah",
            "38": "jawa-tengah",

            // HLR area Jawa Timur (39-43)
            "39": "jawa-timur",
            "40": "jawa-timur",
            "41": "jawa-timur",
            "42": "jawa-timur",
            "43": "jawa-timur",

            // HLR area Bali (44-47)
            "44": "bali",
            "45": "bali",
            "46": "bali",
            "47": "bali",

            // HLR area Kalimantan (48-59) - Using Kalimantan Barat as representative
            "48": "kalimantan-barat",
            "49": "kalimantan-barat",
            "50": "kalimantan-barat",
            "51": "kalimantan-barat",
            "52": "kalimantan-barat",
            "53": "kalimantan-barat",
            "54": "kalimantan-barat",
            "55": "kalimantan-barat",
            "56": "kalimantan-barat",
            "57": "kalimantan-barat",
            "58": "kalimantan-barat",
            "59": "kalimantan-barat",

            // HLR area Sumatera bagian utara (60-68) - Using Sumatera Utara as representative
            "60": "sumatera-utara",
            "61": "sumatera-utara",
            "62": "sumatera-utara",
            "63": "sumatera-utara",
            "64": "sumatera-utara",
            "65": "sumatera-utara",
            "66": "sumatera-utara",
            "67": "sumatera-utara",
            "68": "sumatera-utara",

            // HLR area Sumatera bagian tengah (69-74) - Using Riau as representative
            "69": "riau",
            "70": "riau",
            "71": "riau",
            "72": "riau",
            "73": "riau",
            "74": "riau",

            // HLR area Sumatera bagian selatan (75-86) - Using Sumatera Selatan as representative
            "75": "sumatera-selatan",
            "76": "sumatera-selatan",
            "77": "sumatera-selatan",
            "78": "sumatera-selatan",
            "79": "sumatera-selatan",
            "80": "sumatera-selatan",
            "81": "sumatera-selatan",
            "82": "sumatera-selatan",
            "83": "sumatera-selatan",
            "84": "sumatera-selatan",
            "85": "sumatera-selatan",
            "86": "sumatera-selatan",

            // HLR area Sulawesi (87-96) - Using Sulawesi Selatan as representative
            "87": "sulawesi-selatan",
            "88": "sulawesi-selatan",
            "89": "sulawesi-selatan",
            "90": "sulawesi-selatan",
            "91": "sulawesi-selatan",
            "92": "sulawesi-selatan",
            "93": "sulawesi-selatan",
            "94": "sulawesi-selatan",
            "95": "sulawesi-selatan",
            "96": "sulawesi-selatan",

            // HLR area Papua dan Maluku (97-99) - Using Papua as representative
            "97": "papua",
            "98": "papua",
            "99": "papua",

            // Default fallback if no specific 2-digit HLR match
            "00": "lainnya" // This will catch any unmapped 2-digit HLRs
        };

        // Function to dynamically generate province bars
        function generateProvinceBars() {
            provinceBarsContainer.innerHTML = ''; // Clear existing bars
            provinces.forEach(province => {
                const bar = document.createElement('div');
                bar.id = `province-${province.id}`;
                bar.className = 'province-bar'; // Apply base class
                bar.textContent = province.name;
                provinceBarsContainer.appendChild(bar);
            });
        }

        // Function to highlight a province bar
        function highlightProvinceBar(provinceId) {
            // Reset all bars to default color first
            const allBars = document.querySelectorAll('.province-bar');
            allBars.forEach(bar => {
                bar.classList.remove('highlighted');
            });

            // Highlight the specified province bar
            const targetBar = document.getElementById(`province-${provinceId}`);
            if (targetBar) {
                targetBar.classList.add('highlighted');
                // Optional: Scroll to the highlighted bar if it's out of view
                targetBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                // If no specific province found, highlight the "Lainnya" bar
                const otherBar = document.getElementById('province-lainnya');
                if (otherBar) {
                    otherBar.classList.add('highlighted');
                    otherBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }

        // --- International Generator Section Variables ---
        const field5 = document.getElementById('field5');
        const field6 = document.getElementById('field6');
        const field7 = document.getElementById('field7');
        const generateIntBtn = document.getElementById('generateIntBtn');
        const downloadIntBtn = document.getElementById('downloadIntBtn');
        const clearIntBtn = document.getElementById('clearIntBtn');
        const outputIntArea = document.getElementById('outputIntArea');
        const countryBarsContainer = document.getElementById('country-bars-container');

        // Comprehensive list of country codes (dialing codes)
        const countryCodes = [
            { name: "Amerika Serikat / Kanada", id: "us-ca", code: "1" },
            { name: "Rusia", id: "ru", code: "7" },
            { name: "Mesir", id: "eg", code: "20" },
            { name: "Afrika Selatan", id: "za", code: "27" },
            { name: "Yunani", id: "gr", code: "30" },
            { name: "Belanda", id: "nl", code: "31" },
            { name: "Belgia", id: "be", code: "32" },
            { name: "Prancis", id: "fr", code: "33" },
            { name: "Spanyol", id: "es", code: "34" },
            { name: "Hungaria", id: "hu", code: "36" },
            { name: "Italia", id: "it", code: "39" },
            { name: "Rumania", id: "ro", code: "40" },
            { name: "Swiss", id: "ch", code: "41" },
            { name: "Austria", id: "at", code: "43" },
            { name: "Britania Raya", id: "uk", code: "44" },
            { name: "Denmark", id: "dk", code: "45" },
            { name: "Swedia", id: "se", code: "46" },
            { name: "Norwegia", id: "no", code: "47" },
            { name: "Polandia", id: "pl", code: "48" },
            { name: "Jerman", id: "de", code: "49" },
            { name: "Peru", id: "pe", code: "51" },
            { name: "Meksiko", id: "mx", code: "52" },
            { name: "Kuba", id: "cu", code: "53" },
            { name: "Argentina", id: "ar", code: "54" },
            { name: "Brasil", id: "br", code: "55" },
            { name: "Chili", id: "cl", code: "56" },
            { name: "Kolombia", id: "co", code: "57" },
            { name: "Venezuela", id: "ve", code: "58" },
            { name: "Malaysia", id: "my", code: "60" },
            { name: "Australia", id: "au", code: "61" },
            { name: "Indonesia", id: "id", code: "62" },
            { name: "Filipina", id: "ph", code: "63" },
            { name: "Selandia Baru", id: "nz", code: "64" },
            { name: "Singapura", id: "sg", code: "65" },
            { name: "Thailand", id: "th", code: "66" },
            { name: "Jepang", id: "jp", code: "81" },
            { name: "Korea Selatan", id: "kr", code: "82" },
            { name: "Vietnam", id: "vn", code: "84" },
            { name: "Tiongkok", id: "cn", code: "86" },
            { name: "Turki", id: "tr", code: "90" },
            { name: "India", id: "in", "code": "91" },
            { name: "Pakistan", id: "pk", code: "92" },
            { name: "Afghanistan", id: "af", code: "93" },
            { name: "Sri Lanka", id: "lk", code: "94" },
            { name: "Myanmar", id: "mm", code: "95" },
            { name: "Iran", id: "ir", code: "98" },
            { name: "Maroko", id: "ma", code: "212" },
            { name: "Aljazair", id: "dz", code: "213" },
            { name: "Tunisia", id: "tn", code: "216" },
            { name: "Libya", id: "ly", code: "218" },
            { name: "Gambia", id: "gm", code: "220" },
            { name: "Senegal", id: "sn", code: "221" },
            { name: "Mauritania", id: "mr", code: "222" },
            { name: "Mali", id: "ml", code: "223" },
            { name: "Niger", id: "ne", code: "227" },
            { name: "Nigeria", id: "ng", code: "234" },
            { name: "Kamerun", id: "cm", code: "237" },
            { name: "Angola", id: "ao", code: "244" },
            { name: "Seychelles", id: "sc", code: "248" },
            { name: "Sudan", id: "sd", code: "249" },
            { name: "Rwanda", id: "rw", code: "250" },
            { name: "Ethiopia", id: "et", code: "251" },
            { name: "Somalia", id: "so", code: "252" },
            { name: "Kenya", id: "ke", code: "254" },
            { name: "Tanzania", id: "tz", code: "255" },
            { name: "Uganda", id: "ug", code: "256" },
            { name: "Burundi", id: "bi", code: "257" },
            { name: "Mozambik", id: "mz", code: "258" },
            { name: "Zambia", id: "zm", code: "260" },
            { name: "Madagaskar", id: "mg", code: "261" },
            { name: "Zimbabwe", id: "zw", code: "263" },
            { name: "Namibia", id: "na", code: "264" },
            { name: "Malawi", id: "mw", code: "265" },
            { name: "Lesotho", id: "ls", code: "266" },
            { name: "Botswana", id: "bw", code: "267" },
            { name: "Swaziland", id: "sz", code: "268" },
            { name: "Komoro", id: "km", code: "269" },
            { name: "Eritrea", id: "er", code: "291" },
            { name: "Aruba", id: "aw", code: "297" },
            { name: "Kepulauan Faroe", id: "fo", code: "298" },
            { name: "Greenland", id: "gl", code: "299" },
            { name: "Gibraltar", id: "gi", code: "350" },
            { name: "Portugal", id: "pt", code: "351" },
            { name: "Luksemburg", id: "lu", code: "352" },
            { name: "Irlandia", id: "ie", code: "353" },
            { name: "Islandia", id: "is", code: "354" },
            { name: "Albania", id: "al", code: "355" },
            { name: "Malta", id: "mt", code: "356" },
            { name: "Siprus", id: "cy", code: "357" },
            { name: "Finlandia", id: "fi", code: "358" },
            { name: "Bulgaria", id: "bg", code: "359" },
            { name: "Lituania", id: "lt", code: "370" },
            { name: "Latvia", id: "lv", code: "371" },
            { name: "Estonia", id: "ee", code: "372" },
            { name: "Moldova", id: "md", code: "373" },
            { name: "Armenia", id: "am", code: "374" },
            { name: "Belarus", id: "by", code: "375" },
            { name: "Andorra", id: "ad", code: "376" },
            { name: "Monako", id: "mc", code: "377" },
            { name: "San Marino", id: "sm", code: "378" },
            { name: "Ukraina", id: "ua", code: "380" },
            { name: "Serbia", id: "rs", code: "381" },
            { name: "Montenegro", id: "me", code: "382" },
            { name: "Kroasia", id: "hr", code: "385" },
            { name: "Slovenia", id: "si", code: "386" },
            { name: "Bosnia dan Herzegovina", id: "ba", code: "387" },
            { name: "Makedonia Utara", id: "mk", code: "389" },
            { name: "Republik Ceko", id: "cz", code: "420" },
            { name: "Slovakia", id: "sk", code: "421" },
            { name: "Liechtenstein", id: "li", code: "423" },
            { name: "Korea Utara", id: "kp", code: "850" },
            { name: "Hong Kong", id: "hk", code: "852" },
            { name: "Makau", id: "mo", code: "853" },
            { name: "Kamboja", id: "kh", code: "855" },
            { name: "Laos", id: "la", code: "856" },
            { name: "Bangladesh", id: "bd", code: "880" },
            { name: "Taiwan", id: "tw", code: "886" },
            { name: "Maladewa", id: "mv", code: "960" },
            { name: "Lebanon", id: "lb", code: "961" },
            { name: "Yordania", id: "jo", code: "962" },
            { name: "Suriah", id: "sy", code: "963" },
            { name: "Irak", id: "iq", code: "964" },
            { name: "Kuwait", id: "kw", code: "965" },
            { name: "Arab Saudi", id: "sa", code: "966" },
            { name: "Yaman", id: "ye", code: "967" },
            { name: "Oman", id: "om", code: "968" },
            { name: "Palestina", id: "ps", code: "970" },
            { name: "Uni Emirat Arab", id: "ae", code: "971" },
            { name: "Israel", id: "il", code: "972" },
            { name: "Bahrain", id: "bh", code: "973" },
            { name: "Qatar", id: "qa", code: "974" },
            { name: "Bhutan", id: "bt", code: "975" },
            { name: "Mongolia", id: "mn", code: "976" },
            { name: "Nepal", id: "np", code: "977" },
            { name: "Timor Leste", id: "tl", code: "670" },
            { name: "Brunei", id: "bn", code: "673" },
            { name: "Nauru", id: "nr", code: "674" },
            { name: "Papua Nugini", id: "pg", code: "675" },
            { name: "Tonga", id: "to", code: "676" },
            { name: "Tuvalu", id: "tv", code: "688" },
            { name: "Samoa", id: "ws", code: "685" },
            { name: "Fiji", id: "fj", code: "679" },
            { name: "Vanuatu", id: "vu", code: "678" },
            { name: "Solomon Islands", id: "sb", code: "677" },
            { name: "Kiribati", id: "ki", code: "686" },
            { name: "Marshall Islands", id: "mh", code: "692" },
            { name: "Micronesia", id: "fm", code: "691" },
            { name: "Palau", id: "pw", code: "680" },
            { name: "Lainnya", id: "lainnya-int", code: "00" } // Fallback for unmapped country codes
        ];

        // Function to dynamically generate country bars
        function generateCountryBars() {
            countryBarsContainer.innerHTML = ''; // Clear existing bars
            countryCodes.forEach(country => {
                const bar = document.createElement('div');
                bar.id = `country-${country.id}`;
                bar.className = 'country-bar'; // Apply base class
                bar.textContent = `${country.name} (+${country.code})`;
                countryBarsContainer.appendChild(bar);
            });
        }

        // Function to highlight a country bar
        function highlightCountryBar(countryCode) {
            // Reset all bars to default color first
            const allBars = document.querySelectorAll('.country-bar');
            allBars.forEach(bar => {
                bar.classList.remove('highlighted');
            });

            // Find the country ID based on the country code
            const foundCountry = countryCodes.find(c => c.code === countryCode);
            const targetCountryId = foundCountry ? foundCountry.id : 'lainnya-int';

            // Highlight the specified country bar
            const targetBar = document.getElementById(`country-${targetCountryId}`);
            if (targetBar) {
                targetBar.classList.add('highlighted');
                // Optional: Scroll to the highlighted bar if it's out of view
                targetBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                // Fallback to "Lainnya" if no specific country found
                const otherBar = document.getElementById('country-lainnya-int');
                if (otherBar) {
                    otherBar.classList.add('highlighted');
                    otherBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }


        // --- Checker Section Variables ---
        const backendUrl = 'http://192.168.100.107:3000';
        const qrCodeImg = document.getElementById('whatsapp-qr-code');
        const connectionStatusSpan = document.getElementById('connection-status');
        const qrInstructionP = document.getElementById('qr-instruction');
        const uploadForm = document.getElementById('uploadForm');
        const phoneFileInput = document.getElementById('phone_file');
        const resultsContainer = document.getElementById('results-container');
        const resultsTableBody = document.getElementById('results-table-body');
        const submitButton = document.getElementById('submitButton');
        const generalMessageBox = document.getElementById('general-message-box');
        const logoutWhatsappButton = document.getElementById('logoutWhatsappButton');
        const destroySessionButton = document.getElementById('destroySessionButton');
        const clearCacheButton = document.getElementById('clearCacheButton');
        const onlineClientsCountSpan = document.getElementById('online-clients-count'); // Get the span for client count

        const uploadProgressContainer = document.getElementById('upload-progress-container');
        const uploadProgressBar = document.getElementById('upload-progress-bar');
        const uploadProgressText = document.getElementById('upload-progress-text');

        const scanProgressContainer = document.getElementById('scan-progress-container');
        const scanProgressBar = document.getElementById('scan-progress-bar');
        const scanProgressText = document.getElementById('scan-progress-text');
        const pauseScanButton = document.getElementById('pauseScanButton');
        const resumeScanButton = document.getElementById('resumeScanButton');
        const cancelScanButton = document.getElementById('cancelScanButton');

        const numbersFoundTextArea = document.getElementById('numbers-found');
        const numbersNotFoundTextArea = document.getElementById('numbers-not-found');

        // New: WhatsApp Search Elements
        const searchPhoneNumberInput = document.getElementById('searchPhoneNumber');
        const searchWhatsappButton = document.getElementById('searchWhatsappButton');
        const searchResultsDisplay = document.getElementById('search-results-display');
        const searchProfilePic = document.getElementById('searchProfilePic');
        const searchRegisteredDisplay = document.getElementById('searchRegisteredDisplay');
        const searchMessageBox = document.getElementById('search-message-box');
        const searchError = document.getElementById('searchError');

        // New: Instant Scan Elements
        const instantScanNumbersInput = document.getElementById('instantScanNumbersInput');
        const startInstantScanButton = document.getElementById('startInstantScanButton');
        const instantResultsTableBody = document.getElementById('instantResultsTableBody');
        const instantScanMessageBox = document.getElementById('instant-scan-message-box');
        const instantResultsContainer = document.getElementById('instant-results-container');
        // New: Textareas for instant scan found/not found numbers
        const instantNumbersFound = document.getElementById('instantNumbersFound');
        const instantNumbersNotFound = document.getElementById('instantNumbersNotFound');
        const instantScanButtonHint = document.getElementById('instantScanButtonHint');
        // New: Instant Scan Control Buttons
        const pauseInstantScanButton = document.getElementById('pauseInstantScanButton');
        const resumeInstantScanButton = document.getElementById('resumeInstantScanButton');
        const cancelInstantScanButton = document.getElementById('cancelInstantScanButton');


        // Inisialisasi koneksi Socket.IO
        const socket = io(backendUrl);

        // State untuk melacak apakah WhatsApp siap dan Socket.IO memiliki ID
        let isWhatsAppReady = false;
        let isSocketConnected = false;
        let currentScanCounter = 0; // For mass scan
        let instantScanCounter = 0; // For instant scan
        let isScanningPaused = false;
        let isScanActive = false; // Flag untuk melacak apakah pemindaian sedang aktif (berjalan atau dijeda)
        let activeScanType = null; // 'mass' or 'instant'

        // Variabel untuk mengelola timeout refresh QR
        let qrDisplayTimeoutId = null; 

        // Fungsi untuk memperbarui status tombol submit dan pause/resume/cancel
        function updateButtonStates() {
            const canStartNewScan = isWhatsAppReady && isSocketConnected && !isScanActive;

            submitButton.disabled = !canStartNewScan;
            startInstantScanButton.disabled = !canStartNewScan;

            logoutWhatsappButton.disabled = !isWhatsAppReady;
            destroySessionButton.disabled = !isWhatsAppReady;
            clearCacheButton.disabled = !isSocketConnected; // Clear cache button only needs socket connection
            searchWhatsappButton.disabled = !isWhatsAppReady;

            // Update hint for instant scan button
            instantScanButtonHint.classList.remove('hidden', 'text-green-600', 'text-red-500', 'text-orange-500'); // Reset classes
            if (!isWhatsAppReady) {
                instantScanButtonHint.classList.remove('hidden');
                instantScanButtonHint.classList.add('text-red-500'); // Make it red if not ready
                instantScanButtonHint.textContent = 'Peringatan: Klien WhatsApp belum terhubung. Pindai QR Code di atas untuk mengaktifkan fitur ini.';
            } else if (!isSocketConnected) {
                instantScanButtonHint.classList.remove('hidden');
                instantScanButtonHint.classList.add('text-orange-500'); // Orange if socket not connected
                instantScanButtonHint.textContent = 'Peringatan: Menunggu koneksi ke server backend. Pastikan backend Node.js berjalan.';
            } else if (isScanActive) {
                instantScanButtonHint.classList.remove('hidden');
                instantScanButtonHint.classList.add('text-orange-500'); // Orange if another scan is active
                instantScanButtonHint.textContent = 'Peringatan: Pemindaian lain sedang berjalan. Harap tunggu atau batalkan pemindaian yang sedang berlangsung.';
            } else {
                instantScanButtonHint.classList.add('hidden'); // Hide if everything is ready
            }

            console.log('--- Memperbarui Status Tombol Pemeriksa ---');
            console.log('isWhatsAppReady:', isWhatsAppReady);
            console.log('isSocketConnected:', isSocketConnected);
            console.log('isScanActive (flag baru):', isScanActive);
            console.log('isScanningPaused:', isScanningPaused);
            console.log('activeScanType:', activeScanType);

            // Logic for mass scan buttons
            if (isScanActive && activeScanType === 'mass') {
                cancelScanButton.disabled = false;
                if (!isScanningPaused) {
                    pauseScanButton.disabled = false;
                    resumeScanButton.disabled = true;
                    console.log('Tombol Pemeriksa Massal: Jeda AKTIF, Lanjutkan NONAKTIF, Batalkan AKTIF (Pemindaian Berjalan)');
                } else {
                    pauseScanButton.disabled = true;
                    resumeScanButton.disabled = false;
                    console.log('Tombol Pemeriksa Massal: Jeda NONAKTIF, Lanjutkan AKTIF, Batalkan AKTIF (Pemindaian Dijeda)');
                }
            } else {
                pauseScanButton.disabled = true;
                resumeScanButton.disabled = true;
                cancelScanButton.disabled = true;
                console.log('Tombol Pemeriksa Massal: Semua NONAKTIF (Tidak ada pemindaian massal aktif)');
            }

            // Logic for instant scan buttons
            if (isScanActive && activeScanType === 'instant') {
                cancelInstantScanButton.disabled = false;
                if (!isScanningPaused) {
                    pauseInstantScanButton.disabled = false;
                    resumeInstantScanButton.disabled = true;
                    console.log('Tombol Pemeriksa Instan: Jeda AKTIF, Lanjutkan NONAKTIF, Batalkan AKTIF (Pemindaian Berjalan)');
                } else {
                    pauseInstantScanButton.disabled = true;
                    resumeInstantScanButton.disabled = false;
                    console.log('Tombol Pemeriksa Instan: Jeda NONAKTIF, Lanjutkan AKTIF, Batalkan AKTIF (Pemindaian Dijeda)');
                }
            } else {
                pauseInstantScanButton.disabled = true;
                resumeInstantScanButton.disabled = true;
                cancelInstantScanButton.disabled = true;
                console.log('Tombol Pemeriksa Instan: Semua NONAKTIF (Tidak ada pemindaian instan aktif)');
            }

            console.log('Status tombol akhir:');
            console.log('  submitButton.disabled:', submitButton.disabled);
            console.log('  startInstantScanButton.disabled:', startInstantScanButton.disabled);
            console.log('  pauseScanButton.disabled:', pauseScanButton.disabled);
            console.log('  resumeScanButton.disabled:', resumeScanButton.disabled);
            console.log('  cancelScanButton.disabled:', cancelScanButton.disabled);
            console.log('  pauseInstantScanButton.disabled:', pauseInstantScanButton.disabled);
            console.log('  resumeInstantScanButton.disabled:', resumeInstantScanButton.disabled);
            console.log('  cancelInstantScanButton.disabled:', cancelInstantScanButton.disabled);
            console.log('------------------------------------');
        }

        // Fungsi untuk menampilkan pesan di message box umum
        function showMessage(message, type = 'message-box') {
            generalMessageBox.className = `mt-8 ${type}`;
            generalMessageBox.innerHTML = message;
            generalMessageBox.classList.remove('hidden');
        }
        // Fungsi untuk menampilkan pesan di message box pencarian
        function showSearchMessage(message, type = 'message-box') {
            searchMessageBox.className = `mt-4 ${type}`;
            searchMessageBox.innerHTML = message;
            searchMessageBox.classList.remove('hidden');
        }
        // Fungsi untuk menampilkan pesan di message box instan
        function showInstantScanMessage(message, type = 'message-box') {
            instantScanMessageBox.className = `mt-4 ${type}`;
            instantScanMessageBox.innerHTML = message;
            instantScanMessageBox.classList.remove('hidden');
        }

        // Fungsi untuk menyembunyikan message box umum
        function hideMessage() {
            generalMessageBox.classList.add('hidden');
        }
        // Fungsi untuk menyembunyikan message box pencarian
        function hideSearchMessage() {
            searchMessageBox.classList.add('hidden');
        }
        // Fungsi untuk menyembunyikan message box instan
        function hideInstantScanMessage() {
            instantScanMessageBox.classList.add('hidden');
        }


        // --- Socket.IO Event Listeners (for WhatsApp Checker) ---
        socket.on('connect', () => {
            console.log('Socket.IO terhubung. ID:', socket.id);
            isSocketConnected = true;
            updateButtonStates();
            // Permintaan status QR awal setelah terhubung
            socket.emit('request_new_qr'); 
        });

        socket.on('disconnect', () => {
            console.log('Socket.IO terputus.');
            isSocketConnected = false;
            isWhatsAppReady = false; // WhatsApp juga terputus jika soket terputus
            isScanActive = false; // Pemindaian tidak lagi aktif jika soket terputus
            activeScanType = null;
            console.log('isScanActive set to FALSE by socket disconnect');
            updateButtonStates();
            showMessage('Koneksi terputus. Harap muat ulang halaman atau periksa backend.', 'error-box');
            if (qrDisplayTimeoutId) {
                clearTimeout(qrDisplayTimeoutId);
                qrDisplayTimeoutId = null;
            }
            // Tidak perlu request QR baru di sini, karena akan dipicu oleh connect ulang
        });

        socket.on('qr_code', (data) => {
            if (qrDisplayTimeoutId) {
                clearTimeout(qrDisplayTimeoutId);
                qrDisplayTimeoutId = null;
            }
            if (data.qrCodeUrl) {
                qrCodeImg.src = data.qrCodeUrl;
                console.log('URL Kode QR diterima via Socket.IO:', data.qrCodeUrl);
                connectionStatusSpan.textContent = 'Memindai QR...';
                connectionStatusSpan.classList.remove('text-green-500', 'text-red-500');
                connectionStatusSpan.classList.add('text-orange-500');
                qrInstructionP.classList.remove('hidden');
                isWhatsAppReady = false; // Reset status WhatsApp jika QR baru
                updateButtonStates();

                // Set a timeout to request a new QR after 7 seconds if not ready
                qrDisplayTimeoutId = setTimeout(() => {
                    if (!isWhatsAppReady) {
                        console.log('Meminta QR baru setelah 7 detik.');
                        socket.emit('request_new_qr'); // Emit event baru ke backend
                    }
                }, 7000); // 7 detik
            }
        });

        socket.on('whatsapp_ready', (data) => {
            if (qrDisplayTimeoutId) {
                clearTimeout(qrDisplayTimeoutId); // Clear the timeout as client is ready
                qrDisplayTimeoutId = null;
            }
            console.log('Klien WhatsApp siap via Socket.IO:', data.message);
            qrCodeImg.src = 'https://placehold.co/200x200/4CAF50/FFFFFF?text=Terhubung';
            connectionStatusSpan.textContent = 'Terhubung';
            connectionStatusSpan.classList.remove('text-orange-500', 'text-red-500');
            connectionStatusSpan.classList.add('text-green-500');
            qrInstructionP.classList.add('hidden');
            isWhatsAppReady = true;
            updateButtonStates();
        });

        socket.on('whatsapp_disconnected', (data) => {
            if (qrDisplayTimeoutId) {
                clearTimeout(qrDisplayTimeoutId);
                qrDisplayTimeoutId = null;
            }
            console.warn('Klien WhatsApp terputus via Socket.IO:', data.reason);
            qrCodeImg.src = 'https://placehold.co/200x200/FF0000/FFFFFF?text=Terputus';
            connectionStatusSpan.textContent = 'Terputus';
            connectionStatusSpan.classList.remove('text-green-500', 'text-orange-500');
            connectionStatusSpan.classList.add('text-red-500');
            qrInstructionP.textContent = 'Koneksi terputus. Harap muat ulang halaman atau periksa backend.';
            qrInstructionP.classList.remove('hidden');
            isWhatsAppReady = false;
            isScanActive = false;
            activeScanType = null;
            console.log('isScanActive set to FALSE by whatsapp_disconnected');
            updateButtonStates();
            // Immediately request a new QR code after disconnection
            socket.emit('request_new_qr');
        });

        socket.on('auth_failure', (data) => {
            if (qrDisplayTimeoutId) {
                clearTimeout(qrDisplayTimeoutId);
                qrDisplayTimeoutId = null;
            }
            console.error('Autentikasi gagal via Socket.IO:', data.message);
            showMessage(`Autentikasi WhatsApp gagal: ${data.message}. Coba pindai ulang QR.`, 'error-box');
            isWhatsAppReady = false;
            isScanActive = false;
            activeScanType = null;
            console.log('isScanActive set to FALSE by auth_failure');
            updateButtonStates();
            // Immediately request a new QR code after auth failure
            socket.emit('request_new_qr');
        });

        socket.on('scan_status', (data) => {
            if (data.status === 'paused') {
                isScanningPaused = true;
                // Update text based on active scan type
                if (activeScanType === 'mass') {
                    scanProgressText.textContent = `Pemindaian dijeda: ${data.message}`;
                } else if (activeScanType === 'instant') {
                    showInstantScanMessage(`Pemindaian instan dijeda: ${data.message}`, 'message-box');
                }
            } else if (data.status === 'resumed') {
                isScanningPaused = false;
                // Update text based on active scan type
                if (activeScanType === 'mass') {
                    scanProgressText.textContent = `Pemindaian dilanjutkan: ${data.message}`;
                } else if (activeScanType === 'instant') {
                    showInstantScanMessage(`Pemindaian instan dilanjutkan: ${data.message}`, 'success-box');
                }
            } else if (data.status === 'cancelled') {
                isScanActive = false; // Reset flag baru
                activeScanType = null;
                console.log('isScanActive set to FALSE by scan_status (cancelled)');
                isScanningPaused = false;
                // Hide progress and clear results based on active scan type
                if (data.type === 'mass') { // Backend should send type with status
                    scanProgressText.textContent = `Pemindaian dibatalkan: ${data.message}`;
                    showMessage('Pemindaian dibatalkan.', 'message-box');
                    setTimeout(() => {
                        uploadProgressContainer.classList.add('hidden');
                        scanProgressContainer.classList.add('hidden');
                        resultsTableBody.innerHTML = '';
                        resultsContainer.classList.add('hidden');
                        numbersFoundTextArea.value = '';
                        numbersNotFoundTextArea.value = '';
                    }, 1000);
                } else if (data.type === 'instant') {
                    showInstantScanMessage(`Pemindaian instan dibatalkan: ${data.message}`, 'message-box');
                    setTimeout(() => {
                        instantResultsTableBody.innerHTML = '';
                        instantResultsContainer.classList.add('hidden');
                        instantNumbersFound.value = '';
                        instantNumbersNotFound.value = '';
                    }, 1000);
                }
            } else if (data.status === 'cache_cleared') { // New: Handle cache cleared status
                showMessage(`Cache berhasil dibersihkan: ${data.message}`, 'success-box');
            }
            updateButtonStates(); // Perbarui status tombol jeda/lanjutkan/batalkan
        });

        socket.on('scan_progress', (data) => {
            // This event is specifically for mass scan progress
            activeScanType = 'mass';
            scanProgressContainer.classList.remove('hidden');
            scanProgressBar.style.width = `${data.percentage}%`;
            scanProgressText.textContent = `${data.statusText || `Memindai ${data.currentNumber}...`} (${data.percentage.toFixed(0)}%)`;
            
            resultsContainer.classList.remove('hidden');
            
            // Set isScanActive to true ketika progres pertama diterima
            if (!isScanActive) {
                isScanActive = true;
                console.log('isScanActive set to TRUE by scan_progress (first event)');
            }

            // Buat hyperlink wa.me
            const waLink = `https://wa.me/${data.formattedNumber}`;
            const displayLink = `<a href="${waLink}" target="_blank" class="text-blue-600 hover:underline">${htmlspecialchars(data.currentNumber)}</a>`;

            // Tentukan sumber foto profil. Backend HARUS mengirimkan 'profilePicUrl' di sini.
            // Jika 'profilePicUrl' tidak ada atau kosong, placeholder akan digunakan.
            const profilePicSrc = data.profilePicUrl || 'https://placehold.co/50x50/CCCCCC/000000?text=Foto'; 

            const row = `
                <tr class="${currentScanCounter % 2 == 0 ? 'bg-gray-50' : 'bg-white'}">
                    <td class="py-3 px-4 text-sm text-gray-800">${currentScanCounter + 1}</td>
                    <td class="py-3 px-4 text-sm text-gray-800">${displayLink}</td>
                    <td class="py-3 px-4 text-sm text-gray-800">
                        ${data.isRegistered ? '<span class="text-green-600 font-semibold">Ada</span>' : '<span class="text-red-600 font-semibold">Tidak Ada</span>'}
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-800 text-wrap break-all">${htmlspecialchars(data.debug_info || 'OK')}</td>
                    <td class="py-3 px-4 text-sm text-gray-800">
                        <img src="${profilePicSrc}" alt="Profil" class="w-8 h-8 rounded-full object-cover inline-block">
                    </td>
                </tr>
            `;
            resultsTableBody.insertAdjacentHTML('beforeend', row);
            currentScanCounter++;
            resultsTableBody.scrollTop = resultsTableBody.scrollHeight;

            if (data.isRegistered) {
                numbersFoundTextArea.value += `${data.currentNumber}\n`; 
                numbersFoundTextArea.scrollTop = numbersFoundTextArea.scrollHeight;
            } else {
                numbersNotFoundTextArea.value += `${data.currentNumber}\n`;
                numbersNotFoundTextArea.scrollTop = numbersNotFoundTextArea.scrollHeight;
            }
            updateButtonStates(); // Perbarui status tombol selama progres
        });

        socket.on('scan_complete', (data) => {
            console.log('Pemindaian selesai via Socket.IO:', data.results);
            // Check if the completion is for the active scan type
            if (activeScanType === 'mass') {
                scanProgressBar.style.width = '100%';
                scanProgressText.textContent = 'Pemindaian selesai!';
                showMessage('Pemeriksaan selesai!', 'success-box');
                setTimeout(() => {
                    uploadProgressContainer.classList.add('hidden');
                    scanProgressContainer.classList.add('hidden');
                }, 2000);
            } else if (activeScanType === 'instant') {
                 showInstantScanMessage('Pemeriksaan instan selesai!', 'success-box');
                 // No progress bar for instant, just message box
            }
            currentScanCounter = 0;
            instantScanCounter = 0;
            isScanningPaused = false;
            isScanActive = false; // Reset flag baru
            activeScanType = null;
            console.log('isScanActive set to FALSE by scan_complete');
            updateButtonStates();
        });

        socket.on('scan_error', (data) => {
            console.error('Kesalahan pemindaian via Socket.IO:', data.message);
            // Based on active scan type, show error
            if (activeScanType === 'mass') {
                showMessage(`Kesalahan pemindaian: ${data.message}`, 'error-box');
                scanProgressContainer.classList.add('hidden');
                uploadProgressContainer.classList.add('hidden');
            } else if (activeScanType === 'instant') {
                showInstantScanMessage(`Kesalahan pemindaian instan: ${data.message}`, 'error-box');
            }
            currentScanCounter = 0;
            instantScanCounter = 0;
            isScanningPaused = false;
            isScanActive = false; // Reset flag baru
            activeScanType = null;
            console.log('isScanActive set to FALSE by scan_error');
            updateButtonStates();
        });

        socket.on('client_count_update', (data) => { // New: Listener for client count updates
            onlineClientsCountSpan.textContent = `Online: ${data.count}`;
        });

        // New: Listener for WhatsApp profile search results
        socket.on('whatsapp_profile_result', (data) => {
            hideSearchMessage(); // Sembunyikan pesan sebelumnya
            searchResultsDisplay.classList.remove('hidden'); // Tampilkan tampilan hasil

            if (data.error) {
                showSearchMessage(`Error: ${data.error}`, 'error-box');
                searchResultsDisplay.classList.add('hidden'); // Sembunyikan tampilan hasil pada error
                return;
            }

            searchRegisteredDisplay.textContent = data.isRegistered ? 'Ya' : 'Tidak';
            searchRegisteredDisplay.classList.toggle('text-green-600', data.isRegistered);
            searchRegisteredDisplay.classList.toggle('text-red-500', !data.isRegistered);
            
            // searchNameDisplay.textContent = 'N/A'; // Backend tidak mengirim nama
            
            // Tampilkan foto profil jika ada, jika tidak, gunakan placeholder "Tidak Ada"
            if (data.isRegistered && data.profilePicUrl) {
                searchProfilePic.src = data.profilePicUrl;
            } else {
                searchProfilePic.src = 'https://placehold.co/100x100/CCCCCC/000000?text=Tidak+Ada';
            }
            
            if (!data.isRegistered) {
                showSearchMessage('Nomor tidak terdaftar di WhatsApp.', 'message-box');
            }
        });

        // New: Listener for single instant scan result
        socket.on('instant_scan_started', (data) => {
            activeScanType = 'instant';
            isScanActive = true;
            isScanningPaused = false;
            showInstantScanMessage(data.message, 'message-box');
            instantResultsTableBody.innerHTML = ''; // Clear previous results
            instantResultsContainer.classList.remove('hidden'); // Show results container
            instantNumbersFound.value = ''; // Clear instant found numbers
            instantNumbersNotFound.value = ''; // Clear instant not found numbers
            instantScanCounter = 0; // Reset counter
            updateButtonStates();
        });

        socket.on('single_whatsapp_number_result', (data) => {
            hideInstantScanMessage(); // Hide any initial "starting scan" message

            // Ensure activeScanType is 'instant' if this event fires
            if (!isScanActive || activeScanType !== 'instant') {
                activeScanType = 'instant';
                isScanActive = true;
                isScanningPaused = false;
                updateButtonStates();
            }

            const waLink = `https://wa.me/${data.formattedNumberForLink}`; // Use formattedNumberForLink for the URL
            const profilePicSrc = data.profilePicUrl || 'https://placehold.co/50x50/CCCCCC/000000?text=Foto';
            const statusText = data.isRegistered ? '<span class="text-green-600 font-semibold">Ada</span>' : '<span class="text-red-600 font-semibold">Tidak Ada</span>';
            const errorText = data.error ? `<br><span class="text-red-500 text-xs">${htmlspecialchars(data.error)}</span>` : '';

            instantScanCounter++;
            const row = `
                <tr class="${instantScanCounter % 2 == 0 ? 'bg-gray-50' : 'bg-white'}">
                    <td class="py-2 px-3 text-sm text-gray-800">${instantScanCounter}</td>
                    <td class="py-2 px-3 text-sm text-gray-800"><a href="${waLink}" target="_blank" class="text-blue-600 hover:underline">${htmlspecialchars(data.originalNumber)}</a></td>
                    <td class="py-2 px-3 text-sm text-gray-800">${statusText}${errorText}</td>
                    <td class="py-2 px-3 text-sm text-gray-800"><img src="${profilePicSrc}" alt="Profil" class="w-8 h-8 rounded-full object-cover inline-block"></td>
                </tr>
            `;
            instantResultsTableBody.insertAdjacentHTML('beforeend', row);
            instantResultsTableBody.scrollTop = instantResultsTableBody.scrollHeight;

            // Update the new textareas for instant scan results
            if (data.isRegistered) {
                instantNumbersFound.value += `${data.originalNumber}\n`;
                instantNumbersFound.scrollTop = instantNumbersFound.scrollHeight;
            } else {
                instantNumbersNotFound.value += `${data.originalNumber}\n`;
                instantNumbersNotFound.scrollTop = instantNumbersNotFound.scrollHeight;
            }
        });


        // Fungsi untuk mengambil status QR code dari backend saat halaman dimuat
        // Ini hanya untuk inisialisasi awal, refresh QR akan ditangani oleh Socket.IO
        async function fetchInitialQrStatus() {
            try {
                const response = await fetch(`${backendUrl}/get-qr`);
                const data = await response.json();

                if (data.qrCodeUrl) {
                    qrCodeImg.src = data.qrCodeUrl;
                    console.log('URL Kode QR diterima (initial fetch):', data.qrCodeUrl);
                    connectionStatusSpan.textContent = 'Memindai QR...';
                    connectionStatusSpan.classList.remove('text-green-500', 'text-red-500');
                    connectionStatusSpan.classList.add('text-orange-500');
                    qrInstructionP.classList.remove('hidden');
                    isWhatsAppReady = false;
                    // Start the 7-second timer for this initially fetched QR
                    if (qrDisplayTimeoutId) clearTimeout(qrDisplayTimeoutId);
                    qrDisplayTimeoutId = setTimeout(() => {
                        if (!isWhatsAppReady) {
                            console.log('Meminta QR baru setelah 7 detik (initial fetch timer).');
                            socket.emit('request_new_qr');
                        }
                    }, 7000);
                } else if (data.status === 'ready') {
                    qrCodeImg.src = 'https://placehold.co/200x200/4CAF50/FFFFFF?text=Terhubung';
                    connectionStatusSpan.textContent = 'Terhubung';
                    connectionStatusSpan.classList.remove('text-orange-500', 'text-red-500');
                    connectionStatusSpan.classList.add('text-green-500');
                    qrInstructionP.classList.add('hidden');
                    isWhatsAppReady = true;
                    if (qrDisplayTimeoutId) clearTimeout(qrDisplayTimeoutId); // Clear if already ready
                } else if (data.status === 'initializing') {
                    qrCodeImg.src = 'https://placehold.co/200x200/E0E0E0/000000?text=Memuat';
                    connectionStatusSpan.textContent = 'Menghubungkan...';
                    connectionStatusSpan.classList.remove('text-green-500', 'text-red-500');
                    connectionStatusSpan.classList.add('text-orange-500');
                    qrInstructionP.classList.remove('hidden');
                    isWhatsAppReady = false;
                    // If initializing, we might not have a QR yet, but we'll get one via socket.on('qr_code')
                    // No need to set a timer here, let the socket event handle it when QR arrives.
                }
                updateButtonStates();
            } catch (error) {
                console.error('Error fetching QR code or connection status (initial poll):', error);
                qrCodeImg.src = 'https://placehold.co/200x200/FF0000/FFFFFF?text=Error+Koneksi';
                qrCodeImg.alt = 'Kesalahan Koneksi Backend';
                connectionStatusSpan.textContent = 'Kesalahan Koneksi Backend';
                connectionStatusSpan.classList.remove('text-green-500', 'text-orange-500');
                connectionStatusSpan.classList.add('text-red-500');
                qrInstructionP.textContent = 'Pastikan backend Node.js Anda berjalan di ' + backendUrl;
                qrInstructionP.classList.remove('hidden');
                isWhatsAppReady = false;
                updateButtonStates();
            }
        }

        // Panggil saat halaman dimuat pertama kali
        document.addEventListener('DOMContentLoaded', () => {
            fetchInitialQrStatus(); // Call this once on load
            generateProvinceBars();
            generateCountryBars();
        });

        // Event listener untuk tombol Jeda/Lanjutkan/Batalkan (Massal)
        pauseScanButton.addEventListener('click', () => {
            socket.emit('pause_scan');
            console.log('Mengirim event pause_scan (massal)');
        });

        resumeScanButton.addEventListener('click', () => {
            socket.emit('resume_scan');
            console.log('Mengirim event resume_scan (massal)');
        });

        cancelScanButton.addEventListener('click', () => {
            socket.emit('cancel_scan');
            console.log('Mengirim event cancel_scan (massal)');
        });

        // NEW: Event listener untuk tombol Jeda/Lanjutkan/Batalkan (Instan)
        pauseInstantScanButton.addEventListener('click', () => {
            socket.emit('pause_scan'); // Menggunakan event yang sama dengan pemindaian massal
            console.log('Mengirim event pause_scan (instan)');
        });

        resumeInstantScanButton.addEventListener('click', () => {
            socket.emit('resume_scan'); // Menggunakan event yang sama dengan pemindaian massal
            console.log('Mengirim event resume_scan (instan)');
        });

        cancelInstantScanButton.addEventListener('click', () => {
            socket.emit('cancel_scan'); // Menggunakan event yang sama dengan pemindaian massal
            console.log('Mengirim event cancel_scan (instan)');
        });


        // Event listener for Logout WhatsApp Session button
        logoutWhatsappButton.addEventListener('click', () => {
            if (confirm('Anda yakin ingin logout dari sesi WhatsApp? Anda perlu memindai ulang QR Code setelah ini.')) {
                socket.emit('logout_whatsapp');
                console.log('Mengirim event logout_whatsapp');
                // Optionally, reset UI immediately after logout request
                qrCodeImg.src = 'https://placehold.co/200x200/E0E0E0/000000?text=Logging+Out';
                connectionStatusSpan.textContent = 'Logging Out...';
                connectionStatusSpan.classList.remove('text-green-500', 'text-red-500', 'text-orange-500');
                connectionStatusSpan.classList.add('text-gray-500');
                qrInstructionP.textContent = 'Sesi WhatsApp sedang diakhiri. Harap tunggu.';
                isWhatsAppReady = false;
                updateButtonStates();
            }
        });

        // Event listener for Destroy Session & Clear Cache button
        destroySessionButton.addEventListener('click', () => {
            if (confirm('PERINGATAN: Ini akan menghancurkan sesi WhatsApp yang tersimpan di server dan membersihkan cache. Anda perlu memindai ulang QR Code setelah ini. Lanjutkan?')) {
                socket.emit('destroy_session_and_clear_cache');
                console.log('Mengirim event destroy_session_and_clear_cache');
                // Reset UI immediately after destruction request
                qrCodeImg.src = 'https://placehold.co/200x200/E0E0E0/000000?text=Menghancurkan+Sesi';
                connectionStatusSpan.textContent = 'Menghancurkan Sesi...';
                connectionStatusSpan.classList.remove('text-green-500', 'text-red-500', 'text-orange-500');
                connectionStatusSpan.classList.add('text-gray-500');
                qrInstructionP.textContent = 'Sesi WhatsApp dan cache sedang dihapus. Harap tunggu.';
                isWhatsAppReady = false;
                updateButtonStates();
            }
        });

        // New: Event listener for Clear Cache button
        clearCacheButton.addEventListener('click', () => {
            if (confirm('Anda yakin ingin membersihkan data cache di server? Ini tidak akan mengakhiri sesi WhatsApp Anda. Lanjutkan?')) {
                socket.emit('clear_cache');
                console.log('Mengirim event clear_cache');
                showMessage('Permintaan membersihkan cache dikirim. Harap tunggu konfirmasi dari server.', 'message-box');
                // No change to WhatsApp ready state or QR code, as it's just cache
            }
        });

        // Event listener untuk submit form (Massal)
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideMessage();
            resultsTableBody.innerHTML = ''; // Bersihkan tabel sebelum memulai pemindaian baru
            resultsContainer.classList.add('hidden'); // Sembunyikan kontainer hasil
            numbersFoundTextArea.value = ''; // Bersihkan area teks
            numbersNotFoundTextArea.value = ''; // Bersihkan area teks

            // Pastikan Socket.IO terhubung dan WhatsApp siap sebelum melanjutkan
            if (!isSocketConnected || !isWhatsAppReady) {
                showMessage('Koneksi WhatsApp belum siap atau Socket.IO belum terhubung. Harap tunggu.', 'message-box');
                return;
            }

            if (isScanActive) { // Prevent starting new scan if one is already active
                showMessage('Pemindaian lain sedang berlangsung. Harap tunggu atau batalkan pemindaian saat ini.', 'message-box');
                return;
            }

            const file = phoneFileInput.files[0];
            if (!file) {
                showMessage('Harap pilih file untuk diunggah.', 'message-box');
                return;
            }

            if (file.type !== 'text/plain') {
                showMessage('Tipe file tidak didukung. Harap unggah file .txt.', 'error-box');
                return;
            }

            // Reset dan tampilkan kontainer progres unggahan
            uploadProgressContainer.classList.remove('hidden');
            uploadProgressBar.style.width = '0%';
            uploadProgressText.textContent = 'Membaca file...';

            // Sembunyikan kontainer progres pemindaian di awal
            scanProgressContainer.classList.add('hidden');
            scanProgressBar.style.width = '0%';
            scanProgressText.textContent = 'Menunggu pemindaian dimulai...';
            currentScanCounter = 0; // Reset counter saat memulai proses baru
            isScanningPaused = false; // Pastikan status jeda direset
            isScanActive = true; // Set active immediately for mass scan
            activeScanType = 'mass'; // Set type
            console.log('isScanActive set to TRUE, activeScanType to "mass" at start of submit handler');
            updateButtonStates(); // Perbarui tombol untuk mencerminkan status pengiriman (tombol submit dinonaktifkan)

            try {
                const text = await file.text();
                const numbers = text.split('\n')
                                    .map(line => line.trim())
                                    .filter(line => line !== '');

                if (numbers.length === 0) {
                    showMessage('File yang diunggah kosong atau tidak berisi nomor telepon yang valid.', 'message-box');
                    uploadProgressContainer.classList.add('hidden');
                    isScanActive = false; // Reset flag pada file kosong
                    activeScanType = null;
                    updateButtonStates(); // Perbarui status tombol
                    return;
                }

                // Gunakan XMLHttpRequest untuk melacak progres unggahan
                const xhr = new XMLHttpRequest();
                xhr.open('POST', `${backendUrl}/check-whatsapp-numbers`, true);
                xhr.setRequestHeader('Content-Type', 'application/json');

                // Kirim ID soket bersama dengan nomor agar backend tahu ke mana harus mengirim pembaruan
                const payload = {
                    numbers: numbers,
                    socketId: socket.id // Dapatkan ID soket klien saat ini
                };

                // Progres unggahan (untuk mengirim body permintaan)
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        uploadProgressBar.style.width = `${percent}%`;
                        uploadProgressText.textContent = `Mengunggah data: ${percent.toFixed(0)}%`;
                    } else {
                        uploadProgressText.textContent = `Mengunggah data... (ukuran tidak diketahui)`;
                    }
                };

                // Saat permintaan selesai (respon awal dari backend, bukan hasil pemindaian akhir)
                xhr.onload = () => {
                    uploadProgressBar.style.width = '100%';
                    uploadProgressText.textContent = 'Pengunggahan selesai. Menunggu hasil pemindaian...';
                    if (xhr.status === 200) {
                        // Jika unggahan berhasil, tampilkan kontainer progres pemindaian dan perbarui status tombol
                        scanProgressContainer.classList.remove('hidden'); // Tampilkan kontainer progres pemindaian
                        updateButtonStates(); // Perbarui tombol berdasarkan isScanActive (yang akan segera true)
                    } else {
                        let errorMessage = 'Terjadi kesalahan.';
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            errorMessage = errorData.error || errorMessage;
                        } catch (e) {
                            errorMessage = `Respon tidak valid: ${xhr.responseText.substring(0, 100)}...`;
                        }
                        showMessage(`Kesalahan dari backend (HTTP ${xhr.status}): ${errorMessage}`, 'error-box');
                        uploadProgressContainer.classList.add('hidden');
                        scanProgressContainer.classList.add('hidden');
                        isScanActive = false; // Reset flag pada error
                        activeScanType = null;
                        console.log('isScanActive set to FALSE by xhr.onload (error)');
                        updateButtonStates(); // Perbarui status tombol pada error
                    }
                    // Hasil akhir akan diterima melalui Socket.IO
                };

                // Tangani kesalahan jaringan
                xhr.onerror = () => {
                    uploadProgressText.textContent = 'Kesalahan jaringan.';
                    showMessage(`Terjadi kesalahan jaringan: Tidak dapat terhubung ke backend.`, 'error-box');
                    uploadProgressContainer.classList.add('hidden');
                    scanProgressContainer.classList.add('hidden');
                    isScanActive = false; // Reset flag pada error
                    activeScanType = null;
                    console.log('isScanActive set to FALSE by xhr.onerror');
                    updateButtonStates(); // Perbarui status tombol pada error
                };

                // Kirim permintaan
                xhr.send(JSON.stringify(payload));
                uploadProgressText.textContent = 'Memulai pengunggahan data...';


            } catch (error) {
                console.error('Error during file processing:', error);
                showMessage(`Terjadi kesalahan: ${error.message}.`, 'error-box');
                uploadProgressContainer.classList.add('hidden');
                scanProgressContainer.classList.add('hidden');
                isScanActive = false; // Reset flag pada error
                activeScanType = null;
                console.log('isScanActive set to FALSE by file processing error');
                updateButtonStates(); // Perbarui status tombol pada error
            }
        });

        // Event listener untuk tombol Pencarian Profil WhatsApp
        searchWhatsappButton.addEventListener('click', () => {
            hideSearchMessage(); // Bersihkan pesan sebelumnya
            searchResultsDisplay.classList.add('hidden'); // Sembunyikan hasil sebelumnya
            searchError.textContent = ''; // Bersihkan error sebelumnya

            if (!isWhatsAppReady) {
                showSearchMessage('Klien WhatsApp belum siap. Harap pindai kode QR dan tunggu koneksi.', 'message-box');
                return;
            }

            const phoneNumber = searchPhoneNumberInput.value.trim();
            if (!phoneNumber) {
                searchError.textContent = 'Nomor telepon tidak boleh kosong.';
                return;
            }
            if (!phoneNumber.startsWith('+')) {
                searchError.textContent = 'Nomor telepon harus diawali dengan kode negara (contoh: +62).';
                return;
            }
            if (!/^\+[0-9]+$/.test(phoneNumber)) {
                searchError.textContent = 'Nomor telepon hanya boleh mengandung angka dan diawali dengan "+".';
                return;
            }

            // Tampilkan status memuat
            showSearchMessage('Mencari info profil WhatsApp...', 'message-box');
            searchProfilePic.src = 'https://placehold.co/100x100/E0E0E0/000000?text=Memuat...';
            searchRegisteredDisplay.textContent = 'Memuat...';
            searchRegisteredDisplay.classList.remove('text-green-600', 'text-red-500');


            // Kirim permintaan pencarian ke backend
            socket.emit('search_whatsapp_profile', { phoneNumber: phoneNumber });
        });

        // New: Event listener for Instant Scan Button
        startInstantScanButton.addEventListener('click', async () => {
            hideInstantScanMessage();
            instantResultsTableBody.innerHTML = ''; // Clear previous results
            instantResultsContainer.classList.add('hidden'); // Hide results container initially
            instantNumbersFound.value = ''; // Clear instant found numbers
            instantNumbersNotFound.value = ''; // Clear instant not found numbers

            if (!isSocketConnected || !isWhatsAppReady) {
                showInstantScanMessage('Koneksi WhatsApp belum siap atau Socket.IO belum terhubung. Harap tunggu.', 'message-box');
                return;
            }

            if (isScanActive) { // Prevent starting new scan if one is already active
                showInstantScanMessage('Pemindaian lain sedang berlangsung. Harap tunggu atau batalkan pemindaian saat ini.', 'message-box');
                return;
            }

            const numbersRaw = instantScanNumbersInput.value.trim();
            if (!numbersRaw) {
                showInstantScanMessage('Harap masukkan nomor telepon di kolom yang tersedia.', 'message-box');
                return;
            }

            const numbers = numbersRaw.split('\n')
                                    .map(line => line.trim())
                                    .filter(line => line !== '');

            if (numbers.length === 0) {
                showInstantScanMessage('Tidak ada nomor telepon yang valid ditemukan di input.', 'message-box');
                return;
            }

            // Set active scan state for instant scan
            isScanActive = true;
            activeScanType = 'instant';
            isScanningPaused = false;
            instantScanCounter = 0; // Reset counter for instant scan
            updateButtonStates(); // Update button states immediately

            showInstantScanMessage('Memulai pemeriksaan instan...', 'message-box');
            instantResultsContainer.classList.remove('hidden'); // Show results container

            // Send all numbers to backend to initiate the instant scan loop
            socket.emit('start_instant_scan', { numbers: numbers, socketId: socket.id });
        });


        // Fungsi pembantu untuk escaping HTML (mirip dengan htmlspecialchars PHP)
        function htmlspecialchars(str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // --- Generator Section JavaScript (Lokal & Internasional) ---

        /**
         * Memvalidasi bidang input untuk memastikan hanya berisi karakter yang diizinkan dan memiliki panjang yang ditentukan.
         * Menampilkan pesan kesalahan jika validasi gagal.
         * @param {HTMLInputElement} inputElement - Bidang input yang akan divalidasi.
         * @param {number|null} requiredLength - Jumlah karakter/digit yang tepat yang diperlukan, atau null jika tidak ada panjang tertentu.
         * @param {string} fieldId - ID bidang (misalnya, 'field1', 'field3', 'field5') untuk menerapkan aturan validasi tertentu.
         */
        function validateInput(inputElement, requiredLength, fieldId) {
            let value = inputElement.value;
            const errorElement = document.getElementById(`error${fieldId.replace('field', '')}`);

            if (fieldId === 'field1') {
                // Untuk field1, izinkan digit dan '+'
                value = value.replace(/[^0-9+]/g, '');
            } else {
                // Untuk bidang lain, hanya izinkan digit
                value = value.replace(/[^0-9]/g, '');
            }
            inputElement.value = value; // Perbarui bidang input dengan nilai yang sudah dibersihkan

            if (value.length === 0) {
                errorElement.textContent = ''; // Hapus error jika kosong
                return false;
            }

            // Validasi panjang spesifik untuk field1 dan field2
            if (requiredLength !== null && value.length !== requiredLength) {
                if (fieldId === 'field1') {
                    errorElement.textContent = `Harus ${requiredLength} karakter.`;
                } else if (fieldId === 'field2') {
                    errorElement.textContent = `Harus ${requiredLength} digit angka.`;
                }
                return false;
            }
            // Untuk field3, field4, field5, field6, field7, pastikan itu adalah angka yang valid jika tidak kosong
            else if ((fieldId === 'field3' || fieldId === 'field4' || fieldId === 'field5' || fieldId === 'field6' || fieldId === 'field7') && value.length > 0) {
                if (isNaN(parseInt(value, 10))) {
                    errorElement.textContent = `Harus berupa angka.`;
                    return false;
                }
            }

            errorElement.textContent = ''; // Hapus error jika valid
            return true;
        }

        /**
         * Mengisi angka dengan nol di depan untuk mencapai panjang yang ditentukan.
         * @param {number} num - Angka yang akan diisi.
         * @param {number} length - Total panjang string yang diinginkan.
         * @returns {string} Angka yang diisi sebagai string.
         */
        function padNumber(num, length) {
            let s = String(num);
            while (s.length < length) {
                s = '0' + s;
            }
            return s;
        }

        /**
         * Menangani pembuatan nomor lokal (Indonesia) ketika tombol "Generate" diklik.
         */
        generateBtn.addEventListener('click', () => {
            // Validasi semua bidang sebelum melanjutkan dengan pembuatan
            const isValid1 = validateInput(field1, 6, 'field1');
            const isValid2 = validateInput(field2, 2, 'field2');
            const isValid3 = validateInput(field3, null, 'field3');
            const isValid4 = validateInput(field4, null, 'field4');


            if (!isValid1 || !isValid2 || !isValid3 || !isValid4) {
                outputArea.value = "Harap perbaiki semua input yang salah sebelum generate.";
                // Reset sorotan provinsi jika ada error
                highlightProvinceBar('');
                return;
            }

            const startNum = parseInt(field3.value, 10);
            const endNum = parseInt(field4.value, 10);

            if (isNaN(startNum) || isNaN(endNum)) {
                outputArea.value = "Input 'Mulai Dari' atau 'Sampai Dengan' tidak valid.";
                highlightProvinceBar('');
                return;
            }

            if (startNum > endNum) {
                outputArea.value = "Angka 'Mulai Dari' harus lebih kecil atau sama dengan 'Sampai Dengan'.";
                highlightProvinceBar('');
                return;
            }

            outputArea.value = ''; // Bersihkan output sebelumnya
            let generatedNumbers = [];

            // Gunakan panjang string asli dari field4.value untuk padding
            const maxLength = field4.value.length; 

            // Dapatkan awalan tetap dari field1 dan field2
            const prefix1 = field1.value;
            const prefix2 = field2.value;

            // Buat nomor dan simpan dalam array
            for (let i = startNum; i <= endNum; i++) {
                const generatedSuffix = padNumber(i, maxLength);
                generatedNumbers.push(prefix1 + prefix2 + generatedSuffix);
            }

            // Gabungkan semua nomor dengan baris baru untuk tampilan yang efisien
            outputArea.value = generatedNumbers.join('\n');

            // Gulir ke bagian bawah area output
            outputArea.scrollTop = outputArea.scrollHeight;

            // --- Logika Penyorotan Bar Provinsi ---
            const operatorPrefix = field2.value; // Dapatkan kode operator 2 digit
            const provinceIdToHighlight = hlrProvinceMap[operatorPrefix] || 'lainnya'; // Default ke 'lainnya' jika tidak ditemukan
            highlightProvinceBar(provinceIdToHighlight);
        });

        /**
         * Menangani pengunduhan konten area output sebagai file .txt.
         */
        downloadBtn.addEventListener('click', () => {
            const textToDownload = outputArea.value;
            if (textToDownload.trim() === '') {
                // Gunakan kotak pesan kustom alih-alih alert()
                outputArea.value = "Tidak ada data untuk diunduh. Harap generate nomor terlebih dahulu.";
                return;
            }

            const filename = 'nomor_telepon_lokal.txt';
            const blob = new Blob([textToDownload], { type: 'text/plain;charset=utf-8' });

            // Buat elemen tautan sementara
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;

            // Tambahkan tautan ke body, klik, lalu hapus
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href); // Bersihkan objek URL
        });

        /**
         * Membersihkan area output ketika tombol "Bersihkan Output" diklik.
         */
        clearBtn.addEventListener('click', () => {
            outputArea.value = '';
            outputArea.placeholder = "Nomor telepon yang dihasilkan akan muncul di sini...";
            highlightProvinceBar(''); // Bersihkan sorotan provinsi ketika output dibersihkan
        });

        /**
         * Menangani pembuatan nomor internasional ketika tombol "Generate International" diklik.
         */
        generateIntBtn.addEventListener('click', () => {
            const isValid5 = validateInput(field5, null, 'field5');
            const isValid6 = validateInput(field6, null, 'field6');
            const isValid7 = validateInput(field7, null, 'field7');

            if (!isValid5 || !isValid6 || !isValid7) {
                outputIntArea.value = "Harap perbaiki semua input yang salah sebelum generate.";
                highlightCountryBar('');
                return;
            }

            const countryCode = field5.value;
            const startNum = parseInt(field6.value, 10);
            const endNum = parseInt(field7.value, 10);

            if (isNaN(startNum) || isNaN(endNum)) {
                outputIntArea.value = "Input 'Mulai Dari' atau 'Sampai Dengan' tidak valid.";
                highlightCountryBar('');
                return;
            }

            if (startNum > endNum) {
                outputIntArea.value = "Angka 'Mulai Dari' harus lebih kecil atau sama dengan 'Sampai Dengan'.";
                highlightCountryBar('');
                return;
            }

            outputIntArea.value = ''; // Bersihkan output sebelumnya
            let generatedNumbers = [];

            // Gunakan panjang string asli dari field7.value untuk padding
            const maxLength = field7.value.length; 

            for (let i = startNum; i <= endNum; i++) {
                const generatedSuffix = padNumber(i, maxLength);
                generatedNumbers.push(`+${countryCode}${generatedSuffix}`);
            }

            outputIntArea.value = generatedNumbers.join('\n');
            outputIntArea.scrollTop = outputIntArea.scrollHeight;

            // Sorot bar negara berdasarkan kode negara yang dimasukkan
            highlightCountryBar(countryCode);
        });

        /**
         * Menangani pengunduhan konten area output internasional sebagai file .txt.
         */
        downloadIntBtn.addEventListener('click', () => {
            const textToDownload = outputIntArea.value;
            if (textToDownload.trim() === '') {
                outputIntArea.value = "Tidak ada data untuk diunduh. Harap generate nomor internasional terlebih dahulu.";
                return;
            }

            const filename = 'nomor_telepon_internasional.txt';
            const blob = new Blob([textToDownload], { type: 'text/plain;charset=utf-8' });

            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;

            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        });

        /**
         * Membersihkan area output internasional ketika tombol "Bersihkan Output" diklik.
         */
        clearIntBtn.addEventListener('click', () => {
            outputIntArea.value = '';
            outputIntArea.placeholder = "Nomor telepon internasional yang dihasilkan akan muncul di sini...";
            highlightCountryBar(''); // Bersihkan sorotan negara ketika output dibersihkan
        });


        // Validasi awal saat halaman dimuat atau saat blur
        field1.addEventListener('blur', () => validateInput(field1, 6, 'field1'));
        field2.addEventListener('blur', () => validateInput(field2, 2, 'field2'));
        field3.addEventListener('blur', () => validateInput(field3, null, 'field3'));
        field4.addEventListener('blur', () => validateInput(field4, null, 'field4'));
        field5.addEventListener('blur', () => validateInput(field5, null, 'field5'));
        field6.addEventListener('blur', () => validateInput(field6, null, 'field6'));
        field7.addEventListener('blur', () => validateInput(field7, null, 'field7'));
    </script>
</body>
</html>
