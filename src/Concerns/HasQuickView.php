<?php

namespace ManojKiran\FilamentQuickView\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Turns a text column or entry into a link that opens a slide-over with an infolist of the related
 * record. The slide-over is a plain Filament action, so everything an action can do still works.
 */
trait HasQuickView
{
    /** @var class-string|null */
    protected ?string $quickViewResource = null;

    protected ?string $quickViewRelationship = null;

    protected ?Closure $quickViewRecord = null;

    /** @var array<mixed>|Closure|null */
    protected array|Closure|null $quickViewInfolist = null;

    protected ?Closure $modifyQuickViewActionUsing = null;

    /**
     * @param  class-string|null  $resource  the resource of the related record: its infolist is the default
     *                                       slide-over content and its `view` page the "Open full page" target.
     *                                       Optional when `infolist()` is given: the button is then left out.
     * @param  string|null  $relationship  where to find the related record from the row (default: the
     *                                     part of the column name before the last dot, "server" for "server.hostname")
     *
     * A name without a dot, like "server", shows the record title of the related record, so no
     * `state()` is needed. Call `state()` after this method to show something else.
     */
    public function quickView(?string $resource = null, ?string $relationship = null): static
    {
        $this->quickViewResource = $resource;
        $this->quickViewRelationship = $relationship;

        if ($resource && ! str_contains($this->getName(), '.')) {
            $this->state(function (Model $record) use ($resource) {
                $title = ($related = $this->getQuickViewRelatedRecord($record)) ? $resource::getRecordTitle($related) : null;

                return $title instanceof Htmlable ? $title->toHtml() : $title;
            });
        }

        $this->refreshQuickViewAction();

        return $this;
    }

    /**
     * Find the related record some other way than the relationship.
     */
    public function quickViewRecord(?Closure $callback): static
    {
        $this->quickViewRecord = $callback;

        return $this;
    }

    /**
     * The slide-over content, instead of the infolist of the resource. Components are bound to the
     * related record, so entries read its attributes by name.
     *
     * @param  array<mixed>|Closure(Schema, Model): Schema|null  $infolist
     */
    public function infolist(array|Closure|null $infolist): static
    {
        $this->quickViewInfolist = $infolist;
        $this->refreshQuickViewAction();

        return $this;
    }

    /**
     * Change the slide-over action (width, heading, extra footer actions...).
     *
     * @param  Closure(Action): Action|null  $callback
     */
    public function modifyQuickViewActionUsing(?Closure $callback): static
    {
        $this->modifyQuickViewActionUsing = $callback;
        $this->refreshQuickViewAction();

        return $this;
    }

    protected function setUpQuickView(): void
    {
        $this->color('primary')
            ->iconPosition(IconPosition::After)
            ->icon(fn ($state) => filled($state) && $this->hasQuickView() ? Heroicon::Eye : null)
            ->tooltip(fn ($state) => filled($state) && $this->hasQuickView() ? __('filament-quick-view::filament-quick-view.tooltip') : null);
    }

    /**
     * A quick view needs a resource (its infolist) or an infolist of its own.
     */
    protected function hasQuickView(): bool
    {
        return $this->quickViewResource || $this->quickViewInfolist !== null;
    }

    /**
     * Entries only take an action instance, so it is built as soon as there is something to show.
     */
    protected function refreshQuickViewAction(): void
    {
        if (! $this->hasQuickView()) {
            return;
        }

        $action = $this->makeQuickViewAction();

        $this->action($this->modifyQuickViewActionUsing ? ($this->evaluate($this->modifyQuickViewActionUsing, ['action' => $action]) ?? $action) : $action);
    }

    protected function getQuickViewRelatedRecord(Model $record): ?Model
    {
        if ($this->quickViewRecord) {
            return $this->evaluate($this->quickViewRecord, ['record' => $record]);
        }

        $relationship = $this->quickViewRelationship ?? Str::beforeLast($this->getName(), '.');

        $related = data_get($record, $relationship);

        return $related instanceof Model ? $related : null;
    }

    /**
     * The resource decides when there is one, otherwise the policy of the model (if it has one).
     */
    protected function canViewQuickViewRecord(Model $related): bool
    {
        if ($this->quickViewResource) {
            return $this->quickViewResource::canView($related);
        }

        return Gate::getPolicyFor($related) === null || Gate::allows('view', $related);
    }

    protected function makeQuickViewAction(): Action
    {
        $resource = $this->quickViewResource;

        return Action::make('quickView'.Str::studly(str_replace('.', '_', $this->getName())))
            ->label(__('filament-quick-view::filament-quick-view.label'))
            ->slideOver()
            ->modalWidth(Width::Large)
            ->modalHeading(fn (Model $record) => ($related = $this->getQuickViewRelatedRecord($record)) && $resource ? $resource::getRecordTitle($related) : $this->getLabel())
            ->visible(fn (Model $record) => ($related = $this->getQuickViewRelatedRecord($record)) && $this->canViewQuickViewRecord($related))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-quick-view::filament-quick-view.close'))
            ->schema(function (Schema $schema, Model $record) use ($resource) {
                $related = $this->getQuickViewRelatedRecord($record);
                $schema = $schema->record($related);

                return match (true) {
                    $this->quickViewInfolist === null => $resource ? $resource::infolist($schema) : $schema,
                    is_array($this->quickViewInfolist) => $schema->components($this->quickViewInfolist),
                    default => $this->evaluate($this->quickViewInfolist, ['schema' => $schema, 'infolist' => $schema, 'record' => $related]) ?? $schema,
                };
            })
            ->extraModalFooterActions(fn (Model $record) => ! $resource ? [] : [
                Action::make('openFullPage')
                    ->label(__('filament-quick-view::filament-quick-view.open'))
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(fn () => $resource::getUrl('view', ['record' => $this->getQuickViewRelatedRecord($record)])),
            ]);
    }
}
