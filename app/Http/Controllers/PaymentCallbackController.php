<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil data dari DOKU
        $data = $request->all();
        $headerSignature = $request->header('Signature');
        
        // Log untuk memantau data masuk (opsional, berguna saat debug)
        Log::info('DOKU Notification Received:', $data);

        /* CATATAN: Di lingkungan produksi, Anda harus memvalidasi Signature HMAC 
           untuk memastikan request benar-benar dari DOKU. 
           Untuk tahap awal pengembangan, kita fokus pada update data.
        */

        // 2. Cari data pembayaran berdasarkan invoice
        $invoiceNumber = $data['order']['invoice_number'] ?? null;
        $payment = Payment::where('external_id', $invoiceNumber)->first();

        if (!$payment) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // 3. Cek status transaksi dari DOKU
        // DOKU biasanya mengirimkan status di field transaction.status
        $dokuStatus = $data['transaction']['status'] ?? '';

        if (strtoupper($dokuStatus) === 'SUCCESS') {
            // Update status Payment
            $payment->update([
                'status' => 'success',
                'payment_channel' => $data['payment']['payment_method'] ?? 'DOKU',
                'doku_payment_datetime' => now(),
            ]);

            // Update status Tiket menjadi in_progress
            $payment->ticket->update([
                'status' => 'in_progress'
            ]);

            return response()->json(['message' => 'Payment Success Updated'], 200);
        } else {
            // Update status jika gagal
            $payment->update(['status' => 'failed']);
            
            return response()->json(['message' => 'Payment Failed Updated'], 200);
        }
    }
}