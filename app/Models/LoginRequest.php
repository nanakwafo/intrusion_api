<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginRequest extends Model
{
    //
    protected $fillable = ['user_id','ip','time','phone_number','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
