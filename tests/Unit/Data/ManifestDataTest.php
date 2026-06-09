<?php

use App\Data\BifrostData;
use App\Data\DeployData;
use App\Data\ManifestData;
use App\Data\ModelData;
use App\Data\QualityData;
use App\Data\StackData;

describe('ManifestData', function () {
    it('constructs from a full JSON manifest', function () {
        $data = ManifestData::from([
            'name' => 'billing-service',
            'description' => 'Handles billing and subscriptions',
            'stack' => [
                'framework' => 'laravel',
                'type' => 'microservice',
                'php' => '8.2',
                'auth' => 'github-socialite',
                'queue' => 'redis',
                'db' => 'sqlite',
            ],
            'models' => [
                [
                    'name' => 'Plan',
                    'attributes' => ['name', 'price_cents'],
                    'relationships' => [
                        ['type' => 'hasMany', 'model' => 'Subscription'],
                    ],
                ],
            ],
            'bifrost' => [
                'source' => 'billing-service',
                'events' => ['subscription.created'],
            ],
            'quality' => [
                'runner' => true,
                'coverage' => 100,
                'pint' => true,
                'phpstan' => true,
            ],
            'deploy' => [
                'host' => 'odin',
                'quadlet' => true,
            ],
        ]);

        expect($data->name)->toBe('billing-service')
            ->and($data->description)->toBe('Handles billing and subscriptions')
            ->and($data->stack)->toBeInstanceOf(StackData::class)
            ->and($data->stack->framework)->toBe('laravel')
            ->and($data->models)->toHaveCount(1)
            ->and($data->models->first())->toBeInstanceOf(ModelData::class)
            ->and($data->models->first()->name)->toBe('Plan')
            ->and($data->bifrost)->toBeInstanceOf(BifrostData::class)
            ->and($data->bifrost->source)->toBe('billing-service')
            ->and($data->quality)->toBeInstanceOf(QualityData::class)
            ->and($data->quality->coverage)->toBe(100.0)
            ->and($data->deploy)->toBeInstanceOf(DeployData::class)
            ->and($data->deploy->host)->toBe('odin');
    });

    it('serialises back to array', function () {
        $data = ManifestData::from([
            'name' => 'my-service',
            'description' => 'Test service',
            'stack' => [
                'framework' => 'laravel',
                'type' => 'microservice',
                'php' => '8.3',
                'auth' => 'none',
                'queue' => 'sync',
                'db' => 'sqlite',
            ],
            'models' => [],
            'bifrost' => ['source' => 'my-service', 'events' => []],
            'quality' => ['runner' => false, 'coverage' => 80, 'pint' => false, 'phpstan' => false],
            'deploy' => ['host' => 'staging', 'quadlet' => false],
        ]);

        $array = $data->toArray();

        expect($array['name'])->toBe('my-service')
            ->and($array['stack']['db'])->toBe('sqlite');
    });

    it('handles multiple models', function () {
        $data = ManifestData::from([
            'name' => 'shop',
            'description' => 'E-commerce service',
            'stack' => [
                'framework' => 'laravel',
                'type' => 'microservice',
                'php' => '8.2',
                'auth' => 'none',
                'queue' => 'redis',
                'db' => 'mysql',
            ],
            'models' => [
                ['name' => 'Product', 'attributes' => ['title'], 'relationships' => []],
                ['name' => 'Order', 'attributes' => ['total'], 'relationships' => []],
            ],
            'bifrost' => ['source' => 'shop', 'events' => ['order.placed']],
            'quality' => ['runner' => true, 'coverage' => 100, 'pint' => true, 'phpstan' => true],
            'deploy' => ['host' => 'odin', 'quadlet' => true],
        ]);

        expect($data->models)->toHaveCount(2);
    });
});
