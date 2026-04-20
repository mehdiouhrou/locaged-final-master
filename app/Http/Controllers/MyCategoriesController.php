<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class MyCategoriesController extends Controller
{
    /**
     * Liste des catégories accessibles en consultation (respecte le GlobalScope Category).
     */
    public function index(): View
    {
        abort_unless(auth()->check(), 403);

        $categories = Category::query()
            ->withCount(['documents', 'subcategories'])
            ->orderBy('name')
            ->get();

        return view('categories.my-accessible', compact('categories'));
    }
}
