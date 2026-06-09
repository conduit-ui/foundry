<?php

namespace App\Http\Controllers;

use App\Data\ManifestData;
use App\Enums\ScaffoldStatus;
use App\Jobs\CodeGeneratorJob;
use App\Jobs\RepoGeneratorJob;
use App\Models\ScaffoldRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ScaffoldController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'stack' => ['required', 'array'],
            'stack.framework' => ['required', 'string'],
            'stack.type' => ['required', 'string'],
            'stack.php' => ['required', 'string'],
            'stack.auth' => ['required', 'string'],
            'stack.queue' => ['required', 'string'],
            'stack.db' => ['required', 'string'],
            'models' => ['present', 'array'],
            'bifrost' => ['required', 'array'],
            'bifrost.source' => ['required', 'string'],
            'bifrost.events' => ['present', 'array'],
            'quality' => ['required', 'array'],
            'quality.runner' => ['required', 'boolean'],
            'quality.coverage' => ['required', 'numeric'],
            'quality.pint' => ['required', 'boolean'],
            'quality.phpstan' => ['required', 'boolean'],
            'deploy' => ['required', 'array'],
            'deploy.host' => ['required', 'string'],
            'deploy.quadlet' => ['required', 'boolean'],
        ]);

        $manifest = ManifestData::from($request->all());

        $run = ScaffoldRun::create([
            'manifest' => $manifest->toArray(),
            'status' => ScaffoldStatus::Queued,
        ]);

        RepoGeneratorJob::dispatch($manifest);
        CodeGeneratorJob::dispatch($run);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status->value,
            'status_url' => route('scaffold.show', $run->id),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $run = ScaffoldRun::findOrFail($id);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status->value,
        ]);
    }
}
