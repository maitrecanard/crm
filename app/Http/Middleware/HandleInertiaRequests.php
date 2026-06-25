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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'vendeur' => config('crm.vendeur'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            // Notifications in-app (badge) — uniquement pour les comptes admin.
            'notifications' => fn () => ($user && $user->isAdmin())
                ? $user->unreadNotifications()->latest()->take(10)->get()
                    ->map(fn ($n) => [
                        'id'         => $n->id,
                        'titre'      => $n->data['titre'] ?? 'Notification',
                        'url'        => $n->data['url'] ?? null,
                        'created_at' => $n->created_at->toIso8601String(),
                    ])
                : [],
        ];
    }
}
