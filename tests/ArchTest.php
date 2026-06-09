<?php

arch('no debugging left in code')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('jobs are queueable')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('data objects extend spatie data')
    ->expect('App\Data')
    ->ignoring('App\Data\Enums')
    ->toExtend('Spatie\LaravelData\Data');
