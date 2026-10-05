<?php

namespace App\Http\Requests;

use App\Models\Courier;
use App\Services\CourierDriverFactory;
use App\Services\CourierProviderConfiguration;
use Illuminate\Foundation\Http\FormRequest;
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
        ] + ($this->configuration()?->rules() ?? []);
    }

    public function configuration(): ?CourierProviderConfiguration
    {
        return app(CourierDriverFactory::class)->configuration($this->route('courier'));
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Courier $courier */
            $courier = $this->route('courier');
            $configuration = $this->configuration();
            if ($configuration === null) {
                $validator->errors()->add('api_enabled', 'This courier has no API integration. Use manual booking.');

                return;
            }

            if ($this->boolean('api_enabled')) {
                foreach ($configuration->fields as $field => $schema) {
                    if (! ($schema['required_when_enabled'] ?? false)) {
                        continue;
                    }
                    $replacement = (! $schema['secret'] || $this->boolean('replace_api_credentials')) && $this->filled($field);
                    if (! $replacement && blank($courier->{$field})) {
                        $validator->errors()->add($field, 'Configure '.$schema['label'].' before enabling the integration.');
                    }
                }
            }
        }];
    }
}
