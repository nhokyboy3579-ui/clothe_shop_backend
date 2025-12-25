<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';

    protected $fillable = [
        'name',
    ];

    /**
     * Mối quan hệ: Lấy các giá trị (values) của thuộc tính này.
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_id');
    }
}
