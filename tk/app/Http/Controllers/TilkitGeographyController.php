<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Services\Tilkit\GeographyCatalogService;
use App\Services\ToolAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TilkitGeographyController extends Controller
{
    public function continents(Request $request, DcProject $tool, ToolAccessService $access, GeographyCatalogService $geo): JsonResponse
    {
        $access->assertCanView($request->user(), $tool);

        return response()->json(['data' => $geo->continents()]);
    }

    public function countries(Request $request, DcProject $tool, ToolAccessService $access, GeographyCatalogService $geo): JsonResponse
    {
        $access->assertCanView($request->user(), $tool);

        $continent = $request->string('continent')->trim();
        if ($continent === '') {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $geo->countriesForContinent($continent->toString())]);
    }
}
