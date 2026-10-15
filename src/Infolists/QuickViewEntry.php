<?php

namespace ManojKiran\FilamentQuickView\Infolists;

use Filament\Infolists\Components\TextEntry;
use ManojKiran\FilamentQuickView\Concerns\HasQuickView;

/**
 * A text entry whose value opens a quick view slide-over of the related record.
 */
class QuickViewEntry extends TextEntry
{
    use HasQuickView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpQuickView();
    }
}
