<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::query()->latest()->paginate(20));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($payload);

        return response()->json($user->refresh());
    }

    public function destroy(User $user): JsonResponse
    {
        // حماية الحساب الأساسي: النظام لا يعمل من دون super admin.
        if ($user->email === 'super.admin@gmail.com') {
            return response()->json(['message' => 'لا يمكن حذف حساب المدير الأعلى.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف المستخدم بنجاح.']);
    }
}
