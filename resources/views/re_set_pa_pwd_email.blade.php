<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>{{\Illuminate\Support\Facades\Lang::get("password.pwdTitle")}}</title>

</head>
<body >
<table bgcolor="#181820" width="100%" cellpadding="0" cellspacing="0" border="0" align="center">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" align="center"
                   style="background: url({{$_SERVER['local_url']}}/imgs/booking/k-bg) scroll 50% 0;
                           background-size: cover;
                           max-width: 600px;table-layout: fixed;font-family:Helvetica Neue, Helvetica, Arial,Arial Narrow,serif">
                <tr>
                    <td width="50" style="min-width: 25px"></td>
                    <td width="500">
                        <table align="center" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td height="100"></td>
                            </tr>
                            <tr>
                                <td align="center">
                                    @if ($type == 1)
                                    <p style="color: #f2f2f2;font-size:36px">{{\Illuminate\Support\Facades\Lang::get("password.welcome")}}</p>
                                    @else
                                    <p style="color: #f2f2f2;font-size:36px">{{\Illuminate\Support\Facades\Lang::get("password.recovery")}}</p>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td height="25"></td>
                            </tr>
                            <tr>
                                <td height="50" align="center">
                                    <table>
                                        <tr>
                                            <td width="10"></td>
                                            @if ($type == 1)
                                            <td>
                                                <p style="color: #d02e1a;">
                                                    {{\Illuminate\Support\Facades\Lang::get("password.personAddIn")}}
                                                </p>
                                                <p style="color: #d02e1a;">
                                                    ‘{{$info->name}}’ {{\Illuminate\Support\Facades\Lang::get("password.personAddInAsPassenger")}}
                                                </p>
                                            </td>
                                            @else
                                            <td>
                                                <p style="color: #d02e1a;">
                                                   {{\Illuminate\Support\Facades\Lang::get("password.customerForget")}}
                                                </p>
                                            </td>
                                            @endif


                                            <td width="10"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <hr color="#9b0901">
                                    <p style='font-size:15px;color:#747478;'>{{\Illuminate\Support\Facades\Lang::get("password.clientName")}}</p>
                                    <p style="color: #f2f2f2;text-align:center;font-size:25px;">{{$info->email}}</p>
                                    <p style="color: #777779 ; font-size: 15px">{{\Illuminate\Support\Facades\Lang::get("password.tempPwd")}}</p>
                                    <p style="color: #f2f2f2 ; font-size: 24px">
                                        {{$info->password}}
                                    </p>
                                    <hr color="#9b0901">
                                </td>
                            </tr>
                            <tr>
                                <td height="35"></td>
                            </tr>
                            <tr>
                                <td>
                                    <table bgcolor="#333333">
                                        <tr>
                                            <td width="5"></td>
                                            <td style="font-size: 16px;color: #a7a7a7;font-weight: 500" align="left"
                                                height="50" valign="top">
<span style="font-size: 14px;line-height: 150%; color: #f2f2f2">
{{\Illuminate\Support\Facades\Lang::get("password.changeLoginNotice")}}</span></td>
                                            <td width="5"></td>
                                        </tr>
                                        <tr>
                                            <td width="5"></td>
                                            <td style="font-size: 16px;color: #f2f2f2;font-weight: 300;line-height: 150%"
                                                align="left" height="60" valign="top">
                                                <span style='color:#000000'>1.</span>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd1Start")}} '
                                                <span style='color:#ff4d00'>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd1Middle")}}</span>
                                                ' {{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd1End")}}<br>
                                                <span style='color:#000000'>2.</span>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd2Start")}}
                                                '<span style='color:#ff4d00'>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd2Middle")}}</span>'
                                                 {{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd2End")}}<br>
                                                <span style='color:#000000'>3.</span>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd3")}}<br>
                                                <span style='color:#000000'>4.</span>{{\Illuminate\Support\Facades\Lang::get("password.clientChangePwd4")}}<br>
                                            </td>
                                            <td width="5"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="20"></td>
                            </tr>
                            <tr>
                                <td height="10"></td>
                            </tr>
                            <tr>
                                <td height="80"></td>
                            </tr>
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr style="color: #757677;font-size:12px;font-weight: 200">
                                            <td align="center" >
                                                {{\Illuminate\Support\Facades\Lang::get("password.appPassengerDownload")}}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="20"></td>
                                        </tr>
                                    </table>
                                    <table cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr style="color: #757677;font-size:12px;font-weight: 200">
                                            <td>
                                                <a href="{{$info->ios}}"
                                                   target="view_window"><img
                                                            src="{{$info->ios_app}}"
                                                            height="42" width="130"/></a></td>
                                            <td width="20"></td>
                                            <td>
                                                <a href="{{$info->android}}"
                                                   target="view_window"><img
                                                            src="{{$info->android_app}}"
                                                            height="42" width="130"/></a></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td height="80"></td>
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
            </table>
        </td>
    </tr>
</table>
</body>
</html>