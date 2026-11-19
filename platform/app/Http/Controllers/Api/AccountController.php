<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $user->update($request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:40'],
        ]));

        return new UserResource($user);
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = $request->user();
        $user->update(['password' => $request->input('password')]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        $user->forceFill(['web_session_id' => null])->save();

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }

    public function devices(Request $request)
    {
        $current = $request->user()->currentAccessToken()->name;

        return response()->json(['data' => $request->user()->devices()->latest('last_seen_at')->get()->map(fn (Device $d) => [
            'name' => $d->name,
            'platform' => $d->platform,
            'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            'is_current' => $d->device_id === $current,
            'is_blocked' => $d->is_blocked,
        ]), 'max_devices' => (int) setting('max_devices')]);
    }

    public function certificates(Request $request)
    {
        return response()->json(['data' => $request->user()->certificates()->with('course:id,title')->latest('issued_at')->get()->map(fn ($c) => [
            'code' => $c->code,
            'course' => $c->course->title,
            'issued_at' => $c->issued_at->toDateString(),
            'url' => route('certificates.show', $c),
        ])]);
    }
}
