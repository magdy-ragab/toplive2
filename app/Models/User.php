<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'country',
        'gender',
        'pic',
        'birth_date',
        'login_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $with=[
        // 'otp'
    ];
# ##########################################################
    function get_csrf() {
        return csrf_token() ;
    }
# ##########################################################
    function otp() {
        return $this
            ->hasOne(Otp::class)
            ->where(
                [
                    [
                        "created_at" ,
                        '>=',
                        Carbon::now()
                            ->subMinutes(30)
                            ->toDateTimeString()
                    ]
                ]
            )->select('id', 'user_id', 'otp');
    }

}
