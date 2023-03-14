<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'article',
        'price',
        'status',
        'name',
        'description',
    ];

    protected $with = ['manufacturer'];

    public function images()
    {
        return $this->hasMany('App\ProductImages');
    }

    public function attributes()
    {
        return $this->belongsToMany('App\Option', 'product_option',
            'product_id', 'option_id')
            ->withPivot('price', 'action');
    }

    public function currency()
    {
        return $this->hasOne('App\Currency', 'id', 'currency_id');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Category', 'product_category',
            'product_id', 'category_id');
    }

    public function relatedProducts()
    {
        return $this->hasMany('App\ProductRelated',
            'product_id', 'id');
    }

    public function manufacturer()
    {
        return $this->hasOne('App\Manufacturer', 'id', 'manufacturer_id');
    }
}
