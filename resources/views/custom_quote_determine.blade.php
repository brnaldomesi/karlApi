<!DOCTYPE>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{{\Illuminate\Support\Facades\Lang::get("booking.customDetermineTitle")}}</title>
<style type="text/css">
<!--
body {
	background-color: #101126;
}
-->
</style></head>

<body style="font-family:Arial;">
<div style="width:650px;margin:20px auto">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#101126">
      <tr>
        <td width="73%" height="100"><img src="{{ $companyLogoUrl }}" width="185" height="65"></td>
        <td width="27%" align="right" style="color:#bcbcbc;">{{$sendTime }}</td>
      </tr>
    </table>
	
	<div style="padding:25px;color:#000000;line-height:180%;background:#FFFFFF">
		<p>
		 {{\Illuminate\Support\Facades\Lang::get("booking.dearPassenger",["passengerName"=>$passageName]) }},
            <br>
            {{\Illuminate\Support\Facades\Lang::get("booking.customDetermineInfo")}}
			 </p>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="7%" height="40">&nbsp;</td>
    <td width="29%"><strong>Passenger	：</strong></td>
    <td width="64%">{{ $passageName }}</td>
  </tr>
  <tr>
    <td height="40">&nbsp;</td>
    <td><strong>{{\Illuminate\Support\Facades\Lang::get("booking.pickupAddress")}}  	：</strong></td>
    <td>{{ $pickUpAddress }}</td>
  </tr>
  <tr>
    <td height="40">&nbsp;</td>
    <td><strong>{{\Illuminate\Support\Facades\Lang::get("booking.customTime")}}       		：</strong></td>
    <td>{{ $startTime }}</td>
  </tr>
  <tr>
    <td height="40">&nbsp;</td>
    <td><strong>{{\Illuminate\Support\Facades\Lang::get("customSpendTime")}}	：</strong></td>
    <td>{{\Illuminate\Support\Facades\Lang::get("booking.customHours",["spendTime"=>$spendTime])}}</td>
  </tr>
</table>

		</p>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:#bcbcbc solid 1px">
        <tr>
          <td width="27%" height="90" align="center" bgcolor="#fafafa"><b>{{\Illuminate\Support\Facades\Lang::get("booking.totalCost")}}：</b></td>
          <td width="39%" align="center" bgcolor="#fafafa">&nbsp;</td>
          <td width="34%" align="center" bgcolor="#fafafa" style="font-size:50px"><b>{{\App\Method\MethodAlgorithm::ccyCvt($ccy,$totalCost)}}</b></td>
        </tr>
      </table>
	  <p style="text-align:center;margin:50px 0;"><a href="{{$confirmUrl }}" style="width:270px;height:20px;background:#0023fd;display:block;padding:20px;color:#FFFFFF;margin:0 auto;text-decoration:none;-webkit-border-radius: 15px;">{{\Illuminate\Support\Facades\Lang::get("booking.confirm")}}</a></p>
	</div>
</div>
</body>
</html>
