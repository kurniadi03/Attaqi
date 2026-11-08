<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AppointmentModel;
use App\Models\CustomerModel;
use App\Models\BarberModel;
use App\Models\ScheduleModel;
use App\Models\SettingModel;

class Dashboard extends BaseController
{
    public function index()
{
    $db           = \Config\Database::connect();
    $selectedDate = $this->request->getGet('date') ?? date('Y-m-d');

    // 1. Hitung SELURUH Online Booking Pending di staging (tanpa filter tanggal agar tidak ada request terlewat)
    $pendingOnlineCount = $db->table('appointments')
        ->whereIn('LOWER(status)', ['menunggu', 'pending', 'waiting'])
        ->countAllResults();

    // 2. Hitung Online Approved & Walk-in berdasarkan booking_date yang sesuai filter tanggal
    $approvedOnlineCount = $db->table('customers')
        ->where('customer_type', 'online')
        ->where('COALESCE(booking_date, DATE(created_at))', $selectedDate)
        ->countAllResults();

    $walkInBookings = $db->table('customers')
        ->where('customer_type', 'walk_in')
        ->where('COALESCE(booking_date, DATE(created_at))', $selectedDate)
        ->countAllResults();

    $onlineBookings = $pendingOnlineCount + $approvedOnlineCount;
    $totalBookings  = $onlineBookings + $walkInBookings;

    // 3. Data Antrean Utama (Tabel Customers) - FILTER KETAT SESUAI JADWAL CUKUR (booking_date)
    $activeQueue = $db->table('customers c')
        ->select('
            c.*, 
            c.name as customer_name, 
            c.phone as customer_phone, 
            b.name as barber_name, 
            s.name as service_name, 
            s.price
        ')
        ->join('barbers b', 'b.id = c.barber_id', 'left')
        ->join('services s', 's.id = c.service_id', 'left')
        // Menggunakan COALESCE agar jika booking_date kosong (misal walk-in lama), otomatis memakai DATE(created_at)
        ->where('COALESCE(c.booking_date, DATE(c.created_at))', $selectedDate)
        ->orderBy('c.booking_time', 'ASC')
        ->get()
        ->getResultArray();

    // 4. Data Request Booking Online Staging (Tabel Appointments) - Tampilkan semua permintaan pending
   $pendingAppointments = $db->table('appointments a')
    ->select('a.*, s.name as service_name, b.name as barber_name')
    ->join('services s', 's.id = a.service_id', 'left')
    ->join('barbers b', 'b.id = a.barber_id', 'left')
    ->where('a.booking_date', $selectedDate) // Filter ketat hanya untuk tanggal yang dipilih
    ->orderBy('a.booking_time', 'ASC')
    ->get()
    ->getResultArray();

    //5.  Hitung total pendapatan sesuai tanggal terpilih
    $paymentModel = new \App\Models\PaymentModel();
    $todayRevenue = $paymentModel->getTodayRevenue($selectedDate);
//6.  Layanan service
    $barberModel  = new \App\Models\BarberModel();
    $serviceModel = new \App\Models\ServiceModel();

    $barbers  = $barberModel->findAll();
    $services = $serviceModel->findAll();

//7. Setting
    $settingModel = new \App\Models\SettingAkunModel();
    $settings     = $settingModel->first() ?? [
        'name'    => 'DR.BARBER',
        'logo'    => '',
        'address' => '',
        'phone'   => ''
    ];

    return view('admin/dashboard', [
        'selectedDate'        => $selectedDate,
        'settings' => $settings,
        'todayRevenue'        => $todayRevenue,
        'appointments'        => $activeQueue,
        'pendingAppointments' => $pendingAppointments,
        'totalBookings'       => $totalBookings,
        'onlineBookings'      => $onlineBookings,
        'walkInBookings'      => $walkInBookings,
        'pendingOnlineCount'  => $pendingOnlineCount,
        'barbers'             => $barbers,  // Data Barber untuk Dropdown PHP/JS
        'services'            => $services,
    ]);
}

    // Process AJAX: Terima Booking Online & Pindahkan ke tabel customers
   public function approveBooking()
{
    $json          = $this->request->getJSON(true);
    $appointmentId = $json['appointment_id'] ?? null;
    $barberId      = $json['barber_id'] ?? null;

    if (!$appointmentId || !$barberId) {
        return $this->response->setJSON(['success' => false, 'message' => 'Pilih Barber terlebih dahulu.']);
    }

    $appointmentModel = new \App\Models\AppointmentModel();
    $customerModel    = new \App\Models\CustomerModel();

    $appointment = $appointmentModel->find($appointmentId);
    if (!$appointment) {
        return $this->response->setJSON(['success' => false, 'message' => 'Data booking tidak ditemukan.']);
    }

    $db = \Config\Database::connect();
    $db->transStart();

    // 1. Masukkan ke tabel customers (antrean aktif)
    $customerModel->insert([
        'name'           => $appointment['customer_name'],
        'phone'          => $appointment['customer_phone'],
        'customer_type'  => 'online',
        'barber_id'      => $barberId,
        'service_id'     => $appointment['service_id'],
        'booking_date'   => $selectedDate ?? date('Y-m-d'),
        'booking_time'   => $appointment['booking_time'],
        'payment_method' => 'cash',
        'status'         => 'waiting',
        'created_at'     => date('Y-m-d H:i:s')
    ]);

    // 2. Tandai status di tabel appointments menjadi 'diterima'
    $appointmentModel->update($appointmentId, [
    'status'    => 'diterima',
    'barber_id' => $barberId
]);

    $db->transComplete();

    if ($db->transStatus() === false) {
        return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyetujui booking.']);
    }

    return $this->response->setJSON([
        'success' => true, 
        'message' => 'Booking berhasil diterima dan dipindahkan ke antrean utama!'
    ]);
}



   public function updateProfile()
{
    try {
        $userModel = new UserModel();
        $adminId   = session()->get('user_id') ?? 1;

        $name     = $this->request->getPost('name');
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $avatar   = $this->request->getFile('avatar');

        if (empty($name) || empty($email)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Nama dan Email tidak boleh kosong!'
            ]);
        }

        $updateData = [
            'name'  => $name,
            'email' => $email,
        ];

        // 1. Proses Upload Foto jika ada file baru yang diunggah
        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            // Buat folder uploads/avatars di direktori public jika belum ada
            $uploadPath = FCPATH . 'uploads/avatars';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Generate nama acak baru untuk gambar
            $newName = $avatar->getRandomName();
            $avatar->move($uploadPath, $newName);

            // Masukkan nama file ke array update database
            $updateData['avatar'] = $newName;
            
            // Simpan path foto baru ke session
            session()->set('avatar', base_url('uploads/avatars/' . $newName));
        }

