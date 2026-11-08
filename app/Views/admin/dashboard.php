<?php
// Ambil data barber dari database untuk tab Roster & Status Barber.
$barberRows = \Config\Database::connect()
    ->table('barbers')
    ->orderBy('name', 'ASC')
    ->get()
    ->getResultArray();

$barbersForJs = array_map(static function ($barber) {
    $statusMap = ['on_duty' => 'duty', 'break' => 'break', 'off' => 'off'];

    return [
        'id'     => (int) $barber['id'],
        'name'   => $barber['name'] ?? '',
        'phone'  => $barber['phone'] ?? '',
        'status' => $statusMap[$barber['status'] ?? 'on_duty'] ?? 'duty',
        'avatar' => $barber['avatar'] ?? '',
    ];
}, $barberRows);
?>
<?php
$db = \Config\Database::connect();

$activeBarbers = $db->table('barbers')
    ->where('status', 'on_duty')
    ->countAllResults();

$breakBarbers = $db->table('barbers')
    ->where('status', 'break')
    ->countAllResults();
?>

<?php
// Hitung jumlah status antrean khusus hari ini secara efisien
$cAll       = count($appointments ?? []);
$cWaiting   = 0;
$cProgress  = 0;
$cCompleted = 0;

if (!empty($appointments)) {
    foreach ($appointments as $a) {
        $st = strtolower($a['status'] ?? '');
        if ($st === 'waiting' || $st === 'menunggu') {
            $cWaiting++;
        } elseif ($st === 'in_progress' || $st === 'sedang_cukur' || $st === 'diproses') {
            $cProgress++;
        } elseif ($st === 'completed' || $st === 'selesai') {
            $cCompleted++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberShop Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            glow: 'rgba(245, 158, 11, 0.25)'
                        },
                        dark: {
                            base: '#0b0f17',
                            card: '#131926',
                            border: '#1e2638',
                            hover: '#192132'
                        }
                    },
                    boxShadow: {
                        'gold-glow': '0 0 25px -5px rgba(245, 158, 11, 0.25)',
                        'gold-sm': '0 0 12px rgba(245, 158, 11, 0.18)',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Custom Scrollbar */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0b0f17;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e2638;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #d97706;
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, #fef08a 0%, #f59e0b 50%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gold-border-glow {
            border: 1px solid rgba(245, 158, 11, 0.25);
            box-shadow: inset 0 0 15px rgba(245, 158, 11, 0.04);
        }

        .glass-card {
            background: rgba(19, 25, 38, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }

        @keyframes pulse-light {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .animate-pulse-slow {
            animation: pulse-light 2.5s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-dark-base text-slate-100 font-sans min-h-screen flex flex-col items-center justify-start p-0 md:p-4 overflow-x-hidden">

    <!-- TOP CONTROL BAR FOR VIEW MODES & LIVE CLOCK -->
<header class="w-full max-w-7xl mb-3 p-3 rounded-2xl glass-card flex flex-wrap items-center justify-between gap-3 shadow-xl z-40 text-xs md:text-sm">
    <!-- PEMBUNGKUS UTAMA LOGO & TEKS (Menjaga agar selalu berdampingan) -->
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center text-slate-950 font-black shadow-gold-sm overflow-hidden">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= base_url(esc($settings['logo'])); ?>?v=<?= time(); ?>" 
                     alt="Logo" 
                     class="w-full h-full object-cover"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <i class="fa-solid fa-scissors text-base hidden"></i>
            <?php else: ?>
                <i class="fa-solid fa-scissors text-base"></i>
            <?php endif; ?>
        </div>
        <div>
            <h1 class="font-bold text-white text-sm md:text-base leading-tight">
                <?= esc($settings['name'] ?? 'DR.BARBER'); ?> <span class="text-amber-400">ADMIN</span>
            </h1>
            <p class="text-[11px] text-slate-400">Panel Manajemen Barber</p>
        </div>
    </div>

    <!-- Live Date & Clock -->
    <div class="hidden sm:flex items-center gap-2 bg-slate-900/90 border border-slate-800 px-3 py-1.5 rounded-xl text-slate-300">
        <i class="fa-regular fa-clock text-amber-400 text-xs"></i>
        <span id="liveClockText" class="font-mono text-xs font-semibold">Memuat waktu...</span>
    </div>

    <!-- Group Tombol Aksi -->
    <div class="flex items-center gap-2">
        <button id="viewToggleBtn" onclick="toggleViewMode()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 font-semibold border border-amber-500/30 transition flex items-center gap-2 text-xs shadow-sm">
            <i class="fa-solid fa-mobile-screen" id="viewToggleIcon"></i>
            <span id="viewToggleText">Tampilan Mobile Admin</span>
        </button>

        <button onclick="openSettingsModal()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold border border-slate-700 transition flex items-center gap-1.5 text-xs">
            <i class="fa-solid fa-gear text-amber-400"></i>
            <span>Setting</span>
        </button>

        <button onclick="openCreateAccountModal()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold border border-amber-500/30 transition flex items-center gap-1.5 text-xs">
            <i class="fa-solid fa-user-plus"></i>
            <span>Buat Akun</span>
        </button>

        <button onclick="openWalkInModal()" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold transition flex items-center gap-1.5 text-xs shadow-gold-sm">
            <i class="fa-solid fa-user-plus"></i>
            <span>+ Ambil Antrian</span>
        </button>
    </div>
</header>

   <!-- MAIN APP CONTAINER (Dynamic Phone Frame vs Desktop Full Width) -->
<div id="mainContainer" class="w-full max-w-7xl bg-dark-base rounded-2xl border border-slate-800/80 shadow-2xl relative overflow-hidden transition-all duration-300 flex flex-col min-h-[85vh]">
    
    <!-- HEADER TOP DASHBOARD BAR -->
    <div class="p-4 md:p-5 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 z-30">
    
    <!-- Navigation Dials / Tabs (Scrollable dengan Padding Ujung) -->
    <nav class="flex items-center gap-1.5 overflow-x-auto no-scrollbar w-full min-w-0 max-w-full py-1 pr-6">
        <button onclick="switchTab('overview')" id="tabBtn-overview" class="tab-btn active-tab shrink-0 whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-amber-500 text-slate-950 shadow-gold-sm">
            <i class="fa-solid fa-chart-line"></i> <span>Ringkasan & Antrean</span>
        </button>
        <button onclick="switchTab('barbers')" id="tabBtn-barbers" class="tab-btn shrink-0 whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent">
            <i class="fa-solid fa-users text-amber-400"></i> <span>Status Barber</span>
        </button>
        <button onclick="switchTab('analytics')" id="tabBtn-analytics" class="tab-btn shrink-0 whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent">
            <i class="fa-solid fa-chart-pie text-amber-400"></i> <span>Analisis & Grafik</span>
        </button>
        <button onclick="switchTab('services')" id="tabBtn-services" class="tab-btn shrink-0 whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent">
            <i class="fa-solid fa-tags text-amber-400"></i> <span>Layanan & Tarif</span>
        </button>
        <button onclick="openScheduleModal()" id="btn-schedule" class="shrink-0 whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent">
            <i class="fa-solid fa-calendar-days text-amber-400"></i> <span>Jadwal Layanan</span>
        </button>
    </nav>

    <!-- Profile Admin -->
    <div class="flex items-center justify-end gap-3 shrink-0 border-t md:border-t-0 border-slate-800/60 pt-2 md:pt-0">
        <div onclick="openProfileModal()" class="flex items-center gap-2.5 cursor-pointer hover:opacity-80 transition group">
            <img id="header_admin_avatar" 
                 src="<?= session()->get('avatar') ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" 
                 alt="Admin Avatar" 
                 class="w-8 h-8 rounded-full border border-amber-500/60 object-cover group-hover:border-amber-400 transition" 
                 onerror="this.src='https://placehold.co/80x80/1e293b/f59e0b?text=Admin'">
            
            <div class="hidden lg:block text-left">
                <span class="admin-name-text text-xs font-bold text-white block leading-tight group-hover:text-amber-400 transition">
                    <?= session()->get('name') ?? 'Administrator'; ?>
                </span>
                <span class="text-[10px] text-amber-400 font-semibold block">Super Admin / Barber Manager</span>
            </div>
        </div>
    </div>
</div>

        <!-- MAIN CONTENT AREA -->
        <div class="p-4 md:p-6 overflow-y-auto no-scrollbar flex-1 space-y-6">

            <!-- KPI SUMMARY CARDS (ALWAYS VISIBLE OR TOP OF OVERVIEW) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                
                
                <!-- KPI 1: Total Pendapatan -->
<div class="glass-card p-4 rounded-2xl border border-slate-800 hover:border-amber-500/40 transition relative overflow-hidden group">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-slate-400">
            Pendapatan <?= ($selectedDate === date('Y-m-d')) ? 'Hari Ini' : date('d M Y', strtotime($selectedDate)); ?>
        </span>
        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-xs">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>
    <div class="flex items-baseline justify-between">
        <h3 class="text-xl font-extrabold text-white">
            Rp <?= number_format($todayRevenue ?? 0, 0, ',', '.'); ?>
        </h3>
    </div>
    <div class="flex items-center gap-1 text-[11px] text-emerald-400 font-semibold mt-2">
        <i class="fa-solid fa-circle-check text-[10px]"></i>
        <span>Transaksi Terbayar</span>
    </div>
</div>

                <!-- KPI 2: Total Booking Hari Ini -->
<div onclick="openTotalBookingModal()" class="glass-card p-4 rounded-2xl border border-slate-800 hover:border-amber-500/60 transition cursor-pointer relative overflow-hidden group">
    
    <!-- Top Header & Icon -->
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-slate-400 group-hover:text-amber-400 transition flex items-center gap-1.5">
            <span>Total Booking</span>
            <?php if (!empty($pendingOnlineCount) && $pendingOnlineCount > 0): ?>
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping" title="<?= $pendingOnlineCount; ?> booking butuh persetujuan"></span>
            <?php endif; ?>
        </span>
        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-xs group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
    </div>

    <!-- Main Value & Date Badge -->
    <div class="flex items-baseline justify-between">
        <h3 class="text-2xl font-extrabold text-white group-hover:text-amber-400 transition" id="kpi-total-bookings">
            <?= number_format($totalBookings ?? 0); ?>
        </h3>
        <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
            <?= ($selectedDate == date('Y-m-d')) ? 'Hari Ini' : date('d/m/Y', strtotime($selectedDate)); ?>
        </span>
    </div>

    <!-- Breakdown Online & Walk-in -->
    <div class="text-[11px] text-slate-400 mt-2 flex items-center justify-between border-t border-slate-800/60 pt-2">
        <div class="flex items-center gap-1.5">
            <span class="text-emerald-400 font-semibold flex items-center gap-1" id="kpi-online-count">
                <i class="fa-solid fa-globe text-[10px]"></i>
                <?= $onlineBookings ?? 0; ?> Online
            </span>
            <span>•</span>
            <span class="text-sky-400 font-semibold flex items-center gap-1" id="kpi-walkin-count">
                <i class="fa-solid fa-person-walking text-[10px]"></i>
                <?= $walkInBookings ?? 0; ?> Walk-in
            </span>
        </div>
        <span class="text-[10px] text-slate-500 group-hover:text-amber-400 transition">
            Detail <i class="fa-solid fa-chevron-right text-[8px] ms-0.5"></i>
        </span>
    </div>

</div>

                <!-- KPI 3: Antrean Menunggu -->
                <div class="glass-card p-4 rounded-2xl border border-slate-800 hover:border-amber-500/40 transition relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-400">Antrean Menunggu</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-xs">
                            <i class="fa-solid fa-clock font-bold"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <!-- Antrean Menunggu -->
<h3><?= $waitingQueue ?? 0; ?></h3>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2">Est. waktu tunggu: ~25 mnt</p>
                </div>

                <!-- KPI 4: Barber Active / On Duty -->
<div class="glass-card p-4 rounded-2xl border border-slate-800 hover:border-amber-500/40 transition relative overflow-hidden">

    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-slate-400">
            Barber Stand by
        </span>

        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-xs">
            <i class="fa-solid fa-user-scissors"></i>
        </div>
    </div>

    <div class="flex items-baseline justify-between">
        <!-- Jumlah Barber On Duty -->
        <h3 id="kpiActiveBarbers">
            <?= $activeBarbers; ?>
        </h3>
    </div>

    <!-- Jumlah Barber Istirahat -->
    <p class="text-[11px] text-slate-400 mt-2">
        <span id="kpiBarberBreak"><?= $breakBarbers; ?></span>
        Barber sedang istirahat
    </p>

</div>

            </div>

            <!-- TAB 1: OVERVIEW & ANTREAN MANAGEMENT (DEFAULT) -->
            <div id="view-overview" class="space-y-6">
                
                <!-- QUEUE MANAGEMENT SECTION -->
                <div class="glass-card rounded-2xl p-4 md:p-5 border border-slate-800">
                    <!-- Filter and Search Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm md:text-base font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-list-check text-amber-400"></i> Kelola Antrean & Jadwal Pelanggan
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui status pengerjaan atau konfirmasi pembayaran pelanggan</p>
                        </div>
<!-- Header & Filter Tanggal -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
    <div>
        <h3 class="text-base font-bold text-white">Kelola Antrean & Jadwal Pelanggan</h3>
        <p class="text-xs text-slate-400">
            Menampilkan antrean tanggal: <span class="text-amber-400 font-bold"><?= date('d M Y', strtotime($selectedDate)); ?></span>
        </p>
    </div>

    <!-- Form Filter Tanggal / Hari -->
    <form method="GET" action="<?= base_url('admin/dashboard'); ?>" class="flex items-center gap-2">
        <div class="relative flex items-center">
            <i class="fa-solid fa-calendar text-slate-400 absolute left-3 text-xs pointer-events-none"></i>
            <input type="date" name="date" value="<?= esc($selectedDate); ?>" onchange="this.form.submit()" 
                   class="bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500 cursor-pointer transition">
        </div>

        <?php if ($selectedDate !== date('Y-m-d')): ?>
            <!-- Tombol reset cepat kembali ke Hari Ini jika sedang melihat tanggal lain -->
            <a href="<?= base_url('admin/dashboard'); ?>" class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold transition flex items-center gap-1">
                <i class="fa-solid fa-rotate-left"></i> Hari Ini
            </a>
        <?php endif; ?>
    </form>
</div>
                        <!-- Status Filter Buttons -->
                        

<!-- Tombol Navigation Filter dengan Angka Dinamis -->
<div class="flex items-center gap-1.5 bg-slate-900 p-1 rounded-xl border border-slate-800 overflow-x-auto w-full sm:w-auto">
    <button type="button" onclick="filterQueue('all', this)" class="q-filter active-q px-3 py-1 rounded-lg text-xs font-bold bg-amber-500 text-slate-950 transition">
        Semua (<span id="countAll"><?= $cAll; ?></span>)
    </button>
    <button type="button" onclick="filterQueue('waiting', this)" class="q-filter px-3 py-1 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition">
        Menunggu (<span id="countMenunggu"><?= $cWaiting; ?></span>)
    </button>
    <button type="button" onclick="filterQueue('in_progress', this)" class="q-filter px-3 py-1 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition">
        Diproses (<span id="countDiproses"><?= $cProgress; ?></span>)
    </button>
    <button type="button" onclick="filterQueue('completed', this)" class="q-filter px-3 py-1 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition">
        Selesai (<span id="countSelesai"><?= $cCompleted; ?></span>)
    </button>
</div>
                    </div>

                    <!-- APPOINTMENT QUEUE TABLE -->
                    <div class="overflow-x-auto">
    <table class="w-full text-left text-xs border-collapse">
        <thead>
            <tr class="text-slate-400 border-b border-slate-800 text-[11px] uppercase tracking-wider">
                <th class="py-3 px-3">No. Tiket</th>
                <th class="py-3 px-3">Pelanggan</th>
                <th class="py-3 px-3">Barber</th>
                <th class="py-3 px-3">Layanan</th>
                <th class="py-3 px-3">Jam Slot</th>
                <th class="py-3 px-3">Total</th>
                <th class="py-3 px-3">Status</th>
                <th class="py-3 px-3 text-center">Aksi Status</th>
            </tr>
        </thead>
        <tbody id="queueTableBody" class="divide-y divide-slate-800/60">
    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $row): ?>
            <?php 
                // Normalisasi status DB ke format JS
                $st = strtolower($row['status'] ?? 'waiting');
                if (in_array($st, ['in_progress', 'diproses', 'sedang_cukur'])) {
                    $statusJS = 'diproses';
                } elseif (in_array($st, ['completed', 'selesai'])) {
                    $statusJS = 'selesai';
                } else {
                    $statusJS = 'menunggu';
                }
            ?>
            <tr data-status="<?= $statusJS; ?>" class="hover:bg-slate-800/40 transition">
                <td class="py-3 px-3 font-mono font-bold text-amber-400">
                    #<?= str_pad($row['queue_number'] ?? $row['id'], 3, '0', STR_PAD_LEFT); ?>
                </td>
                <td class="py-3 px-3">
                    <span class="font-bold text-white block"><?= esc($row['customer_name']); ?></span>
                    <span class="text-[10px] text-slate-400"><?= esc($row['customer_phone']); ?></span>
                </td>
                <td class="py-3 px-3 text-slate-300 font-medium"><?= esc($row['barber_name']); ?></td>
                <td class="py-3 px-3 text-slate-300 font-medium"><?= esc($row['service_name']); ?></td>
                <td class="py-3 px-3 font-semibold text-slate-200"><?= date('H:i', strtotime($row['booking_time'])); ?> WIB</td>
                <td class="py-3 px-3 font-bold text-white">Rp <?= number_format($row['price'], 0, ',', '.'); ?></td>
                
                <!-- Status Badge -->
                <td class="py-3 px-3">
                    <?php if ($statusJS === 'selesai'): ?>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">SELESAI</span>
                    <?php elseif ($statusJS === 'diproses'): ?>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">Sedang Cukur</span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Menunggu</span>
                    <?php endif; ?>
                </td>

                <!-- Tombol Aksi (Telah Diisi) -->
                <td class="py-3 px-3 text-center">
                    <?php if ($statusJS === 'menunggu'): ?>
                        <button onclick="updateQueueStatus('<?= $row['id']; ?>', 'diproses')" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg transition text-[11px]">
                            <i class="fa-solid fa-scissors mr-1"></i> Mulai
                        </button>
                        <button onclick="updateQueueStatus('<?= $row['id']; ?>', 'batal')" class="px-2 py-1 bg-slate-800 hover:bg-rose-950 text-rose-400 font-bold rounded-lg transition text-[11px] ml-1">
                            Batal
                        </button>
                    <?php elseif ($statusJS === 'diproses'): ?>
                        <button onclick="updateQueueStatus('<?= $row['id']; ?>', 'selesai')" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-lg transition text-[11px]">
                            <i class="fa-solid fa-check mr-1"></i> Selesai & Bayar
                        </button>
                    <?php else: ?>
                        <span class="text-slate-500 text-[11px]"><i class="fa-solid fa-check-double text-emerald-400"></i> Lunas</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="py-8 text-center text-slate-500 font-medium">Belum ada antrean untuk hari ini.</td>
        </tr>
    <?php endif; ?>
</tbody>
    </table>
</div>
                </div>

            </div>

            <!-- TAB 2: STATUS BARBER / CAPSTER -->
            <div id="view-barbers" class="space-y-6 hidden">
                <div class="glass-card rounded-2xl p-4 md:p-5 border border-slate-800">
                    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm md:text-base font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-user-gear text-amber-400"></i> Roster & Status Barber (Capster)
                            </h3>
                            <p class="text-xs text-slate-400">Atur ketersediaan tugas dan pantau omset masing-masing barber</p>
                        </div>
                    </div>

                    <div id="barberGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Dynamic Barber Cards rendered via JS -->
                    </div>
                </div>
            </div>

            <!-- TAB 3: GRAFIK ANALYTICS & JAM RAMAI -->
            <div id="view-analytics" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Chart 1: Jam Ramai Antrean (Traffic Hours) -->
                    <div class="glass-card rounded-2xl p-4 md:p-5 border border-slate-800">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-3">
                            <div>
                                <h3 class="text-xs md:text-sm font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-column text-amber-400"></i> Analisis Jam Ramai (Peak Hours)
                                </h3>
                                <p class="text-[11px] text-slate-400">Tingkat kepadatan pengunjung per jam hari ini</p>
                            </div>
                        </div>

                        <!-- Bar Chart Graphic (SVG) -->
                        <div class="h-52 w-full pt-4">
                            <svg class="w-full h-full overflow-visible" viewBox="0 0 400 160">
                                <!-- Grid Lines -->
                                <line x1="30" y1="20" x2="390" y2="20" stroke="#1e2638" stroke-dasharray="3" />
                                <line x1="30" y1="60" x2="390" y2="60" stroke="#1e2638" stroke-dasharray="3" />
                                <line x1="30" y1="100" x2="390" y2="100" stroke="#1e2638" stroke-dasharray="3" />
                                <line x1="30" y1="140" x2="390" y2="140" stroke="#1e2638" />

                                <!-- Bars & Labels -->
                                <!-- 10:00 -->
                                <rect x="45" y="90" width="22" height="50" rx="4" fill="#334155"/>
                                <text x="56" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">10:00</text>

                                <!-- 12:00 -->
                                <rect x="95" y="70" width="22" height="70" rx="4" fill="#d97706"/>
                                <text x="106" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">12:00</text>

                                <!-- 14:00 -->
                                <rect x="145" y="50" width="22" height="90" rx="4" fill="#f59e0b"/>
                                <text x="156" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">14:00</text>

                                <!-- 16:00 (PEAK) -->
                                <rect x="195" y="25" width="22" height="115" rx="4" fill="#fbbf24"/>
                                <text x="206" y="155" font-size="9" fill="#fbbf24" font-weight="bold" text-anchor="middle">16:00 ★</text>

                                <!-- 18:00 -->
                                <rect x="245" y="40" width="22" height="100" rx="4" fill="#f59e0b"/>
                                <text x="256" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">18:00</text>

                                <!-- 20:00 -->
                                <rect x="295" y="80" width="22" height="60" rx="4" fill="#334155"/>
                                <text x="306" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">20:00</text>

                                <!-- 21:00 -->
                                <rect x="345" y="110" width="22" height="30" rx="4" fill="#334155"/>
                                <text x="356" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">21:00</text>
                            </svg>
                        </div>
                        <div class="mt-2 text-center text-[11px] text-amber-400 font-medium">
                            <i class="fa-solid fa-circle-info"></i> Jam Tersibuk: 15:00 - 18:00 WIB
                        </div>
                    </div>

                    <!-- Chart 2: Tren Pendapatan 7 Hari Terakhir -->
                    <div class="glass-card rounded-2xl p-4 md:p-5 border border-slate-800">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-3">
                            <div>
                                <h3 class="text-xs md:text-sm font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-chart-line text-amber-400"></i> Tren Omset (7 Hari)
                                </h3>
                                <p class="text-[11px] text-slate-400">Grafik omset harian minggu ini</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md border border-emerald-500/20">
                                Total: Rp 16.8M
                            </span>
                        </div>

                        <!-- Line Chart Graphic (SVG) -->
                        <div class="h-52 w-full pt-4">
                            <svg class="w-full h-full overflow-visible" viewBox="0 0 400 160">
                                <path d="M 30,120 Q 90,90 140,100 T 250,50 T 370,30" fill="none" stroke="#f59e0b" stroke-width="3" />
                                <path d="M 30,120 Q 90,90 140,100 T 250,50 T 370,30 L 370,140 L 30,140 Z" fill="url(#amberGradient)" opacity="0.25" />

                                <defs>
                                    <linearGradient id="amberGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#f59e0b"/>
                                        <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>

                                <!-- Dots -->
                                <circle cx="30" cy="120" r="4" fill="#fbbf24"/>
                                <text x="30" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Sen</text>

                                <circle cx="86" cy="100" r="4" fill="#fbbf24"/>
                                <text x="86" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Sel</text>

                                <circle cx="142" cy="100" r="4" fill="#fbbf24"/>
                                <text x="142" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Rab</text>

                                <circle cx="198" cy="75" r="4" fill="#fbbf24"/>
                                <text x="198" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Kam</text>

                                <circle cx="254" cy="50" r="4" fill="#fbbf24"/>
                                <text x="254" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Jum</text>

                                <circle cx="310" cy="40" r="4" fill="#fbbf24"/>
                                <text x="310" y="155" font-size="9" fill="#94a3b8" text-anchor="middle">Sab</text>

                                <circle cx="370" cy="30" r="5" fill="#f59e0b" stroke="#ffffff" stroke-width="2"/>
                                <text x="370" y="155" font-size="9" fill="#fbbf24" font-weight="bold" text-anchor="middle">Ming</text>
                            </svg>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 4: LAYANAN & TARIF MANAGEMENT -->
            <div id="view-services" class="space-y-6 hidden">
                <div class="glass-card rounded-2xl p-4 md:p-5 border border-slate-800">
                    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm md:text-base font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-scissors text-amber-400"></i> Katalog Layanan & Tarif
                            </h3>
                            <p class="text-xs text-slate-400">Kelola harga, durasi pengerjaan, dan ketersediaan menu salon</p>
                        </div>
                        <button onclick="openServiceModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-amber-500/20">
    + Tambah Layanan
</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="serviceCatalogGrid">
                        <!-- Services Rendered dynamically via JS -->
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT LAYANAN -->
<div id="serviceModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <!-- Header Modal -->
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 id="serviceModalTitle" class="text-base font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-scissors text-amber-400"></i> Tambah Layanan Baru
            </h3>
            <button onclick="closeServiceModal()" class="text-slate-400 hover:text-white text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form Layanan -->
        <form id="serviceForm" onsubmit="handleSaveService(event)" class="p-4 space-y-4">
            <input type="hidden" id="service_id" name="id">

            <!-- Nama Layanan -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Layanan</label>
                <input type="text" id="service_name" name="name" required placeholder="Contoh: Haircut & Styling"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- Harga & Durasi -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Harga (Rp)</label>
                    <input type="number" id="service_price" name="price" required placeholder="50000"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Durasi (Menit)</label>
                    <input type="text" id="service_duration" name="duration" required placeholder="45 mnt"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <!-- Icon & Status -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Icon (FontAwesome)</label>
                    <input type="text" id="service_icon" name="icon" placeholder="fa-scissors" value="fa-scissors"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Status Ketersediaan</label>
                    <select id="service_status" name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" onclick="closeServiceModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs shadow-md transition">
                    Simpan Layanan
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Profil Admin -->
<div id="adminProfileModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-all">
    <div class="bg-slate-900 border border-amber-500/30 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 modal-content">
        
        <!-- Modal Header / Cover -->
        <div class="h-24 bg-gradient-to-r from-amber-600 to-amber-800 relative p-4 flex justify-end">
            <button onclick="closeProfileModal()" class="w-8 h-8 rounded-full bg-slate-950/50 hover:bg-slate-950 text-slate-300 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body Profil -->
        <div class="px-6 pb-6 relative text-center">
            <!-- Foto Profil -->
            <div class="relative -mt-12 mb-3 inline-block">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80" 
                     alt="Admin Avatar" 
                     class="w-20 h-20 rounded-full border-4 border-slate-900 object-cover mx-auto shadow-lg">
                <span class="w-4 h-4 bg-emerald-500 border-2 border-slate-900 rounded-full absolute bottom-1 right-1" title="Online"></span>
            </div>

            <!-- Detail Info -->
            <h4 class="admin-name-text text-lg font-extrabold text-white">
    <?= session()->get('name') ?? 'Administrator'; ?>
</h4>
            <p class="text-xs text-amber-400 font-medium mb-1">Super Admin / Barber Manager</p>
            <p class="admin-email-text text-xs text-slate-400 mb-4">
    <?= session()->get('email') ?? 'admin@barbershop.com'; ?>
</p>
            <p class="text-xs text-slate-400 mb-4">admin@barbershop.com</p>

            <div class="grid grid-cols-2 gap-2 bg-slate-800/60 p-3 rounded-xl border border-slate-800 text-left text-xs mb-5">
                <div>
                    <span class="text-slate-400 block text-[10px]">ID PENGGUNA</span>
                    <span class="font-bold text-white">#ADM-001</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px]">TERAKHIR LOGIN</span>
                    <span class="font-bold text-white">Hari ini, 19:00</span>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-2">
                <button onclick="openEditProfileModal()" class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center justify-center gap-2 border border-slate-700">
    <i class="fa-solid fa-pen-to-square"></i> Edit Profil
</button>
                <a href="<?= base_url('logout'); ?>" class="flex-1 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            </div>
        </div>

    </div>
</div>
<!-- Modal Edit Profil Admin -->
<div id="adminEditProfileModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-all">
    <div class="bg-slate-900 border border-amber-500/30 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 modal-content">
        
        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i> Edit Profil Admin
            </h3>
            <button onclick="closeEditProfileModal()" class="text-slate-400 hover:text-white transition text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form Edit (Ditambahkan enctype multipart untuk upload file) -->
        <form id="formEditProfile" onsubmit="submitEditProfile(event)" enctype="multipart/form-data" class="p-6 space-y-4">
            
            <!-- SECTION EDIT FOTO PROFIL -->
            <div class="flex flex-col items-center justify-center pb-2">
                <div class="relative group cursor-pointer" onclick="document.getElementById('edit_avatar').click()" title="Klik untuk mengganti foto">
                    <!-- Preview Foto -->
                    <img id="avatar_preview" 
                         src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80" 
                         alt="Admin Avatar" 
                         class="w-20 h-20 rounded-full border-2 border-amber-500/80 object-cover shadow-md group-hover:opacity-75 transition">
                    
                    <!-- Overlay Ikon Kamera Saat Hover -->
                    <div class="absolute inset-0 bg-slate-950/70 rounded-full opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center transition text-amber-400">
                        <i class="fa-solid fa-camera text-base"></i>
                        <span class="text-[9px] font-bold text-white mt-0.5">Ubah</span>
                    </div>
                </div>
                <span class="text-[10px] text-slate-400 mt-1">Klik gambar untuk memilih foto (PNG, JPG, Max 2MB)</span>
                
                <!-- Input File Tersembunyi -->
                <input type="file" id="edit_avatar" name="avatar" accept="image/png, image/jpeg, image/jpg" class="hidden" onchange="previewAvatar(event)">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap</label>
                <input type="text" id="edit_name" name="name" value="Administrator" required 
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Alamat Email</label>
                <input type="email" id="edit_email" name="email" value="admin@barbershop.com" required 
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Password Baru <span class="text-[10px] text-slate-500">(Kosongkan jika tidak diubah)</span></label>
                <input type="password" 
       id="edit_password" 
       name="password" 
       placeholder="••••••••" 
       autocomplete="new-password"
       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition">
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeEditProfileModal()" class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="btnSaveProfile" class="flex-1 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>

    </div>
</div>

    <!-- MODAL: TAMBAH PELANGGAN WALK-IN -->
    <div id="walkInModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
    <div class="w-full max-w-md bg-slate-900 border border-amber-500/30 rounded-3xl p-5 shadow-2xl space-y-4">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-amber-400"></i> Tambah Booking Walk-in
            </h3>
            <button type="button" onclick="closeWalkInModal()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="walkInForm" onsubmit="handleWalkInSubmit(event)" class="space-y-3 text-xs">
            <!-- 1. Nama Pelanggan -->
            <div>
                <label class="font-semibold text-slate-300 block mb-1">Nama Pelanggan <span class="text-rose-400">*</span></label>
                <input type="text" id="walkInName" required placeholder="Contoh: Budi Santoso" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- 2. No. WhatsApp / HP (Opsional) -->
            <div>
                <label class="font-semibold text-slate-300 block mb-1">No. WhatsApp / HP <span class="text-slate-500 font-normal">(Opsional)</span></label>
                <input type="tel" id="walkInPhone" placeholder="08123456789" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- 3. Barber Dinamis dari DB -->
                <div>
                    <label class="font-semibold text-slate-300 block mb-1">Pilih Barber <span class="text-rose-400">*</span></label>
                    <select id="walkInBarber" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Pilih Barber --</option>
                        <?php if (!empty($barbers)): ?>
                            <?php foreach ($barbers as $b): ?>
                                <option value="<?= $b['id']; ?>"><?= esc($b['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 4. Jam Slot Dinamis dari DB (schedules) -->
                <div>
    <label class="font-semibold text-slate-300 block mb-1">Waktu Slot <span class="text-rose-400">*</span></label>
    <select id="walkInTime" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
        <option value="<?= date('H:i'); ?>">Sekarang (Direct)</option>
        <?php if (!empty($schedules)): ?>
            <?php foreach ($schedules as $sch): ?>
                <?php 
                    // Otomatis mendeteksi nama kolom jam dari database (start_time, jam, time, waktu, dll)
                    $rawTime = $sch['start_time'] ?? $sch['jam'] ?? $sch['time'] ?? $sch['waktu'] ?? $sch['slot_time'] ?? null;
                ?>
                <?php if ($rawTime): ?>
                    <option value="<?= date('H:i', strtotime($rawTime)); ?>">
                        <?= date('H:i', strtotime($rawTime)); ?> WIB
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>
            </div>

            <!-- 5. Layanan Dinamis dari DB -->
            <div>
    <label class="font-semibold text-slate-300 block mb-1">Layanan <span class="text-rose-400">*</span></label>
    <select id="walkInService" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
        <option value="">-- Pilih Layanan --</option>
        <?php if (!empty($services)): ?>
            <?php foreach ($services as $s): ?>
                <?php 
                    // Otomatis deteksi nama kolom (name, service_name, nama_layanan, dll)
                    $sName  = $s['name'] ?? $s['service_name'] ?? $s['nama_layanan'] ?? $s['nama'] ?? 'Layanan';
                    $sPrice = $s['price'] ?? $s['harga'] ?? $s['tarif'] ?? 0;
                ?>
                <option value="<?= $s['id']; ?>">
                    <?= esc($sName); ?> - Rp <?= number_format($sPrice, 0, ',', '.'); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>

            <!-- 6. Metode Pembayaran -->
            <div>
                <label class="font-semibold text-slate-300 block mb-1">Metode Pembayaran</label>
                <select id="walkInPayment" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                    <option value="cash">Tunai (Cash)</option>
                    <option value="qris">QRIS / E-Wallet</option>
                    <option value="edc">Mesin EDC Debit</option>
                </select>
            </div>

            <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold rounded-xl transition shadow-gold-sm mt-2">
                Simpan & Tambahkan ke Antrean
            </button>
        </form>
    </div>
</div>

    <!-- NOTIFICATION POPUP MODAL FOR ADMIN -->
    <div id="adminNotifModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
        <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-2xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <h3 class="text-xs font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-400"></i> Notifikasi Sistem Admin
                </h3>
                <button onclick="closeAdminNotifications()" class="text-slate-400 hover:text-white text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-2 text-xs">
                <div class="p-2.5 bg-slate-950 rounded-xl border border-amber-500/20">
                    <p class="font-bold text-amber-400">Booking Online Masuk!</p>
                    <p class="text-[11px] text-slate-300 mt-0.5">Pelanggan #A-07 (Dicky) memesan slot 19:30 bersama Barber Roy.</p>
                </div>
                <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800">
                    <p class="font-bold text-white">Budi Stylist Mengubah Status</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Status berubah ke: Istirahat (Break 30 mnt).</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BUAT AKUN BARU -->
    <div id="createAccountModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
        <div class="w-full max-w-md bg-slate-900 border border-amber-500/30 rounded-2xl p-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-amber-400"></i> Buat Akun Baru
                </h3>
                <button type="button" onclick="closeCreateAccountModal()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="createAccountForm" action="<?= base_url('admin/users/create'); ?>" method="post" class="space-y-3 text-xs">
                <?= csrf_field(); ?>
                <div>
                    <label for="new_account_name" class="font-semibold text-slate-300 block mb-1">Nama Lengkap</label>
                    <input id="new_account_name" name="name" type="text" required autocomplete="name" placeholder="Masukkan nama lengkap"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="new_account_phone" class="font-semibold text-slate-300 block mb-1">Nomor HP</label>
                    <input id="new_account_phone" name="phone" type="tel" maxlength="20" autocomplete="tel" placeholder="Contoh: 081234567890"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="new_account_email" class="font-semibold text-slate-300 block mb-1">Alamat Email</label>
                    <input id="new_account_email" name="email" type="email" required autocomplete="email" placeholder="nama@email.com"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="new_account_password" class="font-semibold text-slate-300 block mb-1">Password</label>
                    <input id="new_account_password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="new_account_password_confirm" class="font-semibold text-slate-300 block mb-1">Konfirmasi Password</label>
                    <input id="new_account_password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password" placeholder="Ulangi password"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeCreateAccountModal()" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

<!-- Modal Jadwal Layanan & Jam Operasional -->
<div id="scheduleModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-all">
    <div class="bg-slate-900 border border-amber-500/30 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 modal-content flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-950/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-calendar-days text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Jadwal Operasional Layanan</h3>
                    <p class="text-[11px] text-slate-400">Atur jam operasional mingguan dan interval slot booking</p>
                </div>
            </div>
            <button onclick="closeScheduleModal()" class="text-slate-400 hover:text-white transition text-sm w-8 h-8 rounded-xl hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form Setting Jadwal -->
        <form id="formSchedule" onsubmit="submitSchedule(event)" class="p-6 overflow-y-auto space-y-5 flex-1 custom-scrollbar">
            
            <!-- Information Box -->
            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-300 flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 text-sm"></i>
                <span>Jadwal ini secara otomatis membatasi jam pilihan booking pelanggan di aplikasi mobile barber shop.</span>
            </div>

            <!-- Set Jam Operasional Harian -->
            <div>
                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-amber-400"></i> Jam Operasional Harian
                </h4>
                
                <div class="space-y-2.5">
                    <?php 
                    $days = [
                        'senin' => 'Senin',
                        'selasa' => 'Selasa',
                        'rabu' => 'Rabu',
                        'kamis' => 'Kamis',
                        'jumat' => 'Jumat',
                        'sabtu' => 'Sabtu',
                        'minggu' => 'Minggu'
                    ];
                    foreach ($days as $key => $day): 
                    ?>
                    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 gap-3">
                        <div class="flex items-center gap-3 w-28">
                            <input type="checkbox" id="day_<?= $key ?>" name="schedule[<?= $key ?>][active]" value="1" checked class="w-4 h-4 rounded border-slate-700 text-amber-500 focus:ring-amber-500 bg-slate-900 cursor-pointer">
                            <label for="day_<?= $key ?>" class="text-xs font-bold text-white cursor-pointer"><?= $day ?></label>
                        </div>

                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <div class="flex items-center gap-1.5 bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5">
                                <span class="text-[10px] text-slate-500 uppercase font-semibold">Buka:</span>
                                <input type="time" name="schedule[<?= $key ?>][open]" value="09:00" class="bg-transparent text-xs text-amber-400 font-bold focus:outline-none">
                            </div>
                            <span class="text-slate-600 text-xs">-</span>
                            <div class="flex items-center gap-1.5 bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5">
                                <span class="text-[10px] text-slate-500 uppercase font-semibold">Tutup:</span>
                                <input type="time" name="schedule[<?= $key ?>][close]" value="21:00" class="bg-transparent text-xs text-amber-400 font-bold focus:outline-none">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Durasi & Limit Antrean -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Durasi Default Per Slot (Menit)</label>
                    <select name="slot_duration" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="30">30 Menit</option>
                        <option value="45" selected>45 Menit</option>
                        <option value="60">60 Menit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Maksimal Antrean Per Jam Slot</label>
                    <input type="number" name="max_quota" value="4" min="1" max="10" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition">
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeScheduleModal()" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="btnSaveSchedule" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold transition shadow-gold-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Jadwal
                </button>
            </div>

        </form>
    </div>
</div>


<!-- MODAL PENGATURAN BARBER -->
<div id="settingsModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-gear text-amber-400"></i> Pengaturan Barbershop
            </h3>
            <button onclick="closeSettingsModal()" class="text-slate-400 hover:text-white text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="settingsForm" onsubmit="handleSaveSettings(event)" enctype="multipart/form-data" class="p-4 space-y-4">
            <!-- Input Upload File Logo -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Upload File Logo (PNG / JPG)</label>
                <input type="file" id="setting_logo" name="logo" accept="image/*"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-amber-500 file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600 cursor-pointer">
            </div>

            <!-- Nama Barber -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Barbershop</label>
                <input type="text" id="setting_name" name="name" required placeholder="Contoh: DR.BARBER"
                       value="<?= esc($settings['name'] ?? 'DR.BARBER'); ?>"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- Alamat Barber -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Alamat Lengkap</label>
                <textarea id="setting_address" name="address" rows="2" placeholder="Jl. Sudirman No. 123..."
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"><?= esc($settings['address'] ?? ''); ?></textarea>
            </div>

            <!-- No. HP -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">No. HP / WhatsApp</label>
                <input type="text" id="setting_phone" name="phone" placeholder="081234567890"
                       value="<?= esc($settings['phone'] ?? ''); ?>"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" onclick="closeSettingsModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs shadow-md transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Detail Total Booking Hari Ini -->
<!-- Modal Booking Online (Staging Table: appointments) -->
<div id="totalBookingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
    <!-- Card Content Modal -->
    <div class="relative w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-10 animate-fade-in">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/50">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-globe text-amber-400"></i>
                    <span>Daftar Request Booking Online</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Permintaan reservasi masuk yang membutuhkan persetujuan Admin & penentuan Barber</p>
            </div>
            <button onclick="closeTotalBookingModal()" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-5 max-h-[70vh] overflow-y-auto">
            <table class="w-full text-left text-sm text-slate-300">
    <thead class="bg-slate-950 text-xs text-slate-400 uppercase">
        <tr>
            <th class="p-3 rounded-l-xl">Kode</th>
            <th class="p-3">Pelanggan</th>
            <th class="p-3">Layanan</th>
            <th class="p-3">Jadwal</th>
            <th class="p-3">Barber</th>
            <th class="p-3 rounded-r-xl text-center">Status / Aksi</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-800/50">
        <?php if (!empty($pendingAppointments)): ?>
            <?php foreach ($pendingAppointments as $item): ?>
                <?php $status = strtolower($item['status'] ?? 'menunggu'); ?>
                <tr id="row-appointment-<?= $item['id']; ?>" class="hover:bg-slate-800/30 transition">
                    <!-- Kode Booking -->
                    <td class="p-3 font-mono text-xs font-bold text-amber-400">
                        <?= esc($item['booking_code'] ?? 'ONL-'.$item['id']); ?>
                    </td>

                    <!-- Pelanggan -->
                    <td class="p-3">
                        <div class="font-semibold text-white"><?= esc($item['customer_name']); ?></div>
                        <div class="text-xs text-slate-400"><?= esc($item['customer_phone'] ?? '-'); ?></div>
                    </td>

                    <!-- Layanan -->
                    <td class="p-3 text-slate-200">
                        <?= esc($item['service_name'] ?? '-'); ?>
                    </td>

                    <!-- Jadwal -->
                    <td class="p-3 text-xs">
                        <div class="text-slate-200 font-medium"><?= esc($item['booking_date']); ?></div>
                        <div class="text-amber-400/90 font-mono"><?= esc($item['booking_time']); ?> WIB</div>
                    </td>

                    <!-- Barber / Dropdown Selection -->
                    <td class="p-3">
                        <?php if (in_array($status, ['menunggu', 'pending', 'waiting'])): ?>
                            <select id="barber-select-<?= $item['id']; ?>" class="bg-slate-950 border border-slate-800 text-xs text-white rounded-xl p-2 focus:outline-none focus:border-amber-500 w-full">
                                <option value="">-- Pilih Barber --</option>
                                <?php if (!empty($barbers)): ?>
                                    <?php foreach ($barbers as $barber): ?>
                                        <option value="<?= $barber['id']; ?>"><?= esc($barber['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        <?php else: ?>
                            <span class="text-xs font-semibold text-slate-200">
                                <?= esc($item['barber_name'] ?? '-'); ?>
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Status & Aksi -->
                    <td class="p-3 text-center">
                        <?php if (in_array($status, ['menunggu', 'pending', 'waiting'])): ?>
                            <!-- Jika Masih Pending: Tampilkan Tombol Terima & Tolak -->
                            <div class="flex items-center justify-center gap-1.5">
                                <button onclick="approveBooking(<?= $item['id']; ?>)" class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs shadow-md transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Terima</span>
                                </button>
                                <button onclick="cancelBookingWA(<?= $item['id']; ?>, '<?= esc($item['customer_phone'] ?? ''); ?>', '<?= esc(addslashes($item['customer_name'])); ?>', '<?= esc($item['booking_code'] ?? 'ONL-'.$item['id']); ?>', '<?= esc($item['booking_time']); ?>')" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 font-bold rounded-xl text-xs transition inline-flex items-center gap-1" title="Tolak via WA">
                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                    <span>Tolak</span>
                                </button>
                            </div>
                        <?php elseif ($status === 'diterima'): ?>
                            <!-- Jika Sudah Diterima -->
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-[10px]"></i> Diterima
                            </span>
                        <?php elseif ($status === 'ditolak'): ?>
                            <!-- Jika Ditolak -->
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-xmark text-[10px]"></i> Ditolak
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="p-8 text-center text-slate-500">
                    <i class="fa-solid fa-inbox text-3xl mb-2 block text-slate-600"></i>
                    Belum ada data permintaan booking online.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
        </div>

        <!-- Footer Modal -->
        <div class="p-4 border-t border-slate-800 bg-slate-900/50 flex justify-end">
            <button onclick="closeTotalBookingModal()" class="px-4 py-2 bg-slate-800 text-slate-300 hover:bg-slate-700 rounded-xl text-sm font-semibold transition">
                Tutup
            </button>
        </div>

    </div>
</div>

<!-- SUB-MODAL: TERIMA & PILIH BARBER -->
<div id="assignBarberModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4 transition-all">
    <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl p-5 text-left">
        <h4 class="text-sm font-bold text-white mb-1 flex items-center gap-2">
            <i class="fa-solid fa-user-check text-emerald-400"></i> Konfirmasi & Pilih Barber
        </h4>
        <p class="text-xs text-slate-400 mb-4">Tentukan kapster yang akan melayani pelanggan <span id="accept_customer_name" class="font-bold text-white"></span></p>

        <form id="formAcceptBooking" onsubmit="submitAcceptBooking(event)">
            <input type="hidden" id="accept_appointment_id" name="appointment_id">
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih Barber Bertugas</label>
                <select id="accept_barber_id" name="barber_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                    <!-- Option Barber akan diisi otomatis via JS -->
                </select>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="closeAssignBarberModal()" class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="btnConfirmAccept" class="flex-1 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-extrabold transition">
                    Proses Booking
                </button>
            </div>
        </form>
    </div>
</div>
    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 pointer-events-none flex flex-col gap-2 max-w-xs"></div>

    <!-- JAVASCRIPT LOGIC ENGINE -->
    <script>
        // INITIAL STATE & DATA STORES
        let isMobileView = false;
       window.activeFilter = window.activeFilter || 'all';

        // MODAL BUAT AKUN
        function openCreateAccountModal() {
            const modal = document.getElementById('createAccountModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCreateAccountModal() {
            const modal = document.getElementById('createAccountModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('createAccountModal');
            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) closeCreateAccountModal();
                });
            }

            const form = document.getElementById('createAccountForm');
            if (form) {
                form.addEventListener('submit', function (event) {
                    const password = document.getElementById('new_account_password').value;
                    const confirmation = document.getElementById('new_account_password_confirm').value;
                    if (password !== confirmation) {
                        event.preventDefault();
                        alert('Konfirmasi password tidak sama.');
                    }
                });
            }
        });

       // Variable filter aktif default
window.activeFilter = window.activeFilter || 'all';

let queueData = <?= json_encode(
    array_map(function($row) {
        $st = strtolower($row['status'] ?? 'waiting');
        if (in_array($st, ['in_progress', 'diproses', 'sedang_cukur'])) {
            $statusJS = 'diproses';
        } elseif (in_array($st, ['completed', 'selesai'])) {
            $statusJS = 'selesai';
        } elseif (in_array($st, ['cancelled', 'batal'])) {
            $statusJS = 'batal';
        } else {
            $statusJS = 'menunggu';
        }

        return [
            'id'       => '#' . str_pad($row['queue_number'] ?? $row['id'], 3, '0', STR_PAD_LEFT),
            'db_id'    => (int)$row['id'], // WAJIB: ID Integer Database
            'name'     => $row['customer_name'] ?? 'Pelanggan',
            'phone'    => $row['customer_phone'] ?? '-',
            'barber'   => $row['barber_name'] ?? '-',
            'service'  => $row['service_name'] ?? '-',
            'time'     => !empty($row['booking_time']) ? date('H:i', strtotime($row['booking_time'])) . ' WIB' : '-',
            'price'    => (float)($row['price'] ?? 0),
            'status'   => $statusJS
        ];
    }, $appointments ?? []),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

// Data Barber dari Database (Dinamis)
let barbersData = <?= json_encode(
    $barbers ?? [],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

// Data Layanan (Services) dari Database (Dinamis)
let servicesCatalog = <?= json_encode(
    $services ?? [],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

        // ON LOAD INITIALIZATION
        window.onload = function() {
            startLiveClock();
            renderQueueTable();
            renderBarbersGrid();
            renderServicesCatalog();
            recalculateKPIs();
        };

        // LIVE CLOCK UPDATER
        function startLiveClock() {
            function update() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
                const dateStr = now.toLocaleDateString('id-ID', options);
                const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
                document.getElementById('liveClockText').innerText = `${dateStr} • ${timeStr}`;
            }
            update();
            setInterval(update, 1000);
        }

        // VIEW MODE TOGGLER (Mobile App Shell vs Full Desktop Dashboard)
        function toggleViewMode() {
            const container = document.getElementById('mainContainer');
            const text = document.getElementById('viewToggleText');
            const icon = document.getElementById('viewToggleIcon');

            if (!isMobileView) {
                container.classList.remove('max-w-7xl');
                container.classList.add('max-w-md', 'mx-auto', 'border-4', 'border-amber-500/40');
                text.innerText = "Tampilan Full Desktop";
                icon.className = "fa-solid fa-desktop";
                isMobileView = true;
                showToast("Beralih ke mode pratinjau Mobile Admin");
            } else {
                container.classList.remove('max-w-md', 'mx-auto', 'border-4', 'border-amber-500/40');
                container.classList.add('max-w-7xl');
                text.innerText = "Tampilan Mobile Admin";
                icon.className = "fa-solid fa-mobile-screen";
                isMobileView = false;
                showToast("Beralih ke mode Desktop Admin Dashboard");
            }
        }

        // TAB NAVIGATION
        function switchTab(tabId) {
    const tabs = ['overview', 'barbers', 'analytics', 'services'];
    
    // Simpan tab yang sedang aktif ke localStorage
    localStorage.setItem('activeAdminTab', tabId);

    tabs.forEach(t => {
        const viewEl = document.getElementById('view-' + t);
        const btnEl  = document.getElementById('tabBtn-' + t);
        
        if (t === tabId) {
            if (viewEl) viewEl.classList.remove('hidden');
            if (btnEl)  btnEl.className = "tab-btn active-tab px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-amber-500 text-slate-950 shadow-gold-sm";
        } else {
            if (viewEl) viewEl.classList.add('hidden');
            if (btnEl)  btnEl.className = "tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-slate-400 hover:text-white hover:bg-slate-900 border border-transparent";
        }
    });
}

        
       function renderQueueTable() {
    const tbody = document.getElementById('queueTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    let filtered = queueData;
    if (activeFilter !== 'all') {
        filtered = queueData.filter(q => q.status === activeFilter);
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-500">Tidak ada antrean dalam kategori ini</td></tr>`;
        return;
    }

    filtered.forEach(q => {
        let statusBadge = '';
        let actions = '';

        if (q.status === 'menunggu') {
            statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Menunggu</span>`;
            actions = `
                <button onclick="updateQueueStatus(${q.db_id}, 'diproses')" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg transition text-[11px]">
                    <i class="fa-solid fa-scissors mr-1"></i> Mulai
                </button>
                <button onclick="updateQueueStatus(${q.db_id}, 'batal')" class="px-2 py-1 bg-slate-800 hover:bg-rose-950 text-rose-400 font-bold rounded-lg transition text-[11px] ml-1">
                    Batal
                </button>
            `;
        } else if (q.status === 'diproses') {
            statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/30 animate-pulse">Sedang Cukur</span>`;
            actions = `
                <button onclick="updateQueueStatus(${q.db_id}, 'selesai')" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-lg transition text-[11px]">
                    <i class="fa-solid fa-check mr-1"></i> Selesai & Bayar
                </button>
            `;
        } else if (q.status === 'selesai') {
            statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Selesai</span>`;
            actions = `<span class="text-slate-500 text-[11px]"><i class="fa-solid fa-check-double text-emerald-400"></i> Lunas</span>`;
        } else if (q.status === 'batal') {
            statusBadge = `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">Batal</span>`;
            actions = `<span class="text-slate-500 text-[11px] italic">Dibatalkan</span>`;
        }

        const row = document.createElement('tr');
        row.className = "hover:bg-slate-900/60 transition";
        row.innerHTML = `
            <td class="py-3 px-3 font-extrabold text-amber-400">${q.id}</td>
            <td class="py-3 px-3 text-white">
                <div class="font-bold">${q.name}</div>
                <div class="text-[10px] text-slate-400">${q.phone}</div>
            </td>
            <td class="py-3 px-3 text-slate-300">${q.barber}</td>
            <td class="py-3 px-3 text-slate-300">${q.service}</td>
            <td class="py-3 px-3 text-slate-400 font-mono text-[11px]">${q.time}</td>
            <td class="py-3 px-3 font-bold text-white">Rp ${q.price.toLocaleString('id-ID')}</td>
            <td class="py-3 px-3">${statusBadge}</td>
            <td class="py-3 px-3 text-center">${actions}</td>
        `;
        tbody.appendChild(row);
    });

    // Update Angka Counter Filter Tabs secara aman
    if (document.getElementById('countAll')) document.getElementById('countAll').innerText = queueData.length;
    if (document.getElementById('countMenunggu')) document.getElementById('countMenunggu').innerText = queueData.filter(q => q.status === 'menunggu').length;
    if (document.getElementById('countDiproses')) document.getElementById('countDiproses').innerText = queueData.filter(q => q.status === 'diproses').length;
    if (document.getElementById('countSelesai')) document.getElementById('countSelesai').innerText = queueData.filter(q => q.status === 'selesai').length;
}

        // UPDATE QUEUE STATUS DYNAMICALLY
       window.updateQueueStatus = function(dbId, targetStatus) {
    if (!dbId || dbId === 'undefined') {
        alert('Gagal: ID Booking tidak valid atau tidak ditemukan.');
        return;
    }

    // Konfirmasi dialog khusus jika status selesai
    if (targetStatus === 'selesai' && !confirm('Apakah pelanggan ini telah selesai dan pembayaran sudah diterima?')) {
        return;
    }

    // Konversi status tampilan ke status MySQL
    let statusDb = targetStatus;
    if (targetStatus === 'diproses') statusDb = 'in_progress';
    if (targetStatus === 'selesai') statusDb = 'completed';
    if (targetStatus === 'batal') statusDb = 'cancelled';

    fetch('<?= base_url("admin/dashboard/updateStatus"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: JSON.stringify({
            id: parseInt(dbId),
            status: statusDb
        })
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            location.reload(); // Refresh halaman untuk memperbarui data KPI & antrean
        } else {
            alert('Gagal memperbarui status: ' + res.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan koneksi.');
    });
};

// ==========================================
// 2. FUNGSI MODAL TOTAL BOOKING (GLOBAL)
// ==========================================
// Buka Modal
window.openTotalBookingModal = function() {
    const modal = document.getElementById('totalBookingModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
};

// Tutup Modal
window.closeTotalBookingModal = function() {
    const modal = document.getElementById('totalBookingModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

// Proses Terima Booking Online & Tugaskan Barber
function approveBooking(appointmentId) {
    const barberSelect = document.getElementById(`barber-select-${appointmentId}`);
    const barberId     = barberSelect ? barberSelect.value : null;

    if (!barberId) {
        alert('Harap pilih Barber terlebih dahulu sebelum menerima booking!');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menyetujui booking ini dan memasukkannya ke antrean utama?')) {
        return;
    }

    fetch('<?= base_url('admin/dashboard/approveBooking'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: JSON.stringify({
            appointment_id: appointmentId,
            barber_id: barberId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Reload untuk memperbarui antrean aktif di tabel customers
        } else {
            alert(data.message || 'Gagal memproses persetujuan booking.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan sistem.');
    });
}

// Tutup modal jika user mengklik area luar modal (backdrop)
document.addEventListener('click', function(e) {
    const modal = document.getElementById('totalBookingModal');
    if (e.target === modal) {
        window.closeTotalBookingModal();
    }
});

        // RECALCULATE KPI CARDS IN REAL-TIME
        function recalculateKPIs() {
            // Total Revenue from completed items + base mockup revenue
            const baseRevenue = 2000000;
            const completedRevenue = queueData
                .filter(q => q.status === 'selesai')
                .reduce((acc, curr) => acc + curr.price, 0);

            const totalRevenue = baseRevenue + completedRevenue;
            document.getElementById('kpiRevenue').innerText = `Rp ${totalRevenue.toLocaleString('id-ID')}`;

            // Total Booking Count
            document.getElementById('kpiTotalBooking').innerText = `${queueData.length + 12} Order`;

            // Waiting Count
            const waitingCount = queueData.filter(q => q.status === 'menunggu').length;
            document.getElementById('kpiWaitingQueue').innerText = `${waitingCount} Orang`;

            // Active Barbers On Duty
            const dutyCount = barbersData.filter(b => b.status === 'duty').length;
            document.getElementById('kpiActiveBarbers').innerText = `${dutyCount} / ${barbersData.length} Barber`;
        }

        // WALK-IN MODAL LOGIC
        window.openWalkInModal = function() {
    const modal = document.getElementById('walkInModal');
    if (modal) modal.classList.remove('hidden');
};

window.closeWalkInModal = function() {
    const modal = document.getElementById('walkInModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('walkInForm').reset();
    }
};

// Handle Form Submit Walk-in via AJAX
window.handleWalkInSubmit = function(event) {
    event.preventDefault();

    const payload = {
        name: document.getElementById('walkInName').value,
        phone: document.getElementById('walkInPhone').value,
        barber_id: document.getElementById('walkInBarber').value,
        service_id: document.getElementById('walkInService').value,
        booking_time: document.getElementById('walkInTime').value,
        payment: document.getElementById('walkInPayment').value
    };

    fetch('<?= base_url("admin/dashboard/saveWalkIn"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            closeWalkInModal();
            location.reload(); // Reload halaman untuk memperbarui daftar antrean & KPI
        } else {
            alert(res.message || 'Gagal menambahkan antrean walk-in.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan koneksi ke server.');
    });
};

        // RENDER BARBER ROSTER GRID DARI TABEL barbers
        function renderBarbersGrid() {
            const grid = document.getElementById('barberGrid');
            if (!grid) return;
            grid.innerHTML = '';

            if (!barbersData.length) {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-10 rounded-xl border border-dashed border-slate-700">
                        <i class="fa-solid fa-user-plus text-3xl text-slate-600 mb-3"></i>
                        <p class="text-sm font-bold text-slate-300">Belum ada data barber</p>
                        <p class="text-xs text-slate-500 mt-1">Klik tombol Buat Akun untuk menambahkan barber baru.</p>
                    </div>`;
                return;
            }

            barbersData.forEach(b => {
                let statusBadge = '';
                if (b.status === 'duty') {
                    statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">On Duty</span>`;
                } else if (b.status === 'break') {
                    statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Istirahat</span>`;
                } else {
                    statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">Off Day</span>`;
                }

                const card = document.createElement('div');
                card.className = "bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-amber-500/30 transition";
                const avatarUrl = b.avatar || 'https://placehold.co/100x100/1e293b/f59e0b?text=Barber';
                const escapeHtml = value => String(value || '').replace(/[&<>"']/g, ch => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[ch]));
                const safeName = escapeHtml(b.name || 'Tanpa Nama');
                const safePhone = escapeHtml(b.phone || 'Nomor HP belum diisi');

                card.innerHTML = `
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <img src="${avatarUrl}" class="w-12 h-12 rounded-xl object-cover border border-amber-500/30"
                                 alt="${safeName}" onerror="this.src='https://placehold.co/100x100/1e293b/f59e0b?text=Barber'">
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-white break-words">${safeName}</h4>
                                <p class="text-[10px] text-slate-400 mt-1">${safePhone}</p>
                                <div class="mt-2">${statusBadge}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-1.5">
                        <button onclick="toggleBarberStatus(${Number(b.id)}, 'duty')" class="flex-1 py-1.5 bg-emerald-950/60 hover:bg-emerald-900 text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-500/30">Duty</button>
                        <button onclick="toggleBarberStatus(${Number(b.id)}, 'break')" class="flex-1 py-1.5 bg-amber-950/60 hover:bg-amber-900 text-amber-400 text-[10px] font-bold rounded-lg border border-amber-500/30">Break</button>
                        <button onclick="toggleBarberStatus(${Number(b.id)}, 'off')" class="flex-1 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-400 text-[10px] font-bold rounded-lg">Off</button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }
const barberCsrfName = <?= json_encode(csrf_token()); ?>;
let barberCsrfHash = <?= json_encode(csrf_hash()); ?>;

const barberStatusUrl = <?= json_encode(base_url('admin/barbers/status')); ?>;
        async function toggleBarberStatus(barberId, newStatus) {
    const barber = barbersData.find(
        b => Number(b.id) === Number(barberId)
    );

    if (!barber) return;

    // Sesuaikan status JavaScript dengan ENUM database
    const statusDatabase = {
        duty: 'on_duty',
        break: 'break',
        off: 'off'
    };

    const status = statusDatabase[newStatus];

    if (!status) {
        showToast('Status tidak valid.');
        return;
    }

    try {
        const formData = new URLSearchParams();

        formData.append('status', status);
        formData.append(barberCsrfName, barberCsrfHash);

        const response = await fetch(
            `${barberStatusUrl}/${barberId}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData.toString(),
                credentials: 'same-origin'
            }
        );

        const result = await response.json();

        // Perbarui token CSRF jika diregenerasi oleh CodeIgniter
        if (result.csrfHash) {
            barberCsrfHash = result.csrfHash;
        }

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Gagal memperbarui status barber.'
            );
        }

        // Update data pada tampilan setelah database berhasil diperbarui
        barber.status = newStatus;

        // Render ulang kartu barber
        renderBarbersGrid();

        // Perbarui angka KPI barber
        recalculateKPIs();

        showToast(
            `Status ${barber.name} berhasil diubah menjadi ${
                newStatus === 'duty'
                    ? 'On Duty'
                    : newStatus === 'break'
                    ? 'Istirahat'
                    : 'Off Day'
            }.`
        );

    } catch (error) {
        console.error('Update status barber:', error);
        showToast(error.message || 'Terjadi kesalahan saat mengubah status.');
    }
}
function recalculateKPIs() {
    const activeCount = barbersData.filter(
        barber => barber.status === 'duty'
    ).length;

    const breakCount = barbersData.filter(
        barber => barber.status === 'break'
    ).length;

    document.getElementById('kpiActiveBarbers').textContent = activeCount;

    document.getElementById('kpiBarberBreak').textContent = breakCount;
}

        // Render katalog layanan dinamis ke Grid
function renderServiceCatalog() {
    const grid = document.getElementById('serviceCatalogGrid');
    if (!grid) return;
    grid.innerHTML = '';

    if (!servicesCatalog || servicesCatalog.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center py-8 text-slate-500">Belum ada layanan terdaftar.</div>`;
        return;
    }

    servicesCatalog.forEach(service => {
        const isActive = (service.status == 1 || service.status === '1' || service.status === true);
        const card = document.createElement('div');
        card.className = "bg-slate-900/80 rounded-xl p-4 border border-slate-800 flex flex-col justify-between group hover:border-amber-500/40 transition";
        card.innerHTML = `
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-xs">
                        <i class="fa-solid ${service.icon || 'fa-scissors'}"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${isActive ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
                        ${isActive ? 'Aktif' : 'Nonaktif'}
                    </span>
                </div>
                <h4 class="font-bold text-white text-sm mb-1">${service.name}</h4>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                    <span><i class="fa-regular fa-clock mr-1"></i>${service.duration || '-'}</span>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                <div class="font-extrabold text-amber-400 text-sm">
                    Rp ${Number(service.price).toLocaleString('id-ID')}
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="openServiceModal(${service.id})" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs transition border border-slate-700">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button onclick="deleteService(${service.id}, '${service.name.replace(/'/g, "\\'")}')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-lg text-xs transition border border-rose-500/30">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// FUNGSI HAPUS LAYANAN
function deleteService(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus layanan "${name}"?`)) {
        return;
    }

    fetch('<?= base_url("admin/dashboard/deleteService"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Hapus item dari array lokal
            servicesCatalog = servicesCatalog.filter(s => s.id != id);

            // Render ulang tampilan katalog & kunci tab
            renderServiceCatalog();
            switchTab('services');
        } else {
            alert('Gagal menghapus: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Terjadi kesalahan koneksi.');
    });
}

// Buka Modal (Tambah baru / Edit)
function openServiceModal(id = null) {
    const modal = document.getElementById('serviceModal');
    const form = document.getElementById('serviceForm');
    const title = document.getElementById('serviceModalTitle');

    form.reset();
    document.getElementById('service_id').value = '';

    if (id) {
        title.innerHTML = '<i class="fa-solid fa-pen-to-square text-amber-400"></i> Edit Layanan';
        const service = servicesCatalog.find(s => s.id == id);
        if (service) {
            document.getElementById('service_id').value = service.id;
            document.getElementById('service_name').value = service.name;
            document.getElementById('service_price').value = service.price;
            document.getElementById('service_duration').value = service.duration || '';
            document.getElementById('service_icon').value = service.icon || 'fa-scissors';
            document.getElementById('service_status').value = (service.status == 1 || service.status === true || service.is_active == 1) ? "1" : "0";
        }
    } else {
        title.innerHTML = '<i class="fa-solid fa-scissors text-amber-400"></i> Tambah Layanan Baru';
    }

    modal.classList.remove('hidden');
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.add('hidden');
}

// Simpan data via AJAX POST
function handleSaveService(event) {
    event.preventDefault();

    const serviceId = document.getElementById('service_id').value;
    const payload = {
        id: serviceId,
        name: document.getElementById('service_name').value,
        price: parseFloat(document.getElementById('service_price').value) || 0,
        duration: document.getElementById('service_duration').value,
        icon: document.getElementById('service_icon').value || 'fa-scissors',
        status: document.getElementById('service_status').value
    };

    fetch('<?= base_url("admin/dashboard/saveService"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeServiceModal();

            if (serviceId) {
                // Update item yang di-edit
                const index = servicesCatalog.findIndex(s => s.id == serviceId);
                if (index !== -1) {
                    servicesCatalog[index] = { ...servicesCatalog[index], ...payload };
                }
            } else {
                // Tambahkan item baru ke array
                payload.id = data.id || Date.now();
                servicesCatalog.push(payload);
            }

            // Render ulang tampilan katalog secara langsung & kunci tab
            renderServiceCatalog();
            switchTab('services');
        } else {
            alert('Gagal menyimpan: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Terjadi kesalahan koneksi.');
    });
}

// Panggil render saat halaman selesai di-load
document.addEventListener('DOMContentLoaded', () => {
    // Ambil tab yang disimpan atau gunakan 'overview' sebagai default
    const savedTab = localStorage.getItem('activeAdminTab') || 'overview';
    
    // Aktifkan tab sesuai riwayat terakhir
    switchTab(savedTab);

    // Render katalog layanan jika fungsi tersedia
    if (typeof renderServiceCatalog === 'function') {
        renderServiceCatalog();
    }
});

        // NOTIFICATION MODAL HELPERS
        function openAdminNotifications() {
            document.getElementById('adminNotifModal').classList.remove('hidden');
        }
        function closeAdminNotifications() {
            document.getElementById('adminNotifModal').classList.add('hidden');
        }

        // DYNAMIC TOAST NOTIFICATION ENGINE
        function showToast(message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'bg-slate-900 border border-amber-500/50 text-white text-xs px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2 pointer-events-auto animate-bounce';
            toast.innerHTML = `<i class="fa-solid fa-circle-check text-amber-400 text-sm"></i> <span>${message}</span>`;
            
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        }

       

// Tutup modal jika area luar (backdrop) diklik
document.getElementById('adminProfileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProfileModal();
    }
});

