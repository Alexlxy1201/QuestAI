<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EssayRecord extends Model
{
    protected $table = 'essay_records';

    protected $fillable = [
        'origin', 'title', 'rubric', 'text', 'ocr_text',
        'scores_json', 'suggestions_json',
        'file_path', 'file_mime', 'file_pages', 'parent_id',
    ];

    protected $casts = [
        'scores_json'      => 'array',
        'suggestions_json' => 'array',
        'file_pages'       => 'integer',
        'parent_id'        => 'integer',
    ];
}
