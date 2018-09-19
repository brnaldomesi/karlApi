<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/9/24
 * Time: 下午3:45
 */

namespace App\Method;


use App\ErrorCode;
use App\Http\Controllers\v1\PaymentController;
use App\Model\BookingTransactionHistory;
use App\Model\CompanyPayMethod;
use App\Model\RunningError;
use Illuminate\Support\Facades\Log;
use PayPal\Api\Address;
use PayPal\Api\Amount;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Refund;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalMethod
{
    const PAY_PAL_PAYMENT_INTENT_AUTHORIZE = 'authorize';
    const PAY_PAL_PAYMENT_INTENT_SALE = 'sale';

    private $payPalActive;
    private static $_instance = null;

    private function __construct()
    {
        $this->payPalActive = PaymentMethod::isPayActive();
    }


    public static function getPayPal()
    {
        if (self::$_instance == null) {
            self::$_instance = new PayPalMethod();
        }
        return self::$_instance;
    }


    private function getPayPalApiContext($clientId, $secret)
    {

        $apiContext = new ApiContext(new OAuthTokenCredential(
            $clientId, $secret));
        $mode = $this->payPalActive ? 'live' : 'sandbox';
        $debug = $this->payPalActive ? 'FINE' : 'DEBUG';
        $apiContext->setConfig(
            array(
                'mode' => $mode,
                'log.LogEnabled' => true,
                'log.FileName' => storage_path('logs/lumen.log'),
                'log.LogLevel' => $debug, // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            )
        );
        return $apiContext;
    }


    /**
     * @desc 添加前需要确定信用卡信息正确
     * @param $owner_id
     * @param $card_type
     * @param $card_number
     * @param $expire_month
     * @param $expire_year
     * @param $cvv2
     * @param $first_name
     * @param $last_name
     * @param $company_id
     * @param $address_line1
     * @param $address_line2
     * @param $address_city
     * @param $address_country
     * @param $address_zip
     * @param $clientId
     * @param $secret
     * @return array
     * @throws \Exception
     */
    public function transferCardInfoToToken($owner_id, $card_type,
                                            $card_number, $expire_month,
                                            $expire_year, $cvv2, $first_name,
                                            $last_name, $company_id,
                                            $address_line1,
                                            $address_line2,
                                            $address_city,$address_country,$address_zip,
                                            $clientId, $secret)
    {

        $apiContext = $this->getPayPalApiContext($clientId, $secret);

        $card = new CreditCard();
        $card->setType(strtolower($this->changeCardType($card_type)))
            ->setNumber($card_number)
            ->setExpireMonth($expire_month)
            ->setExpireYear($expire_year)
            ->setCvv2($cvv2)
            ->setFirstName($first_name)
            ->setLastName($last_name);

        $customerId = "customer_" . $owner_id;
        $card->setMerchantId($company_id);
        $card->setExternalCardId(md5($card_number));     //用户获取全部商户信用卡标记
        $card->setExternalCustomerId($customerId);
        $billingAddress = new Address();
        $billingAddress->setLine1($address_line1);
        $billingAddress->setLine2($address_line2);
        $billingAddress->setCity($address_city);
        $billingAddress->setCountryCode($address_country); 
        $card->setBillingAddress($billingAddress);
        try {
            $card->create($apiContext);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_CREATE_CREDIT,
                "create credit card success"
            );
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_PAY_CREATE_CREDIT,
                $ex->getMessage()
            );
            throw new \Exception(ErrorCode::errorCreateCreditCard("PayPal "));
        }

        return [
            'owner_id' => $owner_id,
            'type' => \App\Model\CreditCard::TYPE_CUSTOMER,
            'card_number' => $card->getNumber(),
            'card_type' => $card_type,
            'card_token' => $card->getId(),
            'valid_until' => MethodAlgorithm::formatTimestampToDate(strtotime($card->getValidUntil()))
        ];
    }

    private function changeCardType($card_type)
    {
        switch ($card_type) {
            case \App\Model\CreditCard::VISA:
                $card_type = 'VISA';
                break;
            case \App\Model\CreditCard::MasterCard:
                $card_type = 'MasterCard';
                break;
            case \App\Model\CreditCard::DISCOVER:
                $card_type = 'DISCOVER';
                break;
            case \App\Model\CreditCard::AmericanExpress:
                $card_type = 'amex';
                break;
            default :
                return null;
        }
        return $card_type;
    }


    private function payPalCaptures(
        $booking_id,
        $clientId,
        $secret,
        $capture_fee
    )
    {
        $history = BookingTransactionHistory::where('booking_id', $booking_id)
            ->first();

        $apiContext = $this->getPayPalApiContext($clientId, $secret);
        $authorization = Authorization::get($history->pay1_auth_id, $apiContext);
        $amt = new Amount();
        $amt->setCurrency("USD")
            ->setTotal($capture_fee);
        ### Capture
        $capture = new Capture();
        $capture->setAmount($amt);
        $capture->setIsFinalCapture(true);
        try {
            $getCapture = $authorization->capture($capture, $apiContext);
            RunningError::recordRunningError(RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_CAPTURE_PAY, 'capture success : ' . $getCapture);
            $history->capture_id = $getCapture->getId();
            $history->capture_amount = $capture_fee;
            $history->capture_success = BookingTransactionHistory::CAPTURE_SUCCESS;
            $history->save();
        } catch (\Exception $ex) {
            $history->capture_amount = $capture_fee;
            $history->capture_success = BookingTransactionHistory::CAPTURE_FAULT;
            $history->save();
            RunningError::recordRunningError(RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_CAPTURE_PAY, 'capture fault : ' . $ex);
        }
    }

    private function payPalFinalPayment(
        $company_id,
        $clientId, $secret,
        $booking_id,
        $more_cost,
        $tva,
        $card_token)
    {
        Log::info("c_id is " . $company_id . " b_id " . $booking_id . " cost " . $more_cost . " tva " . $tva . " token " . $card_token);

        $history = BookingTransactionHistory::where('booking_id', $booking_id)
            ->first();
        if (empty($history)) {
            throw new \Exception(ErrorCode::errorNotExist('booking history'));
        }
        $tripDescription = "Trip Service : Pay other $more_cost for booking $booking_id";
        $apiContext = $this->getPayPalApiContext($clientId, $secret);

        //创建信用卡
        $card = new CreditCardToken();
        $card->setCreditCardId($card_token);

        $fi = new FundingInstrument();
        $fi->setCreditCardToken($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

        $baseItem = new Item();
        $baseItem->setName("Trip Base")
            ->setDescription($tripDescription)
            ->setCurrency("USD")
            ->setQuantity(1)
            ->setTax($more_cost * $tva)
            ->setPrice($more_cost);
        $itemList = new ItemList();
        $itemList->setItems(array($baseItem));

        $details = new Details();
        $details->setTax($more_cost * $tva)
            ->setSubtotal($more_cost);
        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($more_cost * (1 + $tva))
            ->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($tripDescription)
            ->setInvoiceNumber(uniqid());

        try {
            $payment = new Payment();
            $payment->setIntent(PaymentController::PAY_PAL_PAYMENT_INTENT_SALE)
                ->setPayer($payer)
                ->setTransactions(array($transaction));
            $payment->create($apiContext);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY,
                "booking {$booking_id} final pay success"
            );
            $history->pay2_id = $payment->getId();
            $history->pay2_amount = $more_cost;
            $history->pay2_success = BookingTransactionHistory::PAY_SUCCESS;
            $history->save();
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY,
                "booking {$booking_id} final payed fault. error info " . $ex
            );

            $history->pay2_amount = $more_cost;
            $history->pay2_success = BookingTransactionHistory::PAY_FAULT;
            $history->save();
        }
    }


    public function addPayPalMethod($company_id, $client_id, $secret, $account, $active)
    {
        if (is_null($client_id) || is_null($secret) /*|| is_null($account)*/) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }
        if (empty($client_id)) {
            throw new \Exception(ErrorCode::errorParam('client_id'));
        }
        if (empty($secret)) {
            throw new \Exception(ErrorCode::errorParam('secret'));
        }
