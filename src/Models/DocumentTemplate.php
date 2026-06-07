<?php

declare(strict_types=1);

namespace Darejer\Models;

use Darejer\Concerns\HasDarejerTranslatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An uploaded, fill-and-print document design.
 *
 * The user authors the layout in Word (a `.docx` with `${token}` placeholders),
 * uploads it here, and the engine fills it with live voucher data at print
 * time — see `Darejer\Documents\DocumentRenderer`. One `document_type` may
 * have several templates; the active default for the relevant scope wins.
 */
class DocumentTemplate extends Model
{
    use HasDarejerTranslatable, SoftDeletes;

    protected $table = 'document_templates';

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected $fillable = [
        'document_type',
        'name',
        'file_path',
        'paper_size',
        'company_id',
        'branch_id',
        'is_default',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The disk the uploaded `.docx` lives on. Mirrors the Darejer uploads
     * config so it follows whatever the host configures for attachments.
     */
    public function disk(): string
    {
        return (string) config('darejer.uploads.disk', 'public');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the template that should render a given document type. Prefers a
     * company-scoped match over a global one, and a default over a non-default,
     * falling back to any active template for the type.
     */
    public static function resolveFor(string $documentType, ?int $companyId = null): ?self
    {
        return static::query()
            ->active()
            ->where('document_type', $documentType)
            ->when(
                $companyId !== null,
                fn (Builder $q) => $q->where(
                    fn (Builder $w) => $w->where('company_id', $companyId)->orWhereNull('company_id')
                ),
                fn (Builder $q) => $q->whereNull('company_id'),
            )
            ->orderByRaw('company_id is null')
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }
}
