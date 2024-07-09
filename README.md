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

StoreCustomerRequest works as axum's fromRequest fn
so it intercepts it before it reaches Customer::create() and does modify() and everything to check the rules and prepare for validation usin modify()


be careful when you specify the fields that you want to be fillable

all the fields are fillabe in the customer.php

Customer.php
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

because we don't have authorization yet 


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
			// the key you want to add or modify => the value from the request data
// adds postal_code to the request data instead of postalCode and it's value is the value of postalCode from the original request
            'postal_code' => $this->postalCode,
        ]);
    }
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
		// 'PATCH'
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

# part 9
## Implementing Bulk Insert
inserting records in bulk, not every api need to provide that but for our use cases inserting invoices in batches makes sense.

```php
public function bulkStore(Request $request) {
	
}
```
we will change the parameter later to be our custom class to make sure that it is a valid "bulk" request before inserting in the database

we could use artisan to make our requestclass but we will do it our selves

```php
Route::prefix('v1')->namespace('App\Http\Controllers\api\v1')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);

	// new!!
    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);
});
```

duplicate the StoreCustomerRequest.php and change it's name to BulkStoreInvoiceRequest.php


bulk data
`` [{CustomerId: }, {CustomerId: }]


BulkStoreInvoiceRequest.php
```php
public function rules(): array
    {
        return [
			// *.because we have an array of jsons
			// if we had
			// data: [
			//     { }
			// ]
			// then it would be like this
			// 'data.*.customer_id' => ['required', 'integer'],
            '*.customerId' => ['required', 'integer'],
            '*.amount' => ['required', 'numeric'],
            '*.status' => ['required', Rule::in(['B', 'P', 'V', 'b', 'p', 'v'])],
            '*.billedDate' => ['required', 'date_format:Y-m-d H:i:s'],
            '*.paidDate' => ['date_format:Y-m-d H:i:s', 'nullable'],
        ];
    }

```

invoiceController.php
```php
public function bulkStore(BulkStoreInvoiceRequest $request)
{
	// transform $request array to a collection
	$bulk = collect($request->all())->map(function ($arr, $key) {
		return Arr::except($arr, ['customerId', 'billedDate', 'paidDate']);
	});


	// insert takes an array not a collection
	Invoice::insert($bulk->toArray());
}
```

# part 10
## protecting routes with sanctum
## sanctum, token authentication
sanctum is added by default


if user exists, then assign some tokens
if the user doesn't exist, then nothing happen


`composer require laravel/sanctum`
`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`

web.php
```php
Route::get('/setup', function () {
    $credentials = [
        'email' => 'admin@admin.com',
        'password' => 'password'
    ];

    if (!Auth::attempt($credentials)) {
        // Create a new user
        $user = new User();

		// add the name and stuff to the user row in the db
        $user->name = 'admin';
        $user->email = $credentials['email'];
        $user->password = Hash::make($credentials['password']);

		// save it, I think save acts like a transaction or something
        $user->save();

        // Attempt authentication again
        if (Auth::attempt($credentials)) {
			// https://stackoverflow.com/questions/69444423/laravel-8-undefined-method-createtoken-intelephense1013
			// I think the annotation line tell PHP intelephense that $user variable is not Illuminate\Foundation\Auth\User type but \App\Models\MyUserModel type.

            /** @var \App\Models\User $user **/
            $user = Auth::user();

            // Create tokens
			// it will get hashed in the db
            $adminToken = $user->createToken('admin-token', ['create', 'update', 'delete']);
            $updateToken = $user->createToken('update-token', ['create', 'update']);
			// read only accesss
            // not specifying abilities would result of `basicToken` having all access
            // in the next chapter we will fix that by manually changing it in the db
            $basicToken = $user->createToken('basic-token');

			// you have to return the token in plain text after creating it
            // because it's the only time we can get that plain text

            return [
                'admin' => $adminToken->plainTextToken,
                'update' => $updateToken->plainTextToken,
                'basic' => $basicToken->plainTextToken,
            ];
		}
	}
// implemented some error handling in the file in the repo
});
```


