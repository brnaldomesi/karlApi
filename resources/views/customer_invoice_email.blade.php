<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>{{\Illuminate\Support\Facades\Lang::get('invoice.title')}}</title>

    @if($trip->show_type==1)
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js">
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.3/jspdf.debug.js"></script>
        <script type="text/javascript" src="//cdn.rawgit.com/niklasvh/html2canvas/0.5.0-alpha2/dist/html2canvas.min.js">
        </script>
        <script>
            function downloadPDF(filename) {
                var form = document.getElementById("invoiceBody");
                var a4BestHeight = 600;
                var a4BestWidth = form.offsetWidth * a4BestHeight / form.offsetHeight;

                html2canvas(form, {
                    onrendered: function (canvas) {
                        var imgData = canvas.toDataURL(
                            'image/png');
                        var doc = new jsPDF({
                            unit: "px",
                            format: "a4"
                        });
                        doc.addImage(imgData, 'png', 125, 20, a4BestWidth, a4BestHeight);
                        doc.save("booking-" + filename + '.pdf');
                    }
                });
            }
        </script>
    @endif
</head>
<body>

@if($trip->show_type==1)
    <button onclick="downloadPDF({{strtotime($trip->startTime->getFullDateAndTime())}})" style="background: #337ab7; cursor: pointer;width: 66% ;
 height: 45px;margin-left: 17%;margin-bottom: 10px; border:none;
  color: white;
