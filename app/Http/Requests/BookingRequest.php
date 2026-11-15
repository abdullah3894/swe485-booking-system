<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The booking request fields and the operating-hours rule (FR-13).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:06:00'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', 'before_or_equal:23:00'],
        ];
    }
}
