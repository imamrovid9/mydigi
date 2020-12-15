<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\DepositsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Common;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Setting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Transaction_point;
use App\Models\Transaction_referral;

class DepositController extends Controller
{
    protected $helper;
    protected $deposit;

    public function __construct()
    {
        $this->helper  = new Common();
        $this->deposit = new Deposit();
    }

    public function index(DepositsDataTable $dataTable)
    {
        $data['menu'] = 'deposits';

        $data['d_status']     = $d_status     = $this->deposit->select('status')->groupBy('status')->get();
        $data['d_currencies'] = $d_currencies = $this->deposit->with('currency:id,code')->select('currency_id')->groupBy('currency_id')->get();
        $data['d_pm']         = $d_pm         = $this->deposit->with('payment_method:id,name')->select('payment_method_id')->whereNotNull('payment_method_id')->groupBy('payment_method_id')->get();

        if (isset($_GET['btn'])) {
            $data['status']   = $_GET['status'];
            $data['currency'] = $_GET['currency'];
            $data['pm']       = $_GET['payment_methods'];
            $data['user']     = $user     = $_GET['user_id'];

            $data['getName'] = $getName = $this->deposit->getDepositsUsersName($user);

            if (empty($_GET['from'])) {
                $data['from'] = null;
                $data['to']   = null;
            } else {
                $data['from'] = $_GET['from'];
                $data['to']   = $_GET['to'];
            }
        } else {
            $data['from']     = null;
            $data['to']       = null;
            $data['status']   = 'all';
            $data['currency'] = 'all';
            $data['pm']       = 'all';
            $data['user']     = null;
        }
        return $dataTable->render('admin.deposits.list', $data);
    }

    public function depositsUserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->deposit->getDepositsUsersResponse($search);

