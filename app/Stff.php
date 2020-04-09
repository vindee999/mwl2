<?php

namespace App;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Stff extends Model
{
	use Searchable;
    protected $table = "staff";
}
