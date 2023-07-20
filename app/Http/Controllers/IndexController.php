<?php

namespace App\Http\Controllers;

use App\Services\Autherization;

class IndexController
{
    protected $autherization;
    
    public function __construct(Autherization $autherization)
    {
        $this->autherization = $autherization;
    }

    public function index()
    {
        $this->autherization->authorization();
        return view('welcome');
    }
}