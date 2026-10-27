<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare input for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('domain_name')) {
            $domain = trim((string) $this->domain_name);
            // Strip http:// or https:// if user pasted a URL
            $domain = preg_replace('#^https?://#i', '', $domain);
            $domain = rtrim($domain, '/');
            $this->merge([
                'domain_name' => strtolower($domain),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,}$/',
                'unique:domains,domain_name',
            ],
            'is_primary' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
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
            'domain_name.required' => 'The domain name is required.',
            'domain_name.regex' => 'The domain name must be a valid FQDN (e.g. app.example.com).',
            'domain_name.unique' => 'This domain name is already registered to another website or entry.',
        ];
    }
}
