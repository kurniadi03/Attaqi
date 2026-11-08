<?php

namespace App\Models;

use CodeIgniter\Model;

class BarberModel extends Model
{
    protected $table         = 'barbers';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'phone', 'status', 'avatar'];
}