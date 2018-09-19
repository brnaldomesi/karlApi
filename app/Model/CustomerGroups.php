<?php
namespace App\Model;
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/05/10
 * Time: 02:31
 */
use App\ErrorCode;
use Illuminate\Database\Eloquent\Model;

class CustomerGroups extends Model
{
    const SORT_RIDES_COUNT =1;
    const SORT_COST_TOTAL =2;
    protected $fillable = [
        'id',
        'name',
        "type",
        "company_id",
        "section_start",
        "section_end",
        "priority",
        "outer_id",
        "bind_id"
    ];

    protected $hidden = [
        "created_at","updated_at"
    ];


    public static function checkAndCreateGroups($groups,$bindId,$companyId)
    {
        if (count($groups) == 1) {
            $group = $groups[0];
            $group["bind_id"]=$bindId;
            $group["company_id"]=$companyId;
            CustomerGroups::create($group);
            return;
        }
        $priority = [];
        for ($i = 0; $i < count($groups); $i++) {
            $groups[$i]['bind_id']=$bindId;
            $groups[$i]['company_id']=$companyId;
            $group = $groups[$i];
            if (!isset($group['section_start'])
                || !isset($group['section_end'])
                || !isset($group['name'])
                || !isset($group['type'])
                || !isset($group['priority'])
                || !isset($group['outer_id'])) {
                throw new \Exception(ErrorCode::errorParam('groups'));
            }

            if (!is_numeric($group['section_start']) || $group['section_start'] < 0) {
                throw new \Exception(ErrorCode::errorParam('groups interval start'));
            }
            if (!is_numeric($group['section_end']) || $group['section_end'] < $group['section_start']) {
                throw new \Exception(ErrorCode::errorParam('groups interval end'));
            }

            if(!is_numeric($group['type']) ||
                ($group['type'] != CustomerGroups::SORT_RIDES_COUNT &&
                 $group['type'] != CustomerGroups::SORT_COST_TOTAL)){
                throw new \Exception(ErrorCode::errorParam('type'));
            }
            if (empty($group['outer_id'])) {
                throw new \Exception(ErrorCode::errorParam('groups'));
            }
            if (!is_numeric($group['priority']) || isset($priority[$group['priority']])){
                throw new \Exception(ErrorCode::errorParam('priority'));
            }
            $priority[$group['priority']] = $group;
        }
        foreach ($priority as $item) {
            CustomerGroups::create($item);
        }
    }
}

?>