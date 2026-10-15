# Filament Quick View

Show a related record in a slide-over, straight from a table column or an infolist entry, without leaving the page.

The value looks like a link with an eye icon. A click opens a slide-over with a few titled groups of labelled values, and a button opens the record's own page.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5

## Installation

```bash
composer require manojkiran/filament-quick-view
```

The service provider is auto-discovered.

## Usage

`QuickViewColumn` extends Filament's `TextColumn` and `QuickViewEntry` extends `TextEntry`, so every method you already know still works. Point them at the resource of the related record:

```php
use ManojKiran\FilamentQuickView\Columns\QuickViewColumn;
use ManojKiran\FilamentQuickView\Infolists\QuickViewEntry;

// in a table
QuickViewColumn::make('server.hostname')
    ->label('Mailbox server')
    ->quickView(ServerResource::class);

// in an infolist
QuickViewEntry::make('server.hostname')
    ->quickView(ServerResource::class);
```

### What is shown in the column

The relationship comes from the column name, so Filament already reads the value for you:

| Column name | Shown | Related record |
| --- | --- | --- |
| `server.hostname` | the server's `hostname` | `server` relationship |
| `server` | the server's record title (`$recordTitleAttribute` of the resource) | `server` relationship |

With the second form there is no need for `getStateUsing()`:

```php
QuickViewColumn::make('server')->quickView(ServerResource::class);
```

If the name has no relationship in it, pass one: `QuickViewColumn::make('hostname')->quickView(ServerResource::class, 'server')`, or find the record yourself with `->quickViewRecord(fn ($record) => ...)`. Call `->state()` after `quickView()` to show something else.

### What opens in the slide-over

The click opens a slide-over (a normal Filament `Action`) with an "Open full page" button to the resource's `view` page. It is only available when the user may `canView` the related record.

**By default the content is the infolist of the resource.** `->quickView(ServerResource::class)` renders `ServerResource::infolist()`, the same method Filament uses for the resource's View page, bound to the related record. There is nothing to define twice.

This needs the infolist to be defined on the resource (`public static function infolist(Schema $schema): Schema`). If your resource doesn't define one, or builds it inside its `ViewRecord` page, the slide-over is empty: pass `->infolist(...)` as shown below.

### Your own infolist

Pass a schema instead of using the resource's infolist. It is bound to the **related record**, so `TextEntry::make('name')` shows `server.name`:

```php
QuickViewColumn::make('server.hostname')
    ->infolist(fn (Schema $schema) => $schema->components([
        Section::make('Server')->columns(2)->schema([
            TextEntry::make('hostname')->copyable()->icon(Heroicon::Server),
            TextEntry::make('state')->badge(),
        ]),
    ]));
```

The resource is optional when you give an infolist. Without it there is no "Open full page" button, and the link is hidden when the model has a policy that denies `view`. Add `->quickView(ServerResource::class)` to get the button and the resource's `canView` check.

### Customising the slide-over

```php
->modifyQuickViewActionUsing(fn (Action $action) => $action->modalWidth(Width::FiveExtraLarge))
```

Texts are translatable: `php artisan vendor:publish --tag=filament-quick-view-translations`.

## Testing

```bash
composer install
vendor/bin/pest
```

In your own tests, `QuickViewRegistry::forget(Server::class)` removes a registration.

## License

MIT
