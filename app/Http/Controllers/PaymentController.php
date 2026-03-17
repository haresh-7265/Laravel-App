<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService){
        $this->paymentService=$paymentService;
    }
    public function pay(){
        return $this->paymentService->process(5000);
    }
}