// Buka Modal Edit Profil
function openEditProfileModal() {
    closeProfileModal(); // Tutup modal profil utama lebih dulu
    
    const modal = document.getElementById('adminEditProfileModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Tutup Modal Edit Profil
function closeEditProfileModal() {
    const modal = document.getElementById('adminEditProfileModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Proses Simpan via AJAX
function submitEditProfile(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnSaveProfile');
    const form = document.getElementById('formEditProfile');
    
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...';

    const formData = new FormData(form);
    
    // TAMBAHKAN BARIS INI UNTUK CSRF TOKEN CODEIGNITER 4
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    let text = str.replace(/status_/g, ' ');
    fetch('<?= base_url('admin/profile/update'); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Terjadi kesalahan pada server (Status: ' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.innerHTML = 'Simpan Perubahan';

        if (data.status === 'success') {
    // Update teks nama & email
    document.querySelectorAll('.admin-name-text').forEach(el => el.innerText = data.data.name);
    document.querySelectorAll('.admin-email-text').forEach(el => el.innerText = data.data.email);
    
    // Update foto profil di header jika ada foto baru yang diunggah
    if (data.data.avatar) {
        document.getElementById('header_admin_avatar').src = data.data.avatar;
    }

    document.getElementById('edit_password').value = '';
    closeEditProfileModal();
    alert('✅ ' + data.message);
}
    })
    .catch(error => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.innerHTML = 'Simpan Perubahan';
        alert('❌ Gagal memproses data: ' + error.message);
    });
}

// Fungsi Pratinjau Gambar saat memilih file foto baru
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        // Validasi Ukuran File (Max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran foto terlalu besar! Maksimal 2MB.');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            // Ubah src gambar preview di modal
            document.getElementById('avatar_preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
// Function Buka Modal Jadwal Layanan
function openScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');

    // Tampilkan modal dulu
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Ambil data jadwal dari server via AJAX
    fetch('<?= base_url('admin/schedule/get'); ?>')
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                // Fill Schedule Days
                res.schedules.forEach(item => {
                    const dayKey = item.day;
                    const checkbox = document.getElementById(`day_${dayKey}`);
                    const openInput = document.querySelector(`input[name="schedule[${dayKey}][open]"]`);
                    const closeInput = document.querySelector(`input[name="schedule[${dayKey}][close]"]`);

                    if (checkbox) checkbox.checked = (parseInt(item.is_active) === 1);
                    if (openInput) openInput.value = item.open_time.substring(0, 5);
                    if (closeInput) closeInput.value = item.close_time.substring(0, 5);
                });

                // Fill Settings
                const slotSelect = document.querySelector('select[name="slot_duration"]');
                const quotaInput = document.querySelector('input[name="max_quota"]');
                if (slotSelect) slotSelect.value = res.settings.slot_duration;
                if (quotaInput) quotaInput.value = res.settings.max_quota;
            }
        })
        .catch(err => console.error('Gagal memuat jadwal:', err));
}

