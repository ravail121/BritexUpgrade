<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';
     protected $fillable = ['end_date', 'due_date', 'start_date', 'type','status'];

}