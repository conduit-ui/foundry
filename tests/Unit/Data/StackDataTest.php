<?php

use App\Data\Enums\AuthDriver;
use App\Data\Enums\DatabaseDriver;
use App\Data\StackData;

describe('StackData', function () {
    it('constructs from array', function () {
        $data = StackData::from([
            'framework' => 'laravel',
            'type' => 'microservice',
            'php' => '8.2',
            'auth' => 'github-socialite',
            'queue' => 'redis',
            'db' => 'sqlite',
        ]);

        expect($data->framework)->toBe('laravel')
            ->and($data->type)->toBe('microservice')
            ->and($data->php)->toBe('8.2')
            ->and($data->auth)->toBe(AuthDriver::GitHubSocialite)
            ->and($data->queue)->toBe('redis')
            ->and($data->db)->toBe(DatabaseDriver::Sqlite);
    });

    it('constructs directly', function () {
        $data = new StackData(
            framework: 'laravel',
            type: 'api',
            php: '8.3',
            auth: AuthDriver::None,
            queue: 'sync',
            db: DatabaseDriver::Mysql,
        );

        expect($data->framework)->toBe('laravel')
            ->and($data->auth)->toBe(AuthDriver::None)
            ->and($data->db)->toBe(DatabaseDriver::Mysql);
    });

    it('supports all auth driver cases', function () {
        expect(AuthDriver::GitHubSocialite->value)->toBe('github-socialite')
            ->and(AuthDriver::GoogleSocialite->value)->toBe('google-socialite')
            ->and(AuthDriver::LoginLink->value)->toBe('login-link')
            ->and(AuthDriver::None->value)->toBe('none');
    });

    it('supports all database driver cases', function () {
        expect(DatabaseDriver::Sqlite->value)->toBe('sqlite')
            ->and(DatabaseDriver::Mysql->value)->toBe('mysql')
            ->and(DatabaseDriver::Pgsql->value)->toBe('pgsql');
    });
});
