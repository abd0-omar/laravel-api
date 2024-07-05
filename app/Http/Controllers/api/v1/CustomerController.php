<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CustomerCollection;
use App\Http\Resources\v1\CustomerResource;
use App\Filters\v1\CustomerFilter;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // it's better to filter than to search for apis
        $filter = new CustomerFilter();
        $queryItems = $filter->transform($request); //[['column', 'operator', 'value']]
        // eg. $queryName = $request->query('name'); // Returns ['eq' => 'John']
        // eg. customers?postalCode[gt]=30000

        $customers = Customer::where($queryItems);

        // customers?postalCode[gt]=30000&includeInvoices=true
        // true or false
        // $includeInvoices = $request->query('includeInvoices'); // Returns true or false
        $includeInvoices = $request->query('includeInvoices');

        if ($includeInvoices) {
            // makes sure to add 'invoices' to CustomerResource 
            $customers = $customers->with('invoices');
        }

        return new CustomerCollection($customers->paginate()->appends($request->query()));

        // no need to check for count
        // if (count($queryItems) == 0) {
        //     // do what we did originally without filtering
        //     return new CustomerCollection(Customer::paginate());
        // } else {
        //     // if you pass and empty array `[]` to where([]), then where() will do nothing and execute normally
        //     // $customers = Customer::where([])->paginate();
        //     $customers = Customer::where($queryItems)->paginate();
        //     return new CustomerCollection($customers->appends($request->query()));
        // }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        // true or false
        $includeInvoices = Request()->query('includeInvoices');

        if ($includeInvoices) {
            // the only key missing (invoices) in the resources file
            return new CustomerResource($customer->loadMissing('invoices'));
        }

        return new CustomerResource($customer);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
