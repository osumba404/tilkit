<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Support\QrCodeGenerator;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicToolController extends Controller
{
    public function collect(DcProject $tool): View
    {
        abort_unless($tool->public_collection_enabled, 404);

        $tool->load([
            'forms' => fn ($q) => $q->where('status', 'published')->orderBy('title'),
        ]);

        $publicUrl = $tool->publicCollectUrl();

        return view('public.tool-collect', compact('tool', 'publicUrl'));
    }

    public function qr(DcProject $tool): Response
    {
        abort_unless($tool->public_collection_enabled, 404);

        $svg = QrCodeGenerator::svg($tool->publicCollectUrl());

        return response($svg, HttpResponse::HTTP_OK, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
