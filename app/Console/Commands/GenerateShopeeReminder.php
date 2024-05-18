<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Item;
use Illuminate\Support\Arr;
use App\Models\InventoryValue;
use App\Models\ShopeeReminder;
use App\Models\WebhookRequest;
use Illuminate\Console\Command;
use App\Models\ShopeeCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateShopeeReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:shopee-reminder {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate Shopee Reminder. Will be run every 1 hour by cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function getAccessToken()
    {
        //initialize all parameter needed
        $shopeeCredential = ShopeeCredential::orderBy('created_at', 'desc')->first();
        $baseUrl = env("SHOPEE_BASE_URL");
        $currentTimeStamp = now()->timestamp;
        $apiURL = env("SHOPEE_REFRESH_ACCESS_TOKEN_URL");
        $secretKey = $shopeeCredential->partner_key;
        $partnerID = $shopeeCredential->partner_id;
        $refresh_token = $shopeeCredential->refresh_token;
        $shopID = $shopeeCredential->shop_id;

        $baseString = $partnerID.$apiURL.$currentTimeStamp;
        $sign = hash_hmac('sha256', $baseString, $secretKey);
        
        $postData = [
            'refresh_token' => $refresh_token,
            'partner_id' => (int)$partnerID,
            'shop_id' => (int)$shopID
        ];

        $URL = $baseUrl . $apiURL . '?sign=' . $sign .'&partner_id=' . $partnerID . '&timestamp=' . $currentTimeStamp;

        // Make a POST request to the API endpoint
        $response = Http::post($URL, $postData);

        if ($response->successful()) {
            $responseData = $response->json(); // Get JSON response as array
            $this->info('Get Access Token successful!');
            
            //save new credential
            $credential = new ShopeeCredential;
            $credential->api_timestamp = $currentTimeStamp;
            $credential->refresh_token = $responseData['refresh_token'];
            $credential->access_token = $responseData['access_token'];
            $credential->expire_in = $responseData['expire_in'];
            $credential->shop_id = $shopID;
            $credential->partner_id = $partnerID;
            $credential->partner_key = $secretKey;
            $credential->api_response = json_encode($responseData);
            $credential->save();
            $shopeeCredential = $credential;
        } else {
            // Handle the case when the request was not successful
            $this->error('API request failed!');
            $this->error('Status code: ' . $response->status());
            $this->error('Error message: ' . $response->body());
        }

        return $shopeeCredential;
    }

    public function getOrderDetail($credential, $orderSN){
        $currentTimeStamp = now()->timestamp;
        $baseUrl = env("SHOPEE_BASE_URL");
        $apiURL = env("SHOPEE_GET_ORDER_DETAIL_URL");
        $partnerID = $credential->partner_id;
        $acccess_token = $credential->access_token;
        $shopID = $credential->shop_id;
        $partnerKey = $credential->partner_key;
        $baseString = $partnerID.$apiURL.$currentTimeStamp.$acccess_token.$shopID;
        $sign = hash_hmac('sha256', $baseString, $partnerKey);
        
        $response_optional_fields = "buyer_username,estimated_shipping_fee,actual_shipping_fee,item_list,total_amount";
        $URL = $baseUrl . $apiURL . '?partner_id=' . $partnerID .'&timestamp=' . $currentTimeStamp . '&access_token=' . $acccess_token . '&shop_id=' . $shopID . '&sign=' . $sign . '&order_sn_list=' . $orderSN . '&response_optional_fields=' . $response_optional_fields;

        $response = Http::get($URL);

        if ($response->successful()) {
            $this->info('Get Order Detail successful '. $orderSN);
            return $response;
        }
        else{
            $errorMessage = $response->body();
            $this->error("Get Order Detail Error!");
            $this->error($errorMessage);
            return $response;
        }
    }

    public function handle()
    {
        $date = $this->argument('date');
        $date = $date ? $date : now()->format('Y-m-d');
        set_time_limit(0);

        $cre = $this->getAccessToken();

        $webhooks = WebhookRequest::whereRaw("json_extract(request_body, '$.data.status') = 'PROCESSED'")
        ->whereDate('created_at', '=', $date)
        ->where('is_processed', 0)
        ->orderBy('created_at', 'DESC')
        ->get();

        foreach($webhooks as $webhook){
            $data = json_decode($webhook->request_body,true);
            $ordersn = $data['data']['ordersn'];

            $sm = new ShopeeReminder;
            $sm->webhook_request_id = $webhook->id;
            //call order detail API
            $response = $this->getOrderDetail($cre,$ordersn);
            if($response->successful()){
                //store the result in ShopeeReminder
                $data = $response->json();
                $sm->ordersn = $ordersn;
                $sm->processed_date = $webhook->created_at;
                $sm->customer_name = Arr::get($data, 'response.order_list.0.buyer_username');
                $sm->total_amount = Arr::get($data, 'response.order_list.0.total_amount');
                $sm->item_list = json_encode(Arr::get($data, 'response.order_list.0.item_list'));
                $sm->api_status = $response->status();
                $sm->api_response = json_encode($data);
                
                //update the webhook request is_processed to 1
                $webhook->is_processed = true;
                $webhook->save();
            }
            else{
                $sm->api_status = $response->status();
                $sm->api_response = json_encode($data);
            }
            $sm->save();
        }
    }
}
