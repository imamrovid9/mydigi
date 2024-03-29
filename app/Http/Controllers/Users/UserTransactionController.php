<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Transaction_referral;
use App\Models\Transaction_point;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Repositories\CryptoCurrencyRepository;
use Auth;
use Illuminate\Http\Request;

class UserTransactionController extends Controller
{
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->cryptoCurrency = new CryptoCurrencyRepository();
    }

    public function index()
    {
        $transaction      = new Transaction();
        $data['menu']     = 'transactions';
        $data['sub_menu'] = 'transactions';

        $status = 'all';
        $type   = 'all';
        $wallet = 'all';

        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        }

        if (isset($_GET['wallet'])) {
            $wallet = $_GET['wallet'];
        }

        if (isset($_GET['type'])) {
            $type = $_GET['type'];
        }

        if (isset($_GET['from'])) {
            $from = $_GET['from'];
        } else {
            $from = null;
        }

        if (isset($_GET['to'])) {
            $to = $_GET['to'];
            $to = date("d-m-Y", strtotime($to));
        } else {
            $to = null;
        }
        $data['from'] = $from;
        $data['to']   = $to;

        $data['transactions'] = $transaction->getTransactions($from, $to, $type, $wallet, $status);
        $data['status']       = $status;
        $data['wallet']       = $wallet;
        $data['wallets']      = Wallet::with(['currency:id,code'])->where(['user_id' => Auth::user()->id])->get(['currency_id']);
        if ($type == Deposit || $type == Withdrawal || $type == 'all') {
            $data['type'] = $type;
        } else {
            switch ($type) {
                case 'sent':
                    $data['type'] = 'sent';
                    break;

                case 'request':
                    $data['type'] = 'request';
                    break;

                case 'received':
                    $data['type'] = 'received';
                    break;

                case 'exchange':
                    $data['type'] = 'exchange';
                    break;

                case 'crypto_sent':
                    $data['type'] = 'crypto_sent';
                    break;

                case 'crypto_received':
                    $data['type'] = 'crypto_received';
                    break;
            }
        }
        return view('user_dashboard.transactions.index', $data);
    }




    public function referral()
    {
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        } else {
            $status = 'all';
        }
        if (isset($_GET['from'])) {
            $from = $_GET['from'];
            $from = date("d-m-Y", strtotime($from));
            $fromasd = date("Y-m-d", strtotime($from));
        } else {
            $from = null;
        }
        if (isset($_GET['to'])) {
            $to = $_GET['to'];
            $to = date("d-m-Y", strtotime($to));
            $fromas = date("Y-m-d", strtotime($to));
        } else {
            $to = null;
        }

        $data['menu']     = 'referral';
        $data['sub_menu'] = 'referral';
        $data['from'] = $from;
        $data['to']   = $to;
        $data['status']   = $status;



        if ($status == "Blocked") {
            $status = "Cancelled";
        }

        if ($status !== "all") {
            if ($from == null or $to == null) {
                $data['transactionreferral']      = Transaction_referral::where(['user_id' => Auth::user()->id])->where(['status' => $status])->get();
            } elseif ($from != null or $to != null) {
                $data['transactionreferral']      = Transaction_referral::where(['user_id' => Auth::user()->id])->where(['status' => $status])->whereDate('created_at', '>=', $fromasd)->whereDate('created_at', '<=', $fromas)->get();
            }
        } elseif ($from != null or $to != null && $status == "all") {
            $data['transactionreferral']  = Transaction_referral::where(['user_id' => Auth::user()->id])->whereDate('created_at', '>=', $fromasd)->whereDate('created_at', '<=', $fromas)->get();
        } else {
            $data['transactionreferral']      = Transaction_referral::where(['user_id' => Auth::user()->id])->get();
        }
        return view('user_dashboard.transactions.referral', $data);
    }

    public function point()
    {
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        } else {
            $status = 'all';
        }
        if (isset($_GET['from'])) {
            $from = $_GET['from'];
            $from = date("d-m-Y", strtotime($from));
            $fromasd = date("Y-m-d", strtotime($from));
        } else {
            $from = null;
        }
        if (isset($_GET['to'])) {
            $to = $_GET['to'];
            $to = date("d-m-Y", strtotime($to));
            $fromas = date("Y-m-d", strtotime($to));
        } else {
            $to = null;
        }

        $data['menu']     = 'point';
        $data['sub_menu'] = 'point';
        $data['from'] = $from;
        $data['to']   = $to;
        $data['status']   = $status;



        if ($status == "Blocked") {
            $status = "Cancelled";
        }

        if ($status !== "all") {
            if ($from == null or $to == null) {
                $data['transactionpoint']      = Transaction_point::where(['user_id' => Auth::user()->id])->where(['status' => $status])->get();
            } elseif ($from != null or $to != null) {
                $data['transactionpoint']      = Transaction_point::where(['user_id' => Auth::user()->id])->where(['status' => $status])->whereDate('created_at', '>=', $fromasd)->whereDate('created_at', '<=', $fromas)->get();
            }
        } elseif ($from != null or $to != null && $status == "all") {
            $data['transactionpoint']  = Transaction_point::where(['user_id' => Auth::user()->id])->whereDate('created_at', '>=', $fromasd)->whereDate('created_at', '<=', $fromas)->get();
        } else {
            $data['transactionpoint']      = Transaction_point::where(['user_id' => Auth::user()->id])->get();
        }
        return view('user_dashboard.transactions.point', $data);
    }









    public function getTransaction(Request $request)
    {
        $data['status'] = 0;

        $transaction = Transaction::with([
            'payment_method:id,name',
            'transaction_type:id,name',
            'currency:id,code,symbol',
            'transfer:id,sender_id',
            'transfer.sender:id,first_name,last_name',
            'end_user:id,first_name,last_name',
            'merchant:id,business_name',
            'cryptoapi_log:id,object_id,payload,confirmations',
        ])->find($request->id);
        // dd($transaction->id);

        // Get crypto api log details for Crypto_Sent & Crypto_Received (via custom relationship)
        if (!empty($transaction->cryptoapi_log)) {
            $getCryptoDetails = $this->cryptoCurrency->getCryptoPayloadConfirmationsDetails($transaction->transaction_type_id, $transaction->cryptoapi_log->payload, $transaction->cryptoapi_log->confirmations);
            if (count($getCryptoDetails) > 0) {
                // For "Tracking block io account receiver address changes, if amount is sent from other payment gateways like CoinBase, CoinPayments, etc"
                if (isset($getCryptoDetails['senderAddress'])) {
                    $senderAddress   = $getCryptoDetails['senderAddress'];
                }
                if (isset($getCryptoDetails['receiverAddress'])) {
                    $receiverAddress = $getCryptoDetails['receiverAddress'];
                }
                $confirmations   = $getCryptoDetails['confirmations'];
            }
        }

        if ($transaction->count() > 0) {
            switch ($transaction->transaction_type_id) {
                case Deposit:
                    if ($transaction->payment_method->name == 'Mts') {
                        $pm = getCompanyName();
                    } else {
                        $pm = $transaction->payment_method->name;
                    }
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.deposit.deposited-to') . "</label>" .
                        "<div class=''>" . $transaction->currency->code . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.deposit.payment-method') . "</label>" .
                        "<div  class=''>" . $pm . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.deposit.deposited-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->subtotal)) . "</div>" . //r2
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left '>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total - $transaction->subtotal)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('deposit-money/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('deposit-money/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    }
                    break;

                case Withdrawal:
                    if ($transaction->payment_method->name == 'Mts') {
                        $pm = getCompanyName();
                    } else {
                        $pm = $transaction->payment_method->name;
                    }
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.withdrawal.withdrawan-with') . "</label>" .
                        "<div  class=''>" . $pm . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.withdrawal.withdrawan-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left '>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($fee)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('withdrawal-money/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('withdrawal-money/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    }
                    break;

                case Transferred:
                    $userEmail = '';
                    if ($transaction->user_type == 'unregistered') {
                        $userEmail = "<br><label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.email') . "</label>" .
                            "<div>" . $transaction->email . "</div>";
                    }
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.paid-with') . "</label>" .
                        "<div class=''>" . $transaction->currency->code . "</div>" . $userEmail .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left'>" . __('message.dashboard.left-table.transferred.transferred-amount') . "</div>" .
                        "<div class='right'>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left'>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right'>" . moneyFormat($transaction->currency->symbol, formatNumber($fee)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left'><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right'><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                            "<div  class='act-detail-font'>" . $transaction->note . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('moneytransfer/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left'><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right'><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                            "<div  class='act-detail-font'>" . $transaction->note . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('moneytransfer/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    }
                    break;

                case Received:
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.received.paid-by') . "</label>" .
                        "<div class=''>" . $transaction->transfer->sender->first_name . ' ' . $transaction->transfer->sender->last_name . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.received.received-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->subtotal)) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                        "<div class='act-detail-font'>" . $transaction->note . "</div>" .
                        "</div>" .

                        "<div class='form-group trans_details'>" .
                        "<a href='" . url('moneytransfer/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                        "</div>";
                    break;

                case Exchange_From:
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.exchange-from.from-wallet') . "</label>" .
                        "<div class=''>" . $transaction->currency->code . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.exchange-from.exchange-from-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left '>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($fee)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('transactions/exchangeTransactionPrintPdf/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('transactions/exchangeTransactionPrintPdf/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    }
                    break;

                case Exchange_To:
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.exchange-to.to-wallet') . "</label>" .
                        "<div class=''>" . $transaction->currency->code . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.exchange-from.exchange-from-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->subtotal)) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<a href='" . url('transactions/exchangeTransactionPrintPdf/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                        "</div>";
                    break;

                case Request_From:
                    $conditionForRequestToPhoneAndEMail = !empty($transaction->email) ? $transaction->email : $transaction->phone;
                    $cancel_btn                         = '';
                    if ($transaction->status == 'Pending') {
                        $cancel_btn = "<button class='btn btn-secondary btn-sm trxnreqfrom' data-notificationType='{$conditionForRequestToPhoneAndEMail}' data='{$transaction->id}' data-type='{$transaction->transaction_type_id}' id='btn_{$transaction->id}'>" . __('message.form.cancel') . "</button>";
                    }
                    if ($transaction->user_type == 'registered') {
                        $data['html'] = "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.form.name') . "</label>" .
                            "<div class=''>" . $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name . "</div>" .
                            "</div>";
                    } else {
                        $data['html'] = "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.email') . "</label>" .
                            "<div class=''>" . $transaction->email . "</div>" .
                            "</div>";
                    }
                    $data['html'] .= "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.send-request.request.confirmation.requested-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                        "<div  class='act-detail-font'>" . $transaction->note . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<a href='" . url('request-payment/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" . $cancel_btn .
                        "</div>";
                    break;

                case Request_To:
                    $conditionForRequestToPhoneAndEMail = !empty($transaction->email) ? $transaction->email : $transaction->phone;
                    $twoButtons                         = '';
                    //
                    if ($transaction->status == 'Pending') {
                        $twoButtons = "<button class='btn btn-secondary btn-sm trxn' data-notificationType='{$conditionForRequestToPhoneAndEMail}' data='{$transaction->id}' data-type='{$transaction->transaction_type_id}'
                        id='btn_{$transaction->id}'>" . __('message.form.cancel') . "</button>";

                        $twoButtons .= " <button class='btn btn-secondary btn-sm trxn_accept' data-rel='" . $transaction->transaction_reference_id . "' data='" . $transaction->id . "' id='acceptbtn_" . $transaction->id . "'> " . __('message.dashboard.left-table.request-to.accept') . " </button>";
                    }
                    //
                    if ($transaction->user_type == 'registered') {
                        $data['html'] = "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.form.name') . "</label>" .
                            "<div class=''>" . $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name . "</div>" .
                            "</div>";
                    } else {
                        $data['html'] = "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.email') . "</label>" .
                            "<div class=''>" . $transaction->email . "</div>";
                    }
                    $data['html'] .= "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.send-request.request.confirmation.requested-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left '>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->charge_percentage + $transaction->charge_fixed)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                            "<div class='act-detail-font'>" . $transaction->note . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('request-payment/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" . $twoButtons .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transferred.note') . "</label>" .
                            "<div class='act-detail-font'>" . $transaction->note . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('request-payment/print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" . $twoButtons .
                            "</div>";
                    }
                    break;

                case Payment_Sent:
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.merchant.payment.merchant') . "</label>" .
                        "<div class=''>" . $transaction->merchant->business_name . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.payment-Sent.payment-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->total))) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group'>" .
                        "<a href='" . url('transactions/merchant-payment-print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                        "</div>";
                    break;

                case Payment_Received:
                    $data['html'] = "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div class=''>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.payment-Sent.payment-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber(abs($transaction->subtotal))) . "</div>" .
                        "<div class='clearfix'></div>";
                    $fee = abs($transaction->total) - abs($transaction->subtotal);
                    if ($fee > 0) {
                        $data['html'] .= "<div class='left '>" . __('message.dashboard.left-table.fee') . "</div>" .
                            "<div class='right '>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->charge_percentage + $transaction->charge_fixed)) . "</div>" .
                            "<div class='clearfix'></div>" .
                            "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('transactions/merchant-payment-print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    } else {
                        $data['html'] .= "<hr/>" .
                            "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                            "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, formatNumber($transaction->total)) . "</strong></div>" .
                            "<div class='clearfix'></div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>" .
                            "<a href='" . url('transactions/merchant-payment-print/' . $transaction->id) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                            "</div>";
                    }
                    break;

                case Crypto_Sent:
                    $data['html'] = "";

                    if (isset($receiverAddress)) {
                        $data['html'] .= "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.crypto.transactions.receiver-address') . "</label>" .
                            "<div>" . $receiverAddress . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>";
                    }

                    if (isset($confirmations)) {
                        $data['html'] .= "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.crypto.transactions.confirmations') . "</label>" .
                            "<div>" . $confirmations . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>";
                    }

                    $data['html'] .= "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.crypto.send.confirm.sent-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, $transaction->subtotal) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.crypto.send.confirm.network-fee') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, $transaction->charge_fixed) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, $transaction->total) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group'>" .
                        "<a href='" . url('transactions/crypto-sent-received-print/' . encrypt($transaction->id)) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                        "</div>";
                    break;

                case Crypto_Received:
                    $data['html'] = "";

                    // For "Tracking block io account receiver address changes, if amount is sent from other payment gateways like CoinBase, CoinPayments, etc"
                    if (isset($senderAddress)) {
                        $data['html'] .= "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.crypto.transactions.sender-address') . "</label>" .
                            "<div>" . $senderAddress . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>";
                    }

                    if (isset($confirmations)) {
                        $data['html'] .= "<div class='form-group trans_details'>" .
                            "<label for='exampleInputEmail1'>" . __('message.dashboard.crypto.transactions.confirmations') . "</label>" .
                            "<div>" . $confirmations . "</div>" .
                            "</div>" .
                            "<div class='form-group trans_details'>";
                    }

                    $data['html'] .= "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.transaction-id') . "</label>" .
                        "<div>" . $transaction->uuid . "</div>" .
                        "</div>" .
                        "<div class='form-group trans_details'>" .
                        "<div class='form-group trans_details'>" .
                        "<label for='exampleInputEmail1'>" . __('message.dashboard.left-table.details') . "</label>" .
                        "<div class='clearfix'></div>" .
                        "<div class='left '>" . __('message.dashboard.left-table.received.received-amount') . "</div>" .
                        "<div class='right '>" . moneyFormat($transaction->currency->symbol, $transaction->subtotal) . "</div>" .
                        "<div class='clearfix'></div>" .
                        "<hr/>" .
                        "<div class='left '><strong>" . __('message.dashboard.left-table.total') . "</strong></div>" .
                        "<div class='right '><strong>" . moneyFormat($transaction->currency->symbol, $transaction->subtotal) . "</strong></div>" .
                        "<div class='clearfix'></div>" .
                        "</div>" .
                        "<div class='form-group'>" .
                        "<a href='" . url('transactions/crypto-sent-received-print/' . encrypt($transaction->id)) . "' target='_blank' class='btn btn-secondary btn-sm'>" . __('message.dashboard.vouchers.success.print') . "</a> &nbsp;&nbsp;" .
                        "</div>";
                    break;

                default:
                    $data['html'] = '';
                    break;
            }
        }
        return json_encode($data);
    }

    /**
     * Generate pdf print for exchangeTransaction entries
     */
    public function exchangeTransactionPrintPdf($id)
    {
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);

        // $data['transaction'] = $transaction = Transaction::where(['id' => $id])->first();
        $data['transaction'] = $transaction = Transaction::with([
            'currency:id,code,symbol',
        ])->where(['id' => $id])->first();
        // dd($transaction);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.transactions.exchangeTransactionPrintPdf', $data));
        $mpdf->Output('exchange_' . time() . '.pdf', 'I'); // this will output data
    }

    /**
     * Generate pdf print for merchant payment entries
     */
    public function merchantPaymentTransactionPrintPdf($id)
    {
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);

        $data['transaction'] = $transaction = Transaction::with([
            'merchant:id,business_name',
            'currency:id,symbol',
        ])->where(['id' => $id])->first();

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.transactions.merchantPaymentTransactionPrintPdf', $data));
        $mpdf->Output('merchant-payment_' . time() . '.pdf', 'I'); // this will output data
    }

    /**
     * Generate pdf print for crypto sent & received
     */
    public function cryptoSentReceivedTransactionPrintPdf($id)
    {
        $id                  = decrypt($id);
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);
        $data['transaction'] = $transaction = Transaction::with(['currency:id,symbol', 'cryptoapi_log:id,object_id,payload,confirmations'])->where(['id' => $id])->first();

        // Get crypto api log details for Crypto_Sent & Crypto_Received (via custom relationship)
        if (!empty($transaction->cryptoapi_log)) {
            $getCryptoDetails = $this->cryptoCurrency->getCryptoPayloadConfirmationsDetails($transaction->transaction_type_id, $transaction->cryptoapi_log->payload, $transaction->cryptoapi_log->confirmations);
            if (count($getCryptoDetails) > 0) {
                // For "Tracking block io account receiver address changes, if amount is sent from other payment gateways like CoinBase, CoinPayments, etc"
                if (isset($getCryptoDetails['senderAddress'])) {
                    $data['senderAddress']   = $getCryptoDetails['senderAddress'];
                }
                if (isset($getCryptoDetails['receiverAddress'])) {
                    $data['receiverAddress'] = $getCryptoDetails['receiverAddress'];
                }
                $data['confirmations']   = $getCryptoDetails['confirmations'];
            }
        }

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.transactions.crypto_sent_received', $data));
        $mpdf->Output('crypto-sent-received_' . time() . '.pdf', 'I'); // this will output data
    }
}

//DB transaction template below
///////////////////////////////////////////////////////////////
/* WITH MAIL ROLLBACK
try
{
\DB::beginTransaction();

//Save to tables

//Mail or SMS try catch
try
{
//send mail or sms
}
catch (\Exception $e)
{
\DB::rollBack();
clearActionSession();
$this->helper->one_time_message('error', $e->getMessage());
return redirect('');
}

\DB::commit();
// return;
}
catch (\Exception $e)
{
\DB::rollBack();
$this->helper->one_time_message('error', $e->getMessage());
return redirect('');
}
 */

///////////////////////////////////////////////////////////////
/* USUAL APPROACH
try
{
\DB::beginTransaction();
\DB::commit();
}
catch (\Exception $e)
{
\DB::rollBack();
$this->helper->one_time_message('error', $e->getMessage());
return redirect('');
}
 */
