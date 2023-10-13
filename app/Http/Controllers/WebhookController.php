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
        $webhook->source_app = 'Shopee';
        $webhook->request_body = $request->getContent();
        $webhook->request_header = json_encode($request->header());
        $webhook->save();

        return response('Webhook received', 200);
    }
}
