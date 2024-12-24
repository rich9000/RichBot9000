<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'remote_richbot_id',
        'type',
        'file_path',
        'user_id',
    ];


    public function richbot()
    {
        return $this->belongsTo(RemoteRichbot::class, 'richbot_id');
    }
    public function remoteRichbot()
    {
        return $this->belongsTo(RemoteRichbot::class, 'richbot_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