        // 2. Proses Password baru
        if (!empty($password)) {
            if (strlen($password) < 6) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Password minimal 6 karakter!'
                ]);
            }
            $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // 3. Simpan Perubahan ke Database
        $userModel->update($adminId, $updateData);

        // Update Data Session
        session()->set([
            'name'  => $name,
            'email' => $email
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Profil dan foto berhasil diperbarui!',
            'data'    => [
                'name'   => $name,
                'email'  => $email,
                'avatar' => session()->get('avatar') ?? null
            ]
        ]);

    } catch (\Exception $e) {
        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
 // Fetch data jadwal untuk diisi ke modal (AJAX GET)
    public function getSchedule()
    {
        $scheduleModel = new \App\Models\ScheduleModel();
        $scheduleModel = new ScheduleModel();
        $settingModel  = new SettingModel();

        return $this->response->setJSON([
            'status'    => 'success',
            'schedules' => $scheduleModel->findAll(),
            'settings'  => [
                'slot_duration' => $settingModel->getValue('slot_duration', '45'),
                'max_quota'     => $settingModel->getValue('max_quota', '4'),
            ]
        ]);
    }

    // Simpan perubahan jadwal & setting (AJAX POST)
    public function updateSchedule()
    {
        try {
            $scheduleModel = new ScheduleModel();
            $settingModel  = new SettingModel();

            $schedulesData = $this->request->getPost('schedule');
            $slotDuration  = $this->request->getPost('slot_duration');
            $maxQuota      = $this->request->getPost('max_quota');

            // 1. Simpan Jadwal Harian
            if (is_array($schedulesData)) {
                foreach ($schedulesData as $day => $data) {
                    $isActive  = isset($data['active']) ? 1 : 0;
                    $openTime  = $data['open'] ?? '09:00';
                    $closeTime = $data['close'] ?? '21:00';

                    $scheduleModel->where('day', $day)->set([
                        'is_active'  => $isActive,
                        'open_time'  => $openTime,
                        'close_time' => $closeTime,
                    ])->update();
                }
            }

            // 2. Simpan Durasi Slot & Kuota
            if ($slotDuration) {
                $settingModel->setValue('slot_duration', $slotDuration);
            }
            if ($maxQuota) {
                $settingModel->setValue('max_quota', $maxQuota);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Jadwal operasional berhasil diperbarui!'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ]);
        }
    }

    public function getTodayBookingDetails()
{
    $appointmentModel = new \App\Models\AppointmentModel();
    $barberModel      = new \App\Models\BarberModel();

    $bookings = $appointmentModel
        ->select('appointments.*, customers.name as customer_name, customers.phone as customer_phone, barbers.name as barber_name, services.name as service_name')
        ->join('customers', 'customers.id = appointments.customer_id')
        ->join('barbers', 'barbers.id = appointments.barber_id')
        ->join('services', 'services.id = appointments.service_id')
        ->where('appointments.booking_date', date('Y-m-d'))
        ->orderBy('appointments.queue_number', 'ASC')
        ->findAll();

    // Ambil semua daftar barber aktif
    $barbers = $barberModel->where('status !=', 'off')->findAll();

    $onlineCount = 0;
    $walkInCount = 0;

    foreach ($bookings as $b) {
        $type = $b['booking_type'] ?? 'online';
        if ($type === 'walk_in') {
            $walkInCount++;
        } else {
            $onlineCount++;
        }
    }

    return $this->response->setJSON([
        'status'  => 'success',
        'summary' => [
            'total'   => count($bookings),
            'online'  => $onlineCount,
            'walk_in' => $walkInCount,
        ],
        'barbers' => $barbers,
        'data'    => $bookings
    ]);
}

// 2. Update Status Booking (Terima / Cancel)
public function updateBookingStatus()
{
    try {
        $appointmentModel = new \App\Models\AppointmentModel();

        $id       = $this->request->getPost('appointment_id');
        $status   = $this->request->getPost('status');
        $barberId = $this->request->getPost('barber_id');

        $updateData = ['status' => $status];
        if (!empty($barberId)) {
            $updateData['barber_id'] = $barberId;
        }

        if ($appointmentModel->update($id, $updateData)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Status booking berhasil diperbarui!'
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memperbarui status.']);
    } catch (\Exception $e) {
        return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
public function updateStatus()
{
    $json   = $this->request->getJSON(true);
    $id     = $json['id'] ?? null;
    $status = $json['status'] ?? null;

    if (!$id || !$status) {
        return $this->response->setJSON(['success' => false, 'message' => 'ID dan status tidak valid.']);
    }

    $db = \Config\Database::connect();

    try {
        // Aktifkan transException agar CI4 melempar Exception jika query MySQL gagal
        $db->transException(true)->transStart();

        $customerModel = new \App\Models\CustomerModel();
        $customer      = $customerModel
            ->select('customers.*, services.price')
            ->join('services', 'services.id = customers.service_id', 'left')
            ->find($id);

        if (!$customer) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Data antrean tidak ditemukan.']);
        }

        // 1. Update status di tabel customers
        $customerModel->update($id, ['status' => $status]);

        // 2. Jika status diset ke completed / selesai, catat pembayaran
        if (in_array($status, ['completed', 'selesai'])) {
            $paymentModel = new \App\Models\PaymentModel();

            $existingPayment = $paymentModel->where('appointment_id', $id)->first();
            if (!$existingPayment) {
                $paymentModel->insert([
                    'appointment_id' => $id,
                    'amount'         => (float)($customer['price'] ?? 0),
                    'payment_method' => $customer['payment_method'] ?? 'cash',
                    'payment_status' => 'paid',
                    'paid_at'        => date('Y-m-d H:i:s')
                ]);
            }
        }

        $db->transComplete();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Status berhasil diperbarui!'
        ]);

    } catch (\Throwable $e) {
        $db->transRollback();
        // Menampilkan pesan error asli dari MySQL
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Detail Error: ' . $e->getMessage()
        ]);
    }
}
public function saveWalkIn()
{
    $json = $this->request->getJSON(true);

    $name        = trim($json['name'] ?? '');
    $phone       = trim($json['phone'] ?? '');
    $barberId    = $json['barber_id'] ?? null;
    $serviceId   = $json['service_id'] ?? null;
    $bookingTime = $json['booking_time'] ?? date('H:i');
    $payment     = $json['payment'] ?? 'cash';

    // Validasi input wajib
    if (empty($name) || empty($barberId) || empty($serviceId)) {
        return $this->response->setJSON([
            'success' => false, 
            'message' => 'Harap isi semua kolom wajib (Nama, Barber, Layanan).'
        ]);
    }

    $customerModel = new \App\Models\CustomerModel();

    // Simpan langsung ke tabel customers
    $insertedId = $customerModel->insert([
        'name'           => $name,
        'phone'          => !empty($phone) ? $phone : null,
        'email'          => null,
        'customer_type'  => 'walk_in',
        'barber_id'      => $barberId,
        'service_id'     => $serviceId,
        'booking_time'   => $bookingTime,
        'payment_method' => $payment,
        'status'         => 'waiting',
        'created_at'     => date('Y-m-d H:i:s')
    ]);

    if ($insertedId) {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Data Walk-in berhasil disimpan ke tabel customers!'
        ]);
    }

    $errors = implode(', ', $customerModel->errors() ?: ['Gagal menyimpan data ke database.']);
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Gagal: ' . $errors
    ]);
}
public function rejectBooking()
    {
        $json          = $this->request->getJSON(true);
        $appointmentId = $json['appointment_id'] ?? $this->request->getPost('appointment_id');

        if (!$appointmentId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Booking tidak ditemukan.']);
        }

        $appointmentModel = new \App\Models\AppointmentModel();
        $updated          = $appointmentModel->update($appointmentId, ['status' => 'ditolak']);

        if ($updated) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Booking berhasil ditolak.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengubah status di database.']);
    }

  public function saveService()
{
    $json = $this->request->getJSON(true);
    $id   = $json['id'] ?? null;

    // Disesuaikan persis dengan $allowedFields pada ServiceModel milik Anda
    $data = [
        'name'        => $json['name'] ?? '',
        'price'       => (float)($json['price'] ?? 0),
        'duration'    => $json['duration'] ?? '',
        'description' => $json['description'] ?? null,
        'status'      => $json['status'] ?? 1,
        'icon'        => $json['icon'] ?? 'fa-scissors',
    ];

    if (empty($data['name']) || $data['price'] <= 0) {
        return $this->response->setJSON(['success' => false, 'message' => 'Nama dan harga layanan wajib diisi.']);
    }

    $serviceModel = new \App\Models\ServiceModel();

    try {
        if (!empty($id)) {
            $serviceModel->update($id, $data);
            $msg = 'Layanan berhasil diperbarui!';
        } else {
            $serviceModel->insert($data);
            $msg = 'Layanan baru berhasil ditambahkan!';
        }

        return $this->response->setJSON(['success' => true, 'message' => $msg]);
    } catch (\Throwable $e) {
        return $this->response->setJSON(['success' => false, 'message' => 'Detail Error: ' . $e->getMessage()]);
    }
}
public function deleteService()
{
    $json = $this->request->getJSON(true);
    $id   = $json['id'] ?? null;

    if (!$id) {
        return $this->response->setJSON(['success' => false, 'message' => 'ID layanan tidak valid.']);
    }

    $serviceModel = new \App\Models\ServiceModel();

    try {
        $serviceModel->delete($id);
        return $this->response->setJSON(['success' => true, 'message' => 'Layanan berhasil dihapus!']);
    } catch (\Throwable $e) {
        return $this->response->setJSON(['success' => false, 'message' => 'Detail Error: ' . $e->getMessage()]);
    }
}
public function saveSettings()
{
    $settingModel = new \App\Models\SettingAkunModel();
    $existing     = $settingModel->first();

    $data = [
        'name'       => $this->request->getPost('name') ?? 'DR.BARBER',
        'address'    => $this->request->getPost('address') ?? '',
        'phone'      => $this->request->getPost('phone') ?? '',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Proses upload file logo jika ada yang di-upload
    $logoFile = $this->request->getFile('logo');
    if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
        $newName = 'logo_' . time() . '.' . $logoFile->getExtension();
        
        // Simpan file ke folder public/uploads
        $logoFile->move(FCPATH . 'uploads', $newName);
        $data['logo'] = 'uploads/' . $newName;
    }

    try {
        if ($existing) {
            $settingModel->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $settingModel->insert($data);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Pengaturan toko dan logo berhasil diperbarui!'
        ]);
    } catch (\Throwable $e) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Detail Error: ' . $e->getMessage()
        ]);
    }
}
}