<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Apartment $apartment): RedirectResponse|JsonResponse
    {
        $userId = (int) $request->user()->id;

        $wishlistEntry = Wishlist::query()
            ->where('user_id', $userId)
            ->where('apartment_id', $apartment->id)
            ->first();

        if ($wishlistEntry) {
            $wishlistEntry->delete();
            $isWishlisted = false;
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'apartment_id' => $apartment->id,
            ]);
            $isWishlisted = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'wishlisted' => $isWishlisted,
                'apartment_id' => (int) $apartment->id,
            ]);
        }

        $redirectTo = $this->resolveRedirectUrl($request);

        if ($redirectTo !== null) {
            return redirect()->to($redirectTo);
        }

        return back();
    }

    private function resolveRedirectUrl(Request $request): ?string
    {
        $redirectTo = trim((string) $request->input('redirect_to', ''));

        if ($redirectTo === '') {
            return null;
        }

        $host = parse_url($redirectTo, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        return $redirectTo;
    }
}
