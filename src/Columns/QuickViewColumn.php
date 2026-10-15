<?php

namespace ManojKiran\FilamentQuickView\Columns;

use Filament\Tables\Columns\TextColumn;
use ManojKiran\FilamentQuickView\Concerns\HasQuickView;

/**
 * A text column whose value opens a quick view slide-over of the related record.
 */
class QuickViewColumn extends TextColumn
{
    use HasQuickView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpQuickView();
    }
}
