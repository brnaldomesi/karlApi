<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>{{\Illuminate\Support\Facades\Lang::get("password.pwdTitle")}}</title>

</head>
<body>
<table bgcolor="#181820" width="100%" cellpadding="0" cellspacing="0" border="0" align="center">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" align="center"
                   style="background: url({{$_SERVER['local_url']}}/imgs/booking/k-bg) scroll 50% 0;
                           background-size: cover;
                           max-width: 600px;table-layout: fixed;
                           font-family:Helvetica Neue, Helvetica, Arial,Arial Narrow,serif">
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
                                            <td>
                                                <p style="color: #d02e1a;">
                                                    {{\Illuminate\Support\Facades\Lang::get("password.saleNewHelp1")}}
                                                </p>
                                                <p style="color: #d02e1a;">
                                                    {{\Illuminate\Support\Facades\Lang::get("password.saleNewHelp2")}}
                                                </p>
                                            </td>
                                            <td width="10"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <hr color="#9b0901">
                                    <p style='font-size:15px;color:#747478;'>{{\Illuminate\Support\Facades\Lang::get("password.saleRepId")}}</p>
                                    <p style="color: #f2f2f2;text-align:center;font-size:25px;">{{$saleId}}</p>
                                    <p style="color: #777779 ; font-size: 15px">{{\Illuminate\Support\Facades\Lang::get("password.tempPwd")}}</p>
                                    <p style="color: #f2f2f2 ; font-size: 24px">
                                        {{$pwd}}
                                    </p>
                                    <hr color="#9b0901">
                                </td>
                            </tr>
                            <tr>
                                <td height="35"></td>
                            </tr>
                            <tr>
                                <td height="20"></td>
                            </tr>

                            <tr>
                                <td height="10"></td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="font-size: 16px;color:#6c6c6c">
                                    <br>
                                        {{\Illuminate\Support\Facades\Lang::get("password.saleEmailHelp")}}
                                        <a href="mailto:support@karl.limo">support@karl.limo</a>.</span>
                                </td>
                            </tr>
                            <tr>
                                <td height="80"></td>
                            </tr>
                            <tr>
                                <td>
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
                            </tr>
                            <tr>
                                <td height="80"></td>
                            </tr>
                            <tr>
                                <td>
                                    <hr color="#5e5f63">
                                </td>
                            </tr>
                            <tr>
                                <td height="80"></td>
                            </tr>
                            <tr>
                                <td>
                                    <table height="30" align="center">
                                        <tr>
                                            <td>
                                                <img src="{{$_SERVER['local_url']}}/imgs/password/k-logo">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table height="90" align="center">
                                        <tr>
                                            <td width="80" align="center">
                                                <a href="https://www.facebook.com/karlglobal"><img
                                                            src='{{$_SERVER['local_url']}}/imgs/password/icon-facebook'/></a>
                                            </td>
                                            <td width="80" align="center">
                                                <a href="https://twitter.com/"><img
                                                            src='{{$_SERVER['local_url']}}/imgs/password/icon-twitter'/></a>
                                            </td>
                                            <td width="80" align="center">
                                                <a href="https://www.linkedin.com/company/karl"><img
                                                            src='{{$_SERVER['local_url']}}/imgs/password/icon-linkedin'/></a>
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
            </table>
        </td>
    </tr>
</table>
</body>
</html>