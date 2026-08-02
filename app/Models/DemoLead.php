<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoLead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'institute_name',
        'email',
        'ip_address',
    ];
}
