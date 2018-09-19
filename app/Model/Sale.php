<?php 
namespace App\Model;
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/08/08
 * Time: 01:58
 */
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Method\UserMethod;
use DB;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model{
        protected $fillable = [
            'id','sale_id','user_id','region','country'
        ];
        
        protected $hidden=[
            'created_at','updated_at'
        ];


    public static function updateSaleInfo($saleId, $param, $token)
    {
        $result = DB::transaction(function () use ($saleId, $param, $token) {
            $sale = Sale::where('sale_id', $saleId)->first();
            if (empty($sale)) {
                throw new \Exception(ErrorCode::errorNotExist('sale'));
            }
            $param = json_decode($param, true);
            $param['id'] = $sale->user_id;
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
                    $sale->country=$country;
                }
            }

            if (!is_null($companies)) {
                if (!empty($companies)) {
                    $delComCount = SaleCompany::where('sale_id', $saleId)->count();
                    if ($delComCount != 0) {
                        if (!SaleCompany::where('sale_id', $saleId)->delete()) {
                            throw new \Exception(ErrorCode::errorDB());
                        }
                    }
                    $comArrays = explode(',', $companies);
                    foreach ($comArrays as $comId) {
                        $comInfo = Company::leftjoin('sale_companies','sale_companies.company_id','=','companies.id')
                            ->where('companies.id', $comId)
                            ->select(
                                DB::raw("if(sale_companies.sale_id is null , 0 , 1) as checked"),
                                'sale_companies.sale_id'
                            )
                            ->first();
                        if (empty($comInfo)) {
                            throw new \Exception(ErrorCode::errorNotExist('company info ' . $comId));
                        }
                        if($comInfo->checked == 1){
                            throw new \Exception(ErrorCode::errorAlreadyExist('company already bind to ' . $comInfo->sale_id));
                        }
                        SaleCompany::create(['company_id' => $comId, 'sale_id' => $saleId]);
                    }
                } else {
                    SaleCompany::where('sale_id', $saleId)->delete();
                }
            }
            $sale->save();
            $sale = self::getSaleDetail($saleId, $token);

            return $sale;
        });
        return $result;
    }

    public static function getSaleDetail($saleId,$token)
    {

        $sale = Sale::leftjoin(DB::raw("(select count(*) as count , sale_companies.sale_id from sale_companies 
                                            left join companies on sale_companies.company_id=companies.id 
                                            group by sale_companies.sale_id
                                            ) as sc"),'sc.sale_id','=','sales.sale_id')
            ->leftjoin('users','users.id','=','sales.user_id')
            ->where('sales.sale_id',$saleId)
            ->select(
                'sales.sale_id',
                'sales.user_id as user_id',
                'sales.country',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.gender',
                'users.lang',
                'users.email',
                'users.mobile',
                DB::raw("ifnull(sc.count,0) as count"),
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'sales.id', $token, UrlSpell::companySaleType) . ' as avatar_url', '')
            )
            ->first();
        $companies = Company::leftjoin("sale_companies as sc",'sc.company_id','=','companies.id')
            ->leftjoin("sales",'sales.sale_id','=','sc.sale_id')
            ->leftjoin("users",'sales.user_id','=','users.id')
            ->select(
                DB::raw("if(sc.sale_id='{$saleId}',1,0) as selected"),
                DB::raw("if(sc.sale_id is not null &&sc.sale_id!='{$saleId}',1,0) as marked"),
                "companies.name",
                DB::raw("concat(users.first_name,' ',users.last_name) as sale_name"),
                "companies.id"
            )
            ->get();
        $sale->companies = $companies;
        return $sale;
    }
}