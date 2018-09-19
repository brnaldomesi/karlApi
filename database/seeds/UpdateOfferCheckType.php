<?php
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateOfferCheckType extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update('update offers set `check_type`=`type`');

        $offerPrices = \App\Model\OfferPrice::select('offer_id',
            DB::raw("count(*) as price_count")
            )
            ->groupBy('offer_id')
            ->get();
        foreach ($offerPrices as $offer) {
            if($offer->price_count > 1){
                Offer::where('id',$offer->offer_id)->update(['type'=>Offer::SHOW_TYPE_TRAN]);
            }
        }
    }
}
