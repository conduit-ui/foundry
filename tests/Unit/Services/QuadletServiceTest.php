<?php

use App\Data\BifrostData;
use App\Data\DeployData;
use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use App\Data\ManifestData;
use App\Data\ModelData;
use App\Data\QualityData;
use App\Data\StackData;
use App\Services\QuadletService;
use Illuminate\Support\Facades\Process;
use Spatie\LaravelData\DataCollection;

function quadletManifest(): ManifestData
{
    return new ManifestData(
        name: 'billing-service',
        description: 'Billing',
        stack: new StackData('laravel', 'microservice', '8.2', AuthDriver::None, 'redis', DatabaseDriver::Sqlite),
        models: new DataCollection(ModelData::class, []),
        bifrost: new BifrostData('billing-service', []),
        quality: new QualityData(true, 100.0, true, true),
        deploy: new DeployData('odin', true),
    );
}

describe('QuadletService', function () {
    it('generates a quadlet file containing the service name', function () {
        $service = new QuadletService;
        $quadlet = $service->generate(quadletManifest());

        expect($quadlet)
            ->toContain('billing-service')
            ->toContain('[Container]')
            ->toContain('[Install]');
    });

    it('deploys by SSHing to the deploy host and enabling the service', function () {
        Process::fake();

        $service = new QuadletService;
        $service->deploy(quadletManifest(), '/tmp/billing-service');

        Process::assertRan(fn ($process) => str_contains($process->command, 'ssh') && str_contains($process->command, 'systemctl --user daemon-reload'));
    });
});
