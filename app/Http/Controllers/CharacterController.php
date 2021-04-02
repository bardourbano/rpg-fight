<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CharacterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $heroes = Character::hero()->get();

        return response($heroes->toJson());
    }
}
