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

    public const APPROVAL_LABELS = ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    public const LANGUAGES = ['id' => 'Indonesia', 'en' => 'English'];

    public const HEADER_TYPES = ['NONE' => 'Tanpa header', 'TEXT' => 'Teks', 'IMAGE' => 'Gambar'];

    public const BUTTON_TYPES = ['QUICK_REPLY' => 'Balasan cepat', 'URL' => 'Buka website', 'PHONE_NUMBER' => 'Hubungi telepon'];

    public const MAX_BODY_LENGTH = 550;

    public const MAX_BUTTONS = 10;

    protected $fillable = ['name', 'language', 'display_name', 'header_type', 'header_text', 'asset_path', 'body', 'footer', 'buttons'];

    protected function casts(): array
    {
        return ['buttons' => 'array', 'revision' => 'integer', 'reviewed_at' => 'datetime'];
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
