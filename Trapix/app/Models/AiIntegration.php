<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiIntegration extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key_encrypted',
        'base_url',
        'default_model',
        'is_enabled',
    ];

    protected $casts = [
        // Automatically encrypts/decrypts using the app's key securely.
        'api_key_encrypted' => 'encrypted',
        'is_enabled'        => 'boolean',
    ];

    // Helper accessor for the plaintext key
    public function getApiKeyAttribute()
    {
        return $this->api_key_encrypted;
    }

    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key_encrypted'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