// Tutup Modal
function closeScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Submit Simpan Jadwal ke Server via POST AJAX
function submitSchedule(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnSaveSchedule');
    const form = document.getElementById('formSchedule');
    
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...';

    const formData = new FormData(form);
    // Tambahkan CSRF token untuk proteksi CI4
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url('admin/schedule/update'); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Jadwal';

        if (data.status === 'success') {
            alert('✅ ' + data.message);
            closeScheduleModal();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Jadwal';
        alert('❌ Gagal memproses data: ' + error.message);
    });
}

// Event listener tutup modal jika luar area modal diklik
document.getElementById('scheduleModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeScheduleModal();
    }
});

let activeBarbersList = [];

// 1. Fetch & Render Table Modal Total Booking
function openTotalBookingModal() {
    const modal = document.getElementById('totalBookingModal');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    fetch('<?= base_url('admin/booking/today-details'); ?>')
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                activeBarbersList = res.barbers || [];

                document.getElementById('modal-summary-total').innerText = res.summary.total;
                document.getElementById('modal-summary-online').innerText = res.summary.online;
                document.getElementById('modal-summary-walkin').innerText = res.summary.walk_in;

                const tbody = document.getElementById('totalBookingTableBody');
                if (res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-500">Belum ada booking untuk hari ini.</td></tr>`;
                    return;
                }

                let html = '';
                res.data.forEach(row => {
                    const queueNo = String(row.queue_number).padStart(3, '0');
                    const isWalkIn = (row.booking_type === 'walk_in');
                    const typeBadge = isWalkIn 
                        ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20"><i class="fa-solid fa-person-walking mr-1"></i>Walk-in</span>`
                        : `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fa-solid fa-globe mr-1"></i>Online</span>`;

                    let statusBadge = '';
                    if (row.status === 'completed') {
                        statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">SELESAI</span>`;
                    } else if (row.status === 'in_progress') {
                        statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">PROSES</span>`;
                    } else if (row.status === 'cancelled') {
                        statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">BATAL</span>`;
                    } else {
                        statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">MENUNGGU</span>`;
                    }

                    // Tombol Aksi
                    let actionButtons = '';
                    if (row.status === 'cancelled' || row.status === 'completed') {
                        actionButtons = `<span class="text-[10px] text-slate-500 italic">No Action</span>`;
                    } else {
                        actionButtons = `
                            <div class="flex items-center justify-center gap-1.5">
                                <button onclick="openAssignBarberModal(${row.id}, ${row.barber_id}, '${row.customer_name}')" title="Terima Booking" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500 text-emerald-400 hover:text-slate-950 font-bold border border-emerald-500/30 transition flex items-center gap-1 text-[11px]">
                                    <i class="fa-solid fa-check"></i> Terima
                                </button>
                                <button onclick="cancelBookingWA(${row.id}, '${row.customer_phone}', '${row.customer_name}', '${row.booking_code}', '${row.booking_time}')" title="Batalkan & Kirim WA" class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500 text-rose-400 hover:text-white font-bold border border-rose-500/30 transition flex items-center gap-1 text-[11px]">
                                    <i class="fa-brands fa-whatsapp"></i> Cancel
                                </button>
                            </div>
                        `;
                    }

                    html += `
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 pl-2 font-mono font-bold text-amber-400">#${queueNo}</td>
                            <td class="py-3">
                                <span class="font-bold text-white block">${row.customer_name}</span>
                                <span class="text-[10px] text-slate-400">${row.customer_phone}</span>
                            </td>
                            <td class="py-3 text-slate-300 font-medium">${row.service_name}</td>
                            <td class="py-3 text-slate-300 font-semibold text-amber-400">${row.barber_name}</td>
                            <td class="py-3 font-semibold text-slate-200">${row.booking_time.substring(0, 5)} WIB</td>
                            <td class="py-3">${typeBadge}</td>
                            <td class="py-3">${statusBadge}</td>
                            <td class="py-3 text-center pr-2">${actionButtons}</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;
            }
        });
}

// 2. Buka Sub-Modal Pilih Barber untuk Aksi "Terima"
function openAssignBarberModal(appointmentId, currentBarberId, customerName) {
    document.getElementById('accept_appointment_id').value = appointmentId;
    document.getElementById('accept_customer_name').innerText = customerName;

    const selectBarber = document.getElementById('accept_barber_id');
    selectBarber.innerHTML = '';

    activeBarbersList.forEach(b => {
        const isSelected = (b.id == currentBarberId) ? 'selected' : '';
        selectBarber.innerHTML += `<option value="${b.id}" ${isSelected}>${b.name} (${b.status === 'on_duty' ? 'On Duty' : 'Istirahat'})</option>`;
    });

    document.getElementById('assignBarberModal').classList.remove('hidden');
}

function closeAssignBarberModal() {
    document.getElementById('assignBarberModal').classList.add('hidden');
}

// 3. Submit Aksi Terima Booking
function submitAcceptBooking(e) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmAccept');
    btn.disabled = true;

    const formData = new FormData(document.getElementById('formAcceptBooking'));
    formData.append('status', 'in_progress');
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url('admin/booking/update-status'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.status === 'success') {
            closeAssignBarberModal();
            openTotalBookingModal(); // Refresh data tabel
            alert('✅ Booking diterima & status diperbarui!');
        } else {
            alert('❌ ' + data.message);
        }
    });
}

function cancelBookingWA(appointmentId, phone, customerName, bookingCode, bookingTime) {
    if (!confirm(`Apakah Anda yakin ingin membatalkan booking #${bookingCode} atas nama ${customerName}?`)) {
        return;
    }

    // 1. Format Nomor WA (Ubah '08...' menjadi '628...')
    let formattedPhone = (phone || '').toString().trim().replace(/[^0-9]/g, '');
    if (formattedPhone.startsWith('0')) {
        formattedPhone = '62' + formattedPhone.substring(1);
    }

    // 2. Format Jam & Pesan WhatsApp Permohonan Maaf
    const timeFormatted = bookingTime ? bookingTime.substring(0, 5) : '-';
    const message = `Halo Kak *${customerName}*,\n\nKami dari *DR.Barber* ingin menginfokan permohonan maaf sebesar-besarnya. Booking Anda dengan kode *${bookingCode}* untuk jam *${timeFormatted} WIB* terpaksa kami *BATALKAN / DITOLAK* dikarenakan slot kapster mendadak penuh / kendala teknis.\n\nAnda dapat melakukan booking ulang untuk jadwal lain. Terima kasih atas pengertiannya 🙏`;

    // 3. Update Status di Database via AJAX (Status: 'ditolak')
    fetch('<?= base_url('admin/dashboard/rejectBooking'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: JSON.stringify({
            appointment_id: appointmentId,
            status: 'ditolak'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            // 4. Buka WhatsApp di Tab Baru & Perbarui Tampilan
            const waUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
            window.open(waUrl, '_blank');
            location.reload();
        } else {
            alert('❌ Gagal memperbarui status: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Terjadi kesalahan jaringan.');
    });
}

