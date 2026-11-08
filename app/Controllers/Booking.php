<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AppointmentModel;
use App\Models\ServiceModel;
use App\Models\ScheduleModel;
use App\Models\SettingAkunModel;

class Booking extends BaseController
{
    public function index()
{
    $serviceModel  = new \App\Models\ServiceModel();
    $scheduleModel = new \App\Models\ScheduleModel();

    $schedules  = $scheduleModel->findAll();
    $closedDays = []; // Menampung indeks hari yang TUTUP (is_active = 0)

    // Pemetaan nama hari ke indeks Javascript Date.getDay() (0 = Minggu, 6 = Sabtu)
    $dayMap = [
        'minggu' => 0, 'sunday'    => 0, 0 => 0,
        'senin'  => 1, 'monday'    => 1, 1 => 1,
        'selasa' => 2, 'tuesday'   => 2, 2 => 2,
        'rabu'   => 3, 'wednesday' => 3, 3 => 3,
        'kamis'  => 4, 'thursday'  => 4, 4 => 4,
        'jumat'  => 5, 'friday'    => 5, 5 => 5,
        'sabtu'  => 6, 'saturday'  => 6, 6 => 6,
    ];

    if (!empty($schedules)) {
        foreach ($schedules as $sch) {
            // Ambil kolom hari & is_active (sesuaikan nama kolom di DB Anda)
            $dayName  = strtolower($sch['day'] ?? $sch['hari'] ?? '');
            $isActive = $sch['is_active'] ?? $sch['status'] ?? 1;

            // Jika is_active = 0, masukkan indeks harinya ke daftar tutup
            if ((int)$isActive === 0 && isset($dayMap[$dayName])) {
                $closedDays[] = $dayMap[$dayName];
            }
        }
    }
$settingModel = new SettingAkunModel();
        $settings     = $settingModel->first();
    return view('booking', [
        'services'   => $serviceModel->findAll(),
        'settings' => $settings,
        'schedules'  => $schedules,
        'closedDays' => array_unique($closedDays), // Array angka hari yang tutup [0, 1, dst]
    ]);
}

    public function store()
    {
        $customerName = trim($this->request->getPost('customer_name') ?? '');
        $phone        = trim($this->request->getPost('customer_phone') ?? '');
        $serviceId    = $this->request->getPost('service_id');
        
        $bookingTime  = $this->request->getPost('booking_time');
        $notes        = trim($this->request->getPost('notes') ?? '');

        $bookingDate = $this->request->getPost('booking_date');
    
    if ($bookingDate) {
        // Ambil nama hari dari tanggal yang dipilih (misal: 'minggu', 'senin', dst)
        $dayIndex = (int)date('w', strtotime($bookingDate)); // 0 = Minggu, 6 = Sabtu
        $dayNames = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
        $selectedDayName = $dayNames[$dayIndex];

        $scheduleModel = new \App\Models\ScheduleModel();
        $schedules     = $scheduleModel->findAll();

        // Cek status is_active pada array data jadwal
        foreach ($schedules as $sch) {
            $dbDay = strtolower($sch['day'] ?? $sch['day_name'] ?? $sch['hari'] ?? '');
            
            if ($dbDay === $selectedDayName && isset($sch['is_active']) && (int)$sch['is_active'] === 0) {
                return redirect()->back()->withInput()->with('error', 'Barbershop tutup pada hari ' . ucfirst($selectedDayName) . '. Silakan pilih tanggal lain.');
            }
        }
    }

        if (empty($customerName) || empty($phone) || empty($serviceId) || empty($bookingTime)) {
            return redirect()->back()->withInput()->with('error', 'Harap lengkapi semua kolom.');
        }

        $appointmentModel = new AppointmentModel();

        // Hitung nomor antrean sementara
        $maxResult = $appointmentModel
            ->selectMax('queue_number')
            ->where('booking_date', $bookingDate)
            ->first();

        $lastQueue = $maxResult['queue_number'] ?? 0;
        $nextQueue = $lastQueue + 1;
        $bookingCode = 'ONL-' . strtoupper(substr(md5(uniqid()), 0, 6));

        // Simpan ke tabel appointments (menunggu persetujuan Admin)
        $inserted = $appointmentModel->insert([
    'booking_code'   => $bookingCode,
    'customer_name'  => $customerName,
    'customer_phone' => $phone,
    'barber_id'      => null,
    'service_id'     => $serviceId,
    'booking_date'   => $bookingDate,
    'booking_time'   => $bookingTime,
    'queue_number'   => $nextQueue,
    'status'         => 'menunggu', // Status default saat booking online masuk
    'booking_type'   => 'online',
    'notes'          => $notes ?: null,
    'created_at'     => date('Y-m-d H:i:s')
]);

        if ($inserted) {
            return redirect()->to('booking')->with('success_booking', [
                'code'  => $bookingCode,
                'queue' => $nextQueue,
                'name'  => $customerName,
                'date'  => $bookingDate,
                'time'  => $bookingTime
            ]);
        }

        return redirect()->back()->withInput()->with('error', 'Gagal memproses reservasi.');
    }
}