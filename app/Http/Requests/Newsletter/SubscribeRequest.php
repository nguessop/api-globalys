<?php

// app/Http/Requests/Newsletter/SubscribeRequest.php
namespace App\Http\Requests\Newsletter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'  => ['required','email:rfc,dns'],
            'name'   => ['nullable','string','max:120'],
            'source' => ['nullable','string','max:120'],
            'tags'   => ['nullable','array'],
            'tags.*' => ['string','max:50'],
            'gdpr'   => ['nullable','boolean'],
        ];
    }
}
