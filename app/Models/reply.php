<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class reply extends Model
{
    //
    // Message Model (Message.php)
    

    // Reply Model (Reply.php)
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
