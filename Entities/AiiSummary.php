<?php

namespace Modules\AiIntegration\Entities;

use Illuminate\Database\Eloquent\Model;

class AiiSummary extends Model
{
    protected $table = 'aii_summaries';
    protected $primaryKey = 'conversation_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
