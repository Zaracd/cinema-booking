<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movie;
use App\Models\Genre;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'pageTitle'  => 'Home',
            'nowShowing' => Movie::byStatus('Now Showing', 8),
            'comingSoon' => Movie::byStatus('Coming Soon', 4),
            'genres'     => Genre::allGenres(),
        ]);
    }
}
