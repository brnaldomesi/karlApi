<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Model\Offer;
use App\Model\OfferOption;
use App\Model\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class OptionsController extends Controller
{
    public function getAllOptions()
    {
        $count = Option::all()->count();

        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);

        $options = Option::join('companies', 'options.company_id', "=", "companies.id")
            ->select('options.*', 'companies.name as company_name')
            ->skip($skip)
            ->take($per_page)
            ->get();
        if (empty($options)) {
            return ErrorCode::successEmptyResult("option is empty ");
        }
        foreach ($options as $op) {
            if ($op->type == 'GROUP') {
                $ops = Option::where('parent_id', $op->id)->get();
                $op->group = $ops;
            }
        }
        $result = ['total' => $count, 'options' => $options];
        return ErrorCode::success($result);
    }

    public function getCompaniesOptions(Request $request)
    {
        $company_id = $request->user->company_id;
        $options = Option::wherein('company_id', [$company_id, 0])
            ->where('parent_id', 0)
            ->orderBy('company_id', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        if (empty($options)) {
            return ErrorCode::successEmptyResult("option is empty ");
        }
        foreach ($options as $op) {
            if ($op->type == 'GROUP') {
                $ops = Option::where('parent_id', $op->id)->get();
                $op->group = $ops;
            }
        }
        return ErrorCode::success($options);
    }

    //新增option 支持以组的方式上传
    public function addOptions()
    {
        $tempParam = Input::get('param', null);
        if (is_null($tempParam)) {
            return ErrorCode::errorMissingParam();
        }
        $param = json_decode($tempParam, true);
        try {
            return ErrorCode::success($this->insertOptions(0, $param));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyAddOptions(Request $request)
    {
        $company_id = $request->user->company_id;
        $tempParam = Input::get('param', null);
        if (is_null($tempParam)) {
            return ErrorCode::errorMissingParam();
        }
        $param = json_decode($tempParam, true);
        try {
            return ErrorCode::success($this->insertOptions($company_id, $param));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function insertOptions($company_id, $param)
    {
        $result = DB::transaction(function () use ($company_id, $param) {
            if (sizeof($param) == 0) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }
            $tempOptions = array();
            foreach ($param as $option) {
                if (strtolower($option['type']) == strtolower('GROUP')) {
                    $groups = $option['group'];
                    if (count($groups) == 0) {
                        throw new \Exception(ErrorCode::errorParam("option group is empty "));
                    }
                    $tempOption = $this->checkAndInsert(0, $option, $company_id);
                    $tempGroupOptions = array();
                    foreach ($groups as $group) {
                        $groupOption = $this->checkAndInsert($tempOption->id, $group, $company_id);
                        array_push($tempGroupOptions, $groupOption);
                    }
                    $tempOption->group = $tempGroupOptions;
                }else{
                    $tempOption = $this->checkAndInsert(0, $option, $company_id);
                }
                array_push($tempOptions, $tempOption);
            }
            return $tempOptions;
        });
        return $result;
    }

    private function checkAndInsert($parent_id, $option, $company_id)
    {
        $title = isset($option['title']) ? $option['title'] : null;
        $description = isset($option['desc']) ? $option['desc'] : '';
        $price = isset($option['price']) ? $option['price'] : null;
        $add_max = isset($option['add_max']) ? $option['add_max'] : null;
        $type = isset($option['type']) ? $option['type'] : null;

        if (is_null($title) ||
            is_null($price) ||
            is_null($add_max) ||
            is_null($type)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }
        if (empty($title)) {
            throw new \Exception(ErrorCode::errorParam(' title '));
        }

        if (!is_numeric($price) || $price < 0) {
            throw new \Exception(ErrorCode::errorParam('Error price format'));
        }

        if (strtolower($type) != strtolower('GROUP') &&
            strtolower($type) != strtolower('NUMBER') &&
            strtolower($type) != strtolower('CHECKBOX') &&
            strtolower($type) != strtolower('RADIO')
        ) {
            throw new \Exception(ErrorCode::errorParam("unknown option type " . $type));
        }

        if(strtolower($type) == strtolower('GROUP')){
            $add_max = 0;
        }elseif(strtolower($type) == strtolower('RADIO') ||
            strtolower($type) == strtolower('CHECKBOX')){
            $add_max = 1;
        }else{
            if (!is_numeric($add_max) || $add_max < 1  || $add_max > 100) {
                throw new \Exception(ErrorCode::errorParam('Error add_max format'));
            }
        }



        $create = ['company_id' => $company_id, 'title' => $title,
            'description' => $description, 'price' => $price,
            'type' => $type, 'parent_id' => $parent_id,
            'add_max' => $add_max
        ];
        $tempOption = Option::create($create);
        if (empty($tempOption)) {
            throw new \Exception(ErrorCode::errorDB());
        } else {
            return $tempOption;
        }
    }

    //修改option 不支持修改类型
    public function updateOption($option_id)
    {
        $option = Option::where('options.id', $option_id)->first();
        if (empty($option)) {
            return ErrorCode::errorNotExist('option');
        }

        $param = Input::get("param", null);
        if (is_null($param)) {
            return ErrorCode::errorMissingParam();
        }

        $param = json_decode($param, true);
        try {
            return ErrorCode::success($this->updateOptionDB($option->company_id, $param));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyUpdateOption(Request $request, $option_id)
    {
        $company_id = $request->user->company_id;
        $option = Option::where('options.id', $option_id)->first();
        if (empty($option)) {
            return ErrorCode::errorNotExist('option');
        }
        if ($option->company_id != $company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        $param = Input::get("param", null);

        if (is_null($param)) {
            return ErrorCode::errorMissingParam();
        }
        $param = json_decode($param, true);

        try {
            return ErrorCode::success($this->updateOptionDB($option_id, $param));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function updateOptionDB($option_id, $param)
    {
        $result = DB::transaction(function () use ($option_id, $param) {
            $option = $this->checkAndUpdate($option_id, $param);
            if (strtolower($option->type) == strtolower('GROUP')) {
                Option::where('options.parent_id', $option_id)->delete();
                $groupOptions = $param['group'];
                if (empty($groupOptions) || !is_array($groupOptions) || sizeof($groupOptions) == 0) {
                    throw new \Exception(ErrorCode::errorParam(' group '));
                }
                $tempGroupOptions = array();
                foreach ($groupOptions as $groupOption) {
                    $groupOption = $this->checkAndInsert($option->id, $groupOption, $option->company_id);
                    array_push($tempGroupOptions, $groupOption);
                }
                $option->group = $tempGroupOptions;
            }
            return $option;
        });
        return $result;
    }

    private function checkAndUpdate($option_id, $updateOption)
    {
        $title = isset($updateOption['title']) ? $updateOption['title'] : null;
        $description = isset($updateOption['desc']) ? $updateOption['desc'] : null;
        $c_id = isset($updateOption['company_id']) ? $updateOption['company_id'] : null;
        $p_id = isset($updateOption['parent_id']) ? $updateOption['parent_id'] : null;
        $price = isset($updateOption['price']) ? $updateOption['price'] : null;
        $add_max = isset($updateOption['add_max']) ? $updateOption['add_max'] : null;
        $type = isset($updateOption['type']) ? $updateOption['type'] : null;

        if (is_null($title) ||
            is_null($price) || is_null($p_id) ||
            is_null($add_max) || is_null($type) ||
            is_null($c_id)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }

        if (empty($title)) {
            throw new \Exception(ErrorCode::errorParam('title'));
        }

        if (!is_numeric($price) || $price < 0) {
            throw new \Exception(ErrorCode::errorParam('Error price format'));
        }

        if (strtolower($type) != strtolower('GROUP') &&
            strtolower($type) != strtolower('NUMBER') &&
            strtolower($type) != strtolower('CHECKBOX') &&
            strtolower($type) != strtolower('RADIO')
        ) {
            throw new \Exception(ErrorCode::errorParam("unknown option type " . $type));
        }

        if(strtolower($type) == strtolower('GROUP')){
            $add_max = 0;
        }elseif(strtolower($type) == strtolower('RADIO') ||
            strtolower($type) == strtolower('CHECKBOX')){
            $add_max = 1;
        }else{
            if (!is_numeric($add_max) || $add_max < 1) {
                throw new \Exception(ErrorCode::errorParam('Error add_max format'));
            }
        }

        $option = Option::where('id', $option_id)->first();
        if (empty($option)) {
            throw new \Exception(ErrorCode::errorNotExist('option'));
        }
        if (strtolower($option->type) != strtolower($type)) {
            throw new \Exception(ErrorCode::errorOptionTypeChanged());
        }
        if ($option->parent_id != $p_id) {
            throw new \Exception(ErrorCode::errorOptionParentIdChanged());
        }
        if ($option->company_id != $c_id) {
            throw new \Exception(ErrorCode::errorOptionCompanyIdChanged());
        }


        $option->title = $title;
        $option->add_max = $add_max;
        $option->description = $description;
        $option->price = $price;

        if ($option->save()) {
            return $option;
        } else {
            throw new \Exception(ErrorCode::errorDB());
        }
    }

    //删除option 匹配其他表查询

    public function deleteOption($option_id)
    {
        $result = DB::transaction(function () use ($option_id) {
            //1.先判断,id对应的option是否存在
            $option = Option::where('id', $option_id)->first();
            if (empty($option)) {
                throw new \Exception(ErrorCode::errorNotExist('option'));
            }
            $type = $option->type;
            //1.1 判断是否删除成功
            $delResult = $option->delete();
            if (is_null($delResult) || !$delResult) {
                throw new \Exception(ErrorCode::errorDeleteDB('option'));
            }

            //2.判断是否有子项
            if (strtolower($type) == strtolower($type)) {
                $childOptions = Option::where('parent_id', $option_id)->get();
                if (!is_array($childOptions) || empty($childOptions)) {
                    throw new \Exception(ErrorCode::errorNotExist('option item'));
                }
                $childOptions = Option::where('parent_id', $option_id)->delete();
            }

            //3.删除引用option的offer
            $offerOptions = OfferOption::where('option_id', $option_id)->get();
            if (!empty($offerOptions)) {
                OfferOption::where('option_id', $option_id)->delete();
            }
            return null;
        });
        if (is_null($result)) {
            return ErrorCode::success('success');
        } else {
            return ErrorCode::errorDB();
        }
    }

    public function companyDeleteOption(Request $request, $option_id)
    {
        $company_id = $request->user->company_id;
        try {
            DB::transaction(function () use ($option_id, $company_id) {
                //1.先判断,id对应的option是否存在
                $option = Option::where('id', $option_id)->where('company_id', $company_id)->first();
                if (empty($option)) {
                    throw new \Exception(ErrorCode::errorNotExist('option'));
                }
                $type = $option->type;
                //1.1 判断是否删除成功
                $delResult = $option->delete();
                if (is_null($delResult) || !$delResult) {
                    throw new \Exception(ErrorCode::errorDeleteDB('option'));
                }

                //2.判断是否有子项
                if (strtolower($type) == strtolower($type)) {
                    $childOptions = Option::where('parent_id', $option_id)->where('company_id', $company_id)->get();
                    if (empty($childOptions)) {
                        throw new \Exception(ErrorCode::errorNotExist('option item'));
                    }
                    $childOptions = Option::where('parent_id', $option_id)->where('company_id', $company_id)->delete();
                }

                //3.删除引用option的offer,此处逻辑判断稍显复杂,
                //先判断有没有对应option_id的offer_option,
                //在判断对应的offer_id是否在company_id对应的公司下,
                $offerOptions = OfferOption::where('option_id', $option_id)->get();
                if (!is_null($offerOptions) || !empty($offerOptions)) {
                    foreach ($offerOptions as $offerOption) {
                        $offer = Offer::where('id', $offerOption->offer_id)->where('company_id', $company_id)->first();
                        if (is_null($offer) || empty($offer)) {
                            continue;
                        }
                        OfferOption::where('option_id', $option_id)->where('offer_id', $offer->id)->delete();
                    }
                }
                return null;
            });
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


}