        $res = [
            'status' => 'fail',
        ];
        if (count($user) > 0) {
            $res = [
                'status' => 'success',
                'data'   => $user,
            ];
        }
        return json_encode($res);
    }

    public function depositCsv()
    {
        $from = !empty($_GET['startfrom']) ? setDateForDb($_GET['startfrom']) : null;

        $to = !empty($_GET['endto']) ? setDateForDb($_GET['endto']) : null;

        $status = isset($_GET['status']) ? $_GET['status'] : null;

        $pm = isset($_GET['payment_methods']) ? $_GET['payment_methods'] : null;

        $currency = isset($_GET['currency']) ? $_GET['currency'] : null;

        $user = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $data['deposits'] = $deposits = $this->deposit->getDepositsList($from, $to, $status, $currency, $pm, $user)->orderBy('id', 'desc')->get();
        // dd($deposits);

        $datas = [];
        if (!empty($deposits)) {
            foreach ($deposits as $key => $value) {
                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['User'] = isset($value->user) ? $value->user->first_name . ' ' . $value->user->last_name : "-";

                $datas[$key]['Amount'] = formatNumber($value->amount);

                $datas[$key]['Fees'] = ($value->charge_percentage == 0) && ($value->charge_fixed == 0) ? '-' : formatNumber($value->charge_percentage + $value->charge_fixed);

                $datas[$key]['Total'] = '+' . formatNumber($value->amount + ($value->charge_percentage + $value->charge_fixed));

                $datas[$key]['Currency'] = $value->currency->code;

                $datas[$key]['Payment Method'] = ($value->payment_method->name == 'Mts' ? getCompanyName() : $value->payment_method->name);

                $datas[$key]['Status'] = ($value->status == 'Blocked') ? 'Cancelled' : $value->status;
            }
        } else {
            $datas[0]['Date']           = '';
            $datas[0]['User']           = '';
            $datas[0]['Amount']         = '';
            $datas[0]['Fees']           = '';
            $datas[0]['Total']          = '';
            $datas[0]['Currency']       = '';
            $datas[0]['Payment Method'] = '';
            $datas[0]['Status']         = '';
        }
        // dd($datas);

        return Excel::create('deposit_list_' . time() . '', function ($excel) use ($datas) {
            $excel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $excel->sheet('mySheet', function ($sheet) use ($datas) {
                $sheet->cells('A1:H1', function ($cells) {
                    $cells->setFontWeight('bold');
                });
                $sheet->fromArray($datas);
            });
        })->download();
    }

    public function depositPdf()
    {
        // $data['company_logo'] = \Session::get('company_logo');
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty($_GET['startfrom']) ? setDateForDb($_GET['startfrom']) : null;

        $to = !empty($_GET['endto']) ? setDateForDb($_GET['endto']) : null;

        $status = isset($_GET['status']) ? $_GET['status'] : null;

        $pm = isset($_GET['payment_methods']) ? $_GET['payment_methods'] : null;

        $currency = isset($_GET['currency']) ? $_GET['currency'] : null;

        $user = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $data['deposits'] = $deposits = $this->deposit->getDepositsList($from, $to, $status, $currency, $pm, $user)->orderBy('id', 'desc')->get();

        if (isset($from) && isset($to)) {
            $data['date_range'] = $_GET['startfrom'] . ' To ' . $_GET['endto'];
        } else {
            $data['date_range'] = 'N/A';
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

        $mpdf->WriteHTML(view('admin.deposits.deposits_report_pdf', $data));

        $mpdf->Output('deposits_report_' . time() . '.pdf', 'D');
    }

    public function edit($id)
    {
        $data['menu']    = 'deposits';
        $data['deposit'] = $deposit = Deposit::find($id);
        // $data['deposit']
        // dd($deposit);

        $data['transaction'] = $transaction = Transaction::select('transaction_type_id', 'status', 'transaction_reference_id', 'percentage')
            ->where(['transaction_reference_id' => $deposit->id, 'status' => $deposit->status, 'transaction_type_id' => Deposit])
            ->first();
        // dd($transaction);

        return view('admin.deposits.edit', $data);
    }

    public function update(Request $request)
    {
        // dd($request->all());
        // dd($request->status);
        //Deposit
        if ($request->transaction_type == 'Deposit') {
            if ($request->status == 'Pending') //requested status
            {
                if ($request->transaction_status == 'Pending') {
                    $this->helper->one_time_message('success', 'Deposit is already Pending!');
                    return redirect('admin/deposits');
                } elseif ($request->transaction_status == 'Success') {

                    // dd('current status: Success, doing Pending');
                    if ($request->firsttimedposit == "yes") {

                        $totalmasukxpoint = $request->amount * 20 / 100;
                        $hargapoint = Setting::where(['name' => 'pricepoint'])->first(['value']);
                        $totalaslimasukpoint = $totalmasukxpoint / $hargapoint->value;
                        $totalmasuksaldo = $request->amount - $totalmasukxpoint;

                        $upline = User::where('id', $request->user_id)->first();
                        if ($upline->referral != null) {

                            $namaUpline = $upline->referral;
                            //Mencari nama Downline
                            $namaDownline = $upline->username;
                            //
                            // dd($namaDownline);
                            //mencari id upline
                            $namamentah = User::where('username', $namaUpline)->first();
                            $idUpline = $namamentah->id;
                            // dd($idUpline);
                            $totalsaldomasukupline = $totalmasuksaldo * 5 / 100;
                            $totalpointmasukupline = $totalaslimasukpoint * 5 / 100;


                            $cekhistoryreferral =  Transaction_referral::where('transaction_id', $request->transaction_reference_id)->first();
                            $current_balance_referral = Wallet::where([
                                'user_id'     => $idUpline,
                                'currency_id' => $request->currency_id,
                                // 'is_default'  => 'Yes',
                            ])->select('balance')->first();

                            Wallet::where([
                                'user_id'     => $idUpline,
                                'currency_id' => $request->currency_id,
                                // 'is_default'  => 'Yes',
                            ])->update([
                                'balance' => $current_balance_referral->balance - $totalsaldomasukupline,
                            ]);
                            $current_point_referral = User::where([
                                'id'     => $idUpline,
                                // 'is_default'  => 'Yes',
                            ])->select('xpoint')->first();
                            User::where([
                                'id'     => $idUpline,
                                // 'is_default'  => 'Yes',
                            ])->update([
                                'xpoint' => $current_point_referral->xpoint - $totalpointmasukupline,
                            ]);

                            Transaction_point::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Pending',]);
                            Transaction_referral::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Pending',]);
                        }




                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        $tt = Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);
                        // dd($tt);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance - $totalmasuksaldo,
                        ]);

                        //current user xpoint
                        $current_point = User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->select('xpoint')->first();

                        User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'xpoint' => $current_point->xpoint - $totalaslimasukpoint,
                            'firstdeposit' => "",
                        ]);

                        $this->user   = new User();
                        $this->user->ubahHistoryPoint('SuccessDoingPending', $request->transaction_reference_id);


                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    } else {
                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        $tt = Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);
                        // dd($tt);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance - $request->amount,
                        ]);
                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    }
                } elseif ($request->transaction_status == 'Blocked') {
                    // dd('current status: blocked, doing pending');
                    $deposits         = Deposit::find($request->id);
                    $deposits->status = $request->status;
                    $deposits->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $this->user   = new User();
                    $this->user->ubahHistoryPoint('BlockedDoingPending', $request->transaction_reference_id);

                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/deposits');
                }
            } elseif ($request->status == 'Success') {
                if ($request->transaction_status == 'Success') //current status
                {
                    $this->helper->one_time_message('success', 'Deposit is already Successfull!');
                    return redirect('admin/deposits');
                } elseif ($request->transaction_status == 'Blocked') //current status
                {


                    // dd('current status: blocked, doing success');
                    //Blocked ke Success
                    // dd($request->transaction_status);
                    // dd($request->firsttimedposit);
                    // if ($request->firsttimedposit) {
                    // }
                    if ($request->firsttimedposit == "yes") {



                        $totalmasukxpoint = $request->amount * 20 / 100;
                        $hargapoint = Setting::where(['name' => 'pricepoint'])->first(['value']);
                        $totalaslimasukpoint = $totalmasukxpoint / $hargapoint->value;
                        $totalmasuksaldo = $request->amount - $totalmasukxpoint;


                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance + $totalmasuksaldo,
                        ]);

                        $current_point = User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->select('xpoint')->first();



                        User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'xpoint' => $current_point->xpoint + $totalaslimasukpoint,
                            'firstdeposit' => "yes",
                        ]);

                        $this->user   = new User();
                        $this->user->ubahHistoryPoint('BlockedDoingSuccess', $request->transaction_reference_id);

                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    } else {
                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        $update_wallet_for_deposit = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance + $request->amount,
                        ]);
                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    }
                } elseif ($request->transaction_status == 'Pending') {
                    // dd('current status: Pending, doing Success');

                    if ($request->firsttimedposit == "yes") {

                        $totalmasukxpoint = $request->amount * 20 / 100;
                        $hargapoint = Setting::where(['name' => 'pricepoint'])->first(['value']);
                        $totalaslimasukpoint = $totalmasukxpoint / $hargapoint->value;
                        $totalmasuksaldo = $request->amount - $totalmasukxpoint;
                        // dd($totalmasukxpoint);

                        //Yang akan masuk ke upline

                        //mencari nama upline
                        $upline = User::where('id', $request->user_id)->first();
                        if ($upline->referral != null) {

                            $namaUpline = $upline->referral;
                            //Mencari nama Downline
                            $namaDownline = $upline->username;
                            //
                            // dd($namaDownline);
                            //mencari id upline
                            $namamentah = User::where('username', $namaUpline)->first();
                            $idUpline = $namamentah->id;
                            // dd($idUpline);
                            $totalsaldomasukupline = $totalmasuksaldo * 5 / 100;
                            $totalpointmasukupline = $totalaslimasukpoint * 5 / 100;


                            $cekhistoryreferral =  Transaction_referral::where('transaction_id', $request->transaction_reference_id)->first();

                            if ($cekhistoryreferral == null) {
                                // dd($cekhistoryreferral);
                                $referralbonus5persen = new Transaction_referral();
                                $referralbonus5persen->user_id     = $idUpline;
                                $referralbonus5persen->transaction_id   = $request->transaction_reference_id;
                                $referralbonus5persen->note  = 'Bonus Balance First Top Up Referral From ' . $namaDownline . '';
                                $referralbonus5persen->status     = "Success";
                                $referralbonus5persen->amount = $totalsaldomasukupline;

                                $transaction              = new Transaction_point();
                                $transaction->user_id     = $idUpline;
                                $transaction->transaction_id   = $request->transaction_reference_id;
                                $transaction->note  = 'Bonus Point First Top Up Referral From ' . $namaDownline . '';
                                $transaction->status     = "Success";
                                $transaction->amount = $totalpointmasukupline;
                                $transaction->save();
                                $referralbonus5persen->save();

                                $current_balance_referral = Wallet::where([
                                    'user_id'     => $idUpline,
                                    'currency_id' => $request->currency_id,
                                    // 'is_default'  => 'Yes',
                                ])->select('balance')->first();

                                Wallet::where([
                                    'user_id'     => $idUpline,
                                    'currency_id' => $request->currency_id,
                                    // 'is_default'  => 'Yes',
                                ])->update([
                                    'balance' => $current_balance_referral->balance + $totalsaldomasukupline,
                                ]);
                                $current_point_referral = User::where([
                                    'id'     => $idUpline,
                                    // 'is_default'  => 'Yes',
                                ])->select('xpoint')->first();
                                User::where([
                                    'id'     => $idUpline,
                                    // 'is_default'  => 'Yes',
                                ])->update([
                                    'xpoint' => $current_point_referral->xpoint + $totalpointmasukupline,
                                ]);
                            } else {
                                $current_balance_referral = Wallet::where([
                                    'user_id'     => $idUpline,
                                    'currency_id' => $request->currency_id,
                                    // 'is_default'  => 'Yes',
                                ])->select('balance')->first();

                                Wallet::where([
                                    'user_id'     => $idUpline,
                                    'currency_id' => $request->currency_id,
                                    // 'is_default'  => 'Yes',
                                ])->update([
                                    'balance' => $current_balance_referral->balance + $totalsaldomasukupline,
                                ]);
                                $current_point_referral = User::where([
                                    'id'     => $idUpline,
                                    // 'is_default'  => 'Yes',
                                ])->select('xpoint')->first();
                                User::where([
                                    'id'     => $idUpline,
                                    // 'is_default'  => 'Yes',
                                ])->update([
                                    'xpoint' => $current_point_referral->xpoint + $totalpointmasukupline,
                                ]);

                                Transaction_point::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Success',]);
                                Transaction_referral::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Success',]);
                            }
                        }





                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance + $totalmasuksaldo,
                        ]);

                        $current_point = User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->select('xpoint')->first();



                        User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'xpoint' => $current_point->xpoint + $totalaslimasukpoint,
                            'firstdeposit' => "yes",
                        ]);

                        //  Mencetak history ke point history
                        // $user = User::where(['id' => Auth::user()->id])->first();
                        $cekhistorypoint =  Transaction_point::where('transaction_id', $request->transaction_reference_id)->get();
                        // dd($cekhistorypoint);
                        if ($cekhistorypoint == null) {
                            $this->user   = new User();
                            $this->user->firsttopupHistoryPoint($request->user_id, 'bonus', $totalaslimasukpoint, $request->transaction_reference_id);
                        } else {
                            $this->user   = new User();
                            $this->user->ubahHistoryPoint('PendingDoingSuccess', $request->transaction_reference_id);
                        }


                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    } else {
                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance + $request->amount,
                        ]);
                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    }
                }
            } elseif ($request->status == 'Blocked') {
                if ($request->transaction_status == 'Blocked') //current status
                {
                    $this->helper->one_time_message('success', 'Deposit is already Blocked!');
                    return redirect('admin/deposits');
                } elseif ($request->transaction_status == 'Pending') //current status
                {
                    // dd('current status: Pending, doing Blocked');
                    $deposits         = Deposit::find($request->id);
                    $deposits->status = $request->status;
                    $deposits->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);
                    $this->user   = new User();
                    $this->user->ubahHistoryPoint('PendingDoingBlocked', $request->transaction_reference_id);

                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/deposits');
                } elseif ($request->transaction_status == 'Success') //current status
                {
                    // dd('current status: Success, doing Blocked');
                    if ($request->firsttimedposit == "yes") {



                        $totalmasukxpoint = $request->amount * 20 / 100;
                        $hargapoint = Setting::where(['name' => 'pricepoint'])->first(['value']);
                        $totalaslimasukpoint = $totalmasukxpoint / $hargapoint->value;
                        $totalmasuksaldo = $request->amount - $totalmasukxpoint;


                        $upline = User::where('id', $request->user_id)->first();
                        if ($upline->referral != null) {

                            $namaUpline = $upline->referral;
                            //Mencari nama Downline
                            $namaDownline = $upline->username;
                            //
                            // dd($namaDownline);
                            //mencari id upline
                            $namamentah = User::where('username', $namaUpline)->first();
                            $idUpline = $namamentah->id;
                            // dd($idUpline);
                            $totalsaldomasukupline = $totalmasuksaldo * 5 / 100;
                            $totalpointmasukupline = $totalaslimasukpoint * 5 / 100;


                            $cekhistoryreferral =  Transaction_referral::where('transaction_id', $request->transaction_reference_id)->first();
                            $current_balance_referral = Wallet::where([
                                'user_id'     => $idUpline,
                                'currency_id' => $request->currency_id,
                                // 'is_default'  => 'Yes',
                            ])->select('balance')->first();

                            Wallet::where([
                                'user_id'     => $idUpline,
                                'currency_id' => $request->currency_id,
                                // 'is_default'  => 'Yes',
                            ])->update([
                                'balance' => $current_balance_referral->balance - $totalsaldomasukupline,
                            ]);
                            $current_point_referral = User::where([
                                'id'     => $idUpline,
                                // 'is_default'  => 'Yes',
                            ])->select('xpoint')->first();
                            User::where([
                                'id'     => $idUpline,
                                // 'is_default'  => 'Yes',
                            ])->update([
                                'xpoint' => $current_point_referral->xpoint - $totalpointmasukupline,
                            ]);

                            Transaction_point::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Cancelled',]);
                            Transaction_referral::where(['transaction_id' => $request->transaction_reference_id,])->update(['status' => 'Cancelled',]);
                        }




                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance - $totalmasuksaldo,
                        ]);

                        $current_point = User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->select('xpoint')->first();



                        User::where([
                            'id'     => $request->user_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'xpoint' => $current_point->xpoint - $totalaslimasukpoint,
                            'firstdeposit' => "",
                        ]);

                        $this->user   = new User();
                        $this->user->ubahHistoryPoint('SuccessDoingBlocked', $request->transaction_reference_id);

                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        return redirect('admin/deposits');
                    } else {
                        $deposits         = Deposit::find($request->id);
                        $deposits->status = $request->status;
                        $deposits->save();

                        Transaction::where([
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => $request->transaction_type_id,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $current_balance = Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $request->user_id,
                            'currency_id' => $request->currency_id,
                            // 'is_default'  => 'Yes',
                        ])->update([
                            'balance' => $current_balance->balance - $request->amount,
                        ]);
                        $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                        // var_dump($request->amount);
                        return redirect('admin/deposits');
                    }
                }
            }
        }
    }
}
