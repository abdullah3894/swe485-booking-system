<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A deactivate request only needs the action; anything else needs name and type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'action' => ['nullable', 'in:update,deactivate'],
        ];

        if ($this->input('action') === 'deactivate') {
            return $rules;
        }

        $rules['name'] = [
            'required',
            'string',
            'max:100',
            Rule::unique('items', 'name')->ignore($this->route('item')),
        ];
        $rules['type'] = ['required', 'in:field,equipment'];

        return $rules;
    }
}
