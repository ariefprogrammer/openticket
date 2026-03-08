<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class CheckUserActive
{
    /**
     * Menangani permintaan yang masuk.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role !== 'admin' && !$user->is_active) {
                
                // Cek apakah user baru saja datang dari halaman registrasi
                $isFromRegistration = url()->previous() === route('filament.admin.auth.register');

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($isFromRegistration) {
                    // Pesan khusus untuk user yang baru selesai daftar
                    Notification::make()
                        ->title('Registrasi Berhasil')
                        ->body('Silakan hubungi admin untuk mengaktifkan akun Anda.')
                        ->warning() // Warna oranye agar lebih informatif
                        ->persistent()
                        ->send();
                } else {
                    // Pesan untuk user lama yang mencoba login tapi belum di-acc
                    Notification::make()
                        ->title('Akses Ditolak')
                        ->body('Akun Anda belum diaktifkan oleh admin.')
                        ->danger()
                        ->send();
                }

                return redirect()->route('filament.admin.auth.login');
            }
        }

        return $next($request);
    }
}