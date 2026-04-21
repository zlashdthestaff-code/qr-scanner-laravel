<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    // This allows the import script to save data to these columns
    protected $fillable = ['name', 'class', 'security_code'];
}