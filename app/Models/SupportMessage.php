<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasUuid;

    protected $fillable = ['thread_id', 'sender_id', 'from_staff', 'body'];

    protected $casts = ['from_staff' => 'boolean'];
}
