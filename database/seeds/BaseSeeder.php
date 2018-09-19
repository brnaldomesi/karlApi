<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
abstract class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    /**
     * @var bool 只运行一次的标记
     */
    protected $onlyRunner = true;

    /**
     * @var string 类名
     */
    private $className;

    public function run()
    {
        $this->className = get_called_class();
        $result = $this->seedCheck();
        if($result && $this->onlyRunner){
            echo $this->className." has be run. \n";
            return;
        }
        $this->task();
        $this->afterSeed();
    }

    protected function seedCheck()
    {
        $seed = DB::select('select * from seeders WHERE seed_name =?',[$this->className]);
        return count($seed)>0;
    }
    abstract protected function task();

    protected function afterSeed(){
        if(!$this->onlyRunner){
            return;
        }
        echo $this->className." has be add to db. \n";
        DB::insert(
            "insert into seeders(seed_name,created_at) VALUE (?,now())",
            [$this->className,]);

    }
}
