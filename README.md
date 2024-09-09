## BusSystem API Integration for Laravel

This package provides a PHP wrapper for interacting with the BusSystem API, designed to facilitate communication between your Laravel application and the BusSystem API for bus ticket booking, route searching, and other related services.

### Features
- Search for available routes between locations.
- Retrieve detailed information about points, routes, tickets, and discounts.
- Manage bus ticket bookings, including reservations, payments, and cancellations.
- Support for test and production environments.
- Caching of API responses to reduce redundant requests.
  
### Requirements
- PHP 8.2 or higher
- Laravel 10.x or higher

---

## Installation

1. **Install the package via Composer**:
   ```bash
   composer require nikba/bussystem
   ```

2. **Publish the configuration file**:
   After installing, publish the configuration file to set your API credentials and other settings:
   ```bash
   php artisan vendor:publish --provider="Nikba\BusSystem\BusSystemServiceProvider"
   ```

3. **Configuration**:
   In your `.env` file, add the following configurations:
   ```env
   BUSSYSTEM_API_LOGIN=your_login_here
   BUSSYSTEM_API_PASSWORD=your_password_here
   BUSSYSTEM_API_TEST_MODE=true
   BUSSYSTEM_API_LANG=en
   ```

   These environment variables will be used in the configuration file (`config/bussystem.php`), which you can also customize:
   
   ```php
   return [
       'login' => env('BUSSYSTEM_API_LOGIN', ''),
       'password' => env('BUSSYSTEM_API_PASSWORD', ''),
       'test_mode' => env('BUSSYSTEM_API_TEST_MODE', true),
       'lang' => env('BUSSYSTEM_API_LANG', 'en'),
       'endpoints' => [
           'test' => 'https://test-api.bussystem.eu/server',
           'production' => 'https://api.bussystem.eu/server',
       ],
       'cache_times' => [
           'get_points' => 525600, // 1 year in minutes
           'get_routes' => 1440,   // 1 day in minutes
           'get_baggage' => 720,   // 12 hours
           // Add other cache times as needed
       ],
   ];
   ```

4. **Add Service Provider (if needed)**:
   Laravel automatically detects service providers, but if you're using an older version, you may need to add it manually to the `config/app.php` file:
   ```php
   'providers' => [
       Nikba\BusSystem\BusSystemServiceProvider::class,
   ],
   ```

5. **Add Facade Alias** (optional):
   If you want to use the facade:
   ```php
   'aliases' => [
       'BusApi' => Nikba\BusSystem\Facades\BusApi::class,
   ],
   ```

---

## Usage

Once installed, you can interact with the BusSystem API through the `BusApi` facade or the underlying service class `Nikba\BusSystem\Services\BusApiService`.

### Example: Get Points (Cities or Locations)

```php
$points = BusApi::getPoints([
    'country_id' => 1, // Optional country filter
    'autocomplete' => 'Prague', // Optional search filter
    'trans' => 'bus', // Type of transport: [all, bus, train, air, etc.]
]);
```

---

### Functions and Parameters

#### 1. `getPoints()`

**Description**: Retrieves a list of available points (cities, stations, or airports) based on filters like country or transport type.

**Parameters**:
- `country_id` (optional): Filter by country ID.
- `autocomplete` (optional): Filter by matching initial characters of a location name.
- `trans`: Type of transport [all, bus, train, air, travel, hotel].
  
**Example**:
```php
$points = BusApi::getPoints([
    'country_id' => 1,
    'autocomplete' => 'Praha',
    'trans' => 'bus'
]);
```

---

#### 2. `getRoutes()`

**Description**: Retrieves available bus routes between two points for a specific date.

**Parameters**:
- `id_from`: Departure city ID.
- `id_to`: Arrival city ID.
- `date`: Date of departure (format: yyyy-mm-dd).
- `trans`: Type of transport [all, bus, train, air].

**Example**:
```php
$routes = BusApi::getRoutes([
    'id_from' => 3, // Praha
    'id_to' => 6,   // Kiev
    'date' => '2024-09-09',
    'trans' => 'bus'
]);
```

---

#### 3. `getBaggage()`

**Description**: Retrieves baggage information for a specified route and interval.

**Parameters**:
- `interval_id`: The interval ID from the `getRoutes` request.
- `station_from_id`: Departure station ID.
- `station_to_id`: Arrival station ID.
- `currency`: Response currency [EUR, RON, PLN, MDL, etc.].

**Example**:
```php
$baggage = BusApi::getBaggage([
    'interval_id' => '90|gh340|d29-96',
    'station_from_id' => 1547,
    'station_to_id' => 757,
    'currency' => 'EUR'
]);
```

---

#### 4. `getFreeSeats()`

**Description**: Retrieves available seats on a specified route for a given interval.

**Parameters**:
- `interval_id`: The interval ID from the `getRoutes` request.
- `train_id`: (For trains) Train ID.
- `vagon_id`: (For trains) Vagon ID.
- `currency`: Response currency.

**Example**:
```php
$freeSeats = BusApi::getFreeSeats([
    'interval_id' => '90|gh340|d29-96',
    'train_id' => '141Sh',
    'vagon_id' => '14BLB',
    'currency' => 'EUR'
]);
```

---

#### 5. `newOrder()`

**Description**: Creates a new ticket order based on available routes and selected passengers.

**Parameters**:
- `date`: Departure date.
- `interval_id`: Interval ID for the route.
- `station_from_id`: Departure station ID.
- `station_to_id`: Arrival station ID.
- `seat`: Selected seat numbers.
- `name`: Passenger names.
- `surname`: Passenger surnames.
- `birth_date`: Passenger birthdates.
- `doc_type`: Document types.
- `doc_number`: Document numbers.
- `doc_expire_date`: Document expiration dates.

**Example**:
```php
$order = BusApi::newOrder([
    'date' => ['2024-09-09'],
    'interval_id' => ['ju34hd|30122023|30122023'],
    'station_from_id' => [1547],
    'station_to_id' => [757],
    'seat' => [["32"]],
    'name' => ['John'],
    'surname' => ['Doe'],
    'birth_date' => ['2000-01-01'],
    'doc_type' => [1],
    'doc_number' => ['CZRE5752475-54'],
    'doc_expire_date' => ['2045-12-30'],
    'citizenship' => ['UK'],
    'gender' => ['M'],
]);
```

---

#### 6. `cancelTicket()`

**Description**: Cancels an unpaid order or refund a paid ticket, full or partial.

**Parameters**:
- `order_id`: Order ID to cancel.
- `ticket_id`: Ticket ID to cancel.
- `session`: Your session.

**Example**:
```php
$response = BusApi::cancelTicket([
    'order_id' => 5397146,
    'ticket_id' => 4461298,
    'session' => 'c227e552956b'
]);
```

---

## Testing

The package comes with PHPUnit tests. To run the tests:

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run tests:
   ```bash
   vendor/bin/phpunit
   ```

---

## Caching

The API responses are cached for efficiency. Cache duration for different endpoints can be customized in `config/bussystem.php` under `cache_times`. 

For example, to set cache duration for the `getRoutes` function to 1 day (1440 minutes):
```php
'cache_times' => [
    'get_routes' => 1440,   // 1 day in minutes
],
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## Support

For any issues, please feel free to open an issue on GitHub or contact the developer at office@nikba.com.