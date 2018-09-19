<?php

namespace App\Console\Commands;

use App\Method\MethodAlgorithm;
use App\Model\CcyCvtRecord;
use Illuminate\Console\Command;

class FinanceConverter extends Command
{

    private $ccyArray=[
        [
            "usd","eur"
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccy:cvt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'finance converter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
       //
        $ccyArray = array();
        $time = MethodAlgorithm::formatTimestampToDate(time());
        foreach ($this->ccyArray as $item) {
            $first = $item[0];
            $second = $item[1];
            $one = $this->convertCurrency($first,$second);
            $two = $this->convertCurrency($second,$first);

            $temp1 = [
                "from"=>$first,
                "to"=>$second,
                'rate'=>$one,
                "created_at"=>$time,
                "updated_at"=>$time
            ];
            $temp2 = [
                "from"=>$second,
                "to"=>$first,
                'rate'=>$two,
                "created_at"=>$time,
                "updated_at"=>$time
            ];
            array_push($ccyArray,$temp1);
            array_push($ccyArray,$temp2);
        }
        CcyCvtRecord::insert($ccyArray);
    }

    private function convertCurrency($from,$to)
    {
        $data = file_get_contents("https://www.google.com/finance/converter?a=100&from=$from&to=$to");
        preg_match("/<span class=bld>(.*)<\/span>/",$data, $converted);
        $converted = preg_replace("/[^0-9.]/", "", $converted[1]);
        return number_format(round($converted, 3),2);
    }
    
}
