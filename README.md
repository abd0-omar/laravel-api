# laravel playground
php artisan make:model Customer --all
php artisan make:model Invoice --all

## steps
1. models
2. migrations
3. factory
4. seeders

### models (tables relations)
one-to-many relationship
a one customer can have many invoices
app/models/customer.php
```php
public function invoices() {
	return $this->hasMany(Invoice::class);
}
```

app/models/customer.php
```php
public function customer() {
	return $this->belongsTo(Customer::class);
}
```

### migration (create table)
in the migrations folder, this is where you create your table and stuff with the necessary fields/columns

after creating our tables like so for example
```php
Schema::create('invoices', function (Blueprint $table) {
	$table->id();
	$table->string('customer_id'); //foreign key references customer table id column
	$table->integer('amount');
	$table->string('status'); //Billed, Paid, Void
	$table->dateTime('date_billed');
	$table->dateTime('paid_billed')->nullable();
	$table->timestamps();
});
```

### factory (function that populates table)
we then go to the factories folder 
and populate the tables with random values like this
```php
$type = fake()->randomElement(['I', 'B']); // individual or business
$name = $type == 'I' ? fake()->name() : fake()->company();
return [
	'name' => $name,
	'type' => $type,
	'email' => fake()->email(),
	'address' => fake()->streetAddress(),
	'city' => fake()->city(),
	'state' => fake()->state(),
	'postal_code' => fake()->postcode(),
];
```

### seed (call factory functions to populate table)
then we seed our values in customerseeders file
```php
Customer::factory()
	->count(25)
	->hasInvoices(10)
	->create();

Customer::factory()
	->count(100)
	->hasInvoices(5)
->create();

Customer::factory()
->count(5)
->create();
```

then go to the databaseseeders file
```php
// User::factory(10)->create();

User::factory()->create([
	'name' => 'Test User',
	'email' => 'test@example.com',
]);

$this->call([
	CustomerSeeder::class
]);
```

### last step
`php artisan migrate:fresh --seed`
p.s. `fresh` is used to drop all tables and rerun migrations

# part two
## versioning our api
## Controllers
- create api folder
	- inside it our version of the api folder, for eg. api/v1
	- move customer&invoice controllers to v1, customerController & invoiceController to v1
in the end you'll have a structure like this

controllers/api/v1/CustomerController
controllers/api/v1/InvoiceController

### routes


go to routes/api.php

if it's not there, insatll api that have sanctum with it
`php artisan install:api`

change the namespace
```php
namespace App\Http\Controllers\api\v1;
use App\Http\Controllers\Controller;
```

in api.php
```php
// api/v1/customers (endpoint)

Route::prefix('v1')->namesapce('App\Http\Controllers\api\v1')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
});

// or
// Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers\api\v1'], function () {

//     Route::apiResource('customers', CustomerController::class);
//     Route::apiResource('invoices', InvoiceController::class);
// });

```

### what does `apiResource` do?
### Api Resource controller

```php
Route::apiResource('users', 'UsersController');
```

Gives you these named routes:

```php
Verb          Path                        Action  Route Name
GET           /users                      index   users.index
POST          /users                      store   users.store
GET           /users/{user}               show    users.show
PUT|PATCH     /users/{user}               update  users.update
DELETE        /users/{user}               destroy users.destroy
```

### extra with create and edit
### Normal Resource controller

```php
Route::resource('users', 'UsersController');
```

Gives you these named routes:

```php
Verb          Path                        Action  Route Name
GET           /users                      index   users.index
GET           /users/create               create  users.create
POST          /users                      store   users.store
GET           /users/{user}               show    users.show
GET           /users/{user}/edit          edit    users.edit
PUT|PATCH     /users/{user}               update  users.update
DELETE        /users/{user}               destroy users.destroy
```
# part 3
## Resources
change json response from snake_case to camelCase aka. camelCaps 

linux/macos
`php artisan make:resource v1/CustomerResource`
windows
`php artisan make:resource v1\CustomerResource`
it'll make app/Http/resources/v1/CustomerResource.php
with the namespace set for us
`namespace App\Http\Resources\v1;`

CustomerController.php
show all customers
```php
public function index()
{
	return Customer::all();
}
```
127.0.0.1/8000/api/v1/customers

show specified customer
```php
public function show(Customer $customer)
{
	return $customer;
}
```
127.0.0.1/8000/api/v1/customers/1

custom json return
using our customerResource in the controller
to return something custom instead of of all the json fields
`use CustomerResource;`
then for eg.
```php
public function show(Customer $customer)
{
	return new CustomerResouce($customer);
}
```

