<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappTemplate extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const APPROVAL_LABELS = ['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public const LANGUAGES = ['en' => 'English', 'id' => 'Indonesian'];

    public const HEADER_TYPES = ['NONE' => 'No header', 'TEXT' => 'Text', 'IMAGE' => 'Image'];

    public const BUTTON_TYPES = ['QUICK_REPLY' => 'Quick reply', 'URL' => 'Open website', 'PHONE_NUMBER' => 'Call phone number'];

    public const MAX_BODY_LENGTH = 550;

    public const MAX_BUTTONS = 10;

    protected $fillable = ['name', 'language', 'display_name', 'header_type', 'header_text', 'asset_path', 'body', 'footer', 'buttons'];

    protected function casts(): array
    {
        return ['buttons' => 'array', 'revision' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public static function headerContent(?string $text): string
    {
        return trim(trim(trim($text ?? ''), '*'));
    }

    public static function formatHeader(string $text): string
    {
        return '*'.self::headerContent($text).'*';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVED;
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVED);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(WhatsappBroadcast::class);
    }
}
