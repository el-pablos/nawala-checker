<?php

namespace App\Http\Requests\NawalaChecker;

use App\Http\Requests\Traits\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTargetRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'domain_or_url' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Validate as domain or URL
                    $isDomain = preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $value);
                    $isUrl = filter_var($value, FILTER_VALIDATE_URL);
                    
                    if (!$isDomain && !$isUrl) {
                        $fail('Domain atau URL tidak valid.');
                    }
                },
                Rule::unique('nc_targets', 'domain_or_url')
                    ->where('owner_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', 'string', Rule::in(['domain', 'url'])],
            'group_id' => ['nullable', 'integer', 'exists:nc_groups,id'],
            'enabled' => ['boolean'],
            'check_interval' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:nc_tags,id'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'domain_or_url.required' => 'Domain atau URL wajib diisi.',
            'domain_or_url.unique' => 'Domain atau URL sudah terdaftar.',
            'type.required' => 'Tipe wajib dipilih.',
            'type.in' => 'Tipe harus domain atau url.',
            'group_id.exists' => 'Group tidak ditemukan.',
            'check_interval.min' => 'Interval cek minimal 60 detik.',
            'check_interval.max' => 'Interval cek maksimal 86400 detik (24 jam).',
            'tags.*.exists' => 'Tag tidak valid.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Auto-detect type if not provided
        if (!$this->has('type')) {
            $value = $this->input('domain_or_url');
            $this->merge([
                'type' => str_starts_with($value, 'http') ? 'url' : 'domain',
            ]);
        }

        // Set owner_id
        $this->merge([
            'owner_id' => auth()->id(),
        ]);
    }
}

