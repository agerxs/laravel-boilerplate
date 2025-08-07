<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'roles' => $user->roles ? $user->roles->map(function($role) {
                        return [
                            'id' => $role->id ?? 0,
                            'name' => $role->name ?? '',
                            'guard_name' => $role->guard_name ?? '',
                        ];
                    }) : [],
                    'locality_id' => $user->locality_id ?? null,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message') ?? null,
                'type' => fn () => $request->session()->get('type') ?? null,
                'success' => fn () => $request->session()->get('success') ?? null,
                'error' => fn () => $request->session()->get('error') ?? null,
            ],
        ]);
    }
}
