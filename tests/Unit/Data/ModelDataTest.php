<?php

use App\Data\ModelData;
use App\Data\RelationshipData;

describe('ModelData', function () {
    it('constructs from array with relationships', function () {
        $data = ModelData::from([
            'name' => 'Plan',
            'attributes' => ['name:string', 'price_cents:integer', 'is_active:boolean'],
            'relationships' => [
                ['type' => 'hasMany', 'model' => 'Subscription'],
            ],
        ]);

        expect($data->name)->toBe('Plan')
            ->and($data->attributes)->toBe(['name:string', 'price_cents:integer', 'is_active:boolean'])
            ->and($data->relationships)->toHaveCount(1)
            ->and($data->relationships->first())->toBeInstanceOf(RelationshipData::class)
            ->and($data->relationships->first()->type)->toBe('hasMany');
    });

    it('defaults attributes to empty array', function () {
        $data = ModelData::from([
            'name' => 'Tag',
            'relationships' => [],
        ]);

        expect($data->attributes)->toBe([]);
    });

    it('accepts multiple relationships', function () {
        $data = ModelData::from([
            'name' => 'User',
            'attributes' => ['email:string'],
            'relationships' => [
                ['type' => 'hasMany', 'model' => 'Post'],
                ['type' => 'hasOne', 'model' => 'Profile'],
            ],
        ]);

        expect($data->relationships)->toHaveCount(2);
    });

    it('accepts plain attribute names that default to string type', function () {
        $data = ModelData::from([
            'name' => 'Product',
            'attributes' => ['name', 'slug'],
            'relationships' => [],
        ]);

        expect($data->attributes)->toBe(['name', 'slug']);
    });
});
