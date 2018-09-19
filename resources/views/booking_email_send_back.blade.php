<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{\Illuminate\Support\Facades\Lang::get("booking.confirm")}}</title>
</head>
<body>
<table width="600" cellpadding="0" cellspacing="0" border="0"
       style="table-layout: fixed; font-family:Helvetica Neue, Helvetica, Arial,Arial Narrow ,serif">
    <tr>
        <td width="100%">
            <table cellpadding="0" cellspacing="0" border="0" align="center" class="content_wrap"
                   style="padding: 10px;border: 1px solid #ccc; background:#333 url({{ $_SERVER['local_url'] }}/imgs/booking/k-bg) no-repeat;background-size: cover;">
                <tr>
                    <td>
                        <table align="center" style="margin: 20px auto">
                            <tr>
                                <td height="10"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="border: 1px solid #f2f2f2;border-radius: 100%;width: 80px;height: 80px;margin: auto;background:#333 url({{ $booking->company_logo }}) no-repeat scroll 50% 0;background-size: cover">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td height="20"></td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <div style="color: #f2f2f2;text-align: center;">
                                        <b style="font-size:24px;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;line-height: 26px;">{{\Illuminate\Support\Facades\Lang::get('booking.sendBackHelp1')}}
                                            <br> {{\Illuminate\Support\Facades\Lang::get('booking.sendBackHelp2')}}</b>

                                        <div style="margin: 30px auto 10px auto;color: #f2f2f2;font-size: 15px;width: 180px;height: 50px">
                                            <a href="{{ $_SERVER['dashboard_url'] }}" target="_blank"
                                               style="text-decoration: none">
                                                <p style="display: block;border-radius: 30px;background: transparent;border: 2px solid #e0e0e0;text-align: center;line-height: 50px;color: #f2f2f2;">
                                                    {{\Illuminate\Support\Facades\Lang::get('booking.goToDashboard')}}</p>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>


                <tr>
                    <td>
                        <table width="100%" style="border-top: 1px solid #ccc">
                            <tr>
                                <td height="60">
                                    <span style="font-size:30px;color: #f2f2f2;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.clientDetails")}}</span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div style="background: #3b76c9;border-radius: 7px;padding: 5px 25px;">
                                        <div style="height: 50px;padding: 10px 0;">
                                            <div style="background: url({{ $booking->customer_data->avatar_url }});background-size: cover;width: 45px;height: 45px;border: 1px solid #f2f2f2;float: left;border-radius: 100%;box-shadow: 1px 4px 10px black !important">
                                            </div>
                                            <div style="float: left;margin-left: 15px">
                                                <p style="margin: 0;color: lightgray">{{\Illuminate\Support\Facades\Lang::get("booking.client")}}</p>
                                                <h3 style="margin: 0;color: #f2f2f2">{{ $booking->customer_data->first_name . " " . $booking->customer_data->last_name }}</h3>
                                            </div>
                                        </div>
                                        <hr style="border:none;border-bottom: #2d5da1 solid 1px;"/>
                                        <div>
                                            <table style="margin: 20px 0;width: 100%">
                                                <tr>
                                                    <td width="25%" style="vertical-align: middle;text-align: center;">
                                                        <a href="sms:{{ $booking->customer_data->mobile }}">
                                                            <img src="{{$_SERVER['local_url']."/imgs/booking/mail"}}"
                                                                 height="25" width="30"/></a>
                                                        <p style="margin: 0">{{\Illuminate\Support\Facades\Lang::get('booking.sendSMS')}}</p>
                                                    </td>
                                                    <td width="25%" style="vertical-align: middle;text-align: center;">
                                                        <a href="tel:{{ $booking->customer_data->mobile }}">
                                                            <img src="{{ $_SERVER['local_url']."/imgs/booking/call"}}"
                                                                 height="25" width="30"/></a>
                                                        <p style="margin: 0">{{\Illuminate\Support\Facades\Lang::get("booking.makeCall")}}</p>
                                                    </td>
                                                    <td colspan="2"
                                                        style="vertical-align: middle;text-align: center;border-left: 1px solid #2d5da1;">
                                                        <a href="mailto:{{ $booking->customer_data->email }}">
                                                            <img src="{{ $_SERVER['local_url']."/imgs/booking/email"}}"
                                                                 height="25" width="30"/></a>
                                                        <p style="margin: 0">{{\Illuminate\Support\Facades\Lang::get('booking.sendEmail')}}</p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center" colspan="2" style="color: lightgray;">
                                                        {{ $booking->customer_data->mobile }}
                                                    </td>
                                                    <td align="center" colspan="2"
                                                        style="color: lightgray;border-left: 1px solid #2d5da1">
                                                        {{ $booking->customer_data->email }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td height="60">
                                    <span style="font-size:30px;color: #f2f2f2;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get('booking.clientMsg')}}</span>
                                </td>

                            </tr>
                            <tr>
                                <td style="padding: 10px;border-radius: 10px; background-color: #2d2f36;
                                font-size: 16px;color: #f2f2f2;font-weight: 300;line-height: 150%;"
                                    align="left" valign="top">
                                                <span>{{ $booking->message == null ? '' : $booking->message }}
                                                </span>
                                                    <br>
                                </td>
                            </tr>
                            <tr>
                                <td height="60">
                                    <span style="font-size:30px;color: #f2f2f2;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.tripDetail")}}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>

                                    <div style="background: #3b76c9;border-radius: 7px;padding: 5px 25px;">
                                        <div style="border-bottom: 1px solid #3262a4">
                                            <div style="color: #f2f2f2;font-size: 20px;">
                                                <p style="-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;">
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.tripOn")}}
                                                    <span style="background: #f2f2f2;display: inline-block;padding:8px;border-radius: 5px;color:darkblue;margin: 0 3px;font-weight: 500;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;">{{ $booking->appointed_at->getDate() }}</span>
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.tripAt")}}
                                                    <span style="background: #f2f2f2;display: inline-block;padding:8px;border-radius: 5px;color:darkblue;margin: 0 3px;font-weight: 500;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;">{{ $booking->appointed_at->getTime() }}</span>
                                                </p>
                                            </div>
                                            <div style="color: #f2f2f2;font-size: 20px;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;">
                                                <p>{{\Illuminate\Support\Facades\Lang::get("booking.customerPaid")}}<span
                                                            style="background: orangered;display: inline-block;padding:8px;border-radius: 5px;color:darkblue;color: #f2f2f2;margin: 0 3px;">${{ $booking->total_cost }}</span>
                                                </p>
                                            </div>

                                        </div>

                                        <div style="padding: 15px 0;">
                                            <div>
                                                <span style="font-size: 20px;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.pickupAddress")}}</span>
                                                <table style="color: #f2f2f2;margin-top: 5px">
                                                    <tr>
                                                        <td width="6%" style="vertical-align:text-top;"><span
                                                                    style="display: inline-block;width: 20px;height: 20px;text-align: center;line-height: 20px;background: #4444c0;border-radius: 100%;font-size: 12px;">A</span>
                                                        </td>
                                                        <td style="vertical-align:text-top;"><span
                                                                    style="font-size: 18px;line-height: 150%">{{ $booking->d_address }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                            @if ($booking->type == 1)
                                            <div>
                                                <span style="font-size: 20px;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.dropOffAddress")}}</span>
                                                <table style="color: #f2f2f2;margin-top: 5px">
                                                    <tr>
                                                        <td width="6%" style="vertical-align:text-top;"><span
                                                                    style="display: inline-block;width: 20px;height: 20px;text-align: center;line-height: 20px;background: #302f7e;border-radius: 100%;font-size: 12px;">B</span>
                                                        </td>
                                                        <td style="vertical-align:text-top;"><span
                                                                    style="font-size: 18px;line-height: 150%"><p>{{$booking->a_address}}</p></span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            @endif

                                            <hr style="border:none;border-bottom: #3262a4 solid 1px;"/>
                                            <table style="padding: 15px 0" width="100%">
                                                <tr>
                                                    <td width="50%">
                                                        <span style="font-weight: 200;font-size: 20px;">{{\Illuminate\Support\Facades\Lang::get("booking.estimatedTime")}}</span>
                                                        <div style="margin-top: 5px">
                                                            <img src="{{$_SERVER['local_url']."/imgs/booking/time"}}"
                                                                 height="22" width="23"
                                                                 style="vertical-align: middle"/><span
                                                                    style="color: #f2f2f2;font-weight: 200;font-size: 20px;margin-left: 5px;"> {{$booking->estimate_time }}</span>
                                                        </div>
                                                    </td>

                                                    <td width="50%">
                                                        @if($booking->type == 1)
                                                        <span style="font-weight: 200;font-size: 20px;">{{\Illuminate\Support\Facades\Lang::get("booking.estimatedDistance")}}</span>
                                                        <div style="margin-top: 5px">
                                                            <img src="{{$_SERVER['local_url']."/imgs/booking/distance"}}"
                                                                 height="22" width="27"
                                                                 style="vertical-align: middle"/><span
                                                                    style="color: #f2f2f2;font-weight: 200;font-size: 20px;margin-left: 5px;">                                                                 {{\App\Method\MethodAlgorithm::getUnitType($booking->estimate_distance,$booking->unit,$booking->com_unit)}}</span>
                                                            </span>
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </td>

                                                </tr>
                                            </table>

                                            <div>
                                                <span style="font-weight: 200;font-size: 20px;">{{\Illuminate\Support\Facades\Lang::get("booking.timezone")}}</span>
                                                <span style="color: #f2f2f2;font-weight: 200;font-size: 20px;margin-left: 10px;">{{$booking->appointed_at->getTimezone() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <table width="545">
                                        <tr>
                                            <td height="60">

                                                <b style="font-size:24px;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;
                                                        color: red;
                                                        mso-table-lspace: 0pt;mso-table-rspace: 0pt;line-height: 26px;">
                                                    {{\Illuminate\Support\Facades\Lang::get("booking.checkAlert")}}
                                                </b>
                                            </td>

                                        </tr>
                                        <tr>
                                            <td height="60">
                                                <span style="font-size:30px;color: #f2f2f2;font-weight: 200;">{{\Illuminate\Support\Facades\Lang::get("booking.driverAndVehicle")}}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div style="width: 545px;background: #3b76c9;border-radius: 10px;padding: 0 15px;color: #f2f2f2; max-width: 600px">
                                                    <div style="height: 50px;padding: 15px 0;border-bottom: 1px solid #3262a4">
                                                        <div style="background: url({{$booking->driver_data->avatar_url }});background-size: cover;width: 45px;height: 45px;border: 1px solid #f2f2f2;float: left;border-radius: 100%;box-shadow: 1px 4px 10px black">
                                                        </div>
                                                        <div style="float: left;margin-left: 15px">
                                                            <h3 style="margin: 0">{{$booking->driver_data->first_name . " " . $booking->driver_data->last_name }}</h3>
                                                            <p style="margin: 0;color: lightgray">{{$booking->driver_data->license_number }}</p>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div style="padding-top: 15px;">
                                                            <img src="{{$booking->car_data->img }}"
                                                                 style="width: 100%"/>
                                                        </div>
                                                        <h2 style="margin: 5px 0;font-weight: 400;">{{$booking->car_data->model }}</h2>
                                                        <p style="margin: 5px 0;font-weight: 200;font-size: 20px;">{{$booking->car_data->brand }}</p>
                                                    </div>
                                                    <hr style="border:none;border-bottom: #2d5da1 solid 1px;"/>
                                                    <div style="padding: 15px 0">
                                                        <div style="float: left">
                                                            <img src="{{$_SERVER['local_url']."/imgs/booking/passenger"}}"
                                                                 height="19" width="19"
                                                                 style="vertical-align: middle"/>
                                                            <span style="font-weight: 100;">{{\Illuminate\Support\Facades\Lang::get("booking.maxPassengers")}}: {{$booking->passenger_count }}</span>
                                                        </div>
                                                        <div style="float: right">
                                                            <img src="{{$_SERVER['local_url']."/imgs/booking/box"}}"
                                                                 height="19" width="18"
                                                                 style="vertical-align: middle"/>
                                                            <span style="font-weight: 100;">{{\Illuminate\Support\Facades\Lang::get("booking.maxBags")}}: {{$booking->bags_count }}</span>
                                                        </div>
                                                        <div style="clear: both"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>


                            <tr>
                                <td>
                                    <hr style="border:none;border-bottom: #ccc solid 1px;margin-top: 40px;"/>
                                    <table align="center" style="padding: 15px 0">
                                        <tr>
                                            <td align="center" style="margin: 10px auto;">
                                                <img src="{{$_SERVER['local_url']."/imgs/booking/k-login-logo"}}"
                                                     width="150"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table align="center" style="margin: 10px auto;">
                                                    <tr>
                                                        <td align="center" width="70"><a
                                                                    href="https://www.facebook.com/karlglobal"
                                                                    target="view_window"><img
                                                                        src="{{$_SERVER['local_url']."/imgs/password/icon-facebook"}}"
                                                                        height="50" width="50"/></a></td>
                                                        <td align="center" width="70"><a href="https://twitter.com/"
                                                                                         target="view_window"><img
                                                                        src="{{$_SERVER['local_url']."/imgs/password/icon-twitter"}}"
                                                                        height="50" width="50"/></a></td>
                                                        <td align="center" width="70"><a
                                                                    href="https://www.linkedin.com/company/karl"
                                                                    target="view_window"><img
                                                                        src="{{$_SERVER['local_url']."/imgs/password/icon-linkedin"}}"
                                                                        height="50" width="50"/></a></td>
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
        </td>
    </tr>
</table>
</body>
</html>