<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = trim($this->input('name'));
            $this->merge(['name' => $name]);
        }
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',

                function ($attribute, $value, $fail) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $fail('文字エンコーディングが正しくありません');
                        return;
                    }

                    $length = mb_strlen($value, 'UTF-8');
                    if ($length > 20) {
                        $fail('ユーザーネームは20文字以内で入力してください');
                        return;
                    }

                    if ($length === 0) {
                        $fail('ユーザーネームを入力してください');
                        return;
                    }

                    if (!preg_match('/^[\p{Hiragana}\p{Katakana}\p{Han}\p{L}\p{N}_\-\s・、。！？\(\)（）]+$/u', $value)) {
                        $fail('使用できない文字が含まれています');
                        return;
                    }

                    if (preg_match('/\s{2,}/', $value)) {
                        $fail('連続するスペースは使用できません');
                    }
                },
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'ユーザーネームを入力してください',
            'name.string' => 'ユーザーネームは文字列で入力してください',
            'avatar.image' => '画像ファイルを選択してください',
            'avatar.mimes' => '対応している形式: JPEG, PNG, GIF, WebP',
            'avatar.max' => 'ファイルサイズは5MB以下にしてください',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }

        return $validated;
    }
}