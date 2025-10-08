<?php

namespace App\Http\Requests\NawalaChecker;

use App\Http\Requests\Traits\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShortlinkRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('nc_shortlinks', 'slug'),
            ],
            'group_id' => ['nullable', 'integer', 'exists:nc_shortlink_groups,id'],
            'is_active' => ['boolean'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.url' => ['required', 'url:http,https'],
            'targets.*.priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'targets.*.weight' => ['required', 'integer', 'min:1', 'max:1000'],
            'targets.*.is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'targets.required' => 'Minimal satu target harus ditambahkan.',
            'targets.*.url.required' => 'URL target wajib diisi.',
            'targets.*.url.url' => 'URL target tidak valid.',
            'targets.*.priority.required' => 'Priority wajib diisi.',
            'targets.*.priority.min' => 'Priority minimal 1.',
            'targets.*.weight.required' => 'Weight wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'created_by' => auth()->id(),
        ]);
    }
}

