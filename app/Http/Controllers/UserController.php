<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $ranking = User::orderByDesc('score')->get(['nickname', 'score']);
        return response($ranking->toJson());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $status = null;
        $content = null;

        try {
            $user = User::create([
                'nickname' => $request->nickname,
                'password' => Hash::make($request->password)
            ]);

            $status = 201;
            $content = $user->toJson();
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                $status = 409;
                $content = "Nickname alredy exists";
            } else {
                $status = 500;
                $content = $e->getMessage();
            }
        } finally {
            return response($content, $status);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        return response($user->toJson());
    }
}
