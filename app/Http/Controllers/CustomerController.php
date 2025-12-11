<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class CustomerController extends BaseCrudController
{
  protected $model = Customer::class;
  protected $viewPath = 'customers';
  protected $route = 'customers';
  protected $columns = [
    ['name' => 'name', 'label' => 'Name'],
    ['name' => 'email', 'label' => 'Email'],
    ['name' => 'phone', 'label' => 'Phone'],
  ];
}