Resources/v1/CustomerResource.php
```php
return [
	'id' => $this->id,
	'name' => $this->name,
	'type' => $this->type,
	'email' => $this->email,
	'address' => $this->address,
	'city' => $this->city,
	'state' => $this->state,
	// change it to snake_case
	'postalCode' => $this->postal_code,
	// and we omitted the timestamps filed
];
```

`php artisan make:resource v1/CustomerCollection`

        return new CustomerCollection(Customer::all());
		from 
		        return Customer::all();

this will make the customers endpoint have this

      "data" : [{
            "id": 1,
            "name": "Porter Robel",
            "type": "I",
            "email": "tmarvin@anderson.com",
            "address": "38902 Eichmann Harbors",
            "city": "New Eldoraborough",
            "state": "Pennsylvania",
            "postalCode": "65400"
        },
        {

without putting anything in CustomerCollections it omitted the timestamp and changed postal_code to postalCode automatically

to make it paginated just simply use this `Customer::paginate()`
```php
public function index()
{
	return new CustomerCollection(Customer::paginate());
}
```


you can do the same thing to invoice
`php artisan make:resource v1/InvoiceResource`
`php artisan make:resource v1/InvoiceCollection`

CustomerCollection is for defining how the json would be returned for all

CustomerResource is for defining how the json would be defined for one
# part 4
## Filtering Data
filtering is better than search for apis
filter things that handled GET requests, and only those GET requests that return a colleciton.
reusable filtering code 

eg. customers?postalCode>30000
eg. customers?postalCode\[gt]=30000

```php
public function index(Request $request)
{
	$filter = new CustomerQuery();
	$queryItems = $filter->transform($request); //[['column', 'operator', 'value']]
	// eg. cusomters?postalCode\[gt]=30000

	if (count($queryItems) == 0) {
		// do what we did originally without filtering
		return new CustomerCollection(Customer::paginate());
	} else {
		return new CustomerCollection(Customer::where($queryItems)->paginate());
	}
}
```

create app/services/v1/CustomerQuery.php

```php
<?php

namespace App\Services\v1;
// get access to the request
use Illuminate\Http\Request;

class CustomerQuery
{
    // eg. cusomters?postalCode[gt]=30000
    // first rule of handling user input is to not trust user input
    protected $allowedParams = [
        'name' => ['eq'],
        'type' => ['eq'],
        'mail' => ['eq'],
        'address' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'postalCode' => ['eq', 'gt', 'lt'],
    ];

    protected $columnMap = [
        // json            actual column name in db
        'postalCode' => 'postal_code',
    ];

    protected $operatorMap = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
        // we could add 'in' and 'like' in the future if we want
    ];

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

```

CustomerController.php
```php
public function index(Request $request)
{
	// it's better to filter than to search for apis
	$filter = new CustomerQuery();
	$queryItems = $filter->transform($request); //[['column', 'operator', 'value']]
	// eg. customers?postalCode\[gt]=30000

	if (count($queryItems) == 0) {
		// do what we did originally without filtering
		return new CustomerCollection(Customer::paginate());
	} else {
		return new CustomerCollection(Customer::where($queryItems)->paginate());
	}
}
```

the url query only allows and (&) queries

# part 5
## Filtering More Data
### making a base class for reusable filtering code
like facade pattern but not facade.

rename services folder to filters
and change CustomerQuery class to CustomerFilter

CustomFilter.php
```php
class CustomerFilter extends ApiFilter {
}
```

ApiFilter.php
```php
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

```

we won't version the base class apiFilter
so it will be inside of filters not v1


create InvoicesFilter.php



the links in the paginated response doesn't contain the filter

```json
"links": {
	"first": "http://127.0.0.1:8000/api/v1/invoices?page=1",
	"last": "http://127.0.0.1:8000/api/v1/invoices?page=17",
	"prev": null,
	"next": "http://127.0.0.1:8000/api/v1/invoices?page=2"
},
```
to fix that:

InvoiceController.php
before
```php
return new InvoiceCollection(Invoice::where($queryItems)->paginate());
```
after
```php
$invoices = Invoice::where($queryItems)->paginate();
return new InvoiceCollection($invoices->appends($request->query()));
```

now the links would have the same query
`http://127.0.0.1:8000/api/v1/invoices?status[eq]=B`
```json
"links": {
        "first": "http://127.0.0.1:8000/api/v1/invoices?status%5Beq%5D=P&page=1",
        "last": "http://127.0.0.1:8000/api/v1/invoices?status%5Beq%5D=P&page=17",
        "prev": null,
        "next": "http://127.0.0.1:8000/api/v1/invoices?status%5Beq%5D=P&page=2"
    },
```

do the same thing for CustomerController
# part 6
## Including Related 
 `customers?postalCode[gt]=30000&includeInvoices=true`

CustomerController.php
```php
$includeInvoices = $request->query('includeInvoices');
```


```php
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
```

```php
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
```

CustomerResource.php
```php
return [
	'id' => $this->id,
	'name' => $this->name,
	'type' => $this->type,
	'email' => $this->email,
	'address' => $this->address,
	'city' => $this->city,
	'state' => $this->state,
	// change it to snake_case
	'postalCode' => $this->postal_code,
	// and we omitted the timestamps filed

	// new!!
	'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
];
```

`customers/9?postalCode[gt]=30000&includeInvoices=false`
it'll work with `=false` too or any other thing after the =


# part 7
## Creating Resources With Post Requests
create a customer with a post request

we don't need the create() & edit() in CustomerController.php

```php
// /**
//  * Show the form for editing the specified resource.
//  */
// public function edit(Customer $customer)
// {
//     //
// }
```

```php
// /**
//  * Show the form for creating a new resource.
//  */
// public function create()
// {
//     //
// }
```

```php
public function store(StoreCustomerRequest $request)
{
	return new CustomerResource(Customer::create($request->all()));
}
```

StoreCustomerRequest works as axum's (a Rust framework) fromRequest fn 


be careful when you specify the fields that you want to be fillable

all the fields are fillabe in the customer.php

if you don't have the file StoreCustomerRequest.php
then do this command

`artisan serve make:request v1\StoreCustomerRequest`


change this to true
StoreCustomerRequest.php

```php
/**
 * Determine if the user is authorized to make this request.
 */
public function authorize(): bool
{
	// return false;
	return true;
}
```

for testing and because we don't have authorization yet 

```php
    protected $fillable = [
        'name',
        'type',
        'email',
        'address',
        'city',
        'state',
        // actual db column name
        'postal_code',
    ];

```

StoreCustomerRequest.php
```php
    public function rules(): array
    {
        return [
            // 'name' => ['required', 'name'],
            'name' => ['required'],
            'type' => ['required', Rule::in(['I', 'B', 'i', 'b'])],
            'email' => ['required', 'email'],
            'address' => ['required'],
            'city' => ['required'],
            'state' => ['required'],
            'postalCode' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'postal_code' => $this->postalCode,
        ]);
    }
```

# part 8
## Updating With Put & Patch
edit the customer with a put request

put request updates all fields in a row
patch request updates certain fields

in laravel update() handles both put and patch requests

```php
// CustomerController.php
/**
 * Update the specified resource in storage.
 */
public function update(UpdateCustomerRequest $request, Customer $customer)
{
	//
}
```

if you don't have the file UpdateCustomerReuquest.php
then do this command

`artisan serve make:request v1\UpdateCustomerRequest`

for a put request we will copy all the rules and functions of StoreCustomerRequest because they have essentially the same rules


UpdateCustomerRequest.php
```php
public function rules(): array
{
	// extract the method used (PUT or PATCH)
	$method = $this->method();

	if ($method == 'PUT') {
		return [
			// 'name' => ['required', 'name'],
			'name' => ['required'],
			'type' => ['required', Rule::in(['I', 'B', 'i', 'b'])],
			'email' => ['required', 'email'],
			'address' => ['required'],
			'city' => ['required'],
			'state' => ['required'],
			'postalCode' => ['required'],
		];
	} else {
		return [
			// 'name' => ['required', 'name'],
			'name' => ['sometimes', 'required'],
			'type' => ['sometimes', 'required', Rule::in(['I', 'B', 'i', 'b'])],
			'email' => ['sometimes', 'required', 'email'],
			'address' => ['sometimes', 'required'],
			'city' => ['sometimes', 'required'],
			'state' => ['sometimes', 'required'],
			'postalCode' => ['sometimes', 'required'],
		];
	}
}
```


if we did a patch without providing postalcode, this fn() should do nothing 
```php
    protected function prepareForValidation()
    {
        if ($this->postalCode) {
            $this->merge([
                'postal_code' => $this->postalCode,
            ]);
        }
    }
```


# References
[How to Build a REST API With Laravel: PHP Full Course (youtube.com)](https://www.youtube.com/watch?v=YGqCZjdgJJk&t=348s)
