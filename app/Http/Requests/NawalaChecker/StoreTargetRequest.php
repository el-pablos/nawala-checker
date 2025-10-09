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
                    $type = $this->input('type');

                    // Check domain limit
                    $user = auth()->user();
                    if ($user && $user->domain_limit !== null) {
                        $currentCount = \App\Models\NawalaChecker\Target::where('owner_id', $user->id)->count();
                        if ($currentCount >= $user->domain_limit) {
                            $fail('Anda telah mencapai batas maksimal domain (' . $user->domain_limit . ').');
                            return;
                        }
                    }

                    // Block dangerous protocols
                    $dangerousProtocols = ['javascript:', 'data:', 'file:', 'ftp:'];
                    foreach ($dangerousProtocols as $protocol) {
                        if (stripos($value, $protocol) === 0) {
                            $fail('Domain atau URL tidak valid.');
                            return;
                        }
                    }

                    // Validate based on type
                    if ($type === 'domain') {
                        // For domain type, only accept valid domain names (no protocol)
                        $isDomain = preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $value);
                        if (!$isDomain) {
                            $fail('Format domain tidak valid.');
                        }
                    } elseif ($type === 'url') {
                        // For URL type, only accept valid HTTP/HTTPS URLs
                        $isUrl = filter_var($value, FILTER_VALIDATE_URL);
                        $isHttpUrl = $isUrl && (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0);
                        if (!$isHttpUrl) {
                            $fail('Format URL tidak valid. Hanya HTTP/HTTPS yang diperbolehkan.');
                        }
                    } else {
                        // Fallback: accept either domain or URL
                        $isDomain = preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $value);
                        $isUrl = filter_var($value, FILTER_VALIDATE_URL);
                        if (!$isDomain && !$isUrl) {
                            $fail('Domain atau URL tidak valid.');
                        }
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
            'owner_id' => ['required', 'integer', 'exists:users,id'], // Added to allow owner_id in validated data
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

