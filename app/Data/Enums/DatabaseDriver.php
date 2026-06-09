<?php

namespace App\Data\Enums;

enum DatabaseDriver: string
{
    case Sqlite = 'sqlite';
    case Mysql = 'mysql';
    case Pgsql = 'pgsql';
}
