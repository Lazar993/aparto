<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Apartment;
use App\Models\Reservation;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function store(Request $request, Apartment $apartment)
    {
        $rules = [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after:date_from'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'regex:/^\d{9,}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'required' => __('frontpage.reservation.validation.required'),
            'date' => __('frontpage.reservation.validation.date'),
            'date_to.after' => __('frontpage.reservation.validation.after'),
            'email' => __('frontpage.reservation.validation.email'),
            'phone.regex' => __('frontpage.reservation.validation.phone'),
        ];

        $attributes = trans('frontpage.reservation.attributes');

        $data = $request->validate($rules, $messages, $attributes);

        if (! $apartment->isAvailable($data['date_from'], $data['date_to'])) {
            return back()->withErrors([
                'date_from' => __('frontpage.reservation.validation.unavailable'),
            ])->withInput();
        }

        $days = Carbon::parse($data['date_from'])
            ->diffInDays(Carbon::parse($data['date_to']));

        $total = $days * $apartment->price_per_night;
        $depositRate = (float) config('website.deposit_rate', 0.3);
        $deposit = round($total * $depositRate, 2);

        Reservation::create([
            'apartment_id' => $apartment->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'nights' => $days,
            'price_per_night' => $apartment->price_per_night,
            'total_price' => $total,
            'deposit_amount' => $deposit,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', __('frontpage.reservation.success'));
    }

}
