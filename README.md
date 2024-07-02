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
# References
[How to Build a REST API With Laravel: PHP Full Course (youtube.com)](https://www.youtube.com/watch?v=YGqCZjdgJJk&t=348s)
