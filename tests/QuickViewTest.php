<?php

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use ManojKiran\FilamentQuickView\Columns\QuickViewColumn;
use ManojKiran\FilamentQuickView\Infolists\QuickViewEntry;
use ManojKiran\FilamentQuickView\Tests\Fixtures\Author;
use ManojKiran\FilamentQuickView\Tests\Fixtures\AuthorResource;

it('extends the stock text column and entry', function () {
    expect(QuickViewColumn::make('author.name'))->toBeInstanceOf(TextColumn::class)
        ->and(QuickViewEntry::make('author.name'))->toBeInstanceOf(TextEntry::class);
});

it('has no action or icon until a resource is given', function () {
    $column = QuickViewColumn::make('author.name')->state('Ada');

    expect($column->getAction())->toBeNull()
        ->and($column->getIcon('Ada'))->toBeNull();
});

it('shows an eye icon and opens a slide-over action once a resource is given', function () {
    $column = QuickViewColumn::make('author.name')->state('Ada')->quickView('App\Filament\Resources\AuthorResource');

    expect($column->getIcon('Ada'))->toBe(Heroicon::Eye)
        ->and($column->getColor('Ada'))->toBe('primary');

    $action = $column->getAction();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isModalSlideOver())->toBeTrue();
});

it('finds the related record from the relationship in the name, or a closure', function () {
    $author = new Author(['name' => 'Ada']);
    $book = new Author;
    $book->setRelation('author', $author);

    $find = fn (QuickViewEntry $entry, $record) => (new ReflectionMethod($entry, 'getQuickViewRelatedRecord'))->invoke($entry, $record);

    expect($find(QuickViewEntry::make('author.name'), $book))->toBe($author)
        ->and($find(QuickViewEntry::make('name')->quickViewRecord(fn () => $author), $book))->toBe($author)
        ->and($find(QuickViewEntry::make('name'), $book))->toBeNull();
});

it('shows the record title of the related record when the name is just the relationship', function () {
    $author = new Author(['name' => 'Ada']);
    $book = new Author;
    $book->setRelation('author', $author);

    $column = QuickViewEntry::make('author')->quickView(AuthorResource::class)->container(Schema::make()->record($book));

    expect($column->getState())->toBe('ADA');
});

it('leaves the state alone when the name already points at an attribute', function () {
    $author = new Author(['name' => 'Ada']);
    $book = new Author;
    $book->setRelation('author', $author);

    expect(QuickViewEntry::make('author.name')->quickView(AuthorResource::class)->container(Schema::make()->record($book))->getState())->toBe('Ada');
});

it('opens a slide-over from an infolist alone, without a resource', function () {
    $column = QuickViewColumn::make('author.name')->state('Ada')->infolist(fn ($schema) => $schema->components([]));

    expect($column->getAction())->toBeInstanceOf(Action::class)
        ->and($column->getIcon('Ada'))->toBe(Heroicon::Eye);
});
