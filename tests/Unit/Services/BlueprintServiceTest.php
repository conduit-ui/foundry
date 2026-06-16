<?php

use App\Data\BifrostData;
use App\Data\DeployData;
use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use App\Data\ManifestData;
use App\Data\ModelData;
use App\Data\QualityData;
use App\Data\RelationshipData;
use App\Data\StackData;
use App\Services\BlueprintService;
use Illuminate\Support\Facades\Process;
use Spatie\LaravelData\DataCollection;

function blueprintManifest(array $models = []): ManifestData
{
    return new ManifestData(
        name: 'billing-service',
        description: 'Billing',
        stack: new StackData('laravel', 'microservice', '8.2', AuthDriver::None, 'redis', DatabaseDriver::Sqlite),
        models: new DataCollection(ModelData::class, $models),
        bifrost: new BifrostData('billing-service', []),
        quality: new QualityData(true, 100.0, true, true),
        deploy: new DeployData('odin', true),
    );
}

describe('BlueprintService', function () {
    it('generates draft YAML with no models', function () {
        $service = new BlueprintService;
        $yaml = $service->generateDraft(blueprintManifest());

        expect($yaml)->toContain('models:');
    });

    it('generates draft YAML with typed attributes', function () {
        $model = new ModelData(
            name: 'Plan',
            attributes: ['name:string', 'price_cents:integer'],
            relationships: new DataCollection(RelationshipData::class, []),
        );

        $service = new BlueprintService;
        $yaml = $service->generateDraft(blueprintManifest([$model]));

        expect($yaml)
            ->toContain('Plan:')
            ->toContain('name: string')
            ->toContain('price_cents: integer');
    });

    it('defaults attribute type to string when no type given', function () {
        $model = new ModelData(
            name: 'Plan',
            attributes: ['title'],
            relationships: new DataCollection(RelationshipData::class, []),
        );

        $service = new BlueprintService;
        $yaml = $service->generateDraft(blueprintManifest([$model]));

        expect($yaml)->toContain('title: string');
    });

    it('includes relationships in YAML', function () {
        $model = new ModelData(
            name: 'Plan',
            attributes: ['name:string'],
            relationships: new DataCollection(RelationshipData::class, [
                new RelationshipData('hasMany', 'Subscription'),
            ]),
        );

        $service = new BlueprintService;
        $yaml = $service->generateDraft(blueprintManifest([$model]));

        expect($yaml)
            ->toContain('relationships:')
            ->toContain('hasMany: Subscription');
    });

    it('writes draft.yaml, runs blueprint:build, and commits generated files to target repo', function () {
        Process::fake([
            '*blueprint:build*' => Process::result(
                "   CREATED  Factory [database/factories/TestFactory.php] built successfully.\n".
                "   CREATED  Model [app/Models/Test.php] built successfully.\n"
            ),
            '*git add*' => Process::result(''),
            '*git*commit*' => Process::result(''),
        ]);

        $tmpPath = sys_get_temp_dir() . '/blueprint-test-' . uniqid();
        mkdir($tmpPath);

        $service = new BlueprintService;
        $service->build(blueprintManifest(), $tmpPath);

        expect(file_exists($tmpPath . '/draft.yaml'))->toBeTrue();
        Process::assertRan(fn ($p) => str_contains($p->command, 'blueprint:build'));
        Process::assertRan(fn ($p) => str_contains($p->command, 'git add'));
        Process::assertRan(fn ($p) => str_contains($p->command, 'commit'));

        @unlink($tmpPath . '/draft.yaml');
        rmdir($tmpPath);
    });
});
