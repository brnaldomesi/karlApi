<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Method\GeoLocationAlgorithm;
use App\Method\MethodAlgorithm;
use App\Method\PaymentAlgorithm;
use App\Method\PaymentMethod;
use App\Method\UrlSpell;
use App\Model\Booking;
use App\Model\Company;
use App\Model\CompanyPayMethod;
use App\Model\Customer;
use App\Jobs\SendEmailCustomerInvoiceJob;
use App\Model\Order;
use App\Model\RunningError;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use PayPal\Api\CreditCard;
use phpDocumentor\Reflection\DocBlock\Tags\Method;

class PaymentController extends Controller
{

    const PAY_PAL_PAYMENT_INTENT_AUTHORIZE = 'authorize';
    const PAY_PAL_PAYMENT_INTENT_SALE = 'sale';

    public function addCustomersCreditCard(Request $request)
    {
        $customer_id = $request->user->customer->id;
        $company_id = $request->user->company_id;
        $card_type = Input::get('card_type', null);
        $card_number = Input::get('card_number', null);
        $expire_month = Input::get('expire_month', null);
        $expire_year = Input::get('expire_year', null);
        $cvv2 = Input::get('cvv2', null);
        $first_name = Input::get('first_name', null);
        $last_name = Input::get('last_name', null);
//        $address = Input::get('address', null);
        $zipCode = Input::get("zip", null);
        // if (!is_null($zipCode)) {
        //     if (!MethodAlgorithm::zipCodeMatchRegex($zipCode)) {
        //         return ErrorCode::errorParam('zip');
        //     }
        // }
        return PaymentAlgorithm::getPayment()->addCustomerCreditCard($customer_id, $card_type, $card_number, $expire_month,
            $expire_year, $cvv2, $first_name, $last_name, $company_id, $zipCode);
//        } else {
//            try {
//                $address = PaymentMethod::checkBillingAddress($address);
//            } catch (\Exception $ex) {
//                $address = null;
//            }
//            if (is_null($address)) {
//                return ErrorCode::errorParam('address');
//            }
//            return PaymentAlgorithm::getPayment()->addCustomerCreditCard($customer_id, $card_type, $card_number, $expire_month,
//                $expire_year, $cvv2, $first_name, $last_name, $company_id, $address->zip,
//                $address->line1,
//                $address->line2,
//                $address->city,
//                $address->country);
    }

    public function companyGetCustomersCreditCard(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $customer = Customer::leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->where('customers.id', $customer_id)
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorNoObject('customer');
        }
        if ($company_id != $customer->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        return PaymentAlgorithm::getPayment()->getCustomerCreditCards($customer_id, $company_id);
    }

