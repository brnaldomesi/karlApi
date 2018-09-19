<?php

use App\Model\Offer;
use Curl\Curl;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdateCompanyCountry extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $curl = new Curl();
        $companies = \App\Model\Company::all();
        foreach ($companies as $company) {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$company->lat},{$company->lng}&key={$_SERVER['GEOLOCAL_KEY']}";
            $request = $curl->get($url);
            $result = json_decode(json_encode($request), true);
            $addresses = $result['results'][0]["address_components"];
            for($i=count($addresses)-1;$i>=0;$i--){
                if(in_array("political",$addresses[$i]['types'])&&
                    in_array("country",$addresses[$i]['types'])){
                    $company->country = $addresses[$i]['short_name'];
                    $company->save();
                    break;
                }
            }
        }
    }
}
