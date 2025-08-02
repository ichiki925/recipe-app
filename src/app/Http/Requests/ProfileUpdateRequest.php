<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check(); // ✅ 変更：ログイン済みユーザーのみ許可（falseから変更）
    }

    /**
     * バリデーション前の前処理
     * FormDataの問題やエンコーディングの問題をここで解決
     */
    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = trim($this->input('name'));
            $this->merge(['name' => $name]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                // カスタムバリデーション：日本語対応の文字数チェック
                function ($attribute, $value, $fail) {
                    // UTF-8エンコーディングチェック
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $fail('文字エンコーディングが正しくありません');
                        return;
                    }
                    
                    // 文字数チェック（バイト数ではなく文字数）
                    $length = mb_strlen($value, 'UTF-8');
                    if ($length > 20) {
                        $fail('ユーザーネームは20文字以内で入力してください');
                        return;
                    }
                    
                    if ($length === 0) {
                        $fail('ユーザーネームを入力してください');
                        return;
                    }
                    
                    // 日本語文字の許可（ひらがな、カタカナ、漢字、英数字、一部記号）
                    if (!preg_match('/^[\p{Hiragana}\p{Katakana}\p{Han}\p{L}\p{N}_\-\s・、。！？\(\)（）]+$/u', $value)) {
                        $fail('使用できない文字が含まれています');
                        return;
                    }
                    
                    // 連続スペースチェック
                    if (preg_match('/\s{2,}/', $value)) {
                        $fail('連続するスペースは使用できません');
                    }
                },
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB
            ],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
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

    /**
     * バリデーション失敗時のカスタム処理
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // デバッグ情報をログに出力
        \Log::error('Profile validation failed:', [
            'errors' => $validator->errors()->toArray(),
            'input_data' => $this->all(),
            'name_length' => $this->has('name') ? mb_strlen($this->input('name'), 'UTF-8') : 'N/A',
            'name_bytes' => $this->has('name') ? strlen($this->input('name')) : 'N/A',
        ]);

        parent::failedValidation($validator);
    }

    /**
     * バリデーション済みデータの取得（カスタム処理付き）
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // nameフィールドの最終処理
        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        
        return $validated;
    }
}