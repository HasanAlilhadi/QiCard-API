<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;

class AuditLogController extends BaseController
{
    // I could add filters to search by action, entity_type, performed_by, user_agent...
    // But since this is just a task, I kept it simple
    // I hope you guys understand what I'm saying.
    public function index()
    {
        $logs = AuditLog::query()->get();

        return $this->success(AuditLogResource::collection($logs));
    }
}
