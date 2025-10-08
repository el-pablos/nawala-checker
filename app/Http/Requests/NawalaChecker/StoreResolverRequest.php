<?php

namespace App\Http\Requests\NawalaChecker;

use App\Http\Requests\Traits\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResolverRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::in(['dns', 'doh', 'dot'])],
            'address' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    
                    if ($type === 'dns') {
                        // Validate IP address
                        if (!filter_var($value, FILTER_VALIDATE_IP)) {
                            $fail('Address harus berupa IP address yang valid untuk DNS.');
                        }
                    } elseif (in_array($type, ['doh', 'dot'])) {
                        // Validate URL
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail('Address harus berupa URL yang valid untuk DoH/DoT.');
                        }
                    }
                },
            ],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'weight' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama resolver wajib diisi.',
            'type.required' => 'Tipe resolver wajib dipilih.',
            'type.in' => 'Tipe resolver harus dns, doh, atau dot.',
            'address.required' => 'Address wajib diisi.',
            'priority.required' => 'Priority wajib diisi.',
            'weight.required' => 'Weight wajib diisi.',
        ];
    }
}

