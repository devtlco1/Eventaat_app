<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\UpdateMeRequest;
use App\Http\Resources\Mobile\MeResource;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function show(): JsonResponse
    {
        $user = request()->user();

        return response()->json((new MeResource($user))->toArray(request()));
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->string('name')->toString(),
        ])->save();

        return response()->json([
            'me' => (new MeResource($user))->toArray($request),
        ]);
    }
}