border-radius: 10px">{{\Illuminate\Support\Facades\Lang::get('invoice.downloadAndPrint')}}</button>
@endif
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#E0E1E2">
    <tr>
        <td>
            <table id="invoiceBody" cellpadding="0" cellspacing="0" border="0" align="center"
                   bgcolor="white"
                   style="max-width: 600px;
                   table-layout: fixed;
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
                                    <table cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td>
                                                <div style="width: 80px;height: 80px;margin: auto;background: url({{$trip->company_logo}}) no-repeat scroll 50% 0;background-size: cover"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div style="color: #1B1C1C;font-size:20px;text-align: center;">
                                                    <p>{{\Illuminate\Support\Facades\Lang::get('invoice.dearClient')}}
                                                        ,<br> {{\Illuminate\Support\Facades\Lang::get('invoice.bookingDetail')}}
                                                    </p>
                                                    <p style="width: 100%;color:#888;font-size: 12px;">
                                                        {{\Illuminate\Support\Facades\Lang::get("invoice.contactInfo")}}
                                                        <a style="color: #3C7DBF"
                                                           href="tel:{{$trip->company_number}}">{{$trip->company_number}}</a>
                                                        &amp;
                                                        <a style="color: #3C7DBF"
                                                           href="mailto:{{$trip->company_email}}">{{$trip->company_email}}</a>
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
                                <td bgcolor="#E8E9EA">
                                    <table cellpadding="0" cellspacing="0" border="0"
                                           style="font-size:13px;font-weight: 300">
                                        <tr>
                                            <td height="50">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="20"></td>
                                                        <td width="460"><span
                                                                    style="color: #8C8E8E;">{{\Illuminate\Support\Facades\Lang::get('invoice.yourTrip')}}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="1" width="100%" bgcolor="#264C81"></td>
                                        </tr>
                                        <tr>
                                            <td height="80" bgcolor="#808182">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td>
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td width="320"></td>
                                                                    <td width="160" align="right">
                                                                        <span style="color: #FCFDFD;font-size: 26px">{{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->settle_fee)}}</span>
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
                                            <td height="80">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="15"></td>
                                                        <td>
                                                            <img width="55px" height="55px"
                                                                 src="{{$_SERVER['local_url']}}/imgs/common/custom">
                                                        </td>
                                                        <td width="10"></td>
                                                        <td width="400">
                                                            <table>
                                                                <tr>
                                                                    <td><span
                                                                                style="color: #9D9E9F;">{{\Illuminate\Support\Facades\Lang::get('invoice.client')}}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><span
                                                                                style="color: #2C2F2F;font-size:15px;font-weight: 400">{{$trip->customer_name}}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30"></td>
                                                        <td width="10" valign="top">
                                                            <img src="{{$_SERVER['local_url']}}/imgs/common/green">
                                                        </td>
                                                        <td width="10"></td>
                                                        <td valign="top">
                                                                <span style="color:#6C6E6E;">{{$trip->startTime->getFullDateAndTime()}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="57"></td>
                                                        <td>
                                                            <span style="color: #9D9F9F;">{{$trip->d_address}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="57"></td>
                                                        <td>
                                                            <span style="color: #BCBEBE;font-size: 12px">{{$trip->startTime->getTimezone()}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="10"></td>
                                        </tr>
                                        @if($trip->type==1)
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30"></td>
                                                        <td width="10" valign="top">
                                                            <img src="{{$_SERVER['local_url']}}/imgs/common/red">
                                                        </td>
                                                        <td width="10"></td>
                                                        <td valign="top">
                                                            <span style="color:#6C6E6E;">{{$trip->finishTime->getFullDateAndTime()}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="57"></td>
                                                        <td>
                                                            <span style="color: #9D9F9F;">{{$trip->a_address}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="57"></td>
                                                        <td>
                                                            <span style="color: #BCBEBE;font-size: 12px">{{$trip->finishTime->getTimezone()}}</span>
                                                        </td>
                                                        <td width="20"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td height="15"></td>
                                        </tr>
                                        <tr>
                                            <td height="1" width="100%" bgcolor="#C1C3C3"></td>
                                        </tr>
                                        <tr>
                                            <td height="15"></td>
                                        </tr>
                                        <tr>
                                            <td height="35">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30"></td>
                                                        <td width="85">
                                                            <span style="color: #66A0ED;">{{\Illuminate\Support\Facades\Lang::get("invoice.totalTime")}}</span>
                                                        </td>
                                                        <td>
                                                            @if (($trip->type == 2 || ($trip->type == 1 && $trip->calc_method == 2)) && $trip->additional > 0)
                                                                <span style="color: #6C6E6E;">{{$trip->estimate_time}}</span>
                                                            @else
                                                            <span style="color: #6C6E6E;">{{$trip->estimate_time}}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="35">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30"></td>
                                                        <td width="85">
                                                            <span style="color: #66A0ED;">{{\Illuminate\Support\Facades\Lang::get('invoice.distance')}}</span>
                                                        </td>
                                                        <td>
                                                            @if ($trip->type == 1 && $trip->calc_method == 1 && $trip->additional > 0)
                                                                <span style="color: #6C6E6E;">{{\App\Method\MethodAlgorithm::getUnitType($trip->distance,$trip->unit,$trip->com_unit)}}</span>
                                                            @else
                                                            <span style="color: #6C6E6E;">{{\App\Method\MethodAlgorithm::getUnitType($trip->distance,$trip->unit,$trip->com_unit)}}</span>
                                                            @endif

                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="35">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30"></td>
                                                        <td width="85">
                                                            <span style="color: #66A0ED;">{{\Illuminate\Support\Facades\Lang::get("invoice.rate")}}</span>
                                                        </td>
                                                        <td>
                                                            @if ($trip->type == 1 || $trip->type == 2)
                                                            <span style="color: #6C6E6E;">{{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->rate)}}</span>
                                                            @else
                                                            <span style="color: #6C6E6E;">N/A</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="15"></td>
                                        </tr>
                                        <tr>
                                            <td height="1" width="100%" bgcolor="#C1C3C3"></td>
                                        </tr>
                                        <tr>
                                            <td height="80">
                                                <table cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="15"></td>
                                                        <td>
                                                            <img width="55px" height="55px"
                                                                 src="{{$_SERVER['local_url']}}/imgs/common/driver">
                                                        </td>
                                                        <td width="10"></td>
                                                        <td width="400">
                                                            <table>
                                                                <tr>
                                                                    <td><span
                                                                                style="color: #9D9E9F;">{{\Illuminate\Support\Facades\Lang::get('invoice.driver')}}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><span
                                                                                style="color: #2C2F2F;font-size:15px;font-weight: 400">{{$trip->driver_name}}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" height="40"
                                                style="color: #6C6E6E">{{\Illuminate\Support\Facades\Lang::get("invoice.fareBreakdown")}}</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table cellpadding="0" cellspacing="0" border="0"
                                                       style="font-size: 14px">
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" height="18px"
                                                            style="background: url({{$_SERVER['local_url']}}/imgs/invoice/sign1)"></td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#454850">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="10"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="50">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0"
                                                                               style="color: #909293;">
                                                                            <tr>
                                                                                <td width="20"></td>
                                                                                <td width="350">{{\Illuminate\Support\Facades\Lang::get("invoice.itemDescription")}}</td>
                                                                                <td width="60" align="right">Cost</td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#454850">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="25">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0">
                                                                            <tr>
                                                                                <td width="20"></td>
                                                                                <td width="350" style="color: #F2F3F3;">
                                                                                    {{\Illuminate\Support\Facades\Lang::get("invoice.baseFare")}}
                                                                                </td>
                                                                                <td width="60" align="right"
                                                                                    style="color: #EA2E2D;">
                                                                                    {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->base_fare)}}
                                                                                </td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="5"></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#383B42">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="55">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0">
                                                                            <tr>
                                                                                <td width="20"></td>
                                                                                <td width="350" style="color: #F2F3F3;">
                                                                                    {{\Illuminate\Support\Facades\Lang::get("invoice.addOns")}}
                                                                                </td>
                                                                                <td width="60" align="right"
                                                                                    style="color: #EA2E2D;">
                                                                                    {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->add_ons)}}
                                                                                </td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    @if (($trip->type == 1 || $trip->type == 2) && $trip->additional > 0)
                                                        <tr>
                                                            <td width="25"></td>
                                                            <td width="450" bgcolor="#454850">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td height="35">
                                                                            <table cellpadding="0" cellspacing="0"
                                                                                   border="0" style="color: #EA2E2D;">
                                                                                <tr>
                                                                                    <td width="20"></td>
                                                                                    <td width="350">{{\Illuminate\Support\Facades\Lang::get("invoice.additionalMileage")}}</td>
                                                                                    <td width="60" align="right">
                                                                                        {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->additional)}}
                                                                                    </td>
                                                                                    <td width="20"></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="5"></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td width="25"></td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#454850">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="30">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0" style="color: #F2F3F3;">
                                                                            <tr>
                                                                                <td width="20"></td>
                                                                                <td width="350">{{\Illuminate\Support\Facades\Lang::get("invoice.subtotal")}}</td>
                                                                                <td width="60" align="right">
                                                                                    {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->sub_total)}}
                                                                                </td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="5"></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    @if(!empty($trip->coupon)&&!empty($trip->coupon_off))
                                                        <tr>
                                                            <td width="25"></td>
                                                            <td width="450" bgcolor="#383B42">
                                                                <table cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td height="55">
                                                                            <table cellpadding="0" cellspacing="0"
                                                                                   border="0">
                                                                                <tr>
                                                                                    <td width="20"></td>
                                                                                    <td width="350"
                                                                                        style="color: #F2F3F3;">
                                                                                        {{\Illuminate\Support\Facades\Lang::get("invoice.amountOff")}}
                                                                                    </td>
                                                                                    <td width="60" align="right"
                                                                                        style="color: #00ad78;">
                                                                                        - {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->coupon_off)}}
                                                                                    </td>
                                                                                    <td width="20"></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td width="25"></td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#454850">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="30">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0" style="color: #909293;">
                                                                            <tr>
                                                                                <td width="20"></td>
                                                                                <td width="350">{{\Illuminate\Support\Facades\Lang::get("invoice.tax")}}</td>
                                                                                <td width="60" align="right">
                                                                                    {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->tax)}}
                                                                                </td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="5"></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" bgcolor="#454850">
                                                            <table cellpadding="0" cellspacing="0" border="0">
                                                                <tr>
                                                                    <td height="30">
                                                                        <table cellpadding="0" cellspacing="0"
                                                                               border="0" style="color: #909293;">
                                                                            <tr>
                                                                                <td width="230"></td>
                                                                                <td width="70"
                                                                                    align="right">{{\Illuminate\Support\Facades\Lang::get("invoice.total")}}</td>
                                                                                <td width="130" align="right"
                                                                                    style="color: #F2F3F3;font-size: 26px">
                                                                                    {{\App\Method\MethodAlgorithm::ccyCvt($trip->ccy,$trip->settle_fee)}}
                                                                                </td>
                                                                                <td width="20"></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="40"></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="25"></td>
                                                    </tr>
                                                    <tr>
                                                        <td width="25"></td>
                                                        <td width="450" height="18px"
                                                            style="background: url({{$_SERVER['local_url']}}/imgs/invoice/sign2)"></td>
                                                        <td width="25"></td>
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
                                <td height="50"></td>
                            </tr>
                            @if($trip->show_type!=1)

                                <tr>
                                    <td>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                               style="color: #757677;font-size:12px;font-weight: 200">
                                            <tr>
                                                <td align="center">
                                                    {{\Illuminate\Support\Facades\Lang::get("invoice.appDownload")}}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="65">
                                                    <table cellpadding="0" cellspacing="0" border="0" align="center">
                                                        <tr>
                                                            <td width="120">
                                                                <a href="{{$trip->ios}}" target="view_window"><img
                                                                            src="{{$trip->ios_app}}"
                                                                            width="120"></a>
                                                            </td>
                                                            <td width="20"></td>
                                                            <td width="120">
                                                                <a href="{{$trip->android}}" target="view_window"><img
                                                                            src="{{$trip->android_app}}"
                                                                            width="120"></a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

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
                                                     src="{{$_SERVER['local_url']}}/imgs/common/k0red">
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