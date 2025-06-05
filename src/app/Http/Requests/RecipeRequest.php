<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecipeRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'ingredients' => 'required|string',
            'body' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => '料理名を入力してください。',
            'title.max' => '料理名は100文字以内で入力してください。',
            'ingredients.required' => '材料を入力してください。',
            'body.required' => '作り方を入力してください。',
            'image.image' => '画像ファイルをアップロードしてください。',
            'image.max' => '画像サイズは2MB以内にしてください。',
        ];
    }
}
