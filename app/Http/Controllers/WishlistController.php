<?php

namespace App\Http\Controllers;

use App\Http\Repository\WishlistRepository;
use App\Models\Apartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
    ) {}

    public function toggle(Request $request, Apartment $apartment): RedirectResponse|JsonResponse
    {
        $userId = (int) $request->user()->id;

        $wishlistEntry = $this->wishlistRepository->findEntry($userId, $apartment->id);

        if ($wishlistEntry) {
            $this->wishlistRepository->delete($wishlistEntry);
            $isWishlisted = false;
        } else {
            $this->wishlistRepository->create($userId, $apartment->id);
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
