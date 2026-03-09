<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function companyDetails(){
        $name = config("company.name");
        $email = config("company.email");
        $address = config("company.address");
        $phone = config("company.phone");

        return "name: $name<br>email: $email<br>address: $address<br>phone: $phone";
    }
}
