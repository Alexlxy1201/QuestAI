<?php

namespace App\Http\Controllers;

class EssayController extends Controller
{
    public function index()
    {
        return view('essay-pro'); // resources/views/essay-pro.blade.php
    }
}
