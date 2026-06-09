<?php

use App\Enums\ScaffoldStatus;

describe('ScaffoldStatus', function () {
    it('has the correct cases', function () {
        expect(ScaffoldStatus::cases())->toHaveCount(4);
    });

    it('has queued case with correct value', function () {
        expect(ScaffoldStatus::Queued->value)->toBe('queued');
    });

    it('has running case with correct value', function () {
        expect(ScaffoldStatus::Running->value)->toBe('running');
    });

    it('has complete case with correct value', function () {
        expect(ScaffoldStatus::Complete->value)->toBe('complete');
    });

    it('has failed case with correct value', function () {
        expect(ScaffoldStatus::Failed->value)->toBe('failed');
    });

    it('can be created from a string value', function () {
        expect(ScaffoldStatus::from('queued'))->toBe(ScaffoldStatus::Queued)
            ->and(ScaffoldStatus::from('running'))->toBe(ScaffoldStatus::Running)
            ->and(ScaffoldStatus::from('complete'))->toBe(ScaffoldStatus::Complete)
            ->and(ScaffoldStatus::from('failed'))->toBe(ScaffoldStatus::Failed);
    });
});
