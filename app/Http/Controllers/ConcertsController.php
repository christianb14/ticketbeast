<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Concert;

class ConcertsController extends Controller
{
    public function show($id)
    {
        return view('concerts.show', ['concert' => Concert::published()->findOrFail($id)]);
    }
}
