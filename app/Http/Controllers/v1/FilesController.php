<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Method\UrlSpell;
use App\Model\Car;
use App\Model\CarModel;
use App\Model\CarModelImg;
use App\Model\Company;
use App\Model\User;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FilesController extends Controller
{
    public function uploadAvatarFile(Request $request)
    {
        $token = $request->user->token;
        if (!$request->hasFile('avatar')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('avatar')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $avatarFile = $request->file('avatar');
        $fileMode = $avatarFile->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $user_id = $request->user->id;
        $company_id = $request->user->company_id;


        $fileType = explode('/', $fileMode);

        $avatarFileDirPath = str_replace('c_id', $company_id, $this->avatarFilePath);

        $avatarPath = $avatarFile->move($avatarFileDirPath, $user_id . "." . $fileType[1]);
        $user = User::where('id', $user_id)->first();
        $user->avatar_url = $avatarPath;
        $user->save();
        $avatarUrl = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($user->updated_at,$avatarPath,$token,'',UrlSpell::mine);
        return ErrorCode::success($avatarUrl);
    }


    public function getAvatar(Request $request)
    {

        $avatar_path = $request->user->avatar_url;
        return $this->getFile($avatar_path);
    }


    public function uploadDriverAvatar(Request $request, $driver_id)
    {
        $user_id = User::leftjoin('drivers', 'drivers.user_id', '=', 'users.id')
            ->where('drivers.id', $driver_id)
            ->select('users.id')
            ->first();
        if (is_null($user_id) || empty($user_id)) {
            return ErrorCode::errorNotExist('driver');
        }
        return ErrorCode::success($this->updateAvatar($request, $user_id, $driver_id, UrlSpell::driverType));

    }

    public function uploadCustomerAvatar(Request $request, $customer_id)
    {
        $user_id = User::leftjoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('customers.id', $customer_id)
            ->select('users.id')
            ->first();
        if (is_null($user_id) || empty($user_id)) {
            return ErrorCode::errorNotExist('customers');
        }
        return ErrorCode::success($this->updateAvatar($request, $user_id, $customer_id, UrlSpell::customerType));
    }

    public function uploadAdminAvatar(Request $request, $admin_id)
    {
        $user_id = User::leftjoin('admins', 'admins.user_id', '=', 'users.id')
            ->where('admins.id', $admin_id)
            ->select('users.id')
            ->first();
        if (is_null($user_id) || empty($user_id)) {
            return ErrorCode::errorNotExist('admin');
        }
        return ErrorCode::success($this->updateAvatar($request, $user_id, $admin_id, UrlSpell::adminType));
    }

    private function updateAvatar(Request $request, $user, $target_id, $type)
    {
        $user_id = $user->id;
        $token = $request->get("token");
        if (!$request->hasFile('avatar')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('avatar')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $avatarFile = $request->file('avatar');
        $fileMode = $avatarFile->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $fileType = explode('/', $fileMode);
        $user = User::where('users.id', $user_id)->first();
        $avatarFileDirPath = str_replace('c_id', $user->company_id, $this->avatarFilePath);
        $avatarPath = $avatarFile->move($avatarFileDirPath, $user_id . "." . $fileType[1]);
        $user->avatar_url = $avatarPath;
        if (!($user->save())) {
            throw new \Exception(ErrorCode::errorDB());
        }
        //

        $avatarUrl = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($user->updated_at,$avatarPath,$token,$target_id,$type);
        return $avatarUrl;
    }


    public function getDriverAvatar($driver_id)
    {
        $avatar_url = User::leftjoin('drivers', 'drivers.user_id', '=', 'users.id')
            ->where('drivers.id', $driver_id)
            ->select('users.avatar_url')
            ->first();
        if (is_null($avatar_url) || empty($avatar_url)) {
            return "";
        }
        return $this->getFile($avatar_url->avatar_url);

    }

    public function getCustomerAvatar($customer_id)
    {
        $avatar_url = User::leftjoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('customers.id', $customer_id)
            ->select('users.avatar_url')
            ->first();
        if (is_null($avatar_url) || empty($avatar_url)) {
            return "";
        }
        return $this->getFile($avatar_url->avatar_url);
    }

    public function getAdminAvatar($admin_id)
    {
        $avatar_url = User::leftjoin('admins', 'admins.user_id', '=', 'users.id')
            ->where('admins.id', $admin_id)
            ->select('users.avatar_url')
            ->first();
        if (is_null($avatar_url) || empty($avatar_url)) {
            return "1";
        }
        return $this->getFile($avatar_url->avatar_url);
    }

   

    public function companiesUploadDriverAvatar(Request $request, $driver_id)
    {
        $company_id = $request->user->company_id;
        $user_id = User::leftjoin('drivers', 'drivers.user_id', '=', 'users.id')
            ->where('drivers.id', $driver_id)
            ->where('users.company_id', $company_id)
            ->select('users.id')
            ->first();
        if (is_null($user_id) || empty($user_id)) {
            return ErrorCode::errorNotExist('driver');
        }
        return ErrorCode::success($this->updateAvatar($request, $user_id, $driver_id, UrlSpell::companyDriverType));
    }

    public function companiesUploadCustomerAvatar(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $user_id = User::leftjoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('customers.id', $customer_id)
            ->where('users.company_id', $company_id)
            ->select('users.id')
            ->first();
        if (is_null($user_id) || empty($user_id)) {
            return ErrorCode::errorNotExist('driver');
        }
        return ErrorCode::success($this->updateAvatar($request, $user_id, $customer_id, UrlSpell::companyCustomerType));
    }


    public function companiesGetDriverAvatar(Request $request, $driver_id)
    {
        $company_id = $request->user->company_id;
        $avatar_url = User::leftjoin('drivers', 'drivers.user_id', '=', 'users.id')
            ->where('drivers.id', $driver_id)
            ->where('users.company_id', $company_id)
            ->select('users.avatar_url')
            ->first();
        if (is_null($avatar_url) || empty($avatar_url)) {
            return "";
        }
        return $this->getFile($avatar_url->avatar_url);

    }

    public function companiesGetCustomerAvatar(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $avatar_url = User::leftjoin('customers', 'customers.user_id', '=', 'users.id')
            ->where('customers.id', $customer_id)
            ->where('users.company_id', $company_id)
            ->select('users.avatar_url')
            ->first();
        if (is_null($avatar_url) || empty($avatar_url)) {
            return "";
        }
        return $this->getFile($avatar_url->avatar_url);
    }

    public function uploadCarModelImage(Request $request, $car_model_id)
    {
        $car_model = CarModel::where('id',$car_model_id)->first();
        if(empty($car_model)){
            return ErrorCode::errorNotExist('car model');
        }

        if (!$request->hasFile('car_model_img')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('car_model_img')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $avatarFile = $request->file('car_model_img');
        $fileMode = $avatarFile->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $count = CarModelImg::where('car_model_id',$car_model_id)->count();
        $fileType = explode('/', $fileMode);
        $carModelPath = $avatarFile->move($this->carModelImgFilePath, $car_model_id."-".($count+1). "." . $fileType[1]);
        CarModelImg::create(['car_model_id'=>$car_model_id,'image_path'=>$carModelPath,'priority'=>($count+1)]);
        $result = CarModelImg::where('car_model_id',$car_model_id)
            ->select('id',UrlSpell::getUrlSpell()->getCarModelImgInDB(),'car_model_id','priority')
            ->get();
        return ErrorCode::success($result);
    }

    public function replaceCarModelImage(Request $request,$car_model_image_id)
    {
        $car_model_image = CarModelImg::where('id',$car_model_image_id)->first();
        if(empty($car_model_image)){
            return ErrorCode::errorNotExist('car model image');
        }

        if (!$request->hasFile('car_model_img')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('car_model_img')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $avatarFile = $request->file('car_model_img');
        $fileMode = $avatarFile->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $fileType = explode('/', $fileMode);
        $logoFileDirPath = pathinfo($car_model_image->image_path);
        $filePath=$logoFileDirPath['filename']."." . $fileType[1];
        $fileNewPath = $avatarFile->move($logoFileDirPath['dirname'], $filePath);
        $car_model_image->image_path = $fileNewPath;
        $car_model_image->save();
        $result = CarModelImg::where('car_model_id',$car_model_image->car_model_id)
            ->select('id',UrlSpell::getUrlSpell()->getCarModelImgInDB(),'car_model_id','priority')
            ->get();
        return ErrorCode::success($result);
    }


    public function deleteCarModelImage($model_image_id)
    {
        $car_model = CarModelImg::where('id',$model_image_id)->first();
        if(empty($car_model)){
            return ErrorCode::errorNotExist('model image ');
        }
        $car_model_id = $car_model->car_model_id;
        if(!$car_model->delete()){
            return ErrorCode::errorDB();
        }
        $result = CarModelImg::where('car_model_id',$car_model_id)
            ->select('id as image_id',UrlSpell::getUrlSpell()->getCarModelImgInDB())
            ->get();
        return ErrorCode::success($result);
    }


    public function getCarsImage($car_id,$image_sn)
    {
//        $c_id = $request->user->company_id;
//        if($c_id != $company_id){
//            return "";
//        }
        $car = Car::where("id", $car_id)
            ->where(DB::raw('concat(right(md5(id),4),left(md5(id),4))'),$image_sn)
            ->select('img')
            ->first();
        if (is_null($car) || empty($car)) {
            return "";
        }
        return $this->getFile($car->img);
    }

    public function updateCompanyCarlImg(Request $request, $car_id)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $car = Car::where("id", $car_id)->where('company_id',$company_id)->first();
        if (is_null($car) || empty($car)) {
            return ErrorCode::errorNotExist('car ');
        }
        if (!$request->hasFile('car_image')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('car_image')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $car_model = $request->file('car_image');
        $fileMode = $car_model->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $fileType = explode('/', $fileMode);
        $filePath = str_replace('c_id',$company_id,$this->carImgFilePath);

        $carModelPath = $car_model->move($filePath, $car_id . "." . $fileType[1]);

        $car->img = $carModelPath;
        if ($car->save()) {
            $car = Car::where('id',$car_id)->select(UrlSpell::getUrlSpell()->getCarsImgInDB($company_id,$token))->first();
            return ErrorCode::success($car->img);
        } else {
            return ErrorCode::errorDB();
        }

    }

    public function getCompanyLogoByCompanyId($company_id)
    {
        $company = Company::where('id',$company_id)
            ->select('img')
            ->first();
        return $this->getFile($company->img);
    }

    public function getCompanyLogo(Request $request)
    {
        $company_id = $request->user->company_id;
        $company = Company::where('id',$company_id)
            ->select('img')
            ->first();

        return $this->getFile($company->img);
    }


    public function getCompanyLogoById($company_id)
    {
        $company = Company::where('id',$company_id)
            ->select('img')
            ->first();

        return $this->getFile($company->img);
    }

    public function uploadCompanyImg(Request $request)
    {
        $company_id = $request->user->company_id;
        $company = Company::where('id',$company_id)->first();
        if(empty($company)){
            return ErrorCode::errorNotExist('company');
        }
        $token = $request->user->token;

        if (!$request->hasFile('company_logo')) {
            return ErrorCode::errorMissingParam();
        }
        if (!$request->file('company_logo')->isValid()) {
            return ErrorCode::errorFileUpload();
        }
        $avatarFile = $request->file('company_logo');
        $fileMode = $avatarFile->getMimeType();
        if (strtolower($fileMode) != strtolower('image/png')
            && strtolower($fileMode) != strtolower('image/jpg')
            && strtolower($fileMode) != strtolower('image/jpeg')
        ) {
            return ErrorCode::errorFileType();
        }
        $fileType = explode('/', $fileMode);
        $logoFileDirPath = str_replace('c_id', $company_id, $this->companyImgFilePath);
        $logoPath = $avatarFile->move($logoFileDirPath,  "logo." . $fileType[1]);
        $company->img = $logoPath;
        $company->save();
        $logoUrl = UrlSpell::getUrlSpell()->getCompaniesLogoByName($company->name,$company->updated_at);
        return ErrorCode::success($logoUrl);
    }

    public function invoiceGetCompanyLogo($company_name)
    {
        $img = Company::where(DB::raw("concat(right(md5(companies.name),4),left(md5(companies.name),4))"),$company_name)
            ->select('img')
            ->first();
        if(!empty($img)){
            return $this->getFile($img->img);
        }
        $img = Company::where(DB::raw("md5(companies.name)"),$company_name)
            ->select('img')
            ->first();
        if(!empty($img)){
            return $this->getFile($img->img);
        }

        $img = Company::where("companies.id",$company_name)
            ->select('img')
            ->first();
        if (!empty($img)){
            return $this->getFile($img->img);
        }
        return "";
    }


    public function getCarModelImage($model_id, $sn)
    {
        $img = CarModelImg::where('car_model_id',$model_id)
            ->where(DB::raw('concat(right(md5(image_path),4),left(md5(image_path),4))'),$sn)
            ->first();
        if(empty($img)){
            return "";
        }
        return $this->getFile($img->image_path);
    }
    public function getAskCarModelImage($model_id)
    {
        $img = CarModelImg::where(DB::raw('concat(right(md5(car_model_id),4),left(md5(car_model_id),4))'),$model_id)
            ->first();
        if(empty($img)){
            return "";
        }
        return $this->getFile($img->image_path);
    }

    private function getFile($file_path)
    {
        if (empty($file_path) || !file_exists($file_path)) {
            return "";
        } else {
            $fileTypes = explode('.', $file_path);
            $fileType = $fileTypes[1];
            return response(File::get($file_path))->header('Content-Type', 'image/' . $fileType);
        }
    }


    public function uploadImage(Request $request)
    {
        return response(File::get("/Users/lqh/Documents/test.html"));
    }
}