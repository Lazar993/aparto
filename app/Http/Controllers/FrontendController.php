<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Page;
use Carbon\Carbon;

class FrontendController extends Controller
{
    public function index()
    {
        // Dohvati sve aktivne stanove
        $apartments = Apartment::where('active', true)->get();

        $cities = Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('frontend.index', compact('apartments', 'cities'));
    }

    public function show($id)
    {
        $apartment = Apartment::findOrFail($id);

        $reservationRanges = $apartment->reservations()
            ->where('status', 'confirmed')
            ->whereNull('deleted_at')
            ->get(['date_from', 'date_to'])
            ->map(function ($reservation) {
                return [
                    'from' => Carbon::parse($reservation->date_from)->toDateString(),
                    'to' => Carbon::parse($reservation->date_to)->toDateString(),
                ];
            })
            ->values();

        return view('frontend.show', compact('apartment', 'reservationRanges'));
    }

    public function list(\Illuminate\Http\Request $request)
    {
        $query = Apartment::where('active', true);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $city = trim((string) $request->query('city', ''));
        if ($city !== '') {
            $query->where('city', $city);
        }

        $minPrice = $request->query('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price_per_night', '>=', (float) $minPrice);
        }

        $maxPrice = $request->query('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price_per_night', '<=', (float) $maxPrice);
        }

        $parking = $request->query('parking');
        if ($parking !== null && $parking !== '') {
            $query->where('parking', (bool) ((int) $parking));
        }

        $apartments = $query->orderByDesc('id')
            ->paginate(9)
            ->appends($request->query());

        $cities = Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        if ($request->ajax()) {
            $html = view('frontend.partials.apartments-results', compact('apartments'))->render();

            return response()->json(['html' => $html]);
        }

        return view('frontend.apartments', compact('apartments', 'cities'));
    }

    public function page(string $slug)
    {
        $page = Page::where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontend.page', compact('page'));
    }
}
