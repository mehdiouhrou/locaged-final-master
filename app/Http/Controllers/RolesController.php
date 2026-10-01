<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
        // Règle absolue : $user->can() dans les controllers, jamais hasRole()
        if (!auth()->user()->can('manage roles') && !auth()->user()->can('view roles page')) {
            abort(403, 'Accès non autorisé.');
        }

        return view('roles.index');
    }
}
