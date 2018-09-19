<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/11/2
 * Time: 上午9:58
 */


class UpdateSaleId extends BaseSeeder
{
    protected function task()
    {
        DB::transaction(function(){
            DB::update("update sales set sale_id = concat(left(sale_id,3),right(sale_id,2));");
            DB::update("update sale_assts set sale_id = concat(left(sale_id,3),right(sale_id,2));");
            DB::update("update sale_asst_companies set sale_id = concat(left(sale_id,3),right(sale_id,2));");
            DB::update("update sale_companies set sale_id = concat(left(sale_id,3),right(sale_id,2));");
        });
    }

}