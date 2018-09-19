<?php

namespace App\Http\Controllers\v1;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected $avatarFilePath;
    protected $carImgFilePath;
    protected $carModelImgFilePath;
    protected $companyImgFilePath;
    protected $serverHead;
    protected $carImage = "/1/companies/c_id/cars/car_id/image";

    protected $isDebug;
    protected $payActive;
    protected $appPublish;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->avatarFilePath = $_SERVER['AVATAR_PATH'];
        $this->carImgFilePath = $_SERVER['CAR_IMG_PATH'];
        $this->carModelImgFilePath = $_SERVER['CAR_MODEL_IMG_PATH'];
        $this->companyImgFilePath = $_SERVER['COMPANY_IMG_PATH'];
        $this->serverHead = $_SERVER['local_url'];
        $this->isDebug = $_SERVER['APP_DEBUG'] === 'false' ? false : true;
        $this->payActive = $_SERVER['PAY_ACTIVE'] === 'false' ? false : true;
        $this->appPublish = $_SERVER['APP_PUB'] === 'false' ? false : true;

    }
}

