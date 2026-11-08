<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    protected $table            = 'appointments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    // Perbarui daftar kolom yang diizinkan
    protected $allowedFields    = [
        'booking_code',
        'customer_name',    // Ganti customer_id dengan customer_name
        'customer_phone',   // Kolom nomor telepon pelanggan
        'barber_id',
        'service_id',
        'booking_date',
        'booking_time',
        'queue_number',
        'status',
        'booking_type',
        'notes',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Query Antrean Dashboard
     */
    public function getDashboardQueue(string $date)
    {
        return $this->db->table($this->table . ' a')
            ->select('
                a.id,
                a.id as db_id,
                a.booking_code,
                a.queue_number,
                a.customer_name,
                a.customer_phone,
                a.booking_time,
                a.status,
                a.booking_type,
                b.name as barber_name,
                s.name as service_name,
                s.price
            ')
            ->join('barbers b', 'b.id = a.barber_id', 'left')
            ->join('services s', 's.id = a.service_id', 'left')
            ->where('a.booking_date', $date)
            ->get()
            ->getResultArray();
    }
}