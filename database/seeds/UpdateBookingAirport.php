<?php

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateBookingAirport extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $bookings = \App\Model\Booking::select('id','offer_data')->get();
        foreach ($bookings as $booking) {
            $offer = json_decode($booking->offer_data,true);
            $d_is_airport = ((!is_null($offer))&&(isset($offer['d_is_airport'])))?isset($offer['d_is_airport']):0;
            $a_is_airport = ((!is_null($offer))&&(isset($offer['a_is_airport'])))?isset($offer['a_is_airport']):0;
            \App\Model\Booking::where('id',$booking->id)->update([
                'd_is_airport' => $d_is_airport,
                'a_is_airport' => $a_is_airport
            ]);
        }
    }
}
