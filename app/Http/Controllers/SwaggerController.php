<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    public function docs()
    {
        return new Response(
            file_get_contents(storage_path('api-docs/index.html')),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    public function openapi()
    {
        return new Response(
            file_get_contents(storage_path('api-docs/api-docs.yaml')),
            200,
            ['Content-Type' => 'application/yaml']
        );
    }

    public function yaml()
    {
        return new Response(
            file_get_contents(storage_path('api-docs/api-docs.yaml')),
            200,
            ['Content-Type' => 'application/yaml']
        );
    }
} 