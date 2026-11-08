<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'setting_key';
    protected $allowedFields    = ['setting_key', 'setting_value'];
    protected $useTimestamps     = false;

    // Helper untuk mengambil nilai setting berdasarkan key
    public function getValue($key, $default = '')
    {
        $item = $this->find($key);
        return $item ? $item['setting_value'] : $default;
    }

    // Helper untuk menyimpan atau memperbarui setting
    public function setValue($key, $value)
    {
        return $this->save([
            'setting_key'   => $key,
            'setting_value' => $value
        ]);
    }
}