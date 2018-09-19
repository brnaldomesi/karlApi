<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>{{\Illuminate\Support\Facades\Lang::get("booking.createTitle")}}</title>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#161921">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" align="center"
                   bgcolor="#161921"
                   style="background: url({{$_SERVER['local_url']}}/imgs/booking/k-bg) scroll 50% 0;
                           background-size: cover;
                           max-width: 600px;table-layout: fixed;
                           font-family:Helvetica Neue, Helvetica, Arial,Arial Narrow,serif;">
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
                                            <td align="center">
                                                <img src="{{$booking->an_type==2 ? $booking->exe_com_logo:$booking->own_com_logo}}"
                                                     width="80px">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="30"></td>
                                        </tr>
                                        <tr>
                                            <td height="80" style="color: #F1F2F4;font-size:26px;text-align: center;">
                                                @if($booking->an_type==0)
                                                   {{\Illuminate\Support\Facades\Lang::get("booking.newBooking")}}<br>
                                                   {{\Illuminate\Support\Facades\Lang::get("booking.bookingReview")}}
                                                @else
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.adminAnNotice")}}
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="30"></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table>
                                                    <tr>
                                                        <td width="125"></td>
                                                        <td width="250">
                                                            <a href="{{$_SERVER['dashboard_url']}}" target="_blank"
                                                               style="text-decoration:none">
                                                                <table width="240" height="50"
                                                                       style="border-radius: 32px; background-color: #2a65c0" align="center">
                                                                    <tr>
                                                                        <td style="color: #f2f2f2" align="center">
                                                                            {{\Illuminate\Support\Facades\Lang::get("password.goToDashboard")}}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </a>
                                                        </td>
                                                        <td width="125"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="50"></td>
                            </tr>
                            <tr>
                                <td bgcolor="{{$booking->an_type == 2?"#333333":"#2A65BF"}}">
                                    <table cellpadding="0" cellspacing="0" border="0"
                                           style="font-size:14px;font-weight: 200">
                                        <tr>
                                            <td height="50">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="460">
                                                            <span style="color: {{$booking->an_type == 2?"#787878":"#75A1DE"}};">{{$booking->an_type==2?"Affiliate ":""}}
                                                                {{\Illuminate\Support\Facades\Lang::get("booking.clientDetails")}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="{{$booking->an_type == 2?"#414141":"#264C81"}}" height="110">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td>
                                                            <img width="55px" height="55px"
                                                                 src="{{$_SERVER['local_url']}}/imgs/booking/custom">
                                                        </td>
                                                        <td width="20"></td>
                                                        <td width="400">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td>
                                                                        <span style="color: #E7E8E9;font-size:15px;font-weight: 300">{{$booking->customer_data->first_name}} {{$booking->customer_data->last_name}}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="5"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <span style="color: #071422;font-size:10px;">P:</span>
                                                                        <span style="color: #AEAFB0;font-size:10px;">{{$booking->customer_data->mobile}}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <span style="color: #071422;font-size:10px;">E:</span>
                                                                        <span style="color: #AEAFB0;font-size:10px;">{{$booking->an_type==2 ? $booking->own_com_email:$booking->customer_data->email}}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="100" style="font-size: 12px">
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="sms:{{$booking->customer_data->mobile}}"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/common/text"
                                                                                    width="25"></a>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center"
                                                                        style="color: {{$booking->an_type==2?"#898989":"#7CA4E5"}}">
                                                                        {{\Illuminate\Support\Facades\Lang::get("booking.sendSMS")}}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="tel:{{$booking->customer_data->mobile}}"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/common/call"
                                                                                    width="25"></a>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center"
                                                                        style="color: {{$booking->an_type==2?"#898989":"#7CA4E5"}}">
                                                                        {{\Illuminate\Support\Facades\Lang::get("booking.makeCall")}}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="mailto:{{$booking->an_type==2 ? $booking->own_com_email:$booking->customer_data->email}}"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/common/email"
                                                                                    width="25"></a>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center"
                                                                        style="color: {{$booking->an_type==2?"#898989":"#7CA4E5"}}">
                                                                        {{\Illuminate\Support\Facades\Lang::get("booking.sendEmail")}}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="35"></td>
                            </tr>
                            <tr>
                                <td height="60">
                                    <span style="font-size:30px;color: #f2f2f2;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.clientMsg")}}</span>
                                </td>

                            </tr>
                            <tr>
                                <td style="padding: 10px;border-radius: 10px; background-color: #2d2f36;
                                font-size: 16px;color: #f2f2f2;font-weight: 300;line-height: 150%;"
                                    align="left" valign="top">
                                    <span>{{$booking->message==null?'':$booking->message}}</span> <br>
                                </td>
                            </tr>

                            <tr>
                                <td height="35"></td>
                            </tr>
                            <tr>
                                <td bgcolor="{{$booking->an_type == 0?"#2A65BF":"#333333"}}">
                                    <table cellpadding="0" cellspacing="0" border="0"
                                           style="font-size:14px;font-weight: 200">
                                        <tr>
                                            <td height="50">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="460"><span
                                                                    style="color: {{$booking->an_type == 0?"#75A1DE":"#787878"}}">{{\Illuminate\Support\Facades\Lang::get("booking.tripDetail")}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="1" width="100%" bgcolor="#4F5051"></td>
                                        </tr>
                                        <tr>
                                            <td height="20"></td>
                                        </tr>
                                        @if($booking->an_type==2)
                                            <tr>
                                                <td>
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="20"></td>
                                                            <td width="460" style="color:#757677">
                                                                {{\Illuminate\Support\Facades\Lang::get("booking.bookingCreateTime")}}:
                                                                <span>{{$booking->created->getFullDateAndTime()}}</span>
                                                            </td>
                                                            <td width="20"></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td height="50">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="460">
                                                            <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.tripOn")}}</span>
                                                            <span style="color: #FEFFFF;font-weight: 300">{{$booking->appointed_at->getDate()}}</span>
                                                            <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.tripAt")}}</span>
                                                            <span style="color: #FEFFFF;font-weight: 300">{{$booking->appointed_at->getTime()}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="80" bgcolor="{{$booking->an_type == 0?"#264B82":"#414141"}}">
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
                                                                        <span style="color: #FCFDFD;font-size: 26px">{{\App\Method\MethodAlgorithm::ccyCvt($booking->ccy,$booking->total_cost)}}</span>
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
                                            <td height="45">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="300"><span
                                                                    style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.pickupAddress")}}</span></td>
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
                                                            <span style="color: #BEBFC0;">{{$booking->d_address}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        @if($booking->type==1)
                                            <tr>
                                                <td height="45">
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="20"></td>
                                                            <td width="300"><span
                                                                        style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.dropOffAddress")}}</span>
                                                            </td>
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
                                                                <span style="color: #BEBFC0;">{{$booking->a_address}}</span>
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
                                                            <span style="color: #BEBFC0;">{{$booking->estimate_time}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        @if($booking->type==1)
                                            <tr>
                                                <td height="35">
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="20"></td>
                                                            <td width="170">
                                                                <span style="color: #5AA1FF;">{{\Illuminate\Support\Facades\Lang::get("booking.estimatedDistance")}}</span>
                                                            </td>
                                                            <td>
                                                            <span style="color: #BEBFC0;">
                                                                {{\App\Method\MethodAlgorithm::getUnitType($booking->estimate_distance,$booking->unit,$booking->com_unit)}}</span>
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
                                                            <span style="color: #BEBFC0;">{{$booking->appointed_at->getTimezone()}}</span>
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
                                <td bgcolor="{{$booking->an_type == 2?"#2A65BF":"#333333"}}">
                                    <table cellpadding="0" cellspacing="0" border="0"
                                           style="font-size:14px;font-weight: 200">
                                        <tr>
                                            <td height="50">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="460">
                                                            <span style="color: {{$booking->an_type == 2?"#75A1DE":"#787878"}};">{{$booking->an_type == 1?"Affiliate ":""}}
                                                                {{\Illuminate\Support\Facades\Lang::get("booking.driverAndVehicle")}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="{{$booking->an_type==2?"#264B82":"#414141"}}" height="100">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td>
                                                            <img width="55px" height="55px"
                                                                 src="{{$_SERVER['local_url']}}/imgs/booking/driver">
                                                        </td>
                                                        <td width="20"></td>
                                                        <td width="400">
                                                            <table>
                                                                <tr>
                                                                    <td><span
                                                                                style="color: #E7E8E9;font-size:15px;
                                                                            font-weight: 300">{{$booking->driver_data->first_name}} {{$booking->driver_data->last_name}}</span>
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
                                                            <img src="{{$booking->car_data->img}}" width="300px">
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
                                                            <span style="color: #EEEFF0;">{{$booking->car_data->model}}</span>
                                                        </td>
                                                        <td style="min-width: 20px">
                                                            <img src="{{$_SERVER['local_url']}}/imgs/common/maxpassanger">
                                                        </td>
                                                        <td width="5"></td>
                                                        <td width="100">
                                                            <span style="text-align: left;color: #EEEFF0;">{{$booking->passenger_count}}</span>
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
                                                            <span style="color: #EEEFF0;">{{$booking->car_data->brand}}</span>
                                                        </td>
                                                        <td style="min-width: 20px">
                                                            <img src="{{$_SERVER['local_url']}}/imgs/common/maxbag">
                                                        </td>
                                                        <td width="5"></td>
                                                        <td width="100">
                                                            <span style="color: #EEEFF0;">{{$booking->bags_count}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="20"></td>
                                        </tr>
                                        @if($booking->an_type == 1)
                                            <tr>
                                                <td bgcolor="#414141" height="110">
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="25"></td>
                                                            <td width="400">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td><span
                                                                                    style="color: #E7E8E9;font-size:18px;font-weight: 300">{{$booking->exe_com_name}}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="5"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span style="color: #071422;font-size:10px;">P:</span>
                                                                            <span style="color: #AEAFB0;font-size:10px;">{{$booking->exe_com_phone1==null ? ($booking->exe_com_phone2==null?"":$booking->exe_com_phone2) : $booking->exe_com_phone1}}</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <span style="color: #071422;font-size:10px;">E:</span>
                                                                            <span style="color: #AEAFB0;font-size:10px;">{{$booking->exe_com_email}}</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="100" style="font-size: 12px">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td align="center">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td align="center" height="40">
                                                                            <a href="sms:{{$booking->exe_com_phone1}}"><img
                                                                                        src="{{$_SERVER['local_url']}}/imgs/common/text"
                                                                                        width="25"></a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center"
                                                                            style="color: {{$booking->an_type==2?"#7CA4E5":"#898989"}}">
                                                                            {{\Illuminate\Support\Facades\Lang::get("booking.sendSMS")}}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td align="center">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td align="center" height="40">
                                                                            <a href="tel:{{$booking->exe_com_phone1}}"><img
                                                                                        src="{{$_SERVER['local_url']}}/imgs/common/call"
                                                                                        width="25"></a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center"
                                                                            style="color: {{$booking->an_type==2?"#7CA4E5":"#898989"}}">
                                                                            {{\Illuminate\Support\Facades\Lang::get("booking.makeCall")}}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td align="center">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td align="center" height="40">
                                                                            <a href="mailto:{{$booking->exe_com_email}}"><img
                                                                                        src="{{$_SERVER['local_url']}}/imgs/common/email"
                                                                                        width="25"></a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center"
                                                                            style="color: {{$booking->an_type==2?"#7CA4E5":"#898989"}}">
                                                                            {{\Illuminate\Support\Facades\Lang::get("booking.sendEmail")}}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="50"></td>
                            </tr>
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td align="center">
                                                <img src="{{$_SERVER['local_url']}}/imgs/booking/Karl-Logo"
                                                     width="55px">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="30"></td>
                            </tr>
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="130"></td>
                                            <td width="240">
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="https://www.facebook.com/karlglobal"
                                                                           target="view_window"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/password/icon-facebook"
                                                                                    width="35"></a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="https://twitter.com/"
                                                                           target="view_window"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/password/icon-twitter"
                                                                                    width="35"></a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td align="center">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td align="center" height="40">
                                                                        <a href="https://www.linkedin.com/company/karl"
                                                                           target="view_window"><img
                                                                                    src="{{$_SERVER['local_url']}}/imgs/password/icon-linkedin"
                                                                                    width="35"></a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width="130"></td>
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
                                                <img width="25px" height="25px"
                                                     src="{{$_SERVER['local_url']}}/imgs/booking/k0red">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


</body>
</html>