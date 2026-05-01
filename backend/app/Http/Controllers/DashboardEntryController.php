<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardEntryController extends Controller
{
    private const PLATFORM_ROLES = ['super_admin', 'operations_admin'];

    private const RESTAURANT_ROLES = ['restaurant_owner', 'branch_manager', 'restaurant_host'];

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return $this->respondForAuthenticatedUser($request);
        }

        return view('auth.dashboard-login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return $this->respondForAuthenticatedUser($request);
        }

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, false)) {
            return back()
                ->withErrors(['email' => __('These credentials do not match our records.')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return $this->redirectOrDenyDashboardAccess($request, $user);
    }

    private function respondForAuthenticatedUser(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->redirectOrDenyDashboardAccess($request, $user);
    }

    private function redirectOrDenyDashboardAccess(Request $request, User $user): RedirectResponse
    {
        if ($user->hasAnyRole(self::PLATFORM_ROLES)) {
            return redirect('/platform');
        }

        if ($user->hasAnyRole(self::RESTAURANT_ROLES)) {
            return redirect('/restaurant');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('dashboard.login')
            ->with('dashboard_access_denied', __('Dashboard sign-in is not available for this account. Please use the mobile app.'));
    }
}
