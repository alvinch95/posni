<?php

namespace App\Http\Controllers;

use App\Models\WebhookRequest;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Process and handle the webhook data here
        $webhook = new WebhookRequest;
        $webhook->source_app = 'Anchanto';
        $webhook->request_body = $request->getContent();
        $webhook->save();

        return response('Webhook received', 200);
    }
}
