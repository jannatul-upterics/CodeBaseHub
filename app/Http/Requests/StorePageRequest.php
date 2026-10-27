<?php

namespace App\Http\Requests;

use App\Models\Website;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => strtolower(trim((string) $this->slug)),
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
        /** @var Website|string|int $website */
        $website = $this->route('website');
        $websiteId = $website instanceof Website ? $website->id : (int) $website;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->where('website_id', $websiteId),
            ],
            'page_type' => ['required', 'string', 'max:50'],
            'is_homepage' => ['sometimes', 'boolean'],
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
            'title.required' => 'The page title is required.',
            'slug.required' => 'The page slug path is required.',
            'slug.regex' => 'The page slug must contain only lowercase letters, numbers, and dashes (e.g. about-us).',
            'slug.unique' => 'A page with this URL slug already exists in this website.',
            'page_type.required' => 'The page type is required.',
        ];
    }
}
