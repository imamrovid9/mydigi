<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <title>
            Payouts
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
                        <td>User</td>
                        <td>Amount</td>
                        <td>Fees</td>
                        <td>Total</td>
                        <td>Currency</td>
                        <td>Payment Method</td>
                        <td>Method Info</td>
                        <td>Status</td>
                    </tr>

                    @foreach($withdrawals as $withdrawal)

                    <tr style="background-color:#fff; text-align:center; font-size:12px; font-weight:normal;">

                        <td>{{ dateFormat($withdrawal->created_at) }}</td>

                        <td>{{ isset($withdrawal->user) ? $withdrawal->user->first_name.' '.$withdrawal->user->last_name :"-" }}</td>

                        <td>{{ formatNumber($withdrawal->amount) }}</td>

                        <td>{{ ($withdrawal->charge_percentage == 0) && ($withdrawal->charge_fixed == 0) ? '-' : formatNumber($withdrawal->charge_percentage + $withdrawal->charge_fixed) }}</td>

                        <td>{{ '-'.formatNumber($withdrawal->amount + ($withdrawal->charge_percentage + $withdrawal->charge_fixed)) }}</td>

                        <td>{{ $withdrawal->currency->code }}</td>

                        <td>{{ ($withdrawal->payment_method->name == "Mts") ? getCompanyName() : $withdrawal->payment_method->name }}</td>

                        @php
                            if ($withdrawal->payment_method->name != "Bank")
                            {
                                $payment_method_info_withdrawal =  !empty($withdrawal->payment_method_info) ? $withdrawal->payment_method_info : '-';
                            }
                            else
                            {
                                $payment_method_info_withdrawal = !empty($withdrawal->withdrawal_detail) ?
                                $withdrawal->withdrawal_detail->account_name.' '.'('.('*****'.substr($withdrawal->withdrawal_detail->account_number,-4)).')'.' '.$withdrawal->withdrawal_detail->bank_name : '-';
                            }
                        @endphp
                        <td>{{ $payment_method_info_withdrawal }}</td>

                        <td>{{ ($withdrawal->status == 'Blocked') ? 'Cancelled' : $withdrawal->status }}</td>

                    </tr>
                    @endforeach

                </table>
            </div>
        </div>
    </body>
</html>
