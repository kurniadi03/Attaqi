<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Online - Barbershop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased pb-12">

    <!-- Header / Navbar -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-lg overflow-hidden">
            <?php if (!empty($settings['logo']) && (str_contains($settings['logo'], '/') || preg_match('/\.(jpg|jpeg|png|gif|svg|webp)$/i', $settings['logo']))): ?>
                <!-- Tampilkan Gambar Logo -->
                <img src="<?= base_url(esc($settings['logo'])); ?>?v=<?= time(); ?>" alt="Logo" class="w-full h-full object-cover">
            <?php else: ?>
                <!-- Fallback Ikon -->
                <i class="fa-solid <?= !empty($settings['logo']) ? esc($settings['logo']) : 'fa-scissors'; ?>"></i>
            <?php endif; ?>
        </div>
        <div>
            <!-- Nama Barbershop Dinamis -->
            <h1 class="font-bold text-base text-white tracking-wide"><?= esc($settings['name'] ?? 'DR.Barber'); ?></h1>
            <p class="text-[11px] text-slate-400">Online Booking & Staging Queue</p>
        </div>
    </div>
    <a href="<?= base_url('admin/dashboard'); ?>" class="text-xs text-slate-400 hover:text-amber-400 transition flex items-center gap-1.5">
        <i class="fa-solid fa-user-shield"></i>
        <span>Admin Login</span>
    </a>
