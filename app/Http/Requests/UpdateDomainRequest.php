<?php

namespace App\Http\Requests;

use App\Models\Domain;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateDomainRequest extends StoreDomainRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Domain|string|int $domain */
        $domain = $this->route('domain');
        $domainId = $domain instanceof Domain ? $domain->id : (int) $domain;

        $rules = parent::rules();
        $rules['domain'] = [
            'required',
            'string',
            'max:255',
            'regex:/^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,}$/',
            Rule::unique('domains', 'domain')->ignore($domainId),
        ];

        return $rules;
    }
}
