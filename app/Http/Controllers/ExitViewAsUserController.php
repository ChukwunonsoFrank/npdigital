<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExitViewAsUserController extends Controller
{
    /**
     * Return to the admin panel after previewing a user dashboard.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('view_as_admin_id');
        $request->session()->forget('view_as_user_id');

        $admin = $adminId === null
            ? null
            : User::query()->where('is_admin', true)->find($adminId);

        if (! $admin) {
            abort(403);
        }

        Auth::login($admin);

        return redirect()->route('admin.dashboard.users');
    }
}