//        if (empty($account)) {
//            throw new \Exception(ErrorCode::errorParam('account'));
//        }

        $result = CompanyPayMethod::create([
            'company_id' => $company_id,
            'client_id' => $client_id,
            'secret' => $secret,
//            'account' => $account,
            'account' => "",
            'pay_type' => CompanyPayMethod::PAY_TYPE_PAY_PAL,
            'active' => $active
        ]);
        return $result;
    }

    public function getCustomerCreditCard($owner_id, $clientId, $secret)
    {

        $customer_id = 'customer_' . $owner_id;
        $params = array(
            "sort_by" => "create_time",
            "sort_order" => "desc",
            "external_customer_id" => $customer_id// Filtering by MerchantId set during CreateCreditCard.
        );
        try {
            $result = CreditCard::all($params, $this->getPayPalApiContext($clientId, $secret));
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_LIST_CREDIT,
                "list credit card success"
            );
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_PAY_LIST_CREDIT,
                $ex->getMessage()
            );
            return ErrorCode::errorPayPalListCreditCard();
        }
        $creditCards = array();
        foreach ($result->getItems() as $item) {
            $creditCard = array();
            $creditCard['card_token'] = $item->getId();
            $creditCard['card_number'] = $item->getNumber();
            $creditCard['expire_month'] = $item->getExpireMonth();
            $creditCard['expire_year'] = $item->getExpireYear();
            $creditCard['first_name'] = $item->getFirstName();
            $creditCard['last_name'] = $item->getLastName();
            $creditCard['card_type'] = PaymentMethod::getCardTypeNumber($item->getType());
            $creditCard['valid_until'] = strtotime($item->getValidUntil());
            if(is_null($item->getBillingAddress())){
                $creditCard['address_line_1'] = null;
                $creditCard['address_line_2'] = null;
                $creditCard['address_city'] = null;
                $creditCard['address_state'] = null;
                $creditCard['address_country_code'] = null; 
                $creditCard['check_pass'] = \App\Model\CreditCard::CHECK_FAILED;
            }else{
                $creditCard['address_line_1'] = $item->getBillingAddress()->getLine1();
                $creditCard['address_line_2'] = $item->getBillingAddress()->getLine2();
                $creditCard['address_city'] = $item->getBillingAddress()->getCity();
                $creditCard['address_state'] = $item->getBillingAddress()->getState();
                $creditCard['address_country_code'] = $item->getBillingAddress()->getCountryCode(); 
                $creditCard['check_pass'] = \App\Model\CreditCard::CHECK_PASSED;
            }


            array_push($creditCards, $creditCard);
        }
        if (count($creditCards) > 0) {
            return ErrorCode::success($creditCards);
        } else {
            return ErrorCode::successEmptyResult("this customer has no cards");
        }
    }

    public function deleteCustomerAllCards($owner_id, $clientId, $secret)
    {
        $customer_id = 'customer_' . $owner_id;
        $params = array(
            "sort_by" => "create_time",
            "sort_order" => "desc",
            "external_customer_id" => $customer_id// Filtering by MerchantId set during CreateCreditCard.
        );
        try {
            $apiContext = $this->getPayPalApiContext($clientId, $secret);
            $cards = CreditCard::all($params, $apiContext);
            foreach ($cards->getItems() as $card) {
                try {
                    $card->delete($apiContext);
                } catch (\Exception $ex) {
                    Log::error("delete card info error", $card);
                }
            }
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_LIST_CREDIT,
                "list credit card success"
            );
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_PAY_LIST_CREDIT,
                $ex->getMessage()
            );
            return ErrorCode::errorPayPalListCreditCard();
        }
    }


    public function deleteCustomerCreditCard($card_token, $clientId, $secret)
    {
        $apiContext = $this->getPayPalApiContext($clientId, $secret);
        $card = new CreditCard();
        $card->setId($card_token);
        try {
            $card->delete($apiContext);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_DELETE_CREDIT,
                "delete credit card success"
            );
            return null;
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_PAY_DELETE_CREDIT,
                $ex->getMessage()
            );
            return ErrorCode::errorPayPalDeleteCreditCard();
        }
    }


    /**
     * @param $amountPrice
     * @param $tva
     * @param $cardToken
     * @param $clientId
     * @param $secret
     * @param $ccy
     * @return string
     */
    public function makeCharge($amountPrice, $tva, $cardToken, $clientId, $secret,$ccy)
    {

        $tripDescription = "Trip Service : Pay $amountPrice for booking";
        $apiContext = $this->getPayPalApiContext($clientId, $secret);

        //创建信用卡
        $card = new CreditCardToken();
        $card->setCreditCardId($cardToken);

        $fi = new FundingInstrument();
        $fi->setCreditCardToken($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

        $details = new Details();
        $details->setTax(round($amountPrice * ($tva) / (1 + $tva), 2))
            ->setSubtotal($amountPrice);
        $amount = new Amount();
        $amount->setCurrency(strtoupper($ccy))
            ->setTotal($amountPrice)
            ->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription($tripDescription)
            ->setInvoiceNumber(uniqid());

        $payment = new Payment();
        $payment->setIntent(self::PAY_PAL_PAYMENT_INTENT_SALE)
            ->setPayer($payer)
            ->setTransactions(array($transaction));
        $payment->create($apiContext);
        return $payment->getId();
    }

    public function refund($amount, $chargeId, $clientId, $secret)
    {
        $apiContext = $this->getPayPalApiContext($clientId, $secret);
        $payment = Payment::get($chargeId, $apiContext);
        $transactions = $payment->getTransactions();
        $relatedResources = $transactions[0]->getRelatedResources();
        $sale = $relatedResources[0]->getSale();

        $amt = new Amount();
        $amt->setCurrency('USD')
            ->setTotal($amount);
        $refund = new Refund();
        $refund->setAmount($amt);

        $refund = $sale->refund($refund, $apiContext);
        return $refund->getId();
    }


    public function checkPayPalCreditCardTokenAvailability($card_token, $clientId, $secret)
    {

        Log::info("param is " . $card_token . "  " . $clientId . "  " . $secret);
        $apiContext = $this->getPayPalApiContext($clientId, $secret);
        try {
            $item = CreditCard::get($card_token, $apiContext);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_CHECK_CREDIT,
                "check credit card success"
            );

            $creditCard = array();
            $creditCard['card_token'] = $item->getId();
            $creditCard['card_number'] = $item->getNumber();
            $creditCard['expire_month'] = $item->getExpireMonth();
            $creditCard['expire_year'] = $item->getExpireYear();
            $creditCard['first_name'] = $item->getFirstName();
            $creditCard['last_name'] = $item->getLastName();
            $creditCard['card_type'] = PaymentMethod::getCardTypeNumber($item->getType());
            $creditCard['valid_until'] = strtotime($item->getValidUntil());
            if(!is_null($item->getBillingAddress())){
                $creditCard['address_line_1'] = $item->getBillingAddress()->getLine1();
                $creditCard['address_line_2'] = $item->getBillingAddress()->getLine2();
                $creditCard['address_city'] = $item->getBillingAddress()->getCity();
                $creditCard['address_state'] = $item->getBillingAddress()->getState();
                $creditCard['address_country_code'] = $item->getBillingAddress()->getCountryCode(); 
            }else{
                throw new \Exception(ErrorCode::errorParam('card not check'));
            }

            return $creditCard;
        } catch (\Exception $ex) {
            Log::error($ex);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_PAY_CHECK_CREDIT,
                "check credit card fault" . $ex->getMessage()
            );
            return null;
        }
    }

    /**
     * @param $amountPrice
     * @param $tva
     * @param $cardToken
     * @param $client_id
     * @param $secret
     * @param $ccy
     * @return array
     */
    public function makeAuthCharge($amountPrice, $tva, $cardToken, $client_id, $secret,$ccy)
    {

        $tripDescription = "Trip Service : Pay $amountPrice for booking";
        $apiContext = $this->getPayPalApiContext($client_id, $secret);
        //创建信用卡
        $card = new CreditCardToken();
        $card->setCreditCardId($cardToken);


        $fi = new FundingInstrument();
        $fi->setCreditCardToken($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

        $details = new Details();
        $details->setTax($amountPrice * $tva / (1 + $tva))
            ->setSubtotal($amountPrice);
        $amount = new Amount();
        $amount->setCurrency($ccy)
            ->setTotal($amountPrice)
            ->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription($tripDescription)
            ->setInvoiceNumber(uniqid());
        $payment = new Payment();
        $payment->setIntent(self::PAY_PAL_PAYMENT_INTENT_AUTHORIZE)
            ->setPayer($payer)
            ->setTransactions(array($transaction));
        $payment->create($apiContext);
        $transactions = $payment->getTransactions();
        $relatedResources = $transactions[0]->getRelatedResources();
        $authorization = $relatedResources[0]->getAuthorization();
        return [
            "payId"=>$payment->getId(),
            "authId"=>$authorization->getId()
        ];
    }


    public function captureAuth($authId,$captureFee,$clientId,$secret)
    {
        $apiContext = $this->getPayPalApiContext($clientId, $secret);
        $authorization = Authorization::get($authId, $apiContext);
        $amt = new Amount();
        $amt->setCurrency("USD")
            ->setTotal($captureFee);
        ### Capture
        $capture = new Capture();
        $capture->setAmount($amt);
        $capture->setIsFinalCapture(true);
        $getCapture = $authorization->capture($capture, $apiContext);
        return $getCapture->getId();
    }
}