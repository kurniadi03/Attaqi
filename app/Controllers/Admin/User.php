<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class User extends BaseController
{
    public function create()
    {
        $rules = [
            'name' => [
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama wajib diisi.',
                    'max_length' => 'Nama maksimal 100 karakter.',
                ],
            ],
            'phone' => [
                'rules'  => 'permit_empty|max_length[20]',
                'errors' => [
                    'max_length' => 'Nomor HP maksimal 20 karakter.',
                ],
            ],
            'email' => [
                'rules'  => 'required|valid_email|max_length[100]|is_unique[barbers.email]',
                'errors' => [
                    'required'    => 'Email wajib diisi.',
                    'valid_email' => 'Format email tidak valid.',
                    'max_length'  => 'Email maksimal 100 karakter.',
                    'is_unique'   => 'Email sudah terdaftar.',
                ],
            ],
            'password' => [
                'rules'  => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required'   => 'Password wajib diisi.',
                    'min_length' => 'Password minimal 8 karakter.',
                ],
            ],
            'password_confirm' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Konfirmasi password wajib diisi.',
                    'matches'  => 'Konfirmasi password tidak sama.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'     => trim((string) $this->request->getPost('name')),
            'phone'    => trim((string) $this->request->getPost('phone')) ?: null,
            'email'    => strtolower(trim((string) $this->request->getPost('email'))),
            'password' => password_hash(
                (string) $this->request->getPost('password'),
                PASSWORD_DEFAULT
            ),
            // Kolom status menggunakan default database: on_duty.
            // Kolom avatar dan created_at juga dibiarkan memakai default/null database.
        ];

        $db = Database::connect();

        try {
            $db->table('barbers')->insert($data);

            if ($db->affectedRows() > 0) {
                return redirect()->back()
                    ->with('success', 'Akun barber berhasil dibuat.');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Akun barber gagal dibuat.');
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat akun barber: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Akun barber gagal dibuat. Periksa data dan konfigurasi database.');
        }
    }
    public function updateStatus($id)
{
    $status = $this->request->getPost('status');

    // Status yang diizinkan sesuai ENUM tabel barbers
    $allowedStatus = ['on_duty', 'break', 'off'];

    if (!in_array($status, $allowedStatus, true)) {
        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Status barber tidak valid.'
        ]);
    }

    $db = \Config\Database::connect();

    try {
        // Pastikan barber tersedia
        $barber = $db->table('barbers')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$barber) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Data barber tidak ditemukan.'
            ]);
        }

        // Update status pada database
        $db->table('barbers')
            ->where('id', $id)
            ->update([
                'status' => $status
            ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Status barber berhasil diperbarui.',
            'status'  => $status,
            'id'      => (int) $id,
            'csrfHash' => csrf_hash()
        ]);

    } catch (\Throwable $e) {
        log_message('error', 'Update status barber gagal: ' . $e->getMessage());

        return $this->response->setStatusCode(500)->setJSON([
            'success' => false,
            'message' => 'Gagal memperbarui status barber.'
        ]);
    }
}
}
