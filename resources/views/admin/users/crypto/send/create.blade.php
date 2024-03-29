@extends('admin.layouts.master')

@section('title', 'Crypto Send')

@section('head_style')
@endsection

@section('page_content')
    <div class="box">
       <div class="panel-body">
            <ul class="nav nav-tabs cus" role="tablist">
                <li class="active">
                  <a href='{{url("admin/users/edit/$users->id")}}'>Profile</a>
                </li>

                <li>
                  <a href="{{url("admin/users/transactions/$users->id")}}">Transactions</a>
                </li>
                <li>
                  <a href="{{url("admin/users/wallets/$users->id")}}">Wallets</a>
                </li>
                <li>
                  <a href="{{url("admin/users/tickets/$users->id")}}">Tickets</a>
                </li>
                <li>
                  <a href="{{url("admin/users/disputes/$users->id")}}">Disputes</a>
                </li>
           </ul>
          <div class="clearfix"></div>
       </div>
    </div>

    <div class="row">
        <div class="col-md-2">
            &nbsp;&nbsp;&nbsp;&nbsp;<button style="margin-top: 15px;"  type="button" class="btn button-secondary btn-flat active">Crypto Send</button>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-4">
            <div class="pull-right">
                <h3>{{ $users->first_name.' '.$users->last_name }}</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">

                <form action="{{ url("admin/users/crypto/send/$users->id") }}" class="form-horizontal" id="admin-crypto-send-form" method="POST">
                    <input type="hidden" value="{{csrf_token()}}" name="_token" id="token">
                    <input type="hidden" name="user_id" id="user_id" value="{{ $users->id }}">

                        <div class="box-body">

                            <!-- Networks -->
                            <div class="form-group" id="network-div">
                                <label class="col-sm-3 control-label" for="network">Crypto Currency</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="network" id="network">
                                        @foreach ($activeCryptoCurrencies as $key => $value)
                                            <option value='{{ $value }}'>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </input>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- sweetalert -->
<script src="{{asset('public/user_dashboard/js/sweetalert/sweetalert-unpkg.min.js')}}" type="text/javascript"></script>

<!-- restrictNumberToEightdecimals -->
@include('common.restrict_number_to_eight_decimal')

