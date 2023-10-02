<?php

namespace App\Services;
use App\Models\User;
use App\Mail\VerifyCodeMail;
use Illuminate\Support\Facades\Mail;
class UserService
{

    public function verifyCode($user)
    {
        $code = random_int(100000, 999999);
        $user->code=$code;
        $user->save();

        if ($user)
            Mail::to($user->email)->send(new VerifyCodeMail($code));
        return TRUE;
    }


}
