<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['customer_id', 'appointment_id', 'amount', 'payment_method', 'payment_status', 'paid_at'];

    // Menghitung total pendapatan berdasarkan tanggal yang dipilih
    public function getTodayRevenue($date = null)
    {
        $targetDate = $date ?? date('Y-m-d');
        
        $result = $this->selectSum('amount')
                       ->where('payment_status', 'paid')
                       ->where('DATE(paid_at)', $targetDate)
                       ->first();

        return (float)($result['amount'] ?? 0);
    }
}