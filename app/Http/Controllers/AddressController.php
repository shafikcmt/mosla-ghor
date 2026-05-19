<?php

namespace App\Http\Controllers;

use App\Models\BdUnion;
use App\Models\BdUpazila;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function unions(int $upazila): JsonResponse
    {
        $upazilaModel = BdUpazila::where('id', $upazila)
            ->where('is_active', true)
            ->firstOrFail();

        $unions = BdUnion::where('upazila_id', $upazilaModel->id)
            ->where('is_active', true)
            ->orderBy('bn_name')
            ->get(['id', 'name', 'bn_name']);

        return response()->json($unions);
    }
}
