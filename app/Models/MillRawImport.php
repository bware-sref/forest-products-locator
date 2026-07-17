<?php

namespace App\Models;

use App\Enums\MillRawImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MillRawImport extends Model
{
    protected $fillable = [
        'import_id',
        'raw_feature_id',
        'geojson',
        'mill_id',
        'status',
        'errors',
    ];

    protected $casts = [
        'geojson' => 'array',
        'status'  => MillRawImportStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function mill(): BelongsTo
    {
        return $this->belongsTo(Mill::class);
    }

    // -------------------------------------------------------------------------
    // Convenience accessors
    // -------------------------------------------------------------------------

    /**
     * The GeoJSON feature's properties object.
     *
     * @return array<string, mixed>
     */
    public function getPropertiesAttribute(): array
    {
        return $this->geojson['properties'] ?? [];
    }

    /**
     * The GeoJSON feature's geometry object.
     *
     * @return array<string, mixed>|null
     */
    public function getGeometryAttribute(): ?array
    {
        return $this->geojson['geometry'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', MillRawImportStatus::Pending);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', MillRawImportStatus::Processed);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', MillRawImportStatus::Failed);
    }
}
