<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModel extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    public function handle(){
        $model = $this->argument('name');
        if(file_exists("app/Model/{$model}.php")){
            throw new \Exception("The Model {$model} is already exist.");
        }
        $time = new \DateTime();
        $string = "<?php 
namespace App\\Model;
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: {$time->format('Y/m/d')}
 * Time: {$time->format('H:i')}
 */
use Illuminate\\Database\\Eloquent\\Model;

class {$model} extends Model{

        protected \$fillable = [
        
        ];
        
        protected \$hidden=[
        
        ];

}
?>       ";
        File::put("app/Model/{$model}.php",$string);

    }
}
