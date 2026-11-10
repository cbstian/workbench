<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A unit of work with name, description and icon (docs/flujo-canonico.md, RN-13).
 *
 * Soft deletes back the trash bin described in RN-19.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property bool $is_visible_to_client
 */
#[Fillable(['name', 'description', 'icon', 'is_visible_to_client'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible_to_client' => 'boolean',
        ];
    }

    /**
     * Filter projects whose name or description contains the given term.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when(filled($term), function (Builder $query) use ($term): void {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $term).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query->whereLike('name', $like)->orWhereLike('description', $like);
            });
        });
    }
}
