<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{{\Illuminate\Support\Facades\Lang::get("booking.customBookingTitle")}}</title>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#E0E1E2">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" align="center"
                   bgcolor="white"
                   style="max-width: 600px;table-layout: fixed;
                   font-family:Helvetica Neue, Helvetica, Arial,Arial Narrow,serif">
            <tr>
                <td width="50" style="min-width: 25px"></td>
                <td width="500">
                    <table align="center" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td height="100"></td>
                        </tr>
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td>
                                            <div style="width: 80px;height: 80px;margin: auto;background: url({{$info->company_logo}}) no-repeat scroll 50% 0;background-size: cover"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="color: #1B1C1C;font-size:20px;text-align: center;">
                                                <p>
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.dearPassenger",["passengerName"=>"Client"])}}
                                                    <br>{{\Illuminate\Support\Facades\Lang::get("booking.clientNotice")}}</p>
                                                <p style="width: 100%;color:#888;font-size: 12px;text-align: left">
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.customNotice")}}
                                                    <a style="color: #3C7DBF" href="tel:{{ $info->company_number }}">{{ $info->company_number }}</a>
                                                    &amp;
                                                    <a style="color: #3C7DBF"
                                                       href="mailto:{{ $info->company_email }}">{{ $info->company_email }}</a>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td height="50"></td>
                        </tr>
                        <tr>
                            <td bgcolor="#2D68BD">
                                <table cellpadding="0" cellspacing="0" border="0" style="font-size:12px;font-weight: 200">
                                    <tr>
                                        <td height="50">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="460"><span style="color: #f2f2f2;">{{\Illuminate\Support\Facades\Lang::get("booking.tripDetail")}}</span></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="1" width="100%" bgcolor="#264C81"></td>
                                    </tr>
                                    <tr>
                                        <td height="50">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="460">
                                                        <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.tripOn")}}</span>
                                                        <span style="color: #FEFFFF;font-weight: 300">{{ $info->appointed_at->getDate()}}</span>
                                                        <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.tripAt")}}</span>
                                                        <span style="color: #FEFFFF;font-weight: 300">{{ $info->appointed_at->getTime()}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="80" bgcolor="#264C81">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td width="20"></td>
                                                                <td width="300">
                                                                    <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.customerPaid")}}</span>
                                                                </td>
                                                                <td width="160" align="right">
                                                                    <span style="color: #FCFDFD;font-size: 26px">{{\App\Method\MethodAlgorithm::ccyCvt($info->ccy,$info->base_cost)}}</span>
                                                                </td>
                                                                <td width="20"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @if($info->coupon_off >0)
                                    <tr>
                                        <td height="80" bgcolor="#264C81">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td width="20"></td>
                                                                <td width="300">
                                                                    <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.customerPaidOff")}}</span>
                                                                </td>
                                                                <td width="160" align="right">
                                                                    <span style="color: #00ad78;font-size: 20px">-{{\App\Method\MethodAlgorithm::ccyCvt($info->ccy,$info->coupon_off)}}</span>
                                                                </td>
                                                                <td width="20"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="80" bgcolor="#264C81">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td width="20"></td>
                                                                <td width="300">
                                                                    <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.actualPaid")}}</span>
                                                                </td>
                                                                <td width="160" align="right">
                                                                    <span style="color: #FCFDFD;font-size: 26px">{{\App\Method\MethodAlgorithm::ccyCvt($info->ccy, $info->base_cost - round($info->coupon_off*(1+$info->tva/100),2))}}</span>
                                                                </td>
                                                                <td width="20"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td height="45">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="300"><span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.pickupAddress")}}</span></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="10" valign="top">
                                                        <img src="{{$_SERVER['local_url']}}/imgs/common/green">
                                                    </td>
                                                    <td width="10"></td>
                                                    <td valign="top">
                                                        <span style="color: #CDE3FF;">{{ $info->d_address}}</span>
                                                    </td>
                                                    <td width="20"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @if ($info->type == 1)
                                    <tr>
                                        <td height="45">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="300"><span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.dropOffAddress")}}</span></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="10" valign="top">
                                                        <img src="{{$_SERVER['local_url']}}/imgs/common/red">
                                                    </td>
                                                    <td width="10"></td>
                                                    <td valign="top">
                                                        <span style="color: #CDE3FF;">{{ $info->a_address}}</span>
                                                    </td>
                                                    <td width="20"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endif

                                    <tr>
                                        <td height="17"></td>
                                    </tr>
                                    <tr>
                                        <td height="1" width="100%" bgcolor="#264C81"></td>
                                    </tr>
                                    <tr>
                                        <td height="15"></td>
                                    </tr>
                                    <tr>
                                        <td height="35">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="170">
                                                        <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.estimatedTime")}}</span>
                                                    </td>
                                                    <td>
                                                        <span style="color: #CDE3FF;">{{ $info->estimate_time}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @if($info->type == 1)
                                    <tr>
                                        <td height="35">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="170">
                                                        <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.estimatedDistance")}}</span>
                                                    </td>
                                                    <td>
                                                        <span style="color: #CDE3FF;">{{\App\Method\MethodAlgorithm::getUnitType($info->estimate_distance,$info->unit,$info->com_unit)}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td height="35">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="170">
                                                        <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.timezone")}}</span>
                                                    </td>
                                                    <td>
                                                        <span style="color: #CDE3FF;">{{ $info->appointed_at->getTimezone()}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td height="35"></td>
                        </tr>
                        <tr>
                            <td bgcolor="#323334">
                                <table cellpadding="0" cellspacing="0" border="0" style="font-size:12px;font-weight: 200">
                                    <tr>
                                        <td height="50">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="20"></td>
                                                    <td width="460"><span style="color: #767778;">{{\Illuminate\Support\Facades\Lang::get("booking.driverAndVehicle")}}</span></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td bgcolor="#404142" height="110">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="25"></td>
                                                    <td>
                                                        <img width="55px" height="55px" src="{{$_SERVER['local_url']}}/imgs/booking/driver">
                                                    </td>
                                                    <td width="20"></td>
                                                    <td width="400">
                                                        <table>
                                                            <tr>
                                                                <td><span
                                                                        style="color: #E7E8E9;font-size:15px;font-weight: 300">{{$info->driver_data->first_name." ".$info->driver_data->last_name}}</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><span
                                                                        style="color: #AEAFB0;font-size:10px;">{{ $info->driver_data->license_number}}</span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td height="20"></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <img src="{{ $info->car_data->img}}" width="300px">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td height="20"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="50"></td>
                                                    <td width="250" style="min-width: 250px">
                                                        <span style="color: #EEEFF0;">{{ $info->car_data->model}}</span>
                                                    </td>
                                                    <td style="min-width: 20px">
                                                        <img src="{{$_SERVER['local_url']}}/imgs/common/maxpassanger">
                                                    </td>
                                                    <td width="5"></td>
                                                    <td width="100">
                                                        <span style="text-align: left;color: #EEEFF0;">{{$info->passenger_count}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="5"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td width="50"></td>
                                                    <td width="250" style="min-width: 250px">
                                                        <span style="color: #EEEFF0;">{{ $info->car_data->brand}}</span>
                                                    </td>
                                                    <td style="min-width: 20px">
                                                        <img src="{{$_SERVER['local_url']}}/imgs/common/maxbag">
                                                    </td>
                                                    <td width="5"></td>
                                                    <td width="100">
                                                        <span style="color: #EEEFF0;">{{$info->bags_count}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="40"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td height="50"></td>
                        </tr>
                        <tr>
                            <td>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                       style="color: #757677;font-size:12px;font-weight: 200">
                                    <tr>
                                        <td align="center">
                                            {{\Illuminate\Support\Facades\Lang::get("booking.appDownload")}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="65">
                                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                                <tr>
                                                    <td width="120">
                                                        <a href="{{$info->ios}}" target="view_window"><img
                                                                src="{{$info->ios_app}}" width="120"></a>
                                                    </td>
                                                    <td width="20"></td>
                                                    <td width="120">
                                                        <a href="{{$info->android}}" target="view_window"><img
                                                                src="{{$info->android_app}}" width="120"></a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td height="100"></td>
                        </tr>
                    </table>
                </td>
                <td width="50" style="min-width: 25px" valign="top">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td height="20"></td>
                        </tr>
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="20"></td>
                                        <td>
                                            <img width="25px" height="25px" src="{{$_SERVER['local_url']}}/imgs/common/k0red">
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table></td>
    </tr>
</table>


</body>
</html>