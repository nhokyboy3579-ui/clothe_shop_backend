<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// --- QUAN TRỌNG: Phải import 2 dòng này ---
use App\Models\Topic;
use App\Models\User;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'topic_id',
        'title',
        'slug',
        'image',
        'content',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by'
    ];

    // Quan hệ: Bài viết thuộc về 1 Chủ đề
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    // Quan hệ: Bài viết thuộc về 1 User (author)
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title) . '-' . time();
            }
        });
    }
}
