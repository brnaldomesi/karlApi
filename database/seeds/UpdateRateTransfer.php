<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateRateTransfer extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $offerIds = \App\Model\OfferPrice::select(
            "offer_id",
            DB::raw("count(*) as prices")
            )
            ->groupBy('offer_id')
            ->get();
        foreach ($offerIds as $offerId) {
            if($offerId->prices>1){
                \App\Model\Offer::where('id',$offerId->offer_id)
                    ->update([
                        'a_address'=>"",
                        'a_radius'=>"",
                        'a_lat'=>"",
                        'a_lng'=>"",
                        'a_is_airport'=>0,
                        'a_port_price'=>0
                    ]);
            }else{
                \App\Model\Offer::where('id',$offerId->offer_id)
                    ->update([
                        'cost_min'=>0
                    ]);
            }

        }
    }
}
