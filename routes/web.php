<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\{FrontendController, ReservationController, TestPaymentController, AiSearchController};
use Illuminate\Support\Facades\App;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontendController::class, 'index'])->name('home');

Route::get('/apartments', [FrontendController::class, 'list'])->name('apartments.index');
Route::get('/apartments/{id}', [FrontendController::class, 'show'])->name('apartments.show');
Route::get('/pages/{slug}', [FrontendController::class, 'page'])
	->where('slug', '[A-Za-z0-9\-]+')
	->name('pages.show');
Route::get('/lang/{locale}', function (string $locale) {
	$allowed = ['en', 'sr', 'ru'];

	if (! in_array($locale, $allowed, true)) {
		$locale = config('app.locale');
	}

	session(['locale' => $locale]);
	App::setLocale($locale);

	return redirect()->back();
})->name('locale.switch');

Route::get('/osm/search', function (Request $request) {
	$query = trim((string) $request->query('q', ''));

	if ($query === '') {
		return response()->json([]);
	}

	$locale = app()->getLocale();
	$acceptLanguage = match ($locale) {
		'sr' => 'sr-Latn',
		'ru' => 'ru',
		default => 'en',
	};

	$response = Http::withHeaders([
		'Accept' => 'application/json',
		'Accept-Language' => $acceptLanguage,
		'User-Agent' => 'Aparto/1.0 (contact@aparto.local)',
		'Referer' => config('app.url'),
	])->get('https://nominatim.openstreetmap.org/search', [
		'format' => 'json',
		'addressdetails' => 1,
		'limit' => 6,
		'q' => $query,
		'accept-language' => $acceptLanguage,
	]);

	if (! $response->ok()) {
		return response()->json([]);
	}

	$results = $response->json() ?? [];

	$shorten = function (array $item): array {
		$address = $item['address'] ?? [];
		$road = $address['road'] ?? null;
		$house = $address['house_number'] ?? null;
		$neighborhood = $address['neighbourhood'] ?? ($address['suburb'] ?? null);
		$city = $address['city'] ?? ($address['town'] ?? ($address['village'] ?? null));

		$parts = [];

		if ($road) {
			$street = $road;
			if ($house) {
				$street .= ' ' . $house;
			}
			$parts[] = $street;
		} elseif (!empty($item['name'])) {
			$parts[] = $item['name'];
		}

		if ($neighborhood) {
			$parts[] = $neighborhood;
		}

		if ($city) {
			$parts[] = $city;
		}

		$item['display_name'] = implode(', ', $parts);

		return $item;
	};

	if (is_array($results)) {
		$results = array_map($shorten, $results);
	}

	if (in_array($locale, ['sr', 'en', 'ru'], true)) {
		$map = [
			'Љ' => 'Lj', 'Њ' => 'Nj', 'Џ' => 'Dž',
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Ђ' => 'Đ',
			'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z', 'И' => 'I', 'Ј' => 'J', 'К' => 'K',
			'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
			'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
			'Ц' => 'C', 'Ч' => 'Č', 'Ш' => 'Š',
			'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dž',
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ђ' => 'đ',
			'е' => 'e', 'ж' => 'ž', 'з' => 'z', 'и' => 'i', 'ј' => 'j', 'к' => 'k',
			'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
			'с' => 's', 'т' => 't', 'ћ' => 'ć', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
			'ц' => 'c', 'ч' => 'č', 'ш' => 'š',
		];

		$transliterate = function ($value) use (&$transliterate, $map) {
			if (is_array($value)) {
				foreach ($value as $key => $item) {
					$value[$key] = $transliterate($item);
				}

				return $value;
			}

			if (! is_string($value)) {
				return $value;
			}

			return strtr($value, $map);
		};

		$results = $transliterate($results);
	}

	return response()->json($results);
})->name('osm.search');

Route::post('/apartments/{apartment}/reserve', [ReservationController::class, 'store'])
    ->name('reserve');

Route::get('/test-payments', [TestPaymentController::class, 'index'])
	->name('test.payments.index');
Route::post('/test-payments/{reservation}/confirm', [TestPaymentController::class, 'confirm'])
	->name('test.payments.confirm');

# AI Search endpoint
Route::post('/ai-search', [AiSearchController::class, 'search'])
    ->name('ai.search');
