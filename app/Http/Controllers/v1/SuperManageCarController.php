<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/6
 * Time: 上午11:05
 */

namespace App\Http\Controllers\v1;


use App\ErrorCode;
use App\Method\UrlSpell;
use App\Model\CarBrand;
use App\Model\CarCategory;
use App\Model\CarModel;
use App\Model\CarModelImg;
use Illuminate\Support\Facades\Input;

class SuperManageCarController extends Controller
{
    public function getAllCarModelAndImageOfPlatform()
    {
        $carModels = CarModel::leftjoin('car_brands','car_brands.id','=','car_models.car_brand_id')
            ->leftjoin('car_categories','car_categories.id','=','car_models.car_category_id')
            ->select(
                'car_brands.name as brand_name',
                'car_models.name as model_name',
                'car_categories.name as category_name',
                'car_models.id',
                'car_models.bags_max',
                'car_models.seats_max',
                'car_brands.id as brand_id',
                'car_categories.id as category_id'
            )
            ->orderBy('car_brands.sort','asc')
            ->orderBy('car_models.sort','asc')
            ->get();
        foreach ($carModels as $carModel) {
            $img = CarModelImg::where("car_model_id",$carModel->id)
                ->select('id','car_model_id',"priority",
                    UrlSpell::getUrlSpell()->getCarModelImgInDB())
                ->orderBy('priority','asc')->get();
            $carModel->imgs = $img;
        }

        return ErrorCode::success($carModels);
    }


    public function getCarCategories()
    {
        $categories = CarCategory::all();
        return ErrorCode::success($categories);
    }

    public function getCarBrand()
    {
        $categories = CarBrand::orderBy("sort",'asc')->get();
        return ErrorCode::success($categories);
    }

    public function addCarBrand()
    {
        $brandName = Input::get("name",null);
        if(empty($brandName)){
            return ErrorCode::errorMissingParam("name");
        }
        CarBrand::create(['name'=>$brandName,'sort'=>$brandName]);
        return $this->getCarBrand();
    }

    public function addCarCategories()
    {
        $name = Input::get('name',null);
        $desc = Input::get('desc','');
        $priority = Input::get('priority',null);

        if(is_null($priority)){
            $priority = CarCategory::max("priority")+1;
        }
        if(is_null($name)){
            return ErrorCode::errorMissingParam("name");
        }

        if(!is_numeric($priority)||$priority<0){
            return ErrorCode::errorParam("priority");
        }

        CarCategory::create([
            "name"=>$name,
            "description"=>$desc,
            "priority"=>$priority
        ]);
        return $this->getCarCategories();
    }

    public function editCarCategories($id)
    {
        $name = Input::get('name',null);
        $desc = Input::get('desc',null);
        if(is_null($name) && is_null($desc)){
            return ErrorCode::errorMissingParam("name");
        }

        $category = CarCategory::where("id",$id)->first();
        if(empty($category)){
            return ErrorCode::errorParam("id");
        }
        if(!is_null($name)){
            $category->name = $name;
        }
        if(!is_null($desc)){
            $category->description = $desc;
        }
        $category->save();
        return $this->getCarCategories();
    }

    public function editCarBrand($id)
    {
        $brandName = Input::get("name",null);
        if(is_null($brandName)){
            return ErrorCode::errorMissingParam("name");
        }
        $brand = CarBrand::where("id",$id)->first();
        if(empty($brand)){
            return ErrorCode::errorParam('id');
        }
        $brand->name=$brandName;
        $brand->sort=$brandName;
        $brand->save();
        return $this->getCarBrand();
    }

    public function updateCarModel($modelId)
    {
        $carModel = CarModel::where('id',$modelId)->first();
        if (empty($carModel)){
            return ErrorCode::errorNotExist('car model');
        }
        $maxBags = Input::get('max_bags',null);
        $maxSeats = Input::get('max_seats',null);
        $modelName = Input::get('model_name',null);
        $brandId = Input::get('brand_id',null);
        $categoryId = Input::get('category_id',null);

        if(is_null($maxBags)&&is_null($maxSeats)&&is_null($modelName)
            &&is_null($brandId)&&is_null($categoryId)){
            return ErrorCode::errorMissingParam();
        }
        if(!is_null($maxBags)){
            if(!is_numeric($maxBags) || $maxBags<0){
                return ErrorCode::errorParam('max bags');
            }else{
                $carModel->bags_max = $maxBags;
            }
        }
        if(!is_null($maxSeats)){
            if(!is_numeric($maxSeats) || $maxSeats<0){
                return ErrorCode::errorParam('max bags');
            }else{
                $carModel->seats_max = $maxSeats;
            }
        }
        if(!is_null($brandId)){
            $brand = CarBrand::where('id',$brandId)->first();
            if(empty($brand)){
                return ErrorCode::errorParam('brand id');
            }else{
                $carModel->car_brand_id = $brandId;
            }
        }
        if(!is_null($categoryId)){
            $category = CarCategory::where('id',$categoryId)->first();
            if(empty($category)){
                return ErrorCode::errorParam('category id');
            }else{
                $carModel->car_category_id = $categoryId;
            }
        }

        if(!is_null($modelName)&& !empty($modelName)){
            $carModel->name = $modelName;
            $carModel->sort = $modelName;
        }

        $carModel->save();
        return ErrorCode::success('success');
    }


    public function addNewCarModel()
    {
        $maxBags = Input::get('max_bags',null);
        $maxSeats = Input::get('max_seats',null);
        $modelName = Input::get('model_name',null);
        $brandId = Input::get('brand_id',null);
        $categoryId = Input::get('category_id',null);
        if(is_null($maxBags)||
           is_null($maxSeats)||
           is_null($modelName)||
           is_null($brandId)||
           is_null($categoryId)
        ){
            return ErrorCode::errorMissingParam();
        }

        if(!is_numeric($maxBags)|| $maxBags<0){
            return ErrorCode::errorParam('max bags');
        }
        if(!is_numeric($maxSeats)||$maxSeats<0){
            return ErrorCode::errorParam('max seat');
        }
        if(empty($modelName)){
            return ErrorCode::errorParam('model name');
        }
        $carBrand = CarBrand::where('id',$brandId)->first();
        if(empty($carBrand)){
            return ErrorCode::errorNotExist('car brand');
        }
        $carCategory= CarCategory::where('id',$categoryId)->first();
        if(empty($carCategory)){
            return ErrorCode::errorNotExist('car category');
        }
        $carModel = CarModel::create([
            "car_brand_id"=>$brandId,
            "car_category_id"=>$categoryId,
            "name"=>$modelName,
            "sort"=>$modelName,
            "seats_max"=>$maxSeats,
            "bags_max"=>$maxBags
        ]);

        return ErrorCode::success($carModel);
    }
}