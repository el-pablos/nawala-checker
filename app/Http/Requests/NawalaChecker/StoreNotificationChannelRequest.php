<?php

namespace App\Http\Requests\NawalaChecker;

use App\Http\Requests\Traits\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationChannelRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['telegram', 'slack', 'email', 'webhook'])],
            'chat_id' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    
                    if ($type === 'telegram') {
                        // Validate Telegram chat_id (numeric or @username)
                        if (!is_numeric($value) && !str_starts_with($value, '@')) {
                            $fail('Chat ID harus berupa angka atau username Telegram (dimulai dengan @).');
                        }
                    }
                },
            ],
            'group_id' => ['nullable', 'integer', 'exists:nc_groups,id'],
            'is_active' => ['boolean'],
            'notify_on_block' => ['boolean'],
            'notify_on_recover' => ['boolean'],
            'notify_on_rotation' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama channel wajib diisi.',
            'type.required' => 'Tipe channel wajib dipilih.',
            'chat_id.required' => 'Chat ID wajib diisi.',
            'group_id.exists' => 'Group tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}

