@extends('user_dashboard.layouts.app')

@section('css')
    <!--daterangepicker-->
    <link rel="stylesheet" type="text/css" href="{{asset('public/user_dashboard/css/daterangepicker.css')}}">
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">Transaction point</h4>
                        </div>

                        <div style="margin: 15px 15px 15px 10px;">

                            <form action="" method="get">
                                <input id="startfrom" type="hidden" name="from" value="{{ isset($from) ? $from : '' }}">
                                <input id="endto" type="hidden" name="to" value="{{ isset($to) ? $to : '' }}">
                                <div class="">
                                    <div class="filter_panel">
                                        <div class="daterange_btn" id="daterange-btn" style="width: 100%;">
                                            <span id="drp" style="text-align: left; "><i class="fa fa-calendar"></i> @lang('message.dashboard.transaction.date-range')</span>
                                        </div>
                                    </div>
                                   
                                    <div class="filter_panel">
                                        <select class="form-control" id="status" name="status">
                                            <option value="all" <?= ($status == 'all') ? 'selected' : '' ?>>@lang('message.dashboard.transaction.all-status')
                                            </option>
                                            <option value="Success" <?= ($status == 'Success') ? 'selected' : '' ?>>
                                                @lang('message.dashboard.transaction.success')
                                            </option>
                                            <option value="Pending" <?= ($status == 'Pending') ? 'selected' : '' ?>>
                                                @lang('message.dashboard.transaction.pending')
                                            </option>
                                            <option value="Blocked" <?= ($status == 'Blocked') ? 'selected' : '' ?>>
                                                @lang('message.dashboard.transaction.blocked')
                                            </option>
                                        </select>
                                    </div>
                                   
                                    <div class="">
                                        <button type="submit" class="btn btn-cust">@lang('message.dashboard.button.filter')</button>
                                    </div>

                                </div>
                            </form>
                        </div>
                        <div>
                            <div class="table-responsive">
                                <table class="table recent_activity" text-align="left">
                                    <thead>
                                    <tr>
                                        <td></td>
                                        <td class="text-left" width="15%">
                                            <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                        {{-- <td class="text-left"><strong>&nbsp;</strong></td> --}}
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                       
                                        <td class="text-left">
                                            <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @if($transactionpoint->count()>0)
                                        @foreach($transactionpoint as $key=>$asd)
                                            <tr>
                                                <td></td>
                                                <td class="text-left date_td" width="10%">{{ ($asd->created_at) }}</td>
                                                {{-- <td class="text-left"><strong>&nbsp;</strong></td> --}}
                                                <td class="text-left">{{ $asd->note }}</td>
                                                <td class="text-left">{{ $asd->status }}</td>
                                                <td class="text-left text-success"><p>{{ "+".formatNumber($asd->amount) }}</p></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">No transaction found!</td>
                                        </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- <div class="card-footer">
                            {{ $transactionpoint->appends($_GET)->links('vendor.pagination.bootstrap-4') }}
                        </div> --}}
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection

@section('js')

    <!--daterangepicker-->
    <script src="{{asset('public/user_dashboard/js/daterangepicker.js')}}" type="text/javascript"></script>

    @include('user_dashboard.layouts.common.check-user-status')

    <script>
        $(window).on('load', function()
        {
            var sDate;
            var eDate;
            //Date range as a button
            $('#daterange-btn').daterangepicker(
                {
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment()
                },
                function (start, end) {
                    sDate = moment(start, 'MMMM D, YYYY').format('DD-MM-YYYY');
                    $('#startfrom').val(sDate);
                    eDate = moment(end, 'MMMM D, YYYY').format('DD-MM-YYYY');
                    $('#endto').val(eDate);
                    $('#daterange-btn span').html(sDate + ' - ' + eDate);
                }
            )

            var startDate = "{!! $from !!}";
            var endDate = "{!! $to !!}";
            if (startDate == '') {
                $('#daterange-btn span').html('<i class="fa fa-calendar"></i> {{ __('message.dashboard.transaction.date-range') }}');
            } else {
                $('#daterange-btn span').html(startDate + ' - ' + endDate);
            }
        });
    </script>

    @include('common.user-transactions-scripts')

@endsection