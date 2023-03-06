<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutIntegration extends Model
{
    use HasFactory;

    const TYPE_GA4 = 'ga4';
    const TYPE_GAUA = 'gaua';
    const TYPE_FB = 'fb';
    const TYPE_SNAP = 'snap';
    const TYPE_TIKTOK = 'tiktok';

    const MAPPING_TYPE_MODEL = [
        self::TYPE_GA4 => GA4Integration::class,
        self::TYPE_GAUA => GAUAIntegration::class,
        self::TYPE_FB => FBIntegration::class,
        self::TYPE_SNAP => SnapIntegration::class,
        self::TYPE_TIKTOK => TikTokIntegration::class,
    ];

    /**
     * Get Database Builder for {type}Integration.
     *
     * @throws Exception
     */
    public function integration(): Builder
    {
        if (isset(self::MAPPING_TYPE_MODEL[$this->name])) {
            return (self::MAPPING_TYPE_MODEL[$this->name])::query();
        } else {
            throw new Exception('Checkout integration model do not exists', 404);
        }
    }

    /**
     * Relation to GA4Integration.
     *
     * @return HasMany
     */
    public function ga4_integrations(): HasMany
    {
        return $this->hasMany(GA4Integration::class, 'type_id');
    }

    /**
     * Relation to GAUAIntegration.
     *
     * @return HasMany
     */
    public function gaua_integrations(): HasMany
    {
        return $this->hasMany(GAUAIntegration::class, 'type_id');
    }

    /**
     * Relation to FBIntegration.
     *
     * @return HasMany
     */
    public function fb_integrations(): HasMany
    {
        return $this->hasMany(FBIntegration::class, 'type_id');
    }

    /**
     * Relation to SnapIntegration.
     *
     * @return HasMany
     */
    public function snap_integrations(): HasMany
    {
        return $this->hasMany(SnapIntegration::class, 'type_id');
    }

    /**
     * Relation to TikTokIntegration.
     *
     * @return HasMany
     */
    public function tiktok_integrations(): HasMany
    {
        return $this->hasMany(TikTokIntegration::class, 'type_id');
    }
}
