<?php

namespace Darejer\Actions;

class BulkAction extends BaseAction
{
    protected ?string $batchUrl = null;

    protected string $batchParam = 'ids';

    public function __construct(string $label = '')
    {
        parent::__construct($label);
        $this->method = 'POST';
        $this->variant = 'outline';
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    /**
     * Soft-delete the selected rows. Posts to `$url` with `{ ids: [...] }`.
     */
    public static function delete(string $url): static
    {
        return static::make(__('Delete'))
            ->icon('Trash2')
            ->variant('destructive')
            ->method('DELETE')
            ->confirm(__('Are you sure you want to delete the selected records?'))
            ->batchUrl($url);
    }

    /**
     * Restore the selected soft-deleted rows. Used together with the
     * `Filter::trashed()` filter set to "with" or "only".
     */
    public static function restore(string $url): static
    {
        return static::make(__('Restore'))
            ->icon('RotateCcw')
            ->method('PATCH')
            ->confirm(__('Are you sure you want to restore the selected records?'))
            ->batchUrl($url);
    }

    /**
     * Permanently delete the selected rows. Only meaningful in the
     * "Only deleted" view; surface alongside `Filter::trashed()`.
     */
    public static function forceDelete(string $url): static
    {
        return static::make(__('Delete permanently'))
            ->icon('Trash')
            ->variant('destructive')
            ->method('DELETE')
            ->confirm(__('Permanently delete the selected records? This cannot be undone.'))
            ->batchUrl($url);
    }

    public function batchUrl(string $url): static
    {
        $this->batchUrl = $url;

        return $this;
    }

    public function batchParam(string $param): static
    {
        $this->batchParam = $param;

        return $this;
    }

    protected function actionType(): string
    {
        return 'BulkAction';
    }

    protected function actionProps(): array
    {
        return [
            'batchUrl' => $this->batchUrl,
            'batchParam' => $this->batchParam,
        ];
    }
}
