<?php

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $result = $this->changeStatu(4);
        $this->assertEquals(3007,$result['code'],"error statue code : 3007 ");
        $result = $this->changeStatu(5);
        $this->assertEquals(7001,$result['code'],"error statue code : change 1 to 2");
        $result = $this->changeStatu(6);
        $this->assertEquals(7001,$result['code'],"error statue code ");
        $result = $this->changeStatu(7);
        $this->assertEquals(7001,$result['code'],"error statue code ");
        $result = $this->changeStatu(1);
        $this->assertEquals(2000,$result['code'],"error statue code when change status from 0 to 1");
    }


    private function changeStatu($staues){
        $this->patch("1/drivers/order/state",["booking_id"=>1,"statue"=>$staues,"token"=>"8e9e17b6-264b-31dd-96a8-ad9e52ac2921"]);
        $result = $this->response->getContent();
        var_dump($result);
        return json_decode($result,true);
    }

}
