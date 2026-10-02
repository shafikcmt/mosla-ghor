<?php

namespace App\Http\Requests;

use App\Models\Courier;
use App\Services\SteadfastService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCourierApiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'api_enabled' => ['nullable', 'boolean'],
            'replace_api_credentials' => ['nullable', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', Rule::in(SteadfastService::KNOWN_BASE_URLS)],
            'base_url_select' => ['nullable', Rule::in(SteadfastService::KNOWN_BASE_URLS)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Courier $courier */
            $courier = $this->route('courier');
            if (! $courier->supportsApi()) {
                $validator->errors()->add('api_enabled', 'This courier has no API integration. Use manual booking.');

                return;
            }

            if ($this->boolean('api_enabled')) {
                foreach (['api_key', 'api_secret'] as $field) {
                    $replacement = $this->boolean('replace_api_credentials') && $this->filled($field);
                    if (! $replacement && blank($courier->{$field})) {
                        $validator->errors()->add($field, 'Configure both API Key and Secret Key before enabling the integration.');
                    }
                }
            }
        }];
    }
}
