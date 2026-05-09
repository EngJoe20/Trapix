<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks guest analysis usage by token + IP for the 3-analysis guest limit.
 */
class GuestQuotaToken extends Model
{
    protected $fillable = ['token', 'ip_address', 'analysis_count'];
}
