<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', 'max:255'],
            'price' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'price_cents' => ['prohibited'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoria',
            'name' => 'nome',
            'description' => 'descrição',
            'sku' => 'SKU',
            'price' => 'preço',
            'price_cents' => 'preço em centavos',
            'stock' => 'estoque',
            'is_active' => 'status',
        ];
    }

    public function priceCents(): int
    {
        $price = (string) $this->validated('price');
        [$whole, $cents] = array_pad(explode('.', $price, 2), 2, '');

        return (int) ($whole.str_pad($cents, 2, '0'));
    }
}
