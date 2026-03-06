<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApartmentListRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $minPrice = $this->normalizePrice($this->input('min_price'));
        $maxPrice = $this->normalizePrice($this->input('max_price'));

        // Keep filters forgiving: if user enters bounds in reverse order,
        // silently swap them instead of returning a 422 validation error.
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $this->merge([
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'guests' => ['nullable', 'integer', 'min:1'],
            'parking' => ['nullable', 'in:0,1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after:date_from'],
        ];
    }

    private function normalizePrice(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim(str_replace(',', '.', $value));

            if ($value === '') {
                return null;
            }
        }

        if (!is_numeric($value)) {
            return (string) $value;
        }

        return (string) $value;
    }
}
