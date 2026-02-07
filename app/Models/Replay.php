<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Replay extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

}
