<?php

namespace ManojKiran\FilamentQuickView\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for a Filament resource: the quick view only asks it for a record title.
 */
class AuthorResource
{
    public static function getRecordTitle(?Model $record): string
    {
        return strtoupper($record->name);
    }
}
