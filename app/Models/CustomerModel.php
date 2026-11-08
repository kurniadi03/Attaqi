<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'name',
        'phone',
        'email',
        'customer_type',   // 'online' atau 'walk_in'
        'barber_id',       // NULL jika belum ditentukan Admin
        'service_id',
        'booking_date',
        'booking_time',
        'payment_method',
        'status',          // 'waiting', 'in_progress', 'completed', 'cancelled'
        'notes',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}