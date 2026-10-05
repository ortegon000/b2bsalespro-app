<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Actions\RecordBrevoEvent;
use App\Domain\Crm\Actions\RecordSendEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, RecordBrevoEvent $recordBrevoEvent, RecordSendEvent $recordSendEvent): Response
    {
        $event = $request->input('event');
        $email = $request->input('email');
        $messageId = $request->input('message-id');
        $timestamp = $request->input('ts_event');

        if (is_string($event) && is_string($email)) {
            $recordBrevoEvent->handle($event, mb_strtolower($email));
        }

        if (is_string($event) && is_string($messageId)) {
            $recordSendEvent->handle($event, $messageId, is_numeric($timestamp) ? (int) $timestamp : null);
        }

        return response()->noContent();
    }
}
