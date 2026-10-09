<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Serves config/property_schema.php to the listing form so the frontend and
 * backend validation stay driven by one definition.
 */
class PropertySchemaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $schema = config('property_schema');

        return response()->json(['data' => [
            'groups' => $schema['groups'],
            'types' => $schema['types'],
        ]]);
    }
}
