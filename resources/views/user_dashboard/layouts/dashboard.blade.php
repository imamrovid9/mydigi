@extends('user_dashboard.layouts.app')

@section('css')
    <link href="//fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    {{-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script> --}}
    <!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <style type="text/css">
        @media only screen and (min-width: 768px) {
            /*.wallet-currency-div {
                padding: 18px 12px 5px 14px !important;
            }*/
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">

            <!-- for express api merchant payment success/error message-->
            @include('user_dashboard.layouts.common.alert')
            
            {{-- @php
                $asddf = false;
            @endphp --}}
            @php
                $date = $dailycheck->dailycheck;
                $datenow = date("Y-m-d 00:00:00");
            @endphp

            @if ($dailycheck->dailycheck === NULL) 
                @php
                    $asddf = true;
                @endphp
            @elseif($date !== $datenow) 
                @php
                    $asddf = true;
                @endphp
            @else
                @php
                    $asddf = false;
                    // dd($date);
                    // dd($datenow);
                @endphp
            @endif

            
            @if ($asddf == true)
            <script type="text/javascript">
                $(window).on('load',function(){
                    $('#exampleModal').modal('show');
                });
            </script>
                {{-- {{'asdsada'}} --}}
                    
                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Daily Check <i class="fa fa-check-square-o" aria-hidden="true"></i></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            </div>
                            <div class="modal-body">
                                <div class="container">
                                    <div class="text-center align-middle">
                                        <img src="{{asset('public/dailycheck/4772.jpg')}}" style="width: 100%">
                                        <p>Get 1 Point Everyday</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="text-center align-middle">
                                    <form action="{{ url('dailycheckin') }}" method="post" class="form-horizontal" enctype="multipart/form-data" id="general_settings_form">
                                        {!! csrf_field() !!}
                                        <input type="hidden" name="ngecek" value="fdhjgrgwrhKNokMPlpdWdCfCddfCf">
                                        <button type="submit" class="btn btn-primary"><i class="fa fa-check-square-o" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
            @endif
            
           

            <div class="row">
                <div class="col-md-8 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">@lang('message.dashboard.left-table.title')</h4>
                        </div>
                        <div>
                            <div class="table-responsive">
                                <table class="table recent_activity">
                                    <thead>
                                        <tr>
                                            <td></td>
                                            <td width="25%" class="text-left">
                                                <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($transactions->count()>0)
                                            @foreach($transactions as $key=>$transaction)
                                                <tr click="0" data-toggle="collapse" data-target="#collapseRow{{$key}}" aria-expanded="false" aria-controls="collapseRow{{$key}}"
                                                    class="show_area" trans-id="{{$transaction->id}}" id="{{$key}}">

                                                    <!-- Arrow -->
                                                    <td class="text-center arrow-size">
                                                        <strong>
                                                            <i class="fa fa-arrow-circle-right text-blue"
                                                            id="icon_{{$key}}"></i>
                                                        </strong>
                                                    </td>
                                                    @if ($transaction->status == "Pending")
                                                        @php
                                                            $adapending = "yes";
                                                            // dd($adapending);
                                                        @endphp
                                                    @endif
                                                    <!-- Created At -->
                                                    <td class="text-left date_td" width="17%">{{ dateFormat($transaction->created_at) }}</td>

                                                    <!-- Transaction Type -->
                                                    @if(empty($transaction->merchant_id))

                                                        @if(!empty($transaction->end_user_id))
                                                            <td class="text-left">
                                                                @if($transaction->transaction_type_id)
                                                                    @if($transaction->transaction_type_id==Request_From)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Request Sent')</p>
                                                                    @elseif($transaction->transaction_type_id==Request_To)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Request Received')</p>

                                                                    @elseif($transaction->transaction_type_id == Transferred)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Transferred')</p>

                                                                    @elseif($transaction->transaction_type_id == Received)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Received')</p>
                                                                    @else
                                                                        <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @else

                                                           <?php
                                                                if (isset($transaction->payment_method->name))
                                                                {
                                                                    if ($transaction->payment_method->name == 'Mts')
                                                                    {
                                                                        $payment_method = getCompanyName();
                                                                    }
                                                                    else
                                                                    {
                                                                        $payment_method = $transaction->payment_method->name;
                                                                    }
                                                                }
                                                            ?>
                                                            <td class="text-left">
                                                                <p>
                                                                    @if($transaction->transaction_type->name == 'Deposit')
                                                                        @if ($transaction->payment_method->name == 'Bank')
                                                                            {{ $payment_method }} ({{ $transaction->bank->bank_name }})
                                                                        @else
                                                                            @if(!empty($payment_method))
                                                                                {{ $payment_method }}
                                                                            @endif
                                                                        @endif
                                                                    @endif

                                                                    @if($transaction->transaction_type->name == 'Withdrawal')
                                                                        @if(!empty($payment_method))
                                                                            {{ $payment_method }}
                                                                        @endif
                                                                    @endif

                                                                    @if($transaction->transaction_type->name == 'Transferred' || $transaction->transaction_type->name == 'Request_From' && $transaction->user_type = 'unregistered')
                                                                        {{ ($transaction->email) ? $transaction->email : $transaction->phone }} <!--for send money by phone - mobile app-->
                                                                    @endif
                                                                </p>

                                                                @if($transaction->transaction_type_id)
                                                                    @if($transaction->transaction_type_id==Request_From)
                                                                        <p>@lang('Request Sent')</p>
                                                                    @elseif($transaction->transaction_type_id==Request_To)
                                                                        <p>@lang('Request Received')</p>

                                                                    @elseif($transaction->transaction_type_id == Withdrawal)
                                                                        <p>@lang('Payout')</p>
                                                                    @else
                                                                        <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @endif
                                                    @else
                                                        <td class="text-left">
                                                            <p>{{ $transaction->merchant->business_name }}</p>
                                                            @if($transaction->transaction_type_id)
                                                                <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    <!-- Status -->
                                                    <td class="text-left">
                                                        <p id="status_{{$transaction->id}}">
                                                            {{
                                                                (
                                                                    ($transaction->status == 'Blocked') ? __("Cancelled") :
                                                                    (
                                                                        ($transaction->status == 'Refund') ? __("Refunded") : __($transaction->status)
                                                                    )
                                                                )
                                                                
                                                            }}
                                                        </p>
                                                    </td>

                                                    <!-- Amount -->
                                                    @if($transaction->transaction_type_id == Deposit)
                                                        @if($transaction->subtotal > 0)
                                                            <td>
                                                                <p class="text-left text-success">+{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @elseif($transaction->transaction_type_id == Payment_Received)
                                                        @if($transaction->subtotal > 0)
                                                            @if($transaction->status == 'Refund')
                                                                <td>
                                                                    <p class="text-left text-danger">-{{ formatNumber($transaction->subtotal) }}</p>
                                                                    <p class="text-left">{{ $transaction->currency->code }}</p>
                                                                </td>
                                                            @else
                                                                <td>
                                                                    <p class="text-left text-success">+{{ formatNumber($transaction->subtotal) }}</p>
                                                                    <p class="text-left">{{ $transaction->currency->code }}</p>
                                                                </td>
                                                            @endif
                                                        @elseif($transaction->subtotal == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                                
                                                            </td>
                                                        @elseif($transaction->subtotal < 0)
                                                            <td>
                                                                <p class="text-left text-danger">{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @else
                                                        @if($transaction->total > 0)
                                                            @if($transaction->transaction_type->name == "Exchange_To")    
                                                            <td>
                                                                <p class="text-left text-danger">-{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                            @else
                                                            <td>
                                                                <p class="text-left text-success">{{ $transaction->currency->type != 'fiat' ? "+".$transaction->total : "+".formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                            @endif
                                                        @elseif($transaction->total == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @elseif($transaction->total < 0)
                                                            <td>
                                                                <p class="text-left text-danger">{{ $transaction->currency->type != 'fiat' ? $transaction->total : formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @endif
                                                </tr>

                                                <tr id="collapseRow{{$key}}" class="collapse">
                                                    <td colspan="8" class="">
                                                        <div class="row activity-details" id="loader_{{$transaction->id}}"
                                                             style="min-height: 200px">
                                                            <div class="col-md-7 col-sm-12 text-left" id="html_{{$key}}"></div>
                                                            <div class="col-md-3 col-sm-12">
                                                                <div class="right">
                                                                    @if( $transaction->transaction_type_id == Payment_Sent && $transaction->status == 'Success' && !isset($transaction->dispute->id))
                                                                        <a id="dispute_{{$transaction->id}}" href="{{url('/dispute/add/').'/'.$transaction->id}}" class="btn btn-secondary btn-sm">@lang('message.dashboard.transaction.open-dispute')</a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 col-sm-12">
                                                            </div>

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6"> @lang('message.dashboard.left-table.no-transaction')</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-center ash-color"><a class="font-weight-bold" href="{{url('transactions')}}">@lang('message.dashboard.left-table.view-all')</a>
                            </div>
                        </div>
                    </div>

<br>
<br>

                            <div class="card-header">
                                <h4 class="float-left trans-inline">Recent History Referral</h4>
                            </div>
                            <div>
                                <div class="table-responsive">
                                    <table class="table recent_activity">
                                        <thead>
                                            <tr>
                                                <td></td>
                                                <td width="25%" class="text-left">
                                                    <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                                <td class="text-left">
                                                    <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                                <td class="text-left">
                                                    <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                                <td class="text-left">
                                                    <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                                    <td class="text-left">
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($transactionreferral->count()>0)
                                            @foreach ($transactionreferral as $referral)
                                            <tr>
                                                <td></td>
                                                <td class="text-left date_td" width="17%">{{ dateFormat($referral->created_at) }}</td>
                                                <td class="text-left">{{$referral->note}}</td>
                                                <td class="text-left">{{$referral->status}}</td>
                                                <td class="text-left">
                                                    <p class="text-left text-success">{{ '+'.$referral->amount }}</p>
                                                    <p class="text-left">{{ "IDR" }}</p>
                                                </td>
                                            </tr>
                                            @endforeach
                                        
                                            @else
                                                <tr>
                                                    <td colspan="6"> @lang('message.dashboard.left-table.no-transaction')</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="text-center ash-color"><a class="font-weight-bold" href="{{url('history/referral')}}">@lang('message.dashboard.left-table.view-all')</a>
                                </div>
                            </div>




                    <div class="card-header">
                        <h4 class="float-left trans-inline">Recent History Point</h4>
                    </div>
                    <div>
                        <div class="table-responsive">
                            <table class="table recent_activity">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td width="25%" class="text-left">
                                            <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                            <td class="text-left">
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($transactionpoint->count()>0)
                                    @foreach ($transactionpoint as $point)
                                    <tr>
                                        <td></td>
                                        <td class="text-left date_td" width="17%">{{ dateFormat($point->created_at) }}</td>
                                        <td class="text-left">{{$point->note}}</td>
                                        <td class="text-left">{{$point->status}}</td>
                                        <td class="text-left">
                                            <p class="text-left text-success">{{ '+'.$point->amount }}</p>
                                            <p class="text-left">{{ "POINT" }}</p>

                                        </td>
                                    </tr>
                                    @endforeach
                                   
                                    @else
                                        <tr>
                                            <td colspan="6"> @lang('message.dashboard.left-table.no-transaction')</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="text-center ash-color"><a class="font-weight-bold" href="{{url('history/point')}}">@lang('message.dashboard.left-table.view-all')</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">@lang('message.dashboard.right-table.title')</h4>
                            <div class="chart-list trans-inline float-right ">
                            </div>
                        </div>
                        <div class="wap-wed" style="width: 100%;">
                            @if($wallets->count()>0)
                                @foreach($wallets as $wallet)
                                    @php
                                        $walletCurrencyCode = encrypt(strtolower($wallet->currency->code));
                                        $walletId = encrypt($wallet->id);
                                    @endphp
                                    <div class="set-Box clearfix" style="border-bottom: 1px solid #CCCCCC;">
                                        <div class="row">
                                            <div class="col-md-12 wallet-currency-div" style="padding: 18px 25px 5px 25px;">
                                                <!--LOGO & Currency Code-->
                                                <div class="float-left" style="width: 55%;">
                                                    <!--LOGO-->
                                                    @if(empty($wallet->currency->logo))
                                                        <img src="{{asset('public/user_dashboard/images/favicon.png')}}" class="img-responsive" style="float: none;">
                                                    @else
                                                        <img src='{{asset("public/uploads/currency_logos/".$wallet->currency->logo)}}' class="img-responsive" style="float: none;">
                                                    @endif

                                                    <!--Currency Code-->
                                                    @if ($wallet->currency->type == 'fiat' && $wallet->is_default == 'Yes')
                                                        <span>{{ $wallet->currency->code }}&nbsp;<span class="badge badge-secondary">@lang('message.dashboard.right-table.default-wallet-label')</span></span>
                                                    @else
                                                        <span>{{ $wallet->currency->code }}</span>
                                                    @endif
                                                </div>
                                                <!--BALANCE-->
                                                <span class="float-right" style="position: relative;top: 7px;">
                                                    @if($wallet->balance > 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span class="text-success">{{ '+'.$wallet->balance }}</span>
                                                        @else
                                                            <span class="text-success">{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @elseif($wallet->balance == 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span>{{ $wallet->balance }}</span>
                                                        @else
                                                            <span>{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @elseif($wallet->balance < 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span class="text-danger">{{ $wallet->balance }}</span>
                                                        @else
                                                            <span class="text-danger">{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>

                                            <!--Crypto Send & Receiv Buttons-->
                                            @if ($wallet->currency->type != 'fiat' && $wallet->currency->status == 'Active')
                                                <div class="col-md-12" style="padding: 10px 44px 14px 44px;">
                                                    <div class="text-center">
                                                        <a href="{{ url("/crpto/send/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto float-left">@lang('message.dashboard.right-table.crypto-send')</a>
                                                        <a href="{{ url("/crpto/receive/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto float-right">@lang('message.dashboard.right-table.crypto-receive')</a>
                                                    </div>
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            @else
                                @lang('message.dashboard.right-table.no-wallet')
                            @endif
                            {{-- point --}}
                            <div class="set-Box clearfix" style="border-bottom: 1px solid #CCCCCC;">
                                <div class="row">
                                    <div class="col-md-12 wallet-currency-div" style="padding: 18px 25px 5px 25px;">
                                        <!--LOGO & Currency Code-->
                                        <div class="float-left" style="width: 55%;">
                                            <!--LOGO-->
                                                <img src='{{asset("public/uploads/currency_logos/haha-01.png")}}' class="img-responsive" style="float: none;">
                                                <span>{{"POINT"}}</span>
                                        </div>
                                        <!--BALANCE-->
                                        <span class="float-right" style="position: relative;top: 7px;">
                                            @php
                                                // cek xpoint
                                                $asd = auth()->user()->xpoint;
                                                // dd($asd);
                                            @endphp
                                            @if($asd > 0)
                                                    <span class="text-success">{{ '+'.formatNumber($asd) }}</span>
                                            @elseif($asd == 0)
                                                <span class="text-success">{{ '+'.formatNumber($asd) }}</span>
                                            @endif
                                        </span>
                                    </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="card-footer">
                            <div class="dash-btn row pb6">
                                <div class="left col-md-8 pb6">
                                    <small class="form-text text-muted"><strong>*Fiat Currencies Only</strong></small>
                                </div>
                            </div>

                            <div class="dash-btn row">
                                @if(Common::has_permission(auth()->id(),'manage_deposit'))
                                
                                
                                
                                @if(isset($transaction->status))
                                    

                                        @if(isset($adapending))
                                        
                                        <div class="left col-md-6 pb6">
                                            <a href="{{url('deposit')}}" class="btn btn-cust col-md-12 disabled">
                                                <img src="{{asset('public/user_dashboard/images/deposit.png')}}"
                                                    class="img-responsive" style="margin-top:3px;">
                                                &nbsp;@lang('message.dashboard.button.deposit')
                                            </a>
                                            <small id="emailHelp" class="form-text text-muted">*You Have a Pending Deposit Transaction</small>
                                        </div>
                                            @elseif($transaction->status != "Pending")
                                                <div class="left col-md-6 pb6">
                                                    <a href="{{url('deposit')}}" class="btn btn-cust col-md-12">
                                                        <img src="{{asset('public/user_dashboard/images/deposit.png')}}"
                                                            class="img-responsive" style="margin-top:3px;">
                                                        &nbsp;@lang('message.dashboard.button.deposit')
                                                    </a>
                                                </div>
                                            @endif
                                    @elseif(!isset($transaction->status))
                                        <div class="left col-md-6 pb6">
                                            <a href="{{url('deposit')}}" class="btn btn-cust col-md-12">
                                                <img src="{{asset('public/user_dashboard/images/deposit.png')}}"
                                                    class="img-responsive" style="margin-top:3px;">
                                                &nbsp;@lang('message.dashboard.button.deposit')
                                            </a>
                                        </div>
                                        
                                    @endif
                                @endif
                                @if(Common::has_permission(auth()->id(),'manage_withdrawal'))
                                    <div class="left col-md-6">
                                        <a href="{{url('payouts')}}" class="btn btn-cust col-md-12 ">
                                            <img src="{{asset('public/user_dashboard/images/withdrawal.png')}}" class="img-responsive"> &nbsp;@lang('message.dashboard.button.payout')
                                        </a>
                                    </div>
                                @endif
                                {{-- @endif --}}
                            </div>
                            <div class="clearfix"></div>

                            {{-- <div class="dash-btn row">
                                @if(Common::has_permission(auth()->id(),'manage_exchange'))
                                   
                                    
                                    <div class="center col-md-6">
                                        <a href="{{url('exchange')}}" class="btn btn-cust col-md-12">
                                            <img src="{{asset('public/user_dashboard/images/exchange.png')}}" class="img-responsive" style="margin-top:3px;">
                                            @lang('message.dashboard.button.exchange')
                                        </a>
                                    </div>
                                  
                                @endif
                            </div> --}}
                        </div>
                    </div>


                    <div class="card pt-4">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">Fun Cashback 100%</h4>
                            <div class="chart-list trans-inline float-right ">
                            </div>
                        </div>
                        <div class="dash-btn row">
                                   
                                    
                            <div class="center col-md-6 pt-4 pb-4">
                                <button type="button" class="btn btn-cust" data-toggle="modal" id="x" data-target="#exampleModal">
                                    Fun Cashback 100%
                                  </button>
                                <!-- Modal -->
                                <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Fun Cashback 100%</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container">
                                                <div class="card p-2">
                                                   

                                            <form id="depositForm1" action="{{ url('getcashback') }}" method="post" accept-charset='UTF-8'>
                                                <div class="card pb-3">
                                                    <div class="card-header">
                                                        <div class="chart-list float-left">
                                                            <ul>
                                                                <li class="">Exchange</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <div class="wap-wed mt20 mb20  ">
                                                        <input type="hidden" value="{{csrf_token()}}" name="_token" id="token">
                        
                                                        
                                                            <div class="col-md-12">
                                                                <div class="col-md-12">
                                                                    <div class="form-group">
                                                                        <label for="exampleInputPassword1">Amount</label>
                                                                        <input type="number" class="form-control amount" name="amount"
                                                                                placeholder="0" type="text" id="amount"
                                                                                onkeyup="this.value = this.value.replace (/^\.|[^\d\.]/g, '')"
                                                                                value="0">
                                                                        <span class="amountLimit" style="color: red;font-weight: bold"></span>
                                                                        <small id="emailHelp" class="form-text text-muted">Max Exhange To Get x2 Point : 800.000 IDR</small>
                                                                        <small id="emailHelp" class="form-text text-muted">You'r Total Exchange : {{formatNumber($cektotalamount)}} IDR</small>
                                                                       
                                                                    </div>
                                                                </div>
                                                            </div>
                                                    </div>
                                            
                                            

                                            


                                                </div>

                                            </div>
                                            
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary" id="funcashback">Submit</button>
                                        </form>
                                        </div>
                                        @if ($cektotalamount == 800000)
                                            <input type="hidden" id="limitss" value="limit"> 
                                        @endif

                                        <script>
                                            $(window).on('load',function()
                                            {
                                                var limit = $("#limitss").val();
                                                if(limit == 'limit'){
                                                    $('#x').attr('disabled', true);
                                                };
                                                $(document).on('input', '#amount', function()
                                                    {
                                                        getDepositFeesLimit();
                                                        // getDepositFeesLimit();
                                                    });


                                                    function getDepositFeesLimit()
                                                    {
                                                        var token = $("#token").val();
                                                        var amount = $('#amount').val().trim();
                                                        var currency_id = $('#currencies').val();
                                                        var payment_method_id = $('#payment_method option:selected').val();

                                                        if (amount != '')
                                                        {
                                                            $.ajax(
                                                            {
                                                                method: "POST",
                                                                url: SITE_URL + "/cashback",
                                                                dataType: "json",
                                                                data:
                                                                {
                                                                    "_token": token,
                                                                    'amount': amount,
                                                                }
                                                            }).done(function(response)
                                                            {
                                                                if (response.success.status == 200)
                                                                {
                                                                   
                                                                    // $("#fixed_fee").val(response.success.feesFixed);
                                                                    // $(".fee").val(response.success.totalFees);

                                                                    // $(".total_fees").html(response.success.totalFeesHtml);
                                                                    // $('.pFees').html(response.success.pFeesHtml); //2.3
                                                                    // $('.fFees').html(response.success.fFeesHtml);//2.3

                                                                    // $('.amountLimit').text('');
                                                                    // $('#deposit-money').attr('disabled', false);
                                                                    // return true;
                                                                    $('.amountLimit').text('');
                                                                        $('#funcashback').attr('disabled', false);
                                                                }
                                                                else
                                                                {
                                                                    if (amount == '')
                                                                    {
                                                                        $('.amountLimit').text('');
                                                                        $('#funcashback').attr('disabled', true);
                                                                    }
                                                                    else
                                                                    {
                                                                        $('.amountLimit').text(response.success.message);
                                                                        $('#funcashback').attr('disabled', true);
                                                                        return false;
                                                                    }
                                                                }
                                                            });
                                                        }
                                                    }
                                            }
                                           );
                                        </script>
                                    </div>
                                    </div>
                                </div>
                        </div>
                          
                    </div>
                            <div class="clearfix pt-4"></div>
                        </div>
                        <div class="card-footer">
                            <div class="dash-btn row pb6">
                                <div class="left col-md-12 pb6">
                                    <small class="form-text text-muted"><strong>*Get x2 Point, when Exchange IDR to Point Max Exchange 800.000 IDR</strong></small>
                                    @if ($cektotalamount == 800000)
                                        <small id="emailHelp" class="form-text text-muted">*You have reached the limit</small>
                                    @endif
                                </div>
                            </div>

                            <div class="dash-btn row">
                                
                            </div>
                            <div class="clearfix"></div>

                            <br>

                        </div>
                    </div>





                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')


<!-- sweetalert -->
<script src="{{asset('public/user_dashboard/js/sweetalert/sweetalert-unpkg.min.js')}}" type="text/javascript"></script>

@include('user_dashboard.layouts.common.check-user-status')

@include('common.user-transactions-scripts')

@endsection