<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;

class DashboardController extends Controller
{
    public function index()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'このページは管理者専用です。');
        }

        // 管理用ダッシュボード処理
        $totalRecipes = Recipe::count();
        $todayRecipes = Recipe::whereDate('created_at', today())->count();
        $deletedRecipes = Recipe::onlyTrashed()->latest()->take(5)->get();

        return view('admin.dashboard', compact('totalRecipes', 'todayRecipes', 'deletedRecipes'));
    }
}
