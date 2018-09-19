<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/9/7
 * Time: 上午10:06
 */

namespace App\Model;


use App\ErrorCode;
use App\Method\UrlSpell;
use App\Method\UserMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Asst extends Model
{
    protected $fillable = [
        "user_id","asst_id","country"
    ];
    protected $hidden=[
        "created_at","updated_at"
    ];

    public static function updateAsstInfo($asstId, $param, $token)
    {
        $result = DB::transaction(function () use ($asstId, $param, $token) {
            $asst = Asst::where('asst_id', $asstId)->first();
            if (empty($asst)) {
                throw new \Exception(ErrorCode::errorNotExist('sale'));
            }
            $param = json_decode($param, true);
            $param['id'] = $asst->user_id;
            $password = isset($param['pwd']) ? $param['pwd'] : null;
            $first_name = isset($param['first_name']) ? $param['first_name'] : null;
            $last_name = isset($param['last_name']) ? $param['last_name'] : null;
            $mobile = isset($param['mobile']) ? $param['mobile'] : null;
            $email = isset($param['email']) ? $param['email'] : null;
            $address = isset($param['address']) ? $param['address'] : null;
            $gender = isset($param['gender']) ? $param['gender'] : null;
            $lang = isset($param['lang']) ? $param['lang'] : null;
            $country = isset($param['country']) ? $param['country'] : null;
            $companies = isset($param['companies']) ? $param['companies'] : null;

            if (
                is_null($password) &&
                is_null($first_name) &&
                is_null($last_name) &&
                is_null($mobile) &&
                is_null($email) &&
                is_null($address) &&
                is_null($gender) &&
                is_null($lang) &&
                is_null($country) &&
                is_null($companies)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }
            UserMethod::updateUserInfo($param, false, false,true);

            if(!is_null($country)){
                if(empty($country)){
                    throw new \Exception(ErrorCode::errorParam('country'));
                }else{
                    $asst->country=$country;
                }
            }

            if (!is_null($companies)) {
                if (!empty($companies)) {
                    $delComCount = SaleAsstCompany::where('asst_id', $asstId)->count();
                    if ($delComCount != 0) {
                        if (!SaleAsstCompany::where('asst_id', $asstId)->delete()) {
                            throw new \Exception(ErrorCode::errorDB());
                        }
                    }
                    foreach ($companies as $com) {
                        $comInfo = Company::leftjoin('sale_asst_companies','sale_asst_companies.company_id','=','companies.id')
                            ->where('companies.id', $com['id'])
                            ->where('sale_asst_companies.sale_id', $com['sale_id'])
                            ->select(
                                DB::raw("if(sale_asst_companies.asst_id is null , 0 , 1) as checked"),
                                'sale_asst_companies.asst_id'
                            )
                            ->first();
                        if(empty($comInfo)){
                            SaleAsstCompany::create(['company_id' => $com['id'],'sale_id'=>$com['sale_id'] ,'asst_id' => $asstId]);
                        }else{
                            if($comInfo->checked == 1){
                                throw new \Exception(ErrorCode::errorAlreadyExist('company already bind to ' . $comInfo->asst_id));
                            }else{
                                throw new \Exception(ErrorCode::errorNotExist('company info ' . $com['id']));
                            }
                        }
                    }
                } else {
                    SaleAsstCompany::where('asst_id', $asstId)->delete();
                }
            }
            $asst->save();
            $asst = self::getSaleDetail($asstId, $token);

            return $asst;
        });
        return $result;
    }


    public static function getSaleDetail($asstId,$token)
    {

        $asst = Asst::leftjoin(DB::raw("(select count(*) as count , sale_asst_companies.asst_id from sale_asst_companies 
                                            left join companies on sale_asst_companies.company_id=companies.id 
                                            group by sale_asst_companies.asst_id
                                            ) as sc"),'sc.asst_id','=','assts.asst_id')
            ->leftjoin('users','users.id','=','assts.user_id')
            ->where('assts.asst_id',$asstId)
            ->select(
                'assts.asst_id',
                'assts.user_id as user_id',
                'assts.country',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.gender',
                'users.lang',
                'users.email',
                'users.mobile',
                DB::raw("ifnull(sc.count,0) as count"),
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'assts.id', $token, UrlSpell::companyAsstType) . ' as avatar_url', '')
            )
            ->first();
        $sales = Sale::leftjoin(DB::raw("(
              select sc.sale_id,sc.company_id,sac.asst_id from sale_companies as sc
                LEFT JOIN sale_asst_companies as sac on
                  sc.sale_id=sac.sale_id and sc.company_id=sac.company_id
            ) as sac"),"sac.sale_id","=","sales.sale_id")
            ->leftjoin("users","users.id","=","sales.user_id")
            ->leftjoin("companies","companies.id","=","sac.company_id")
            ->select(
                "users.first_name",
                "users.last_name",
                "sales.sale_id",
                "sac.company_id",
                "companies.name",
                DB::raw("if(sac.asst_id is not null && sac.asst_id!='{$asstId}',1,0) as marked"),
                DB::raw("if(sac.asst_id='{$asstId}',1,0) as selected")
            )
            ->get();

        $asst->sales=$sales;
        return $asst;
    }
}
