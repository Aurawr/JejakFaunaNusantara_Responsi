<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spesies;
use Illuminate\Http\JsonResponse;

class SpesiesApiController extends Controller
{
    public function index(): JsonResponse
    {
        $spesies = Spesies::with('provinsi')->get();

        return response()->json($spesies);
    }

    public function show(int $id): JsonResponse
    {
        $spesies = Spesies::with('provinsi')->findOrFail($id);

        return response()->json($spesies);
    }
}
