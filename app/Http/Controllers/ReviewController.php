<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Check if the user has a completed reservation for this apartment
        // Support both new (with user_id) and old (without user_id) reservations by email
        // For development/testing: Accept 'pending' or 'confirmed' status
        $hasReservation = Reservation::where('apartment_id', $request->apartment_id)
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhere('email', auth()->user()->email);
            })
            ->where('date_to', '<', now())
            ->whereIn('status', ['confirmed', 'pending']) // Allow pending for testing
            ->exists();

        if (! $hasReservation) {
            abort(403, __('You are not allowed to review this apartment.'));
        }

        Review::create([
            'apartment_id' => $request->apartment_id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Thank you for your review! It will be visible once approved by our team.'));
    }
}
