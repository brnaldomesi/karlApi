<?php
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateRateUnit extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("update offers set km_max=mi_max*".\App\Constants::MI_2_KM." , km_min=mi_min*".\App\Constants::MI_2_KM." where check_type=".Offer::CHECK_TYPE_DISTANCE.";");
    }
}
