<?php

declare(strict_types=1);

namespace Platform\Provisioning\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\Provisioning\Services\SignupService;

final class SignupController
{
    public function __construct(
        private readonly SignupService $signupService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'store_name' => ['required', 'string', 'max:255'],
            'plan_slug' => ['required', 'string', 'max:64'],
        ]);

        try {
            $result = $this->signupService->signup(
                email: $validated['email'],
                password: $validated['password'],
                storeName: $validated['store_name'],
                planSlug: $validated['plan_slug'],
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            throw ValidationException::withMessages([
                'plan_slug' => ['The selected plan is invalid.'],
            ]);
        }

        return response()->json([
            'tenant_id' => $result['tenant_id'],
            'provisioning_run_id' => $result['provisioning_run_id'],
            'status' => $result['status'],
            'poll_url' => '/api/v1/provisioning/'.$result['tenant_id'].'/status',
        ], 202);
    }
}
