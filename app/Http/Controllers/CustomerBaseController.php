<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class CustomerBaseController extends Controller
{
    protected function currentCustomer(): ?Customer
    {
        return Customer::where('mobile_number', Auth::user()->phone)->first();
    }

    protected function ordersQuery(): Builder
    {
        $phone    = Auth::user()->phone;
        $customer = $this->currentCustomer();

        return Order::where(function ($q) use ($phone, $customer) {
            $q->where('mobile_number', $phone);
            if ($customer) {
                $q->orWhere('customer_id', $customer->id);
            }
        });
    }

    protected function findOwnOrder(int|string $id): Order
    {
        return $this->ordersQuery()
            ->where('id', $id)
            ->firstOrFail();
    }
}
