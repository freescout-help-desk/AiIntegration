<?php

namespace Modules\AiIntegration\Entities;

use Illuminate\Database\Eloquent\Model;

class ConversationSummary extends Model
{
    protected $table = 'aiintegration_summaries';
    protected $primaryKey = 'conversation_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
