<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Recipe;

class MyPageController extends Controller
{
    public function mypage()
    {
        // 開発用のダミーデータ（本番では使わない）
        $recipes = collect([
            (object)[
                'title' => 'テストレシピ1',
                'genre' => '和食',
            ],
            (object)[
                'title' => 'テストレシピ2',
                'genre' => '洋食',
            ]
        ]);

        return view('user.mypage', compact('recipes'));


    }


}
