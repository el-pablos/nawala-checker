<?php

namespace App\Http\Requests\Traits;

use Illuminate\Support\Str;

trait SanitizesInput
{
    /**
     * Sanitize the input data before validation.
     */
    protected function prepareForValidation(): void
    {
        $sanitized = $this->sanitizeInputData($this->all());
        $this->replace($sanitized);
    }

    /**
     * Recursively sanitize input data.
     */
    protected function sanitizeInputData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInputData($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($key, $value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a string value based on field name.
     */
    protected function sanitizeString(string $key, string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Sanitize based on field type
        if (Str::contains($key, ['slug', 'username'])) {
            return Str::slug($value);
        }

        if (Str::contains($key, ['email'])) {
            return strtolower($value);
        }

        if (Str::contains($key, ['url', 'domain'])) {
            return strtolower($value);
        }

        // Strip tags for general text fields
        if (Str::contains($key, ['name', 'title', 'label'])) {
            return strip_tags($value);
        }

        // For description and notes, allow basic HTML but sanitize
        if (Str::contains($key, ['description', 'notes', 'message'])) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }
}

