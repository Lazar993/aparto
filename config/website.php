<?php

return [
	'currency' => env('CURRENCY', 'EUR'),
	'deposit_rate' => env('DEPOSIT_RATE', 0.3),
	'contact_email' => env('CONTACT_EMAIL', 'aparto@gmail.com'),
	'apartments_per_page' => env('APARTMENTS_PER_PAGE', 12),
	'homepage_apartments_limit' => env('HOMEPAGE_APARTMENTS_LIMIT', 16),
	'homepage_section_apartments_limit' => env('HOMEPAGE_SECTION_APARTMENTS_LIMIT', 8),
	'homepage_trending_period_days' => env('HOMEPAGE_TRENDING_PERIOD_DAYS', 1000),
	'homepage_best_rated_min_reviews' => env('HOMEPAGE_BEST_RATED_MIN_REVIEWS', 3),
];