</div>
    </header>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 mt-6">

        <!-- Banner Flash Message Error -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= session()->getFlashdata('error'); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl">
            <div class="mb-6 pb-4 border-b border-slate-800">
                <h2 class="text-xl font-bold text-white">Formulir Reservasi Online</h2>
                <p class="text-xs text-slate-400 mt-1">Isi data diri Anda, pilih jenis layanan, serta waktu cukur yang diinginkan.</p>
            </div>

            <form action="<?= base_url('booking/store'); ?>" method="POST" class="space-y-5">
                <?= csrf_field(); ?>

                <!-- 1. Data Diri -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap <span class="text-rose-400">*</span></label>
                        <input type="text" name="customer_name" value="<?= old('customer_name'); ?>" required placeholder="Contoh: Budi Santoso" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor WhatsApp / HP <span class="text-rose-400">*</span></label>
                        <input type="tel" name="customer_phone" value="<?= old('customer_phone'); ?>" required placeholder="08123456789" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition">
                    </div>
                </div>

                <!-- 2. Pilih Layanan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Layanan <span class="text-rose-400">*</span></label>
                    <select name="service_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition">
                        <option value="">-- Pilih Layanan --</option>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $s): ?>
                                <?php 
                                    $sName  = $s['name'] ?? $s['service_name'] ?? 'Layanan';
                                    $sPrice = $s['price'] ?? $s['harga'] ?? 0;
                                ?>
                                <option value="<?= $s['id']; ?>" <?= old('service_id') == $s['id'] ? 'selected' : ''; ?>>
                                    <?= esc($sName); ?> - Rp <?= number_format($sPrice, 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 3. Tanggal & Jam Booking -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
            Tanggal Cukur <span class="text-rose-400">*</span>
        </label>
        <input type="date" id="booking_date" name="booking_date" min="<?= date('Y-m-d'); ?>" value="<?= old('booking_date', date('Y-m-d')); ?>" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition">
        
        <!-- Box Alert Peringatan Hari Tutup -->
        <div id="closedDayAlert" class="hidden mt-2 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2">
            <i class="fa-solid fa-store-slash text-sm"></i>
            <span id="closedDayText">Hari ini Barbershop Tutup.</span>
        </div>
    </div>

                    <div>
    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jam Slot <span class="text-rose-400">*</span></label>
    <select name="booking_time" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition">
        <option value="">-- Pilih Jam --</option>

        <?php if (!empty($timeSlots)): ?>
            <?php foreach ($timeSlots as $slot): ?>
                <option value="<?= $slot; ?>" <?= old('booking_time') == $slot ? 'selected' : ''; ?>>
                    <?= $slot; ?> WIB
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback Default Jam jika tabel schedules masih kosong -->
            <option value="09:00">09:00 WIB</option>
            <option value="10:00">10:00 WIB</option>
            <option value="11:00">11:00 WIB</option>
            <option value="13:00">13:00 WIB</option>
            <option value="14:00">14:00 WIB</option>
            <option value="15:00">15:00 WIB</option>
            <option value="16:00">16:00 WIB</option>
            <option value="17:00">17:00 WIB</option>
            <option value="19:00">19:00 WIB</option>
            <option value="20:00">20:00 WIB</option>
        <?php endif; ?>

    </select>
</div>
                </div>

                <!-- 4. Catatan Tambahan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan Khusus (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Model rambut favorit, gaya cukur, dll." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500 transition"><?= old('notes'); ?></textarea>
                </div>

                <!-- Informasi Barber ditentukan Admin -->
                <div class="p-3.5 rounded-xl bg-amber-500/5 border border-amber-500/20 flex items-start gap-3 text-xs text-amber-300">
                    <i class="fa-solid fa-circle-info text-amber-400 mt-0.5"></i>
                    <span>Barber akan ditugaskan secara otomatis oleh Admin setelah reservasi Anda disetujui.</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Kirim Request Reservasi</span>
                </button>
            </form>
        </div>
    </main>

    <!-- Modal Tiket Sukses -->
    <?php if (session()->getFlashdata('success_booking')): ?>
        <?php $ticket = session()->getFlashdata('success_booking'); ?>
        <div id="ticketModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
            <div class="relative w-full max-w-md bg-slate-900 border border-amber-500/30 rounded-3xl p-6 shadow-2xl text-center">
                
                <div class="w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center mx-auto text-2xl mb-4">
                    <i class="fa-solid fa-check"></i>
                </div>

                <h3 class="text-xl font-bold text-white">Reservasi Terkirim!</h3>
                <p class="text-xs text-slate-400 mt-1">Permintaan booking Anda telah masuk ke sistem dan menunggu persetujuan Admin.</p>

                <!-- Box Tiket -->
                <div class="my-5 p-4 rounded-2xl bg-slate-950 border border-slate-800 text-left space-y-2.5">
                    <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                        <span class="text-xs text-slate-400">Kode Booking</span>
                        <span class="font-mono font-bold text-amber-400"><?= esc($ticket['code']); ?></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                        <span class="text-xs text-slate-400">Nomor Antrean Staging</span>
                        <span class="text-lg font-bold text-white">#<?= sprintf('%02d', $ticket['queue']); ?></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                        <span class="text-xs text-slate-400">Nama Pelanggan</span>
                        <span class="text-sm font-semibold text-slate-200"><?= esc($ticket['name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-400">Jadwal Reservasi</span>
                        <span class="text-xs font-medium text-slate-300"><?= esc($ticket['date']); ?> (<?= esc($ticket['time']); ?> WIB)</span>
                    </div>
                </div>

                <button onclick="document.getElementById('ticketModal').remove()" class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-xl text-sm transition">
                    Tutup Tiket
                </button>
            </div>
        </div>
    <?php endif; ?>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const closedDays = <?= json_encode($closedDays ?? []); ?>; // Contoh: [0] jika Minggu tutup
    const dayNames   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    const dateInput  = document.getElementById('booking_date');
    const timeSelect = document.getElementById('booking_time');
    const alertBox   = document.getElementById('closedDayAlert');
    const alertText  = document.getElementById('closedDayText');
    const submitBtn  = document.querySelector('button[type="submit"]');

    function checkClosedDay() {
        if (!dateInput.value) return;

        // Ambil komponen tanggal YYYY-MM-DD
        const parts = dateInput.value.split('-');
        const selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
        const dayIndex = selectedDate.getDay(); // 0 = Minggu, 1 = Senin, dst.

        // Cek apakah indeks hari termasuk dalam closedDays
        if (closedDays.includes(dayIndex)) {
            const namaHari = dayNames[dayIndex];
            
            // Tampilkan Alert & Matikan Tombol Submit/Jam Slot
            alertText.textContent = `Mohon maaf, Barbershop TUTUP pada hari ${namaHari}. Silakan pilih tanggal lain.`;
            alertBox.classList.remove('hidden');
            
            timeSelect.disabled = true;
            timeSelect.value = '';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        } else {
            // Sembunyikan Alert & Aktifkan Kembali Form
            alertBox.classList.add('hidden');
            timeSelect.disabled = false;
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Jalankan pengecekan saat tanggal diubah
    dateInput.addEventListener('change', checkClosedDay);
    
    // Jalankan pengecekan awal saat halaman pertama kali dimuat
    checkClosedDay();
});
</script>
</body>
</html>