make sure to have these lines in user.php
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
```

routes/api.php
add auth middleware
```php
Route::prefix('v1')->namespace('App\Http\Controllers\api\v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);

    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);
});
```

finally
`http://localhost/setup`
```json
{
    "admin": "1|B2xqgmjPankVZjVIshgqAixIxwk0gFeEuyH3cOjSba70ebfd",
    "update": "2|OzuLgrnUCvt1Pey4P2am9Um60VatMIDczjpchp4E47301200",
    "basic": "3|BK9cVAACBmG5NMuCNSMSs2LInO1qGWNq3yD3hklj9a284fc7"
}
```
using any of these in the headers for our api paths eg. /api/v1/customers, will grant you access to it. 
`curl --verbose`
# LAST PART
## Authorizing Requests With Token Abilities

### change "basic" from all access to just viewing access

```sql
select * from personal_access_tokens where id = '3';
```

```json
[{"id":3,"tokenable_type":"App\\Models\\User","tokenable_id":3,"name":"basic-token","token":"450579341a0792b34532ec221a4a8a7e2923c2b005502639684d7f26a5f38c92","abilities":"[\"*\"]","last_used_at":"2024-07-09 09:58:17","expires_at":null,"created_at":"2024-07-08 10:56:12","updated_at":"2024-07-09 09:58:17"}]
```

basic token has `"abilities":"[*]"` which means can do anything, that happened because we didn't specify any abilities for it so it got defaulted to `*` (all), so we should change it to `none` or anything we like but not `*` (all) as it is a basic token

```sql
UPDATE personal_access_tokens SET abilities = '["none"]' WHERE id = '3';
```

```sql
select * from personal_access_tokens where id = '3';
```

```json
[{"id":3,"tokenable_type":"App\\Models\\User","tokenable_id":3,"name":"basic-token","token":"450579341a0792b34532ec221a4a8a7e2923c2b005502639684d7f26a5f38c92","abilities":"[\"none\"]","last_used_at":"2024-07-09 09:58:17","expires_at":null,"created_at":"2024-07-08 10:56:12","updated_at":"2024-07-09 09:58:17"}]
```

so now basic has access to view only the data

### authorization

update these three files

BulkStorelnvoiceRequest.php
StoreCustomerRequest.php
```php
public function authorize(): bool
{
	$user = $this->user();

	return $user != null && $user->tokenCan('create');

	// we could make it like this 'ivnoice:create' or 'customer:create' to be more specific
	// return $user != null && $user->tokenCan('create');
}
```

UpdateCustomerRequest.php
```php
public function authorize(): bool
{
	$user = $this->user();

	return $user != null && $user->tokenCan('update');
}
```

POST `http://127.0.0.1:8000/api/v1/customers`
with data 
```json
{
  "name": "hamada_auth",
  "type": "I",
  "email": "hamada@yahoo.com",
  "address": "38902 4ar3 el ms7a",
  "city": "misr elmkasa",
  "state": "transylvania",
  "postalCode": "42069"
}
```

without token
```json
{
  "message": "Unauthenticated."
}
```

with the bearer token 
```json
3|BK9cVAACBmG5NMuCNSMSs2LInO1qGWNq3yD3hklj9a284fc7
```

201 Created Response
```json
{
  "data": {
    "id": 132,
    "name": "hamada_auth",
    "type": "I",
    "email": "hamada@yahoo.com",
    "address": "38902 4ar3 el ms7a",
    "city": "misr elmkasa",
    "state": "transylvania",
    "postalCode": "42069"
  }
}
```

same thing with PATCH `http://127.0.0.1:8000/api/v1/customers`

and bulk insert POST `http://127.0.0.1:8000/api/v1/invoices/bulk`

# Conclusion

Laravel is battery included super easy to use framework that lets you focus on building the api rather than building anything from scratch yourself

- dynamically typed that resulted in small errors that was hard to find
- slow but in the context of the web wouldn't be a problem for a CRUD api
- Everything just magically works, like for example sanctum.

the last point feels like a plus rather than a downside but personally I don't like to work with that many layers of abstractions, it saves us from reinventing the wheel, but it's fun to do so sometimes and I could make a square-ish wheel but it would be my wheel.



# References
[How to Build a REST API With Laravel: PHP Full Course (youtube.com)](https://www.youtube.com/watch?v=YGqCZjdgJJk&t=348s)
