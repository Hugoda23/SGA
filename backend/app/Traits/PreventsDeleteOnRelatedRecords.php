<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

trait PreventsDeleteOnRelatedRecords
{
    protected function deleteWithGuard($model, ?Closure $hasRelatedRecords, string $message): JsonResponse
    {
        if ($hasRelatedRecords && $hasRelatedRecords($model)) {
            return response()->json(['message' => $message], 409);
        }

        try {
            $model->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json(['message' => $message], 409);
            }

            throw $e;
        }

        return response()->json(null, 204);
    }
}