    public function companyAddCustomersCreditCard(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $customer = Customer::leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->where('customers.id', $customer_id)
            ->first();
        if ($company_id != $customer->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        $card_type = Input::get('card_type', null);
        $card_number = Input::get('card_number', null);
        $expire_month = Input::get('expire_month', null);
        $expire_year = Input::get('expire_year', null);
        $cvv2 = Input::get('cvv2', null);
        $first_name = Input::get('first_name', null);
        $last_name = Input::get('last_name', null);
//        $address = Input::get('address', null);
        $zipCode = Input::get('zip', null);

        // if (!is_null($zipCode)) {
        //     if (!MethodAlgorithm::zipCodeMatchRegex($zipCode)) {
        //         return ErrorCode::errorParam('zip');
        //     }
        // }
        return PaymentAlgorithm::getPayment()->addCustomerCreditCard(
            $customer_id, $card_type, $card_number, $expire_month,
            $expire_year, $cvv2, $first_name, $last_name, $company_id, $zipCode);
//        } else {
//            try {
//                $address = PaymentMethod::checkBillingAddress($address);
//            } catch (\Exception $ex) {
//                $address = null;
//            }
//
//            if (is_null($address)) {
//                return ErrorCode::errorParam('address');
//            }
//            return PaymentAlgorithm::getPayment()->addCustomerCreditCard(
//                $customer_id, $card_type, $card_number, $expire_month,
//                $expire_year, $cvv2, $first_name, $last_name, $company_id, $address->zip,
//                $address->line1,
//                $address->line2,
//                $address->city,
//                $address->country);
//        }
    }

    public function companyDeleteCustomerCreditCards(Request $request, $customer_id, $card_token)
    {
        $company_id = $request->user->company_id;
        $customer = Customer::leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->where('customers.id', $customer_id)
            ->first();
        if ($company_id != $customer->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        return PaymentAlgorithm::getPayment()->deleteCustomerCreditCard($customer_id, $company_id, $card_token);
    }

    public function getCustomersCreditCard(Request $request)
    {
        $customer_id = $request->user->customer->id;
        $company_id = $request->user->company_id;
        return PaymentAlgorithm::getPayment()->getCustomerCreditCards($customer_id, $company_id);
    }

    public function deleteCustomerCreditCards(Request $request, $card_token)
    {
        $customer_id = $request->user->customer->id;
        $company_id = $request->user->company_id;
        return PaymentAlgorithm::getPayment()->deleteCustomerCreditCard($customer_id, $company_id, $card_token);
    }

//    public function putCompanyCreditCard(Request $request)
//    {
//        $company_id = $request->user->company_id;
//        $card_type = Input::get('card_type', null);
//        $card_number = Input::get('card_number', null);
//        $expire_month = Input::get('expire_month', null);
//        $expire_year = Input::get('expire_year', null);
//        $cvv2 = Input::get('cvv2', null);
//        $first_name = Input::get('first_name', null);
//        $last_name = Input::get('last_name', null);
//
//        $result = $this->checkCreditCardInfo($card_type,
//            $card_number, $expire_month,
//            $expire_year, $cvv2, $first_name, $last_name);
//        if (!is_null($result)) {
//            return $result;
//        }
//        $companyPay = \App\Model\CreditCard::where([
//            ['owner_id', $company_id],
//            ['type', \App\Model\CreditCard::TYPE_COMPANY]
//        ])->first();
//        if (empty($companyPay)) {
//            return ErrorCode::errorParam('error there no credit card for this company');
//        }
//        if (is_null($card_type)) {
//            return ErrorCode::errorParam('card_type');
//        }
//        $companyPay->card_type = $card_type;
//        $companyPay->card_number = $card_number;
//        $companyPay->expire_month = $expire_month;
//        $companyPay->expire_year = $expire_year;
//        $companyPay->cvv2 = $cvv2;
//        $companyPay->first_name = $first_name;
//        $companyPay->last_name = $last_name;
//        $companyPay->save();
//        return ErrorCode::success($companyPay->toArray(), false);
//    }


    public function getCompanyCreditCard(Request $request)
    {
        $company_id = $request->user->company_id;
        return $this->getCreditCard($company_id, \App\Model\CreditCard::TYPE_COMPANY, $company_id);
    }

//    public function deleteCompanyCreditCards(Request $request, $card_number)
//    {
//        $company_id = $request->user->company_id;
//        return $this->deleteCreditCard($company_id, \App\Model\CreditCard::TYPE_CUSTOMER, $card_number, $company_id);
//    }

    private function getCreditCard($owner_id, $type, $company_id)
    {
        $company_id = ($type == \App\Model\CreditCard::TYPE_CUSTOMER ? $company_id : 0);
        $payInfo = CompanyPayMethod::where([
            "company_id" => $company_id,
            "active" => 1
        ])->first();
        switch ($payInfo->pay_type) {
            case CompanyPayMethod::PAY_TYPE_PAY_PAL:
                $customer_id = ($type == \App\Model\CreditCard::TYPE_CUSTOMER ? 'customer_' : 'company_') . $owner_id;
                $params = array(
                    "sort_by" => "create_time",
                    "sort_order" => "desc",
                    "external_customer_id" => $customer_id// Filtering by MerchantId set during CreateCreditCard.
                );
                try {
                    $result = CreditCard::all($params, $this->getPayPalApiContext($payInfo));
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
                //TODO 临时添加稍后删除
                $creditCards = array();
                foreach ($result->getItems() as $item) {
                    $creditCard = array();
                    $creditCard['card_token'] = $item->getId();
                    $creditCard['card_number'] = $item->getNumber();
                    $creditCard['expire_month'] = $item->getExpireMonth();
                    $creditCard['expire_year'] = $item->getExpireYear();
                    $creditCard['first_name'] = $item->getFirstName();
                    $creditCard['last_name'] = $item->getLastName();
                    $creditCard['card_type'] = $this->getCardTypeNumber($item->getType());
                    $creditCard['valid_until'] = strtotime($item->getValidUntil());
                    array_push($creditCards, $creditCard);
                }
                break;
            case CompanyPayMethod::PAY_TYPE_CHASE:
                $creditCards = null;
                break;
        }

        if (empty($creditCards) || count($creditCards) == 0) {
            return ErrorCode::successEmptyResult("user has no credit card");
        } else {
            return ErrorCode::success($creditCards, false);
        }
    }

    private function getCardTypeNumber($cardShort)
    {
        switch (strtolower($cardShort)) {
            case 'visa':
                return \App\Model\CreditCard::VISA;
            case 'mastercard':
                return \App\Model\CreditCard::MasterCard;
            case 'amex':
                return \App\Model\CreditCard::AmericanExpress;
            case 'discover':
                return \App\Model\CreditCard::DISCOVER;
        }
    }

    public function getCompanyAllPayMethod(Request $request)
    {
        $method = Company::where('id', $request->user->company_id)
            ->select(
                DB::raw('ifnull(stripe_acct_id,"") as stripe_acct_id')
            )
            ->first();

        if (empty($method)) {
            return ErrorCode::successEmptyResult('no pay method');
        } else {
            return ErrorCode::success($method->stripe_acct_id);
        }
    }

    public function addCompanyAllPayMethod(Request $request)
    {
        $company_id = $request->user->company_id;
        $client_id = Input::get('client_id', null);
        $secret = Input::get('secret', null);
        $account = Input::get('account', null);
        $pay_type = Input::get('pay_type', null);
        $active = Input::get('active', 0);
        try {
            $result = DB::transaction(function () use (
                $company_id, $client_id,
                $secret, $account, $pay_type, $active
            ) {
                return PaymentAlgorithm::getPayment()->addCompanyPayMethod($company_id, $client_id,
                    $secret, $account, $pay_type, $active);
            });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateCompanyPayMethodActive(Request $request, $method_id)
    {
        $company_id = $request->user->company_id;
        $companyPay = CompanyPayMethod::where('id', $method_id)
            ->first();
        if (empty($companyPay)) {
            return ErrorCode::errorNotExist('pay method');
        }

        if ($companyPay->company_id != $company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        if ($companyPay->pay_type != CompanyPayMethod::PAY_TYPE_STRIPE) {
            return ErrorCode::errorPayMethod();
        }

        try {
            $result = DB::transaction(function () use ($companyPay, $company_id, $method_id) {
                $payInfo = CompanyPayMethod::where('company_id', $company_id)
                    ->where('active', CompanyPayMethod::ACTIVE)
                    ->where('pay_type', CompanyPayMethod::PAY_TYPE_STRIPE)
                    ->first();
                if (!empty($payInfo)) {
                    \App\Model\CreditCard::where('pay_method_id', $payInfo->id)->delete();
                }
                CompanyPayMethod::where('company_id', $company_id)
                    ->update(['active' => CompanyPayMethod::NEGATIVE]);
                CompanyPayMethod::where('id', $method_id)
                    ->update(['active' => CompanyPayMethod::ACTIVE]);
                DB::delete("
                delete FROM stripe_customers WHERE customer_id in (
    SELECT concat('customer_',customers.id) FROM customers LEFT JOIN users
      on customers.user_id=users.id WHERE users.company_id=1);
                ");
                return CompanyPayMethod::where('id', $company_id)
                    ->orderBy('active', 'desc')
                    ->get();
            });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return ErrorCode::errorDB();
        }
    }

    public function deleteCompanyPayMethod(Request $request, $method_id)
    {
        $company_id = $request->user->company_id;
        $companyPay = CompanyPayMethod::where('id', $method_id)
            ->first();
        if (empty($companyPay)) {
            return ErrorCode::errorNotExist('pay method');
        }

        if ($companyPay->company_id != $company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        if ($companyPay->active == 1) {
            return ErrorCode::errorPayMethodInActive();
        }
        if ($companyPay->delete()) {
            return ErrorCode::success('success');
        } else {
            return ErrorCode::errorDB();
        }
    }

    public function changeCardType($card_type)
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

    public function sendInvoiceToCustomerHtml(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;
        $booking = Booking::where([
            ['id', $booking_id],
            ['company_id', $company_id]
        ])
            ->first();
        if (empty($booking)) {
            return "";
        }
        $order = \App\Model\Order::where(
            [
                ['orders.booking_id', $booking_id],
                ['orders.trip_state', \App\Model\Order::TRIP_STATE_SETTLE_DONE]
            ]
        )->first();
        if (empty($order)) {
            return "";
        }
        $trip = Booking::getBookingForInvoice($booking_id);
        $trip->show_type = 1;
        if (strtolower($trip->lang) == 'fr') {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        \app('translator')->setLocale($trip->lang);
        return view('customer_invoice_email', ['trip' => $trip, "lang" => $trip->lang])->render();
    }

    public function sendInvoiceToCustomer(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;
        $email = Input::get("email", null);
        $cc = Input::get("cc", null);
        $archive = Input::get("archive", 0);
        return $this->sendInvoice($booking_id, $company_id, $cc, $email, $archive == 1);
    }

    public function customerAskRateInvoice(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;
        $email = Input::get("email", null);
        $cc = Input::get("cc", null);
        return $this->sendInvoice($booking_id, $company_id, $cc, $email);
    }

    private function sendInvoice($booking_id, $company_id, $cc, $email, $archive = false)
    {
        if (!is_null($email) && !MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $ccEmailAddresses = null;
        if (!is_null($cc)) {
            $ccEmailAddresses = explode(',', $cc);
            foreach ($ccEmailAddresses as $ccEmailAddress) {
                if (!MethodAlgorithm::emailRegex($ccEmailAddress)) {
                    return ErrorCode::errorParam('cc');
                }
            }
        }

        $booking = Booking::where([
            ['id', $booking_id],
            ['company_id', $company_id]
        ])
            ->first();
        if (empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        }
        $order = \App\Model\Order::where(
            [
                ['orders.booking_id', $booking_id],
                ['orders.trip_state', \App\Model\Order::TRIP_STATE_SETTLE_DONE]
            ]
        )->first();
        if (empty($order)) {
            return ErrorCode::errorGetInvoiceOrderNotFinished();
        }
        if ($archive) {
            $order->archive = Order::ARCHIVE_TYPE_ARCHIVE;
        }
        $order->invoice_sent = 1;
        $order->save();

        $sender = (new SendEmailCustomerInvoiceJob($booking_id, $ccEmailAddresses, $email));
        $this->dispatch($sender);
        return ErrorCode::success('success');
    }

    public function getBookingInvoiceView($booking_id)
    {
        $trip = Booking::getBookingForInvoice($booking_id);
        $trip->show_type = 1;
        if (strtolower($trip->lang) == 'fr') {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        return view('customer_invoice_email', ['trip' => $trip, "lang" => $trip->lang])->render();
    }

    public function getBookingInvoiceDetail($booking_id)
    {
        return ErrorCode::success(Booking::getBookingForInvoice($booking_id));
    }
}