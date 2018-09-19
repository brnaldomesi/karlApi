<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //mark
        $this->call(UpdateCarsBagsAndSeats::class);
        //mark
        $this->call(UpdateCarsRating::class);
        //mark
        $this->call(UpdateDriversRating::class);
        //mark
        $this->call(UpdateCompanySetting::class);
        //mark
        $this->call(UpdateBookingsCompanyId::class);
        //mark
        $this->call(InsertCompanyAnnex::class);
        //mark
        $this->call(UpdateBookingsCustomerData::class);
        //mark
        $this->call(UpdateBookingsDriverDataAddEmail::class);
        //mark
        $this->call(UpdateDBCalenderEventRepeat::class);
        //mark
        $this->call(UpdateCarPreTime::class);
        //mark
        $this->call(UpdateCompanyEmailPassword::class);
        //mark
        $this->call(InsertCompanyTimezone::class);
        //mark
        $this->call(InsertCompanyCalendarTimezone::class);

//        $this->call(InsertCompanyStatData::class);
//        $this->call(InsertCompanyBookingDayStatData::class);

        //mark
        $this->call(InsertOfferPrices::class);
        //MARK STAT
        $this->call(UpdatedCompanyBookingDayStatData::class);
        //mark
        $this->call(InsertCompanySetting::class);
        //mark
        $this->call(UpdateRateTransfer::class);
        //mark
        $this->call(UpdateBookingAirport::class);
        //mark
        $this->call(UpdateOfferCheckType::class);
        //mark
        $this->call(UpdateCreditCardInfo::class);
        //mark
        $this->call(UpdateCompanyAnnex::class);
        //mark
        $this->call(InsertDriverAnnex::class);
        //mark
        $this->call(UpdateSaleId::class);
        //mark
        $this->call(UpdateCustomerBookingStatics::class);
        //mark
        $this->call(UpdateRateUnit::class);
        //mark
        $this->call(StripeConnectUpdate::class);
        //mark
        $this->call(UpdateCompanyCountry::class);
        //mark
        $this->call(ResetCreditCardInfo::class);
        //mark
        $this->call(CreateC2CMatch::class);
        //mark
        $this->call(UpdateCustomerMCData::class);
        //mark
        $this->call(UpdateCompaniesDst::class);
        //mark
        $this->call(UpdateRateLongDistance::class);
    }
}
