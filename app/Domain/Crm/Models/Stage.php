<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\StageType;
use Database\Factories\Crm\StageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(StageFactory::class)]
class Stage extends Model
{
    /** @use HasFactory<StageFactory> */
    use HasFactory;

    protected $table = 'crm_stages';

    protected $fillable = ['name', 'slug', 'type', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StageType::class,
        ];
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
