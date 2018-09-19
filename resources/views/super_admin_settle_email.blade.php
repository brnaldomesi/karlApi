<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<table width="650" style="background: #000;padding:10px;line-height: 180%;color:#fff;font-family: arial;">
    <tr>
        <td style="width: 100%;height:120px;text-align: center;font-weight: 500;font-size: 44px;">KARL's Bill</td>
    </tr>
    <tr>
        <td>

            @for($i=0 ; $i<count($settleDatas); $i++)
                <table style = "background: #fff;color:#666565;margin-bottom: 30px;" width = "100%" border = "0" cellspacing = "0" cellpadding = "0" >
                    <tr >
                        <td style = "height:95px;background: #0c33f9;color: #fff;font-size: 20px;padding-left:20px ;" colspan = "6" >
                            <span > </span > {{$i}}/ <span > {{$settleData->name}}</span >
                        </td >
                    <tr >
                        <td style = "padding: 10px 15px;font-size:20px;" >
                            <span style = "color:#9c9c9c ;font-size:14px;" > Statistical Time </span ><br />
                            <span > {{$startTime->format('D M j G:i:s e')}} </span > -- <span > {{$endTime->format('D M j G:i:s e')}} </span >
                        </td ></tr >
                    <tr >
                        <td style = "padding: 10px 15px;font-size:20px; width:100%" >
                            <span style = "color:#9c9c9c ;font-size:14px;" > Total:</span ><br /><br />
                            <span style = "font-size: 50px;" > ${{$settleData->pay}} </span ><br /><br />
                        </td >
                    </tr >
                    </tr >
                </table >
            @endfor
        </td>
    </tr>
    <tr>
        <td style="background:#fff;width: 50px;line-height: 150px;color: #fff;font-size: 30px;text-align: center;">
            <span><a href="https://secure.paymentech.com/signin/pages/login.faces" style="color: #fff;">GOTO CHASE BANK</a></span></td>
    </tr>
</table>
</body>
</html>
