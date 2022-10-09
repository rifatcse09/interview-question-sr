<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $id = null;
        if ($this->method() == 'PUT') {
            $id = $this->route('product.id');
        }

        $rules = [
            'title'             => 'required|string|min:0|max:191',
            'description'       => 'nullable|string',
            'sku'               => 'required|unique:products,sku,' . $id,
            'product_image.*'   => 'nullable',
            'product_variant.*' => 'required',
            'product_variant_prices'  => 'required'
        ];

        return $rules;
    }
}
