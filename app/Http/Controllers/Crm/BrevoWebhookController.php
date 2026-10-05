<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Actions\RecordBrevoEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, RecordBrevoEvent $recordBrevoEvent): Response
    {
        $event = $request->input('event');
        $email = $request->input('email');

        if (is_string($event) && is_string($email)) {
            $recordBrevoEvent->handle($event, mb_strtolower($email));
        }

        return response()->noContent();
    }
}
