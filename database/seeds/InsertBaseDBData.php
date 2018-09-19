<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class InsertBaseDBData extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $user = \App\Model\User::create([
            "company_id" => 0,
            "first_name" => "super",
            "last_name" => "admin",
            "username" => "superadmin",
            "mobile" => "",
            "email" => "",
            "password" => md5("123456"),
        ]);

        \App\Model\Superadmin::create([
            "user_id" => $user->id
        ]);
        DB::table("car_categories")->insert([
            ["id" => 100,
                "name" => "Alternative Fuels",
                "description" => "Alternative Fuels",
                "priority" => 100],
            ["id" => 101,
                "name" => "Coupes",
                "description" => "Coupes",
                "priority" => 101],
            ["id" => 102,
                "name" => "Convertibles",
                "description" => "Convertibles",
                "priority" => 102],
            ["id" => 103,
                "name" => "Sedans",
                "description" => "Sedans",
                "priority" => 103],
            ["id" => 104,
                "name" => "SUVs",
                "description" => "SUVs",
                "priority" => 104],
            ["id" => 105,
                "name" => "Trucks",
                "description" => "Trucks",
                "priority" => 105],
            ["id" => 106,
                "name" => "Vans",
                "description" => "Vans",
                "priority" => 106],
            ["id" => 107,
                "name" => "Wagons",
                "description" => "Wagons",
                "priority" => 107],
            ["id" => 108,
                "name" => "Compacts",
                "description" => "Compacts",
                "priority" => 108],
            ["id" => 109,
                "name" => "Luxury",
                "description" => "Luxury",
                "priority" => 109],
            ["id" => 110,
                "name" => "Sport",
                "description" => "Sport",
                "priority" => 110],
            ["id" => 111,
                "name" => "Comfort",
                "description" => "Comfort",
                "priority" => 111],
            ["id" => 112,
                "name" => "Business",
                "description" => "Business",
                "priority" => 112],
            ["id" => 113,
                "name" => "Premium",
                "description" => "Premium",
                "priority" => 113],
            ["id" => 114,
                "name" => "Platinum",
                "description" => "Platinum",
                "priority" => 114],
            ["id" => 115,
                "name" => "Stretch",
                "description" => "Stretch",
                "priority" => 115]
        ]);
        DB::table("car_brands")->insert([
            ["id" => 102, "name" => "Audi", "sort" => "Audi"],
            ["id" => 103, "name" => "BMW", "sort" => "BMW"],
            ["id" => 104, "name" => "Benz", "sort" => "Benz"],
            ["id" => 105, "name" => "Lincoln", "sort" => "Lincoln"],
            ["id" => 106, "name" => "Hummer", "sort" => "Hummer"],
            ["id" => 107, "name" => "Cadillac", "sort" => "Cadillac"],
            ["id" => 108, "name" => "Maserati", "sort" => "Maserati"],
            ["id" => 109, "name" => "Bentley", "sort" => "Bentley"],
            ["id" => 110, "name" => "Rolls-Royce", "sort" => "Rolls-Royce"],
            ["id" => 111, "name" => "Chevy", "sort" => "Chevy"],
            ["id" => 112, "name" => "Tesla", "sort" => "Tesla"],
            ["id" => 113, "name" => "Chrysler", "sort" => "Chrysler"],
            ["id" => 114, "name" => "BUICK", "sort" => "BUICK"],
            ["id" => 115, "name" => "Dodge", "sort" => "Dodge"],
            ["id" => 116, "name" => "Ford", "sort" => "Ford"],
            ["id" => 117, "name" => "GMC", "sort" => "GMC"],

        ]);
        DB::table("car_models")->insert([
            ["id" => 115,
                "car_brand_id" => 102,
                "car_category_id" => 107,
                "name" => "A4 Wagon 2015 35T",
                "seats_max" => 5,
                "bags_max" => 3,
                "sort" => "A4"],
            ["id" => 116,
                "car_brand_id" => 103,
                "car_category_id" => 107,
                "name" => "3 Wagon",
                "seats_max" => 5,
                "bags_max" => 3,
                "sort" => "3"],
            ["id" => 117,
                "car_brand_id" => 102,
                "car_category_id" => 108,
                "name" => "A3",
                "seats_max" => 5,
                "bags_max" => 2,
                "sort" => "A3"],
            ["id" => 118,
                "car_brand_id" => 104,
                "car_category_id" => 109,
                "name" => "S500",
                "seats_max" => 4,
                "bags_max" => 2,
                "sort" => "S500"],
            ["id" => 119,
                "car_brand_id" => 104,
                "car_category_id" => 104,
                "name" => "ML",
                "seats_max" => 5,
                "bags_max" => 4,
                "sort" => "ML"],
            ["id" => 120,
                "car_brand_id" => 104,
                "car_category_id" => 109,
                "name" => "S600 Maybach",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "S600"],
            ["id" => 121,
                "car_brand_id" => 104,
                "car_category_id" => 106,
                "name" => "Sprinter",
                "seats_max" => 10,
                "bags_max" => 6,
                "sort" => "Sprinter"],
            [
                "id" => 122,
                "car_brand_id" => 104,
                "car_category_id" => 103,
                "name" => "E-Class",
                "seats_max" => 4,
                "bags_max" => 4,
                "sort" => "E-Class"
            ],
            [
                "id" => 123,
                "car_brand_id" => 102,
                "car_category_id" => 111,
                "name" => "A4",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "A4"
            ],
            [
                "id" => 124,
                "car_brand_id" => 102,
                "car_category_id" => 112,
                "name" => "A6",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "A6"
            ],
            [
                "id" => 125,
                "car_brand_id" => 102,
                "car_category_id" => 113,
                "name" => "A7",
                "seats_max" => 4,
                "bags_max" => 4,
                "sort" => "A7"
            ],
            [
                "id" => 126,
                "car_brand_id" => 102,
                "car_category_id" => 109,
                "name" => "A8",
                "seats_max" => 4,
                "bags_max" => 4,
                "sort" => "A8"
            ],
            [
                "id" => 127,
                "car_brand_id" => 102,
                "car_category_id" => 104,
                "name" => "Q5",
                "seats_max" => 5,
                "bags_max" => 5,
                "sort" => "Q5"
            ],
            [
                "id" => 128,
                "car_brand_id" => 102,
                "car_category_id" => 104,
                "name" => "Q7",
                "seats_max" => 6,
                "bags_max" => 5,
                "sort" => "Q7"
            ],
            [
                "id" => 129,
                "car_brand_id" => 105,
                "car_category_id" => 115,
                "name" => "Towncar Stretch",
                "seats_max" => 10,
                "bags_max" => 4,
                "sort" => "Towncar Stretch"
            ],
            [
                "id" => 130,
                "car_brand_id" => 105,
                "car_category_id" => 112,
                "name" => "Towncar",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "Towncar"
            ],
            [
                "id" => 131,
                "car_brand_id" => 105,
                "car_category_id" => 104,
                "name" => "Navigator",
                "seats_max" => 6,
                "bags_max" => 4,
                "sort" => "Navigator"
            ],
            [
                "id" => 132,
                "car_brand_id" => 105,
                "car_category_id" => 115,
                "name" => "Navigator Stretch	",
                "seats_max" => 14,
                "bags_max" => 5,
                "sort" => "Navigator Stretch"
            ],
            [
                "id" => 133,
                "car_brand_id" => 106,
                "car_category_id" => 104,
                "name" => "H2",
                "seats_max" => 5,
                "bags_max" => 4,
                "sort" => "H2"
            ],
            [
                "id" => 134,
                "car_brand_id" => 106,
                "car_category_id" => 104,
                "name" => "h3",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "h3"
            ],
            [
                "id" => 135,
                "car_brand_id" => 106,
                "car_category_id" => 115,
                "name" => "H2 Stretch",
                "seats_max" => 5,
                "bags_max" => 14,
                "sort" => "H2 Stretch"
            ],
            [
                "id" => 136,
                "car_brand_id" => 107,
                "car_category_id" => 112,
                "name" => "CTS",
                "seats_max" => 4,
                "bags_max" => 2,
                "sort" => "CTS"
            ],
            [
                "id" => 137,
                "car_brand_id" => 107,
                "car_category_id" => 109,
                "name" => "XTS",
                "seats_max" => 4,
                "bags_max" => 2,
                "sort" => "XTS"
            ],
            [
                "id" => 138,
                "car_brand_id" => 107,
                "car_category_id" => 104,
                "name" => "Escalade",
                "seats_max" => 6,
                "bags_max" => 4,
                "sort" => "Escalade"
            ],
            [
                "id" => 139,
                "car_brand_id" => 107,
                "car_category_id" => 115,
                "name" => "Escalade Stretch",
                "seats_max" => 12,
                "bags_max" => 5,
                "sort" => "Escalade Stretch"
            ],
            [
                "id" => 140,
                "car_brand_id" => 107,
                "car_category_id" => 115,
                "name" => "XTS Stretch",
                "seats_max" => 8,
                "bags_max" => 4,
                "sort" => "XTS Stretch"
            ],
            [
                "id" => 141,
                "car_brand_id" => 108,
                "car_category_id" => 112,
                "name" => "Ghibli",
                "seats_max" => 3,
                "bags_max" => 3,
                "sort" => "Ghibli"
            ],
            [
                "id" => 142,
                "car_brand_id" => 108,
                "car_category_id" => 109,
                "name" => "Quattroporte",
                "seats_max" => 3,
                "bags_max" => 3,
                "sort" => "Quattroporte"
            ],
            [
                "id" => 143,
                "car_brand_id" => 109,
                "car_category_id" => 114,
                "name" => "Flying Spur",
                "seats_max" => 3,
                "bags_max" => 2,
                "sort" => "Flying Spur"
            ],
            [
                "id" => 144,
                "car_brand_id" => 109,
                "car_category_id" => 114,
                "name" => "Mulsanne",
                "seats_max" => 3,
                "bags_max" => 2,
                "sort" => "Mulsanne"
            ],
            [
                "id" => 145,
                "car_brand_id" => 110,
                "car_category_id" => 114,
                "name" => "Ghost",
                "seats_max" => 3,
                "bags_max" => 3,
                "sort" => "Ghost"
            ],
            [
                "id" => 146,
                "car_brand_id" => 110,
                "car_category_id" => 114,
                "name" => "Phantom",
                "seats_max" => 3,
                "bags_max" => 3,
                "sort" => "Phantom"
            ],
            [
                "id" => 147,
                "car_brand_id" => 103,
                "car_category_id" => 111,
                "name" => "3Series",
                "seats_max" => 3,
                "bags_max" => 2,
                "sort" => "3Series"
            ],
            [
                "id" => 148,
                "car_brand_id" => 103,
                "car_category_id" => 112,
                "name" => "5Series",
                "seats_max" => 4,
                "bags_max" => 3,
                "sort" => "5Series"
            ],
            [
                "id" => 149,
                "car_brand_id" => 103,
                "car_category_id" => 113,
                "name" => "5GT",
                "seats_max" => 4,
                "bags_max" => 4,
                "sort" => "5GT"
            ],
            [
                "id" => 150,
                "car_brand_id" => 103,
                "car_category_id" => 109,
                "name" => "7Series",
                "seats_max" => 4,
                "bags_max" => 4,
                "sort" => "7Series"
            ],
            [
                "id" => 151,
                "car_brand_id" => 103,
                "car_category_id" => 104,
                "name" => "X5",
                "seats_max" =>4,
                "bags_max"=>4,
                "sort"=>"X5"
            ],
            [
                "id" => 152,
                "car_brand_id" => 103,
                "car_category_id" => 104,
                "name" => "X7",
                "seats_max" =>5,
                "bags_max"=>4,
                "sort"=>"X7"
            ],
            [
                "id" => 153,
                "car_brand_id" => 111,
                "car_category_id" => 111,
                "name" => "Malibu",
                "seats_max" =>3,
                "bags_max"=>3,
                "sort"=>"Malibu"
            ],
            [
                "id" => 154,
                "car_brand_id" => 111,
                "car_category_id" => 112,
                "name" => "Impala",
                "seats_max" => 3,
                "bags_max"=>3,
                "sort"=>"Impala"
            ],
            [
                "id" => 155,
                "car_brand_id" => 111,
                "car_category_id" => 104,
                "name" => "Tahoe",
                "seats_max" => 5,
                "bags_max"=>5,
                "sort"=>"Tahoe"
            ],
            [
                "id" => 156,
                "car_brand_id" => 111,
                "car_category_id" => 104,
                "name" => "Suburban",
                "seats_max" => 6,
                "bags_max"=>5,
                "sort"=>"Subarban"
            ],
            [
                "id" => 157,
                "car_brand_id" => 112,
                "car_category_id" => 113,
                "name" => "Model S",
                "seats_max" => 3,
                "bags_max"=>2,
                "sort"=>"Model S"
            ],
            [
                "id" => 158,
                "car_brand_id" => 113,
                "car_category_id" => 112,
                "name" => "300",
                "seats_max" => 3,
                "bags_max"=>3,
                "sort"=>"300"
            ],
            [
                "id" => 159,
                "car_brand_id" => 113,
                "car_category_id" => 115,
                "name" => "300 Stretch"
                , "seats_max" => 10,
                "bags_max"=>4,
                "sort"=>"300 Stretch"
            ],
            [
                "id" => 160,
                "car_brand_id" => 114,
                "car_category_id" => 104,
                "name" => "Enclave",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Enclave"
            ],
            [
                "id" => 161,
                "car_brand_id" => 115,
                "car_category_id" => 111,
                "name" => "Charger",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Charger"
            ],
            [
                "id" => 162,
                "car_brand_id" => 115,
                "car_category_id" => 104,
                "name" => "Durango",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Durango"
            ],
            [
                "id" => 163,
                "car_brand_id" => 116,
                "car_category_id" => 111,
                "name" => "Fusion",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Fusion"
            ],
            [
                "id" => 164,
                "car_brand_id" => 116,
                "car_category_id" => 104,
                "name" => "Expedition",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Expedition"
            ],
            [
                "id" => 165,
                "car_brand_id" => 117,
                "car_category_id" => 104,
                "name" => "Denali",
                "seats_max" => 2,
                "bags_max"=>2,
                "sort"=>"Denali"
            ]
        ]);
        DB::table("permissions")->insert([
            ["id"=>100,
                "name"=>"home tab",
                "description"=>"home tab"],
            ["id"=>101,
                "name"=>"setting tab",
                "description"=>"setting tab"],
            ["id"=>102,
                "name"=>"easybook tab",
                "description"=>"easybook tab"],
            ["id"=>103,
                "name"=>"calendar tab",
                "description"=>"calendar tab"],
            ["id"=>104,
                "name"=>"vehicles tab",
                "description"=>"vehicles tab"],
            ["id"=>105,
                "name"=>"drivers tab",
                "description"=>"drivers tab"],
            ["id"=>106,
                "name"=>"rates tab",
                "description"=>"rates tab"],
            ["id"=>107,
                "name"=>"stats tab",
                "description"=>"stats tab"],
            ["id"=>108,
                "name"=>"profile tab",
                "description"=>"profile tab"],
            ["id"=>109,
                "name"=>"clients tab",
                "description"=>"clients tab"],
            ["id"=>110,
                "name"=>"option tab",
                "description"=>"option tab"],

        ]);
    }
}
