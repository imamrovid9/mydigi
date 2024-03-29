<?php

namespace App\Models;

use App\Http\Helpers\Common;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DocumentVerification;
use App\Models\RequestPayment;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\Preference;
use App\Models\Transfer;
use App\Models\Transaction_point;
use App\Models\UserDetail;
use App\Models\VerifyUser;
use App\Models\Wallet;
use App\Models\Setting;
use App\Repositories\CryptoCurrencyRepository;
use Hexters\CoinPayment\Entities\CoinPaymentuserRelation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, CoinPaymentuserRelation;
    // use Notifiable, HasApiTokens;

    protected $fillable = [
        'role_id',
        'type',
        'username',
        'first_name',
        'last_name',
        'phone',
        'google2fa_secret',
        'defaultCountry',
        'carrierCode',
        'email',
        'password',
        'phrase',
        'status',
        'picture',
        'address_verified',
        'identity_verified',
    ];

    protected $table = 'users';

    protected $hidden = [
        'password', 'remember_token', 'phrase', 'google2fa_secret',
    ];

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

    //User - hasOne - deposit
    public function deposit()
    {
        return $this->hasOne(Deposit::class);
    }

    public function transfer()
    {
        return $this->hasOne(Transfer::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function request_payment()
    {
        return $this->hasOne(RequestPayment::class);
    }

    public function merchant()
    {
        return $this->hasMany(Merchant::class);
    }

    public function merchant_payment()
    {
        return $this->hasMany(MerchantPayment::class);
    }

    //User - hasOne - log
    public function activity_log()
    {
        return $this->hasOne(ActivityLog::class);
    }

    public function dispute()
    {
        return $this->hasMany(Dispute::class);
    }

    public function disputeDiscussion()
    {
        return $this->hasMany(DisputeDiscussion::class, 'user_id');
    }

    /**
     * [Role]
     * @return [one to one relationship] [Role belongs to a User]
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class, 'user_id');
    }

    public function file()
    {
        return $this->hasOne(Ticket::class, 'user_id');
    }

    public function ticket_reply()
    {
        return $this->hasOne(TicketReply::class, 'user_id');
    }

    public function payoutSettings()
    {
        return $this->hasMany(PayoutSetting::class, 'user_id');
    }

    public function verifyUser()
    {
        return $this->hasOne(VerifyUser::class, 'user_id');
    }

    public function device_log()
    {
        return $this->hasOne(DeviceLog::class, 'user_id');
    }

    public function bank()
    {
        return $this->hasOne(Bank::class, 'user_id');
    }

    public function user_detail()
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function document_verification()
    {
        return $this->hasMany(DocumentVerification::class, 'user_id');
    }

    /**
     * Create new user during registration
     * param  [object] $request
     * return [object] $user
     */
    public function createNewUser($request, $intiatedBy)
    {
        $asd = Setting::where(['name' => 'xpoint'])->first();
        // dd($asd->value);
        $user = new self();
        if ($intiatedBy == 'user') {
            $user->type = $request->type;
        }
        $user->username = $request->username;
        if ($request->referral) {
            $user->referral = $request->referral;
            $user->xpoint = $asd->value;
        }
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        $formattedPhone   = str_replace('+' . $request->carrierCode, "", $request->formattedPhone);
        if (!empty($request->phone)) {
            $user->phone          = preg_replace("/[\s-]+/", "", $formattedPhone);
            $user->defaultCountry = $request->defaultCountry;
            $user->carrierCode    = $request->carrierCode;
            $user->formattedPhone = $request->formattedPhone;
        } else {
            $user->phone          = null;
            $user->defaultCountry = null;
            $user->carrierCode    = null;
            $user->formattedPhone = null;
        }
        $user->password = \Hash::make($request->password);
        if ($intiatedBy == 'user') {
            if ($request->type == 'user') {
                $role = Role::select('id')->where(['customer_type' => 'user', 'user_type' => 'User', 'is_default' => 'Yes'])->first(['id']);
            } else {
                $role = Role::select('id')->where(['customer_type' => 'merchant', 'user_type' => 'User', 'is_default' => 'Yes'])->first(['id']);
            }
            $user->role_id = $role->id;
        } else {
            $user->role_id = $request->role;
            $user->status  = $request->status;
        }
        $user->save();
        return $user;
    }

    /**
     * Create user's detail
     * param  [object] $userId
     */
    public function createUserDetail($userId)
    {

        $userDetail          = new UserDetail();
        $userDetail->user_id = $userId;
        $randomCountry       = Country::first(['id']);
        if (!empty($randomCountry)) {
            $userDetail->country_id = $randomCountry->id;
        }
        // Get system default timezone
        $timezone             = Preference::where(['category' => 'preference', 'field' => 'dflt_timezone'])->first(['value']);
        $userDetail->timezone = $timezone->value;
        $userDetail->save();
    }

    /**
     * Create user's default wallet
     * param  [object] $userId
     * param  [object] $defaultCurrency
     */
    public function createUserDefaultWallet($userId, $defaultCurrency)
    {
        $wallet              = new Wallet();
        $wallet->user_id     = $userId;
        $wallet->currency_id = $defaultCurrency;
        $wallet->is_default  = 'Yes';
        $wallet->save();
    }

    public function createHistoryPoint($userId, $status)
    {
        $asd = Setting::where(['name' => 'xpoint'])->first();
        $transaction              = new Transaction_point();
        $transaction->user_id     = $userId;
        if ($status == "referral") {
            $transaction->status     = "Success";
            $transaction->note  = 'Bonus Input Referral';

            $transaction->amount = $asd->value;
        }
        $transaction->save();
    }

    public function dailyHistoryPoint($userId, $status, $dapat)
    {
        // $asd = Setting::where(['name' => 'xpoint'])->first();
        $transaction              = new Transaction_point();
        $transaction->user_id     = $userId;
        if ($status == "harian") {
            $transaction->status     = "Success";
            $transaction->note  = 'Reward Daily Checkin';
            $transaction->amount = $dapat;
        }
        $transaction->save();
    }

    public function firsttopupHistoryPoint($userId, $status, $dapat, $transaction_id)
    {
        $asd = Setting::where(['name' => 'xpoint'])->first();
        $transaction              = new Transaction_point();
        $transaction->user_id     = $userId;
        if ($status == "bonus") {
            $transaction->note  = 'Bonus First Top Up (Hasil topup 80% -> Balance, 20% -> Point dengan s&k harga point saat ini)';
            $transaction->status     = "Success";
            $dapatt = (int)$dapat;
            $transaction->transaction_id = $transaction_id;
            $transaction->amount = $dapatt;
        }
        $transaction->save();
    }

    public function ubahHistoryPoint($status, $transaction_id)
    {
        // dd($status);
        // $asd = Setting::where(['name' => 'xpoint'])->first();
        $asd = Transaction_point::where('transaction_id', $transaction_id)->first();
        if ($status == "SuccessDoingPending") {
            $asd->status     = "Pending";
        } elseif ($status == "PendingDoingSuccess") {
            $asd->status     = "Success";
        } elseif ($status == "SuccessDoingBlocked") {
            $asd->status     = "Canclelled";
        } elseif ($status == "BlockedDoingSuccess") {
            $asd->status     = "Success";
        } elseif ($status == "BlockedDoingPending") {
            $asd->status     = "Pending";
        } elseif ($status == "PendingDoingBlocked") {
            $asd->status     = "Canclelled";
        }



        // dd($asd);
        // $transaction              = new Transaction_point();
        // $transaction->user_id     = $userId;
        // if ($status == "bonus") {
        //     $transaction->note  = 'Bonus First Top Up (Hasil topup 80% -> Balance, 20% -> Point dengan s&k harga point saat ini)';

        // }
        $asd->save();
    }
    /**
     * Process Registered User Transfers
     * param  [object] $userEmail
     * param  [object] $userFormattedPhone
     * param  [object] $user
     * param  [object] $defaultCurrency
     */
    public function processUnregisteredUserTransfers($userEmail, $userFormattedPhone, $user, $defaultCurrency)
    {
        if (!empty($user->email) || !empty($user->formattedPhone)) {
            $unknownTransferTransaction = Transaction::where(function ($q) use ($userEmail) {
                $q->where(['user_type' => 'unregistered']);
                $q->where(['email' => $userEmail]);
                $q->whereIn('transaction_type_id', [Transferred]);
            })
                ->orWhere(function ($q) use ($userFormattedPhone) {
                    $q->where(['user_type' => 'unregistered']);
                    $q->where(['phone' => $userFormattedPhone]);
                    $q->whereIn('transaction_type_id', [Transferred]);
                })
                ->get(['transaction_reference_id', 'uuid']);
            // dd($unknownTransferTransaction);

            if (!empty($unknownTransferTransaction)) {
                foreach ($unknownTransferTransaction as $key => $value) {
                    $transfer = Transfer::where(['uuid' => $value->uuid])->first(['id', 'uuid', 'amount', 'currency_id', 'receiver_id', 'status']);
                    // dd($transfer);

                    if ($transfer->uuid == $value->uuid) {
                        $transfer->receiver_id = $user->id;
                        $transfer->status      = 'Success';
                        $transfer->save();

                        Transaction::where([
                            'transaction_reference_id' => $value->transaction_reference_id,
                            'transaction_type_id'      => Transferred,
                        ])->update([
                            'end_user_id' => $user->id,
                            'user_type'   => 'registered',
                            'status'      => 'Success',
                        ]);

                        Transaction::where([
                            'transaction_reference_id' => $value->transaction_reference_id,
                            'transaction_type_id'      => Received,
                        ])->update([
                            'user_id'   => $user->id,
                            'user_type' => 'registered',
                            'status'    => 'Success',
                        ]);

                        $unknownTransferWallet = Wallet::where(['user_id' => $user->id, 'currency_id' => $transfer->currency_id])->first(['id', 'balance']);
                        if (empty($unknownTransferWallet)) {
                            $wallet              = new Wallet();
                            $wallet->user_id     = $user->id;
                            $wallet->currency_id = $transfer->currency_id;
                            if ($wallet->currency_id == $defaultCurrency) {
                                $wallet->is_default = 'Yes';
                            } else {
                                $wallet->is_default = 'No';
                            }
                            $wallet->balance = $transfer->amount;
                            $wallet->save();
                        } else {
                            $unknownTransferWallet->balance = ($unknownTransferWallet->balance + $transfer->amount);
                            $unknownTransferWallet->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Process Registered User Request Payments
     * param  [object] $userEmail
     * param  [object] $userFormattedPhone
     * param  [object] $user
     * param  [object] $defaultCurrency
     */
    public function processUnregisteredUserRequestPayments($userEmail, $userFormattedPhone, $user, $defaultCurrency)
    {
        if (!empty($user->email) || !empty($user->formattedPhone)) {
            $unknownRequestTransaction = Transaction::where(function ($q) use ($userEmail) {
                $q->where(['user_type' => 'unregistered']);
                $q->where(['email' => $userEmail]);
                $q->whereIn('transaction_type_id', [Request_From]);
            })
                ->orWhere(function ($q) use ($userFormattedPhone) {
                    $q->where(['user_type' => 'unregistered']);
                    $q->where(['phone' => $userFormattedPhone]);
                    $q->whereIn('transaction_type_id', [Request_From]);
                })
                ->get(['transaction_reference_id', 'uuid']);
            // dd($unknownRequestTransaction);

            if (!empty($unknownRequestTransaction)) {
                foreach ($unknownRequestTransaction as $key => $value) {
                    $request_payment = RequestPayment::where(['uuid' => $value->uuid])->first(['id', 'uuid', 'currency_id', 'receiver_id']);
                    if ($request_payment->uuid == $value->uuid) {
                        $request_payment->receiver_id = $user->id;
                        $request_payment->save();

                        Transaction::where([
                            'transaction_reference_id' => $value->transaction_reference_id,
                            'transaction_type_id'      => Request_From,
                        ])->update([
                            'end_user_id' => $user->id,
                            'user_type'   => 'registered',
                        ]);

                        Transaction::where([
                            'transaction_reference_id' => $value->transaction_reference_id,
                            'transaction_type_id'      => Request_To,
                        ])->update([
                            'user_id'   => $user->id,
                            'user_type' => 'registered',
                        ]);

                        $unknownRequestWallet = Wallet::where(['user_id' => $user->id, 'currency_id' => $request_payment->currency_id])->first(['id']);
                        if (empty($unknownRequestWallet)) {
                            $wallet              = new Wallet();
                            $wallet->user_id     = $user->id;
                            $wallet->currency_id = $request_payment->currency_id;
                            if ($wallet->currency_id == $defaultCurrency) {
                                $wallet->is_default = 'Yes';
                            } else {
                                $wallet->is_default = 'No';
                            }
                            $wallet->balance = 0.00;
                            $wallet->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Process User Email Verification
     * param  [object] $user
     */
    public function processUserEmailVerification($user)
    {
        $verifyUser = VerifyUser::where(['user_id' => $user->id])->first(['id']);
        if (empty($verifyUser)) {
            $verifyUserNewRecord          = new VerifyUser();
            $verifyUserNewRecord->user_id = $user->id;
            $verifyUserNewRecord->token   = str_random(40);
            $verifyUserNewRecord->save();
        }

        //mail - temp -17
        $common                               = new Common();
        $englishUserVerificationEmailTempInfo = $common->getEmailOrSmsTemplate(17, 'email');
        $userVerificationEmailTempInfo        = $common->getEmailOrSmsTemplate(17, 'email', getDefaultLanguage());
        if (!empty($userVerificationEmailTempInfo->subject) && !empty($userVerificationEmailTempInfo->body)) {
            // subject
            $userVerificationEmailTempInfo_sub = $userVerificationEmailTempInfo->subject;
            $userVerificationEmailTempInfo_msg = str_replace('{user}', $user->first_name . ' ' . $user->last_name, $userVerificationEmailTempInfo->body);
        } else {
            $userVerificationEmailTempInfo_sub = $englishUserVerificationEmailTempInfo->subject;
            $userVerificationEmailTempInfo_msg = str_replace('{user}', $user->first_name . ' ' . $user->last_name, $englishUserVerificationEmailTempInfo->body);
        }
        $userVerificationEmailTempInfo_msg = str_replace('{email}', $user->email, $userVerificationEmailTempInfo_msg);
        $userVerificationEmailTempInfo_msg = str_replace('{verification_url}', url('user/verify', $user->verifyUser->token), $userVerificationEmailTempInfo_msg);
        $userVerificationEmailTempInfo_msg = str_replace('{soft_name}', getCompanyName(), $userVerificationEmailTempInfo_msg);

        return [
            'email'   => $user->email,
            'subject' => $userVerificationEmailTempInfo_sub,
            'message' => $userVerificationEmailTempInfo_msg,
        ];
    }

    /**
     * Create user's Crypto Currency/Currencies wallet address
     * @param string $value [description]
     */
    public function generateUserCryptoWalletAddress($user)
    {
        $getCryptoCurrencyPref = $this->cryptoCurrency->getCurrenciesPreference();
        if ($getCryptoCurrencyPref->value != 'fiat') {
            $getDefaultCryptoCurrenciesSetting = $this->cryptoCurrency->getAllowedCryptoCurrenciesSetting();
            if ($getDefaultCryptoCurrenciesSetting !== 'none') {
                foreach (explode(',', $getDefaultCryptoCurrenciesSetting->value) as $getDefaultCryptoCurr) {
                    $getAllCurrencies = (new Currency())->getAllCurrencies(['id' => $getDefaultCryptoCurr, 'status' => 'Active'], ['id', 'code']);
                    foreach ($getAllCurrencies as $currency) {
                        $wallet              = new Wallet();
                        $wallet->user_id     = $user->id;
                        $wallet->currency_id = $currency->id;
                        $wallet->is_default  = 'No';
                        $wallet->save();

                        return $this->cryptoCurrency->getCryptoApiLogOfWallet($wallet->id, $currency->code, $user);
                    }
                }
            }
        }
    }
}
