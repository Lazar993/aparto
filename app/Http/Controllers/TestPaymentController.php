<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestPaymentController extends Controller
{
    public function index()
    {
        $reservations = Reservation::where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return view('frontend.test-payments', compact('reservations'));
    }

    public function confirm(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
            return redirect()->route('test.payments.index')
                ->with('error', 'Reservation is not pending.');
        }

        $reservation->update([
            'status' => 'confirmed',
            'paid_at' => now(),
            'payment_provider' => 'test',
            'payment_reference' => 'test-' . Str::uuid(),
            'deposit_amount' => $reservation->deposit_amount
                ?? round(
                    $reservation->total_price * (float) config('website.deposit_rate', 0.3),
                    2
                ),
        ]);

        return redirect()->route('test.payments.index')
            ->with('success', 'Payment marked as confirmed.');
    }
}
