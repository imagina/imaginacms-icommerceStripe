<?php

namespace Modules\Icommercestripe\Http\Controllers\Api;

// Requests & Response
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// Base Api
use Modules\Ihelpers\Http\Controllers\Api\BaseApiController;

// Services
use Modules\Icommercestripe\Services\StripeService;


class StripeApiController extends BaseApiController
{

   
    private $stripeService;

    public function __construct(StripeService $stripeService){
        $this->stripeService = $stripeService;
    }

    /**
    *  API - Generate Link
    * @param 
    * @return
    */
    public function generateLink($paymentMethod,$order,$transaction){

        \Log::info('Icommercestripe: Generate Link to order: '.$order->id." transaction: ".$transaction->id);
           
        try {

            // Set Api Key
            \Stripe\Stripe::setApiKey($paymentMethod->options->secretKey);

            // Make Configuration
            $conf = $this->stripeService->makeConfigurationToGenerateLink($paymentMethod,$order,$transaction);

            // Create a Session
            $response = \Stripe\Checkout\Session::create($conf);

            return $response;

            //\Log::info('Icommercestripe: Generate Link - Response: '.$response);

        } catch (\Exception $e) {

            \Log::error('Icommercestripe: Generate Link - Message: '.$e->getMessage());

            //Message Error
            $status = 500;
            $response = [
                'errors' => $e->getMessage()
            ];
        }

        

        return response()->json($response, $status ?? 200);
    }


    /**
    *  API - Create Account
    * @param 
    * @return
    */
    public function createAccount($stripe,$attr){

        \Log::info('Icommercestripe: create Account');

        try {
            
            $data['type'] = $attr['type'] ?? 'express';
            $data['country'] = $attr['country'] ?? 'US';
            
            // Email
            if(isset($attr['email']))
                $data['email'] = $attr['email'];

            /*
            * The recipient ToS agreement is not supported for platforms in US creating accounts in US.
            */
            if($data['country']!='US'){
                
                $data['capabilities'] =  config('asgard.icommercestripe.config.capabilities');

                $data['tos_acceptance'] = config('asgard.icommercestripe.config.tos_acceptance');
            }

            // Create Account
            $account = $stripe->accounts->create($data);

            //Response
            $response = $account->id;

        } catch (Exception $e) {
            
            \Log::error('Icommercestripe: Create Account - Message: '.$e->getMessage().'Code: '.$e->getMessage());
            //Message Error
            $status = 500;
            $response = [
                'errors' => $e->getMessage()
            ];
        }

        return $response;

    }

    /**
    *  API - create Link Account
    * @param $paymentMethod
    * @param
    * @return
    */
    public function createLinkAccount($paymentMethod,$attr){

        $stripe = new \Stripe\StripeClient($paymentMethod->options->secretKey);

        // Create Account
        $accountId = $this->createAccount($stripe,$attr);

        // Validate Error created account
        if(isset($accountId['errors'])){
            $response['error'] =  $accountId['errors']; 
            return $response;
        }

        // Create Link To Account
        \Log::info('Icommercestripe: create Link Account');
        $accountLink = $stripe->accountLinks->create(
          [
            'account' => $accountId,
            'refresh_url' => url('/'),
            'return_url' => "https://connect.stripe.com/hosted/oauth?success=true",
            'type' => 'account_onboarding'
          ]
        );   

        return $accountLink->url;
        
    }

    /**
    *  API - Retrieve Account
    * @param $paymentMethod
    * @param accountId
    * @return
    */
    public function retrieveAccount($paymentMethod,$accountId){

        $stripe = new \Stripe\StripeClient($paymentMethod->options->secretKey);

        $result = $stripe->accounts->retrieve($accountId,[]);

        return $result;
        
    }

 
}