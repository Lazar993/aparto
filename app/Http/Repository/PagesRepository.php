<?php

namespace App\Http\Repository;

use App\Models\Page;

class PagesRepository
{
    public static function pages()
    {
        $pages = Page::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        return $pages;
    }
}