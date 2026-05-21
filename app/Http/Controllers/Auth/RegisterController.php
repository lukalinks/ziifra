<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RegisterOrganizationService;
use App\Support\Workspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, RegisterOrganizationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        $result = $service->register(
            $validated['name'],
            $validated['email'],
            $validated['password'],
            $validated['company_name'],
        );

        event(new Registered($result['user']));

        Auth::login($result['user']);

        $request->session()->put('current_organization_id', $result['organization']->id);

        return Workspace::redirect('dashboard', $result['organization']);
    }
}
