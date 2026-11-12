<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreWebsiteRequest extends FormRequest
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
        $mode = $this->input('tenant_db_mode');
        $prefix = match ($mode) {
            'create' => 'create_',
            'connect' => 'connect_',
            default => null,
        };

        $merge = [];

        if ($prefix !== null) {
            if (! $this->filled('db_host') && $this->filled($prefix.'db_host')) {
                $merge['db_host'] = $this->input($prefix.'db_host');
            }
            if (! $this->filled('db_port') && $this->filled($prefix.'db_port')) {
                $merge['db_port'] = $this->input($prefix.'db_port');
            }
            if (! $this->filled('db_name') && $this->filled($prefix.'db_name')) {
                $merge['db_name'] = $this->input($prefix.'db_name');
            }
            if (! $this->filled('db_username') && $this->filled($prefix.'db_username')) {
                $merge['db_username'] = $this->input($prefix.'db_username');
            }
            if (! $this->has('db_password') && $this->has($prefix.'db_password')) {
                $merge['db_password'] = $this->input($prefix.'db_password');
            }
        }

        // When creating a new database, fall back to default database provisioner credentials if left empty
        if ($mode === 'create') {
            $defaultDriver = (string) config('database.default', 'mysql');
            $driverConfig = config("database.connections.{$defaultDriver}", []);

            if (empty($merge['db_host'] ?? $this->input('db_host'))) {
                $merge['db_host'] = $driverConfig['host'] ?? '127.0.0.1';
            }
            if (empty($merge['db_port'] ?? $this->input('db_port'))) {
                $merge['db_port'] = (int) ($driverConfig['port'] ?? 3306);
            }
            if (empty($merge['db_username'] ?? $this->input('db_username'))) {
                $merge['db_username'] = $driverConfig['username'] ?? 'root';
            }
            if (! isset($merge['db_password']) && ! $this->has('db_password')) {
                $merge['db_password'] = $driverConfig['password'] ?? '';
            }
        }

        if ($this->has('status_submitted')) {
            $merge['status'] = $this->boolean('status') ? 1 : 0;
        } elseif ($this->has('status')) {
            $merge['status'] = (int) $this->input('status');
        }

        if (! empty($merge)) {
            $this->merge($merge);
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
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'website_name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:websites,domain'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'provider' => ['required', 'string', 'max:255'],

            // Tenant Database Configuration
            'tenant_db_mode' => ['nullable', 'string', 'in:create,connect'],
            'db_host' => ['required_with:tenant_db_mode', 'nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'integer', 'between:1,65535'],
            'db_name' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::requiredIf(fn () => $this->input('tenant_db_mode') === 'connect'),
            ],
            'db_username' => ['required_with:tenant_db_mode', 'nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'run_migrations' => ['nullable', 'boolean'],
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
            'website_name.required' => 'The website name is required.',
            'domain.required' => 'The domain name is required.',
            'domain.unique' => 'This domain is already registered with another website.',
            'provider.required' => 'A hosting provider is required.',
            'db_name.required' => 'A database name is required when connecting to an existing database.',
            'db_name.regex' => 'The database name may only contain alphanumeric characters and underscores.',
            'db_host.required_with' => 'Database host is required for tenant database configuration.',
            'db_username.required_with' => 'Database username is required for tenant database configuration.',
        ];
    }
}
