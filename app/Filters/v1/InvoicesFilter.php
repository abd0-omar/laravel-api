<?php

namespace App\Filters\v1;

// get access to the request
use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class InvoicesFilter extends ApiFilter
{
    // eg. cusomters?postalCode[gt]=30000
    // first rule of handling user input is to not trust user input
    protected $allowedParams = [
        'customerId' => ['eq'],
        'amount' => ['eq', 'gt', 'lt', 'gte', 'lte',],
        'status' => ['eq', 'ne'],
        'billedDate' => ['eq', 'gt', 'lt', 'gte', 'lte',],
        'paidDate' => ['eq', 'gt', 'lt', 'gte', 'lte',],
    ];

    protected $columnMap = [
        // json            actual column name in db
        'billed_date' => 'billedDate',
        'paid_date' => 'paidDate',
        'customer_id' => 'customerId',
        // there is camelCase fn in php btw
    ];

    protected $operatorMap = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
        'ne' => '!=',
        // we could add 'in' and 'like' in the future if we want
    ];
}
