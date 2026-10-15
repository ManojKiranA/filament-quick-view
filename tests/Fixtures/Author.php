<?php

namespace ManojKiran\FilamentQuickView\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A model with no table: the quick view only reads attributes.
 */
class Author extends Model
{
    protected $guarded = [];
}
