<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Show the application's home page
     */
    public function home()
    {
        // Return the new welcome view
        return view('welcome-new');
    }

    /**
     * Show the about page
     */
    public function about()
    {
        return view('tentang');
    }

    /**
     * Show the blog/news page
     */
    public function blog()
    {
        return view('berita');
    }
}
