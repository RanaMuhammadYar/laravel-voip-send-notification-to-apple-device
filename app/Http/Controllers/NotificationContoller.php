<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\VoIPNotification;

class NotificationContoller extends Controller
{
    use VoIPNotification;

    public function index()
    {

        $token = "12345";
        $message = [
            'apns' =>[
                'data' => "hello world"
            ],
        ];

       $response =  $this->sendVoIPNotification($token , json_encode($message));
       return $response;



    }

}
