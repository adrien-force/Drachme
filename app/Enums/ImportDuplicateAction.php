<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportDuplicateAction: string
{
    case Skip = 'skip';
    case Import = 'import';
    case Replace = 'replace';
}
