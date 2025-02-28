<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    
    protected $fillable = ['code', 'name', 'ar_name'];


    public function sections()
    {
        return $this->hasMany(Section::class);
    }



}
