<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Common;
use App\Models\Funcashback;
use App\Models\Setting;
use App\Models\Transaction_point;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;

class FuncashbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function cek(Request $request)
    {

        $rules = array(
            'amount' => 'required|numeric',
        );
        $valid = $this->validate($request, $rules);

        if ($valid == true) {
            $amount  = $request->amount;
            $user_id = auth()->user()->id;


            $cektotalamount = Funcashback::where('user_id', $user_id)->sum('amount');
            //cek total funcashback dia
            if ($cektotalamount >= 800000) {
                $success['message'] = 'You are in the limit "fun cashback" zone';
                $success['status'] = '401';
                //cek saldo dia kurang apa engga
            } elseif ($cektotalamount >= 0) {
                $cektotalamountt = Wallet::where('user_id', $user_id)->where('currency_id', '5')->first(['balance']);
                if ($cektotalamountt->balance < $amount) {
                    $success['message'] = 'Your Balance Is Insufficient!';
                    $success['status']  = '401';
                } elseif ($cektotalamountt->balance >= $amount) {
                    if ($cektotalamount + $amount > 800000) {
                        $success['message'] = 'you exceed the "fun cashback" limit';
                        $success['status']  = '401';
                    } else {
                        $success['message'] = 'Success';
                        $success['status']  = '200';
                    }
                }
            }
        };

        return response()->json(['success' => $success]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        $rules = array(
            'amount' => 'required|numeric',
        );
        $valid = $this->validate($request, $rules);

        if ($valid == true) {
            $amount  = $request->amount;
            $user_id = auth()->user()->id;
            //saldo saat ini - request fun cashback;
            $cektotalamountt = Wallet::where('user_id', $user_id)->where('currency_id', '5')->first(['balance']);
            $totalsaldoseharusnya =  $cektotalamountt->balance - $request->amount;
            // //save amount asli
            Wallet::where('user_id', $user_id)->where('currency_id', '5')->update(['balance' => $totalsaldoseharusnya,]);
            //point saat ini + point funcashback *2 / dengan harga point saat ini
            //ini harga point saat ini
            $hargapoint = Setting::where('name', 'pricepoint')->first(['value']);
            $hargapoint = $hargapoint->value;
            //ini point user saat ini
            $totalpointusersaatini = User::where('id', $user_id)->first(['xpoint']);
            $totalpointusersaatini = $totalpointusersaatini->xpoint;
            //perhitungan fun cashback
            $dapatpoint = $amount / $hargapoint;
            $dapatpointnow = $dapatpoint * 2;
            $dapatpointbersih = $totalpointusersaatini + $dapatpointnow;
            //update point user
            User::where('id', $user_id)->update(['xpoint' => $dapatpointbersih,]);

            //Membuat history
            //History funcashback
            $historyfunchasback              = new Funcashback();
            $historyfunchasback->user_id     = $user_id;
            $historyfunchasback->transaction_id     = 0;
            $historyfunchasback->note     = "Fun Cashback x2 at current point prices";
            $historyfunchasback->status     = "Success";
            $historyfunchasback->amount     = $amount;
            $historyfunchasback->save();
            //History Point
            $historypoint              = new Transaction_point();
            $historypoint->user_id     = $user_id;
            $historypoint->transaction_id     = 0;
            $historypoint->note     = "Fun Cashback x2 at current point prices";
            $historypoint->status     = "Success";
            $historypoint->amount     = $dapatpointnow;
            $historypoint->save();
            //History Transaction
            $historyftransaction              = new Transaction();
            $historyftransaction->user_id     = $user_id;
            $historyftransaction->currency_id     = 5;
            $historyftransaction->transaction_type_id     = 6;
            $historyftransaction->user_type     = "registered";
            $historyftransaction->subtotal     = $amount;
            $historyftransaction->total     = $amount;
            $historyftransaction->note     = "balance deduction for fun cashback";
            $historyftransaction->status     = "Success";
            $historyftransaction->save();
            $this->helper->one_time_message('success', __('Fun Cashback Accepted!'));
            return back();
        };
    }


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Funcashback  $funcashback
     * @return \Illuminate\Http\Response
     */
    public function show(Funcashback $funcashback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Funcashback  $funcashback
     * @return \Illuminate\Http\Response
     */
    public function edit(Funcashback $funcashback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Funcashback  $funcashback
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Funcashback $funcashback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Funcashback  $funcashback
     * @return \Illuminate\Http\Response
     */
    public function destroy(Funcashback $funcashback)
    {
        //
    }
}
