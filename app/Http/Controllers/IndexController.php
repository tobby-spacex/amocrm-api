<?php

namespace App\Http\Controllers;

use App\Services\Autherization;
use Illuminate\Contracts\View\View;

class IndexController
{
    protected $autherization;
    
    public function __construct(Autherization $autherization)
    {
        $this->autherization = $autherization;
    }

    public function index(): View
    {
        $this->autherization->authorization();
        return view('welcome');
    }
}