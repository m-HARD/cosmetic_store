<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /** قائمة الفئات النشطة لنماذج الإدخال ونقطة البيع. */
    public function index(): JsonResponse
    {
        $rows = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }
}
