<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table            = 'schedules';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['day', 'is_active', 'open_time', 'close_time'];
    protected $useTimestamps     = false;
}