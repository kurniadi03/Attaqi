<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingAkunModel extends Model
{
    protected $table            = 'setting_akun';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['name', 'logo', 'address', 'phone', 'created_at', 'updated_at'];
}