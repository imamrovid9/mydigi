<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <title>
            Merchant Payments
        </title>
    </head>
    <style>
        body {
        font-family: "DeJaVu Sans", Helvetica, sans-serif;
        color: #121212;
        line-height: 15px;
    }

    table, tr, td {
        padding: 6px 6px;
        border: 1px solid black;
    }

    tr {
        height: 40px;
    }

    </style>

    <body>
        <div style="width:100%; margin:0px auto;">
            <div style="height:80px">
                <div style="width:80%; float:left; font-size:13px; color:#383838; font-weight:400;">
                    <div>
                        <strong>
                            {{ ucwords(Session::get('name')) }}
                        </strong>
                    </div>
                    <br>
                    <div>
                        Period : {{ $date_range }}
                    </div>
                    <br>
                    <div>
                        Print Date : {{ dateFormat(now())}}
                    </div>
                </div>
                <div style="width:20%; float:left;font-size:15px; color:#383838; font-weight:400;">
                    <div>
                        <div>
                            @if (!empty($company_logo))
                                <img src="{{ url('public/images/logos/'.$company_logo) }}" width="288" height="90" alt="Logo"/>
                            @else
                                <img src="{{ url('public/uploads/userPic/default-logo.jpg') }}" width="288" height="90">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div style="clear:both">
            </div>
            <div style="margin-top:30px;">
                <table style="width:100%; border-radius:1px;  border-collapse: collapse;">

                    <tr style="background-color:#f0f0f0;text-align:center; font-size:12px; font-weight:bold;">
                        <td>Date</td>
                        <td>Merchant</td>
                        <td>User</td>
                        <td>Amount</td>
                        <td>Fees</td>
                        <td>Total</td>
                        <td>Currency</td>
                        <td>Payment Method</td>
                        <td>Status</td>
                    </tr>

                    @foreach($merchant_payments as $merchant_payment)

                    <tr style="background-color:#fff; text-align:center; font-size:12px; font-weight:normal;">

                        <td>{{ dateFormat($merchant_payment->created_at) }}</td>

                        <td>{{ isset($merchant_payment->merchant) ? $merchant_payment->merchant->user->first_name.' '.$merchant_payment->merchant->user->last_name :"-" }}</td>

                        <td>{{ isset($merchant_payment->user) ? $merchant_payment->user->first_name.' '.$merchant_payment->user->last_name :"-" }}</td>

                        <td>{{ formatNumber($merchant_payment->amount) }}</td>

                        <td>{{ ($merchant_payment->charge_percentage == 0) && ($merchant_payment->charge_fixed == 0) ? "-" : formatNumber($merchant_payment->charge_percentage + $merchant_payment->charge_fixed) }}</td>

                        @php
                            $total = $merchant_payment->charge_percentage + $merchant_payment->charge_fixed + $merchant_payment->amount;
                        @endphp
                        <td>{{ '+'.formatNumber($total) }}</td>

                        <td>{{ $merchant_payment->currency->code }}</td>

                        <td>{{ ($merchant_payment->payment_method->name == "Mts") ? "Pay Money" : $merchant_payment->payment_method->name }}</td>

                        <td>{{ $merchant_payment->status }}</td>

                    </tr>
                    @endforeach

                </table>
            </div>
        </div>
    </body>
</html>
