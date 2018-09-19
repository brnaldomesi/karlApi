<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/10
 * Time: 下午3:12
 */

namespace App\Method;


use Illuminate\Support\Facades\DB;

class UrlSpell
{
    private $carModel = "/1/cars/models/m_id";
    private $carImage = "/1/companies/cars/car_id/image";
    private $payModel = "/1/payment/methods/img/";
    private $companyNameLogo = '/1/companies/logo';

    private $serverHead;

    private $userAvatar = '/1/users/avatar';
    private $companiesDriversAvatar = '/1/drivers/id/avatar';
    private $companiesCustomersAvatar = '/1/customers/id/avatar';
    private $adminAvatar = '/1/admins/id/avatar';
    private $driverAvatar = '/1/drivers/id/avatar';
    private $customerAvatar = '/1/customers/id/avatar';
    private $saleAvatar = '/1/sales/id/avatar';
    private $asstAvatar = '/1/assts/id/avatar';
    private $carModelImage = '/1/ask/cars/models/';

    private static $_instance = null;

    const driverType = 'driver';
    const customerType = 'customer';
    const adminType = 'admin';
    const companyDriverType = 'companyDriver';
    const companyCustomerType = 'companyCustomer';
    const companySaleType = 'sale';
    const companyAsstType = 'asst';
    const mine = 'mine';

    /**
     * UrlSpell constructor.
     */
    private function __construct()
    {
        $this->serverHead = $_SERVER['local_url'];
    }


    public static function getUrlSpell(){
        if(self::$_instance == null){
            self::$_instance = new UrlSpell();
        }
        return self::$_instance;
    }

    public function getCarModelImgInDB()
    {
        $middle = str_replace('m_id','\',car_model_imgs.car_model_id,\'',$this->carModel);
        $middle = $middle."/',right(MD5(car_model_imgs.image_path),4),left(MD5(car_model_imgs.image_path),4),'";
        $sql = " CASE WHEN car_model_imgs.image_path is not null THEN CONCAT('".
            $this->serverHead.$middle."?t=',
            right(md5(ifnull(car_model_imgs.updated_at,'1234')),4),
            left(md5(ifnull(car_model_imgs.updated_at,'1234')),4)) 
            else ''
            END as img";
//        echo $sql."<br>";
        return DB::raw($sql);
    }


    public function getCarsImgInDB($company_id, $token){
        return DB::raw($this->getCarsImgInSql($company_id,$token),"");
    }

    public function getCarModelImageBD()
    {
        $sql = "concat('".$this->serverHead.$this->carModelImage."',right(md5(car_models.id),4),left(md5(car_models.id),4))";
        return $sql;
    }

    public function getCarsImgInSql($company_id,$token,$asImg='img')
    {
        $middle = str_replace('car_id','\',cars.id,\'',$this->carImage);
        $middle = $middle."/',right(MD5(cars.id),4),left(md5(cars.id),4),'";
        return ("CASE WHEN cars.id THEN CONCAT('".
            $this->serverHead.$middle.
            "?token=".
            $token.
            "&t=',left(md5(cars.updated_at),4)) END as ".$asImg);
    }

    public function getPayMethodsImg(){
        return DB::raw("CASE WHEN pay_methods.id
    THEN
      CONCAT('".$this->serverHead.$this->payModel."',MD5(pay_methods.method))
    END as img");
    }

    public function getCompaniesLogoByNameInDB($asName = 'img' , $table="companies")
    {
        return
                "CONCAT('".$this->serverHead.$this->companyNameLogo."/',right(md5({$table}.name),4),left(md5({$table}.name),4),'?t=',left(md5({$table}.updated_at),4)) 
            as ".$asName;
    }
    public function getCompaniesLogoByName($companyName,$timestamp)
    {
        $comName = md5($companyName);
        $companyName = substr($comName,-4).substr($comName,0,4);
        return $this->serverHead.$this->companyNameLogo.'/'.$companyName.'?t='.md5($timestamp);
    }



    public function spellingAvatarUrl($timestamp,$avatarPath, $token, $targetId, $type)
    {
        if (empty($avatarPath) || !file_exists($avatarPath)) {
            return "";
        } else {
            if ($type == self::adminType) {
                $middle = str_replace('id', $targetId, $this->adminAvatar);
            } elseif ($type == self::driverType) {
                $middle = str_replace('id', $targetId, $this->driverAvatar);
            } elseif ($type == self::customerType) {
                $middle = str_replace('id', $targetId, $this->customerAvatar);
            } elseif ($type == self::companyDriverType) {
                $middle = str_replace('id', $targetId, $this->companiesDriversAvatar);
            } elseif ($type == self::companyCustomerType) {
                $middle = str_replace('id', $targetId, $this->companiesCustomersAvatar);
            } elseif ($type == self::companySaleType) {
                $middle = str_replace('id', $targetId, $this->saleAvatar);
            } elseif ($type == self::companyAsstType) {
                $middle = str_replace('id', $targetId, $this->asstAvatar);
            } elseif ($type == self::mine) {
                $middle = $this->userAvatar;
            } else {
                throw new \Exception("Tommy Lee code bug");
            }
            return $this->serverHead . $middle . "?token=" . $token."&t=".md5($timestamp);
        }
    }



    public function getSpellAvatarInDB($timestamp,$avatar_url,$targetId, $token, $type)
    {
        if ($type == self::adminType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->adminAvatar);
        } elseif ($type == self::driverType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->driverAvatar);
        } elseif ($type == self::customerType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->customerAvatar);
        } elseif ($type == self::companyDriverType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->companiesDriversAvatar);
        } elseif ($type == self::companyCustomerType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->companiesCustomersAvatar);
        } elseif ($type == self::companySaleType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->saleAvatar);
        } elseif ($type == self::companyAsstType) {
            $middle = str_replace('id', '\','.$targetId.',\'', $this->asstAvatar);
        } else {
            throw new \Exception("Tommy Lee code bug");
        }

        return
            'CASE WHEN '.$avatar_url.' is NULL 
                THEN \'\'
             WHEN '.$avatar_url.' =\'\'
                THEN \'\'
             ELSE             
                concat(\''.$this->serverHead . $middle . "?token=" . $token.'&t=\',left(md5('.$timestamp.'),4))
             END ';
    }
}