// Close Modal
function closeTotalBookingModal() {
    const modal = document.getElementById('totalBookingModal');
    if (!modal) return;
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

// Tutup jika area luar diklik
document.getElementById('totalBookingModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTotalBookingModal();
    }
});
 
    </script>
    <script>
function openProfileModal() {
    const modal = document.getElementById('adminProfileModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeProfileModal() {
    const modal = document.getElementById('adminProfileModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function openSettingsModal() {
    document.getElementById('settingsModal').classList.remove('hidden');
}

function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function handleSaveSettings(event) {
    event.preventDefault();

    const formData = new FormData();
    const logoInput = document.getElementById('setting_logo');

    if (logoInput.files[0]) {
        formData.append('logo', logoInput.files[0]);
    }
    formData.append('name', document.getElementById('setting_name').value);
    formData.append('address', document.getElementById('setting_address').value);
    formData.append('phone', document.getElementById('setting_phone').value);

    fetch('<?= base_url("admin/dashboard/saveSettings"); ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeSettingsModal();
            location.reload();
        } else {
            alert('Gagal menyimpan pengaturan: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Terjadi kesalahan koneksi.');
    });
}

        </script>
        <script>
// QUEUE FILTERING & RENDER TABLE
       // Pengendali Filter Tabel Antrean
function filterQueue(status, btnElement) {
    // 1. Ubah tampilan visual tombol filter aktif
    document.querySelectorAll('.q-filter').forEach(btn => {
        btn.classList.remove('bg-amber-500', 'text-slate-950', 'active-q');
        btn.classList.add('text-slate-400', 'hover:text-white');
    });

    if (btnElement) {
        btnElement.classList.add('bg-amber-500', 'text-slate-950', 'active-q');
        btnElement.classList.remove('text-slate-400', 'hover:text-white');
    }

    const target = status.toLowerCase().trim();
    // Ambil semua baris di dalam tbody tabel antrean
    const rows = document.querySelectorAll('#queueTableBody tr');

    rows.forEach(row => {
        // Ambil status dari atribut data-status ATAU baca teks di dalam baris (fallback)
        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
        const rowText = row.innerText.toLowerCase();

        let isMatch = false;

        if (target === 'all' || target === 'semua') {
            isMatch = true;
        } else if (target === 'waiting' || target === 'menunggu') {
            isMatch = rowStatus === 'waiting' || rowStatus === 'menunggu' || rowText.includes('menunggu');
        } else if (target === 'in_progress' || target === 'diproses' || target === 'sedang_cukur') {
            isMatch = rowStatus === 'in_progress' || rowStatus === 'diproses' || rowStatus === 'sedang_cukur' || rowText.includes('sedang cukur') || rowText.includes('diproses');
        } else if (target === 'completed' || target === 'selesai') {
            isMatch = rowStatus === 'completed' || rowStatus === 'selesai' || rowText.includes('selesai');
        }

        // Sembunyikan atau tampilkan baris
        if (isMatch) {
            row.classList.remove('hidden');
            row.style.display = '';
        } else {
            row.classList.add('hidden');
            row.style.display = 'none';
        }
    });
}
            </script>
</body>
</html>