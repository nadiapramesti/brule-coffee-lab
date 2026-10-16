<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderPrintController extends Controller
{
    public function __invoke(Order $order)
    {
        return view('admin.orders.print', ['order' => $order->load('items', 'user')]);
    }
}
