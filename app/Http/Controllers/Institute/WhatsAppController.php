<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function index(WhatsAppService $whatsApp): View
    {
        $institute = Auth::user()->institute;

        $logs = WhatsAppLog::where('institute_id', $institute->id)
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('institute.whatsapp.index', [
            'connectionStatus' => $whatsApp->connectionStatus($institute),
            'logs' => $logs,
        ]);
    }
}
