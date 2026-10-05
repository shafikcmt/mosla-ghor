<?php

namespace App\Services;

use App\Models\Courier;
use Illuminate\Validation\Rule;

/** Trusted driver metadata, never constructed from request input. */
class CourierProviderConfiguration
{
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
        public readonly bool $canTestConnection = true,
    ) {
        foreach ($fields as $field => $schema) {
            // This first iteration deliberately uses the existing storage only.
            // Secrets must stay in encrypted, hidden columns; endpoints are selects.
            if (! in_array($field, ['api_key', 'api_secret', 'base_url'], true)
                || (($schema['secret'] ?? false) !== ($field !== 'base_url'))
                || ($field === 'base_url' && ($schema['type'] !== 'select' || empty($schema['options'])))) {
                throw new \InvalidArgumentException('Unsupported courier configuration storage or field type.');
            }
            if ($field === 'base_url') {
                foreach (array_keys($schema['options']) as $endpoint) {
                    if (! filter_var($endpoint, FILTER_VALIDATE_URL) || parse_url($endpoint, PHP_URL_SCHEME) !== 'https'
                        || parse_url($endpoint, PHP_URL_USER) !== null || parse_url($endpoint, PHP_URL_PASS) !== null) {
                        throw new \InvalidArgumentException('Courier endpoints must be trusted HTTPS URLs without credentials.');
                    }
                }
            }
        }
    }

    public function rules(): array
    {
        $rules = [];
        foreach ($this->fields as $field => $schema) {
            $rules[$field] = $schema['rules'];
            if ($schema['type'] === 'select') {
                $rules[$field][] = Rule::in(array_keys($schema['options']));
            }
        }
        // Preserve the existing endpoint-select request contract.
        if (isset($rules['base_url'])) {
            $rules['base_url_select'] = $rules['base_url'];
        }

        return $rules;
    }

    public function isConfigured(Courier $courier): bool
    {
        foreach ($this->fields as $field => $schema) {
            if (($schema['required_when_enabled'] ?? false) && blank($courier->{$field})) {
                return false;
            }
        }

        return true;
    }
}
