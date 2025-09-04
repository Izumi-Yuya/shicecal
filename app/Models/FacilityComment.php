<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $facility_id
 * @property int $user_id
 * @property string $section
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Facility $facility
 * @property-read \App\Models\User $user
 * @property-read string $section_name
 */
class FacilityComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'user_id',
        'section',
        'comment',
        'assigned_to',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 施設との関連
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * ユーザーとの関連
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * コメント投稿者との関連（userのエイリアス）
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * コメント担当者との関連
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * セクション名の日本語表示
     */
    public function getSectionNameAttribute(): string
    {
        $sections = [
            'basic_info' => '基本情報',
            'contact_info' => '住所・連絡先',
            'building_info' => '開設・建物情報',
            'facility_info' => '基本施設情報',
            'services' => 'サービス種類・指定更新',
        ];

        return $sections[$this->section] ?? $this->section;
    }
}
