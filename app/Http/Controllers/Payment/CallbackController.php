<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;

class CallbackController extends Controller
{
    protected $privateKey = '5QPaZ-uyCgB-gDkUQ-10KZg-scDUb';

    public function handle(Request $request)
    {
        // dd($request->all());
        // ambil callback signature
        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE') ?? '';

        // ambil data JSON
        $json = $request->getContent();

        // generate signature untuk dicocokkan dengan X-Callback-Signature
        $signature = hash_hmac('sha256', $json, $this->privateKey);

        // validasi signature
        if ($callbackSignature !== $signature) {
            return "Invalid Signature"; // signature tidak valid, hentikan proses
        }

        $data = json_decode($json);
        // dd($data);
        $event = $request->server('HTTP_X_CALLBACK_EVENT');

        if ($event == 'payment_status') {
            // dd($data);
            $reference = $data->reference;

            // pembayaran sukses, lanjutkan proses sesuai sistem Anda, contoh:
            $transaction = Booking::where('order_no', $reference)
                ->where('status', 'unpaid')
                ->first();

            if (!$transaction) {
                return "Order not found or current status is not UNPAID";
            }

            // Lakukan validasi nominal
            if (intval($data->total_amount) !== intval($transaction->price)) {
                return "Invalid amount";
            }

            if ($data->status == 'PAID') // handle status PAID
            {
                $transaction->update([
                    'status'    => 'PAID'
                ]);

                return response()->json([
                    'success' => true
                ]);
            } elseif ($data->status == 'EXPIRED') // handle status EXPIRED
            {
                $transaction->update([
                    'status'    => 'CANCELED'
                ]);

                return response()->json([
                    'success' => true
                ]);
            } elseif ($data->status == 'FAILED') // handle status FAILED
            {
                $transaction->update([
                    'status'    => 'CANCELED'
                ]);

                return response()->json([
                    'success' => true
                ]);
            }
        }

        return "No action was taken";
    }
}
