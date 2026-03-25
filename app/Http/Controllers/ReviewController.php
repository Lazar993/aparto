<?php

namespace App\Http\Controllers;

use App\Http\Repository\ReservationRepository;
use App\Http\Repository\ReviewRepository;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private ReservationRepository $reservationRepository,
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $hasReservation = $this->reservationRepository->userHasPastReservation(
            $request->apartment_id,
            auth()->id(),
            auth()->user()->email
        );

        if (! $hasReservation) {
            abort(403, __('frontpage.reviews.not_allowed'));
        }

        $this->reviewRepository->create([
            'apartment_id' => $request->apartment_id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return back()->with('review_success', __('frontpage.reviews.thank_you'));
    }
}