<script type="text/javascript">

    //form - starts
    var network;
    var merchantAddress;
    var userAddress;
    var amount;
    //form - ends

    var userAddressErrorFlag = false;
    var amountErrorFlag = false;

    function checkSubmitBtn()
    {
        if (!userAddressErrorFlag && !amountErrorFlag)
        {
            $('#admin-crypto-send-submit-btn').attr("disabled", false);
        }
        else
        {
            $('#admin-crypto-send-submit-btn').attr("disabled", true);
        }
    }

    //Get merchant network address, merchant network balance and user network address
    function getMerchantAndUserNetworkAddressWithMerchantBalance(network)
    {
        $.ajax(
        {
            url: SITE_URL+"/admin/users/deposit/crypto/send/get-merchant-user-network-address-with-merchant-balance",
            type: "get",
            dataType: 'json',
            data:
            {
                'network': network,
                'user_id': '{{ $users->id }}',
            },
            beforeSend: function ()
            {
                swal("{{__('Please Wait')}}", "{{__('Loading...')}}", {
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                    buttons: false,
                });
            },
        })
        .done(function(res)
        {
            // console.log(res);

            if (res.status == 400)
            {
                $('.amount-validation-error').text(res.message);
                userAddressErrorFlag = true;
                amountErrorFlag = true;
                checkSubmitBtn();

                swal({
                    title: "Error!",
                    text:  res.message,
                    icon: "error",
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                });
            }
            else
            {
                //merchant-address-div
                $('#network-div').after( `<div class="form-group" id="merchant-address-div">
                    <label class="col-sm-3 control-label" for="merchant-address">Merchant Address</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="merchantAddress" id="merchant-address" value="${res.merchantAddress}"/>
                    </div>
                </div>`);

                //merchant-balance-div
                $('#merchant-address-div').after( `<div class="form-group" id="merchant-balance-div">
                    <label class="col-sm-3 control-label" for="merchant-balance">Merchant Balance</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="merchantBalance" id="merchant-balance" value="${res.merchantAddressBalance}"/>
                    </div>
                </div>`);

                //user-address-div
                $('#merchant-balance-div').after( `<div class="form-group" id="user-address-div">
                    <label class="col-sm-3 control-label" for="user-address">User Address</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="userAddress" id="user-address" value="${res.userAddress}"/>
                    </div>
                </div>`);

                var previousCrytoSentUrl = window.localStorage.getItem("previousCrytoSentUrl");
                var confirmationCryptoSentUrl = SITE_URL+`/admin/users/crypto/send/${'{{ $users->id }}'}`;
                var cryptoSendAmount = window.localStorage.getItem('crypto-sent-amount');
                if ((confirmationCryptoSentUrl == previousCrytoSentUrl) && cryptoSendAmount != null)
                {
                    //amount-div
                    $('#user-address-div').after( `<div class="form-group" id="amount-div">
                        <label class="col-sm-3 control-label require" for="Amount">Amount</label>
                        <div class="col-sm-6" id="amount-input-div">
                            <input type="text" class="form-control amount" name="amount" placeholder="0.00000000" id="amount" value="${cryptoSendAmount}" oninput="restrictNumberToEightdecimals(this)"/>
                            <span class="amount-validation-error" style="color: red;font-weight: bold"></span>
                        </div>
                    </div>`);

                    //Get network fees
                    checkMerchantAmountValidity($('.amount').val().trim(), $("#merchant-address").val().trim(), $("#user-address").val().trim(), network)

                    $("#admin-crypto-send-submit-btn").attr("disabled", false);
                    $(".fa-spin").hide();
                    $("#admin-crypto-send-submit-btn-text").html(`Next&nbsp;<i class="fa fa-angle-right"></i>`);
                    window.localStorage.removeItem('crypto-sent-amount');
                    window.localStorage.removeItem('previousCrytoSentUrl');
                }
                else
                {
                    //amount-div
                    $('#user-address-div').after( `<div class="form-group" id="amount-div">
                        <label class="col-sm-3 control-label require" for="Amount">Amount</label>
                        <div class="col-sm-6" id="amount-input-div">
                            <input type="text" class="form-control amount" name="amount" placeholder="0.00000000" id="amount" onkeyup="this.value = this.value.replace(${RegExp(/^\.|[^\d\.]/g)}, '')"
                            oninput="restrictNumberToEightdecimals(this)"/>
                            <span class="amount-validation-error" style="color: red;font-weight: bold"></span>
                        </div>
                    </div>`);
                }

                if (network == 'DOGE' || network == 'DOGETEST')
                {
                    $('.amount-validation-error').after(`<div class="clearfix"></div>
                    <small class="form-text text-muted" style="font-size: 14px !important;"><b>*Crypto transactions might take few moments to complete.</b></small><br/>
                    <small class="form-text text-muted"><b>*The amount withdrawn/sent must at least be 2 ${network}.</b></small><br/>
                    <small class="form-text text-muted"><b>*Please keep at least 1 ${network} for network fees.</b></small><br/>
                    <small class="form-text text-muted"><b>*Allowed upto 8 decimal places.</b></small>
                    `);
                }
                else if (network == 'BTC' || network == 'BTCTEST')
                {
                    $('.amount-validation-error').after(`<div class="clearfix"></div>
                    <small class="form-text text-muted" style="font-size: 14px !important;"><b>*Crypto transactions might take few moments to complete.</b></small><br/>
                    <small class="form-text text-muted"><b>*The amount withdrawn/sent must at least be 0.00002 ${network}.</b></small><br/>
                    <small class="form-text text-muted"><b>*Please keep at least 0.0002 ${network} for network fees.</b></small><br/>
                    <small class="form-text text-muted"><b>*Allowed upto 8 decimal places.</b></small>
                    `);
                }
                else if (network == 'LTC' || network == 'LTCTEST')
                {
                    $('.amount-validation-error').after(`<div class="clearfix"></div>
                    <small class="form-text text-muted" style="font-size: 14px !important;"><b>*Crypto transactions might take few moments to complete.</b></small><br/>
                    <small class="form-text text-muted"><b>*The amount withdrawn/sent must at least be 0.0002 ${network}.</b></small><br/>
                    <small class="form-text text-muted"><b>*Please keep at least 0.0001 ${network} for network fees.</b></small><br/>
                    <small class="form-text text-muted"><b>*Allowed upto 8 decimal places.</b></small>
                    `);
                }

                //submit-anchor-div
                var cancelUrl = '{{ url('admin/users/edit/'. $users->id) }}';
                $('#amount-div').after( `<div class="form-group" id="submit-anchor-div">
                    <label class="col-sm-3"></label>
                    <div class="col-sm-6">
                        <a href="${cancelUrl}" class="btn button-secondary"><span><i class="fa fa-angle-left"></i>&nbsp;Back</span></a>
                        <button type="submit" class="btn button-secondary pull-right" id="admin-crypto-send-submit-btn">
                            <i class="fa fa-spinner fa-spin" style="display: none;"></i>
                            <span id="admin-crypto-send-submit-btn-text">Next&nbsp;<i class="fa fa-angle-right"></i></span>
                        </button>
                    </div>
                </div>`);

                $('#merchant-address, #merchant-balance, #user-address').attr('readonly', true);

                $('.amount-validation-error').text('');
                userAddressErrorFlag = false;
                amountErrorFlag = false;
                checkSubmitBtn();

                // Set focus on amount
                $("#amount").focus();

                //close swal
                swal.close();
            }
        })
        .fail(function(error)
        {
            // console.log(JSON.parse(error.responseText).exception);
            swal({
                title: "Error!",
                text:  JSON.parse(error.responseText).exception,
                icon: "error",
                closeOnClickOutside: false,
                closeOnEsc: false,
            });
        });
    }

    //Check Minimum Amount
    function checkMinimumAmount(message)
    {
        $('.amount-validation-error').text(message);
        userAddressErrorFlag = true;
        amountErrorFlag = true;
        checkSubmitBtn();
    }

    //Check Amount Validity
    function checkMerchantAmountValidity(amount, merchantAddress, userAddress, network)
    {
        // console.log(amount, merchantAddress, userAddress, network)

        if ((network == 'DOGE' || network == 'DOGETEST') && amount < 2)
        {
            checkMinimumAmount(`The minimum amount must be 2 ${network}`)
        }
        else if ((network == 'BTC' || network == 'BTCTEST') && amount < 0.00002)
        {
            checkMinimumAmount(`The minimum amount must be 0.00002 ${network}`)
        }
        else if ((network == 'LTC' || network == 'LTCTEST') && amount < 0.0002)
        {
            checkMinimumAmount(`The minimum amount must be 0.0002 ${network}`)
        }
        else
        {
            $('.amount-validation-error').text('');
            userAddressErrorFlag = false;
            amountErrorFlag = false;
            checkSubmitBtn();

            $.ajax(
            {
                method: "GET",
                url: SITE_URL + "/admin/users/deposit/crypto/validate-merchant-address-balance",
                dataType: "json",
                data:
                {
                    'network': network,
                    'amount': amount,
                    'merchantAddress': merchantAddress,
                    'userAddress': userAddress,
                },
            })
            .done(function(res)
            {
                // console.log(res.status);
                if (res.status == 400)
                {
                    $('.amount-validation-error').text(res.message);
                    userAddressErrorFlag = true;
                    amountErrorFlag = true;
                    checkSubmitBtn();
                }
            })
            .fail(function(err)
            {
                console.log(err);
            });
        }
    }

    $(window).on('load', function (e)
    {
        $("#network").select2({});

        //Get merchant network address, merchant network balance and user network address
        network = $('#network').val();
        getMerchantAndUserNetworkAddressWithMerchantBalance(network);
    });

    //Get merchant network address, merchant network balance and user network address
    $(document).on('change', '#network', function (e)
    {
        //Remove merchant address, merchant balance and amount div on change of network
        $('#merchant-address-div, #merchant-balance-div, #user-address-div, #amount-div, #submit-anchor-div').remove();

        //Get admin address balance
        network = $(this).val();
        getMerchantAndUserNetworkAddressWithMerchantBalance(network);
    });

    //Validate Amount
    $(document).on('blur', '.amount', function ()
    {
        //Get amount
        network = $('#network').val();
        merchantAddress = $("#merchant-address").val().trim();
        userAddress = $("#user-address").val().trim();
        amount = $(this).val().trim();

        if (amount.length > 0 && !isNaN(amount))
        {
            checkMerchantAmountValidity(amount, merchantAddress, userAddress, network)
        }
        else
        {
            $('.amount-validation-error').text('');
            userAddressErrorFlag = false;
            amountErrorFlag = false;
            checkSubmitBtn();
        }
    });

    $('#admin-crypto-send-form').validate({
        rules: {
            amount: {
                required: true,
                number: true,
            },
        },
        submitHandler: function (form, e)
        {
            e.preventDefault();

            //Set amount to localstorage for showing on create page on going back from confirm page
            window.localStorage.setItem("crypto-sent-amount", $('.amount').val().trim());

            $("#admin-crypto-send-submit-btn").attr("disabled", true);
            $(".fa-spin").show();
            $("#admin-crypto-send-submit-btn-text").text('Sending...');
            form.submit();
        }
    });

</script>

@endpush