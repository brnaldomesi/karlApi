<?php

namespace App\Jobs;

use App\Constants;
use App\Method\MethodAlgorithm;
use App\Method\PaymentAlgorithm;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\BookingTransactionHistory;
use App\Model\Company;
use App\Model\CompanyAnSetting;
use App\Model\Order;
use App\Model\TransRecord;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Transfer;

class PayTripSettleJob extends Job
{

    private $booking_id;
    private $company_id;

    /**
     * TripSettleJob constructor.
     * @param $booking_id
     * @param $company_id
     */
    public function __construct($booking_id, $company_id)
    {
        //
        $this->company_id = $company_id;
        $this->booking_id = $booking_id;
        $this->onQueue(QueueName::PayTripSettle);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $order = Order::where('booking_id', $this->booking_id)
            ->first();
        if ($order->trip_state != Order::TRIP_STATE_WAITING_TO_SETTLE) {
            Log::info('error pay in error settle ');
            return;
        }
        $order->trip_state = Order::TRIP_STATE_SETTLING;
        $order->save();
        $bill = null;
        try {
            $bill = PaymentAlgorithm::getPayment()
                ->tripFinalSettle(
                    $this->booking_id,
                    $this->company_id
                );
            dispatch(new SendEmailCustomerInvoiceJob($this->booking_id));
        } catch (\Exception $ex) {
            Log::error($ex);
        }
        $order->settle_time = MethodAlgorithm::formatTimestampToDate(time());
        $order->trip_state = Order::TRIP_STATE_SETTLE_DONE;
        $order->save();
        $bookingCount = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->whereRaw("bookings.company_id=bookings.exe_com_id")
            ->where('bookings.company_id', $this->company_id)
            ->count();
        if ($bookingCount == CompanyAnSetting::AN_ABLE_COUNT) {
            CompanyAnSetting::where('company_id', $this->company_id)->update(
                ["locked" => CompanyAnSetting::AN_UNLOCKED]
            );
        }
        DB::update("UPDATE customers
SET booking_total = booking_total+1,
  cost_total = cost_total + (SELECT pay1_amount+pay2_amount-pay1_refund_amount-pay2_refund_amount
                             FROM booking_transaction_histories WHERE  booking_id = " . $this->booking_id . ")
WHERE id = (SELECT customer_id
       FROM bookings WHERE id=" . $this->booking_id . ");");

        $booking = Booking::where("id", $this->booking_id)->select("customer_id")->first();
        dispatch(new CustomerCheckGroupJob($booking->customer_id));
        dispatch(new BookingStatisticJob($this->booking_id));
        //生成付费记录
        $histories = Bill::leftjoin("booking_transaction_histories as bth", "bills.booking_id", "=", "bth.booking_id")
            ->leftjoin("companies as own_com", "own_com.id", "=", "bills.own_com_id")
            ->leftjoin("companies as exe_com", "exe_com.id", "=", "bills.exe_com_id")
            ->where("bills.booking_id", $this->booking_id)
            ->select(
                "bills.own_com_id",
                "bills.exe_com_id",
                DB::raw("round(bth.pay1_amount/(bth.pay1_amount+bth.pay2_amount) ,2 ) as pct"),
                "bills.com_income",
                "bills.an_fee",
                "bth.pay1_id",
                "bth.pay2_id",
                "own_com.ccy as own_ccy",
                "exe_com.ccy as exe_ccy",
                "own_com.stripe_acct_id as own_stripe_id"
            )
            ->first();
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);

        if (!is_null($histories) && !is_null($histories->pct)) {
            if ($histories->own_com_id != $histories->exe_com_id) {
                $pay1 = Charge::retrieve($histories->pay1_id,["stripe_account"=>$histories->own_stripe_id]);
                $pay1Btr = BalanceTransaction::retrieve($pay1->balance_transaction,["stripe_account"=>$histories->own_stripe_id]);
                $pay1Amount = round($histories->an_fee * $histories->pct, 2);
                TransRecord::create([
                    'charge_id' => $histories->pay1_id,
                    'trans_balance_id' => $pay1->balance_transaction,
                    "company_id" => $histories->exe_com_id,
                    'available_on' => $pay1Btr->available_on + Constants::DAY_SECONDS,
                    'booking_id' => $this->booking_id,
                    'trans_type' => TransRecord::TYPE_WAIT,
                    'trans_amount' => $pay1Amount,
                    'trans_ccy' => $histories->exe_ccy,
                ]);
                if ($histories->pct != 1) {
                    $pay2 = Charge::retrieve($histories->pay2_id,["stripe_account"=>$histories->own_stripe_id]);
                    $pay2Btr = BalanceTransaction::retrieve($pay2->balance_transaction,["stripe_account"=>$histories->own_stripe_id]);
                    $pay2Amount = $histories->an_fee - $pay1Amount;
                    TransRecord::create([
                        'charge_id' => $histories->pay2_id,
                        'trans_balance_id' => $pay2->balance_transaction,
                        "company_id" => $histories->exe_com_id,
                        'available_on' => $pay2Btr->available_on + Constants::DAY_SECONDS,
                        'booking_id' => $this->booking_id,
                        'trans_type' => TransRecord::TYPE_WAIT,
                        'trans_amount' => $pay2Amount,
                        'trans_ccy' => $histories->exe_ccy,
                    ]);
                }
            }
//            $pay1 = Charge::retrieve($histories->pay1_id);
//            $pay1Btr = BalanceTransaction::retrieve($pay1->balance_transaction);
//            $pay1Amount = round($histories->com_income * $histories->pct, 2);
//            TransRecord::create([
//                'charge_id' => $histories->pay1_id,
//                'trans_balance_id' => $pay1->balance_transaction,
//                "company_id" => $histories->own_com_id,
//                'available_on' => $pay1Btr->available_on + Constants::DAY_SECONDS,
//                'booking_id' => $this->booking_id,
//                'trans_type' => TransRecord::TYPE_WAIT,
//                'trans_amount' => $pay1Amount - $pay1Btr->fee/100,
//                'trans_ccy' => $histories->own_ccy,
//            ]);
//            if ($histories->pct != 1) {
//                $pay2 = Charge::retrieve($histories->pay2_id);
//                $pay2Btr = BalanceTransaction::retrieve($pay2->balance_transaction);
//                $pay2Amount = $histories->com_income - $pay1Amount;
//                TransRecord::create([
//                    'charge_id' => $histories->pay2_id,
//                    'trans_balance_id' => $pay2->balance_transaction,
//                    "company_id" => $histories->own_com_id,
//                    'available_on' => $pay2Btr->available_on + Constants::DAY_SECONDS,
//                    'booking_id' => $this->booking_id,
//                    'trans_type' => TransRecord::TYPE_WAIT,
//                    'trans_amount' => $pay2Amount-$pay2Btr->fee/100,
//                    'trans_ccy' => $histories->own_ccy,
//                ]);
//            }
        }
    }
}
