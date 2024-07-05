<?php

namespace App\Filters;
// get access to the request
use Illuminate\Http\Request;

class ApiFilter
{
    // eg. cusomters?postalCode[gt]=30000
    // first rule of handling user input is to not trust user input
    protected $allowedParams = [];

    protected $columnMap = [];

    protected $operatorMap = [];

    public function transform(Request $request)
    {
        $eloQuery = [];

        //                           'postalCode' => 'eq', 'gt', 'lt'
        foreach ($this->allowedParams as $param => $operators) {
            // query is an array
            $query = $request->query($param);
            // eg.
            // https://127.0.0.1/api/v1/customers?name[eq]=John&postalCode[gt]=30000&postalCode[lt]=40000
            // $queryName = $request->query('name'); // Returns ['eq' => 'John']
            // $queryPostalCode = $request->query('postalCode'); // Returns ['gt' => '30000', 'lt' => '40000']


            // not null
            if (!isset($query)) {
                continue;
            }

            // columnMap only has postalCode
            // so most of the time you need to set default name field
            $column = $this->columnMap[$param] ?? $param;

            foreach ($operators as $operator) {
                if (isset($query[$operator])) {
                    //            postal_code   <      30000
                    $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                }
            }
        }

        return $eloQuery;
        // $eloQuery = [
        //     ['name', '=', 'John'],
        //     ['postal_code', '>', '30000'],
        //     ['postal_code', '<', '40000'],
        // ];

    }
}
