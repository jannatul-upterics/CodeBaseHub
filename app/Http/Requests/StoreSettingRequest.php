<?php

namespace App\Http\Requests;

use App\Models\Website;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Website|string|int $website */
        $website = $this->route('website');
        $websiteId = $website instanceof Website ? $website->id : (int) $website;

        return [
            'setting_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                Rule::unique('settings', 'setting_key')->where('website_id', $websiteId),
            ],
            'setting_value' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'setting_key.required' => 'The setting key is required.',
            'setting_key.regex' => 'The setting key may only contain letters, numbers, dots, underscores, and hyphens.',
            'setting_key.unique' => 'A setting with this key already exists for this website.',
            'setting_value.required' => 'The setting value cannot be empty.',
        ];
    }
}
