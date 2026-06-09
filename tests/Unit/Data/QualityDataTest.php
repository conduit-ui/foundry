<?php

use App\Data\QualityData;

describe('QualityData', function () {
    it('constructs from array', function () {
        $data = QualityData::from([
            'runner' => true,
            'coverage' => 100.0,
            'pint' => true,
            'phpstan' => true,
        ]);

        expect($data->runner)->toBeTrue()
            ->and($data->coverage)->toBe(100.0)
            ->and($data->pint)->toBeTrue()
            ->and($data->phpstan)->toBeTrue();
    });

    it('accepts disabled quality gates', function () {
        $data = QualityData::from([
            'runner' => false,
            'coverage' => 80.5,
            'pint' => false,
            'phpstan' => false,
        ]);

        expect($data->runner)->toBeFalse()
            ->and($data->coverage)->toBe(80.5)
            ->and($data->pint)->toBeFalse()
            ->and($data->phpstan)->toBeFalse();
    });

    it('accepts integer coverage cast to float', function () {
        $data = QualityData::from([
            'runner' => true,
            'coverage' => 90,
            'pint' => true,
            'phpstan' => false,
        ]);

        expect($data->coverage)->toBeFloat()
            ->and($data->coverage)->toBe(90.0);
    });
});
