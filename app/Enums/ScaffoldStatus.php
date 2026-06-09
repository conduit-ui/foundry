<?php

namespace App\Enums;

enum ScaffoldStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Complete = 'complete';
    case Failed = 'failed';
}
