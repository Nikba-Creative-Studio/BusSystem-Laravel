<?php
namespace Nikba\BusSystem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Nikba\BusSystem\Exceptions\BusApiException;

class BusApiService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('bussystem');
    }

    protected function getBaseUrl()
    {
        return $this->config['test_mode'] ? $this->config['endpoints']['test'] : $this->config['endpoints']['production'];
    }

    protected function makeRequest($endpoint, $params = [])
    {
        $url = $this->getBaseUrl() . $endpoint;
        $params = array_merge([
            'login' => $this->config['login'],
            'password' => $this->config['password'],
            'lang' => $this->config['lang']
        ], $params);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($url, $params);
            if ($response->successful()) {
                return $response->json();
            } else {
                throw new BusApiException('API request failed with status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            throw new BusApiException($e->getMessage());
        }
    }

    /**
     * Retrieve the available points from the API.
     *
     * Optional parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "lang" (string): The language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     * - "country_id" (int): Filter by country ID.
     * - "point_id_from" (int): Specify the point ID from where travel can begin.
     * - "point_id_to" (int): Specify the point ID where travel can end.
     * - "autocomplete" (string): Filter by matching initial characters (e.g., city name).
     * - "boundLatSW" (float): GPS southwest latitude for geographical filtering.
     * - "boundLonSW" (float): GPS southwest longitude for geographical filtering.
     * - "boundLatNE" (float): GPS northeast latitude for geographical filtering.
     * - "boundLotNE" (float): GPS northeast longitude for geographical filtering.
     * - "trans" (string): Type of transport (e.g., 'all', 'bus', 'train', 'air', 'travel', 'hotel').
     * - "viev" (string): Response type, either 'get_country' or 'group_country'.
     * - "group_by_point" (int): Set to 1 for a general search returning cities and their stations.
     * - "group_by_iata" (int): Set to 1 when querying for 'air', returns cities and airports.
     * - "all" (int): Set to 1 to include all cities, including non-popular ones.
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with available points.
     */
    public function getPoints(array $params = [])
    {
        $cacheKey = 'busapi_get_points_' . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->config['cache_times']['get_points'], function () use ($params) {
            return $this->makeRequest('/curl/get_points.php', $params);
        });
    }

    /**
     * Retrieve available routes between cities on a specified date.
     *
     * Optional parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "v" (string): Version of the request (default "1.1").
     * - "id_from" (int): Departure city ID (for buses).
     * - "id_to" (int): Arrival city ID (for buses).
     * - "point_train_from_id" (int): Departure city ID (for trains).
     * - "point_train_to_id" (int): Arrival city ID (for trains).
     * - "id_iata_from" (string): Departure city IATA code (for air).
     * - "id_iata_to" (string): Arrival city IATA code (for air).
     * - "station_id_from" (int): Departure station ID.
     * - "station_id_to" (int): Arrival station ID.
     * - "date" (string): Date of departure (yyyy-mm-dd).
     * - "only_by_stations" (int): 0/1 - search only by stations if no results by cities.
     * - "period" (int): Number of days to search around the specified date [-3..14].
     * - "currency" (string): Currency for the response (e.g., EUR, RON, MDL, etc.).
     * - "interval_id" (string): Interval of a previous flight.
     * - "route_id" (int): Specific route ID to search.
     * - "trans" (string): Type of transport (e.g., 'bus', 'train', 'air').
     * - "search_type" (int): Search type [1..3].
     * - "find_order_id" (int): Order ID for checking a ticket.
     * - "find_ticket_id" (int): Ticket ID to be checked.
     * - "find_security" (int): Security code for checking a ticket.
     * - "change" (string): Availability of transfers [auto, 0..25].
     * - "direct" (int): Availability of direct transfers [0, 1].
     * - "baggage_no" (int): Availability of luggage [0, 1].
     * - "service_class" (string): Class of service (for air) [A, E, B].
     * - "adt" (int): Number of adult passengers.
     * - "chd" (int): Number of child passengers under 12.
     * - "inf" (int): Number of infants under 2.
     * - "sort_type" (string): Sorting type [time, price].
     * - "get_all_departure" (int): Include sold-out routes [0, 1].
     * - "ws" (int): Which routes to search for [0, 1, 2].
     * - "lang" (string): Language for the response [en, ru, ua, de, pl, cz].
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with available routes.
     */
    public function getRoutes(array $params = [])
    {
        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_routes_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_routes'], function () use ($params) {
            return $this->makeRequest('/curl/get_routes.php', $params);
        });
    }

    /**
     * Retrieve route schedule based on the provided timetable ID.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "timetable_id" (string): Schedule ID, obtained from the get_routes query.
     * - "lang" (string): The language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the route schedule.
     */
    public function getAllRoutes(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['timetable_id'])) {
            throw new \InvalidArgumentException('The "timetable_id" parameter is required and cannot be empty.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_all_routes_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_all_routes'], function () use ($params) {
            return $this->makeRequest('/curl/get_all_routes.php', $params);
        });
    }

    /**
     * Retrieve baggage information for a specific route.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "interval_id" (string): Interval ID obtained from a get_routes or get_all_routes request.
     * - "station_from_id" (int): Station ID of departure.
     * - "station_to_id" (int): Station ID of arrival.
     * - "currency" (string): Response currency (e.g., EUR, RON, MDL, etc.).
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with baggage information.
     */
    public function getBaggage(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['interval_id']) || empty($params['station_from_id']) || empty($params['station_to_id'])) {
            throw new \InvalidArgumentException('The "interval_id", "station_from_id", and "station_to_id" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_baggage_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_baggage'], function () use ($params) {
            return $this->makeRequest('/curl/get_baggage.php', $params);
        });
    }

    /**
     * Retrieve available free seats for a specific interval and train.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "interval_id" (string): Interval ID obtained from a get_routes request.
     * - "train_id" (string): Train number obtained from the get_routes query (for train).
     * - "vagon_id" (string): Wagon number related to the train_id (for train).
     * - "currency" (string): Response currency (e.g., EUR, RON, MDL, etc.).
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with available free seats.
     */
    public function getFreeSeats(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['interval_id'])) {
            throw new \InvalidArgumentException('The "interval_id", "train_id", and "vagon_id" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_free_seats_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_free_seats'], function () use ($params) {
            return $this->makeRequest('/curl/get_free_seats.php', $params);
        });
    }

    /**
     * Retrieve seating plan based on bus or train type.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "bustype_id" (string): Bus type ID obtained from get_routes or get_free_seats (for bus).
     * - "vagon_type" (string): Vagon type (for train) [L, M, K, P, S, O].
     * - "position" (string): Seat layout [h (horizontal), v (vertical)].
     * - "v" (string): Version of the request (default "2.0").
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the seating plan.
     */
    public function getPlan(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['bustype_id'])) {
            throw new \InvalidArgumentException('The "bustype_id" (for bus) or "vagon_type" (for train) parameter is required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_plan_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_plan'], function () use ($params) {
            return $this->makeRequest('/curl/get_plan.php', $params);
        });
    }

    /**
     * Create a new order for a period depending on the lock_min condition.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "partner" (string): Affiliate site ID.
     * - "v" (string): Query version (default "1.1").
     * - "date" (array): Departure dates in format (yyyy-mm-dd).
     * - "interval_id" (array): Interval IDs for each route.
     * - "station_from_id" (array): IDs of departure stations.
     * - "station_to_id" (array): IDs of arrival stations.
     * - "seat" (array): Selected seats for each passenger on each route.
     * - "name" (array): Names of passengers.
     * - "surname" (array): Surnames of passengers.
     * - "middlename" (array): Patronymics of passengers.
     * - "birth_date" (array): Birthdates of passengers (yyyy-mm-dd).
     * - "doc_type" (array): Document types of passengers.
     * - "doc_number" (array): Document numbers of passengers.
     * - "doc_expire_date" (array): Expiration dates of travel documents (yyyy-mm-dd).
     * - "citizenship" (array): Citizenship of passengers.
     * - "gender" (array): Gender of passengers (M/F).
     * - "discount_id" (array): Discount IDs for passengers.
     * - "baggage" (array): Baggage IDs for each passenger on each route.
     * - "phone" (string): General phone number of all passengers.
     * - "phone2" (string): Additional phone number of all passengers.
     * - "email" (string): General email address for all passengers.
     * - "info" (string): Additional passenger information (optional).
     * - "currency" (string): Currency for the response (e.g., EUR, RON, MDL, etc.).
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after creating the new order.
     */
    public function newOrder(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['date']) || empty($params['interval_id']) || empty($params['station_from_id']) || empty($params['station_to_id'])) {
            throw new \InvalidArgumentException('Required parameters "date", "interval_id", "station_from_id", and "station_to_id" are missing.');
        }

        if (empty($params['seat']) || empty($params['name']) || empty($params['surname']) || empty($params['birth_date'])) {
            throw new \InvalidArgumentException('Passenger details such as "seat", "name", "surname", and "birth_date" are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_new_order_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['new_order'], function () use ($params) {
            return $this->makeRequest('/curl/new_order.php', $params);
        });
    }

    /**
     * Reserve a ticket with payment on boarding (if available for the carrier and flight).
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "v" (string): Query version (default "1.1").
     * - "order_id" (int): Order ID, obtained from the new_order request.
     * - "phone" (string): General telephone number for all passengers.
     * - "phone2" (string): Additional common telephone number for all passengers.
     * - "email" (string): General email address for all passengers.
     * - "info" (string): Passenger information (optional, e.g., seat preferences).
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after reserving the ticket.
     */
    public function reserveTicket(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['order_id']) || empty($params['phone']) || empty($params['email'])) {
            throw new \InvalidArgumentException('Required parameters "order_id", "phone", and "email" are missing.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_reserve_ticket_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['reserve_ticket'], function () use ($params) {
            return $this->makeRequest('/curl/reserve_ticket.php', $params);
        });
    }

    /**
     * Validate the reservation before booking tickets with payment on boarding.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "v" (string): Query version (default "1.1").
     * - "phone" (string): Phone number specified during new_order.
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after validating the reservation.
     */
    public function reserveValidation(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['phone'])) {
            throw new \InvalidArgumentException('The "phone" parameter is required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_reserve_validation_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['reserve_validation'], function () use ($params) {
            return $this->makeRequest('/curl/reserve_validation.php', $params);
        });
    }

    /**
     * Verify phone number by sending and checking an SMS code.
     * 
     * Required parameters:
     * - "sid_guest" (string): Session identifier.
     * - "v" (string): Query version (default "1.1").
     * - "phone" (string): Phone number used in the new_order request.
     * - "send_sms" (int): Send an SMS (1 for the 1st request).
     * - "check_sms" (int): Check code from SMS (1 for the 2nd request).
     * - "validation_code" (string): Code received via SMS (required for the 2nd request).
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after sending or verifying the SMS code.
     */
    public function smsValidation(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['sid_guest']) || empty($params['phone'])) {
            throw new \InvalidArgumentException('The "sid_guest" and "phone" parameters are required.');
        }

        // If the check_sms parameter is set, the validation_code parameter is required
        if (isset($params['check_sms']) && $params['check_sms'] == 1 && empty($params['validation_code'])) {
            throw new \InvalidArgumentException('The "validation_code" parameter is required for SMS code verification.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_sms_validation_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['sms_validation'], function () use ($params) {
            return $this->makeRequest('/curl/sms_validation.php', $params);
        });
    }

    /**
     * Retrieve full information about the entire order.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "order_id" (int): Order ID, obtained from new_order or buy_ticket request.
     * - "security" (string): Security order code.
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the full order information.
     */
    public function getOrder(array $params = [])
    {
        // Verificăm dacă parametrii necesari sunt furnizați
        if (empty($params['order_id']) || empty($params['security'])) {
            throw new \InvalidArgumentException('The "order_id" and "security" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_order_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_order'], function () use ($params) {
            return $this->makeRequest('/curl/get_order.php', $params);
        });
    }

    /**
     * Retrieve full information about a specific ticket or all tickets in an order.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "order_id" (int): Order ID to display all tickets, obtained from new_order or buy_ticket request.
     * - "ticket_id" (int): Ticket ID to display only 1 ticket (optional), obtained from buy_ticket request.
     * - "security" (string): Security code for the order or ticket.
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the ticket information.
     */
    public function getTicket(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['order_id']) || empty($params['security'])) {
            throw new \InvalidArgumentException('The "order_id" and "security" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_ticket_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_ticket'], function () use ($params) {
            return $this->makeRequest('/curl/get_ticket.php', $params);
        });
    }

    /**
     * Complete the purchase of a ticket based on a generated order.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "v" (string): Query version (default "1.1").
     * - "order_id" (int): Order ID, obtained from the new_order request.
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after purchasing the ticket.
     */
    public function buyTicket(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['order_id'])) {
            throw new \InvalidArgumentException('The "order_id" parameter is required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_buy_ticket_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['buy_ticket'], function () use ($params) {
            return $this->makeRequest('/curl/buy_ticket.php', $params);
        });
    }

    /**
     * Register dates for an OPEN ticket.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "v" (string): Query version (default "1.1").
     * - "interval_id" (string): Interval obtained from previous requests.
     * - "date" (string): Departure date (format: yyyy-mm-dd).
     * - "seat" (array): Selected seats for the ticket.
     * - "ticket_id" (array): Ticket IDs, obtained from buy_ticket or get_ticket requests.
     * - "security" (array): Security codes for the tickets.
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after registering the OPEN ticket.
     */
    public function regTicket(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['interval_id']) || empty($params['date']) || empty($params['seat']) || empty($params['ticket_id']) || empty($params['security'])) {
            throw new \InvalidArgumentException('The "interval_id", "date", "seat", "ticket_id", and "security" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_reg_ticket_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['reg_ticket'], function () use ($params) {
            return $this->makeRequest('/curl/reg_ticket.php', $params);
        });
    }

    /**
     * Cancel an unpaid order, cancel a reservation, or refund a paid ticket (full or partial).
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "session" (string): Session ID for tracking requests.
     * - "v" (string): Query version (default "1.1").
     * - "order_id" (int): Order ID to cancel all tickets, obtained from the new_order request.
     * - "ticket_id" (int): Ticket ID to cancel only one ticket (optional), obtained from the buy_ticket request.
     * - "lang" (string): Language for the API response (e.g., 'en', 'ru', 'ua', 'de', 'pl', 'cz').
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response after canceling the ticket or order.
     */
    public function cancelTicket(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['order_id'])) {
            throw new \InvalidArgumentException('The "order_id" parameter is required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_cancel_ticket_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['cancel_ticket'], function () use ($params) {
            return $this->makeRequest('/curl/cancel_ticket.php', $params);
        });
    }

    /**
     * Retrieve a list of tickets waiting for refund, marked for cancellation.
     * 
     * Required parameters:
     * - "login" (string): Your username for authentication.
     * - "password" (string): Your password for authentication.
     * - OR
     * - "sid" (string): Session ID for an authorized dealer (optional).
     * - "sid_guest" (string): Session ID for an authorized customer (optional).
     * - "sid_disp" (string): Session ID for an authorized dispatcher (optional).
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the list of tickets waiting for refund.
     */
    public function getBusTicketsReversal(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['login']) && empty($params['sid']) && empty($params['sid_guest']) && empty($params['sid_disp'])) {
            throw new \InvalidArgumentException('You must provide either "login" and "password" or one of the session IDs: "sid", "sid_guest", or "sid_disp".');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_bus_tickets_reversal_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_bus_tickets_reversal'], function () use ($params) {
            return $this->makeRequest('/curl/get_bus_tickets_reversal.php', $params);
        });
    }

    /**
     * Retrieve cash transactions based on a date range and status.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * - "date_from" (string): Start date for cash transactions (format: yyyy-mm-dd).
     * - "date_until" (string): End date for cash transactions (format: yyyy-mm-dd).
     * - "status" (string): Transaction status [buy, cancel, reversal, com_buy, com_cancel].
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the list of cash transactions.
     */
    public function getCash(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['login']) || empty($params['password']) || empty($params['date_from']) || empty($params['date_until']) || empty($params['status'])) {
            throw new \InvalidArgumentException('The "login", "password", "date_from", "date_until", and "status" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_cash_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_cash'], function () use ($params) {
            return $this->makeRequest('/curl/get_cash.php', $params);
        });
    }

    /**
     * Retrieve a list of orders based on filters like date, status, client info, etc.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * 
     * Optional parameters:
     * - "date_from" (string): Start date for order creation (format: yyyy-mm-dd).
     * - "date_until" (string): End date for order creation (format: yyyy-mm-dd).
     * - "order_id" (int): Order ID from the query new_order or buy_ticket.
     * - "ticket_id" (int): Ticket ID from the request buy_ticket.
     * - "status" (string): Order status [buy, cancel, reserve].
     * - "date_departure" (string): Departure date (format: yyyy-mm-dd).
     * - "date_reservation" (string): Reservation date (format: yyyy-mm-dd).
     * - "date_buy" (string): Payment date (format: yyyy-mm-dd).
     * - "client_surname" (string): Client's surname.
     * - "phone" (string): Client's phone number.
     * - "email" (string): Client's email address.
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the list of orders.
     */
    public function getOrders(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['login']) || empty($params['password'])) {
            throw new \InvalidArgumentException('The "login" and "password" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_orders_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_orders'], function () use ($params) {
            return $this->makeRequest('/curl/get_orders.php', $params);
        });
    }

    /**
     * Retrieve a list of tickets based on filters like date, status, and cancelled routes.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * 
     * Optional parameters:
     * - "date_from" (string): Start date for ticket creation (format: yyyy-mm-dd).
     * - "date_until" (string): End date for ticket creation (format: yyyy-mm-dd).
     * - "ticket_status" (string): Ticket status [buy, cancel, reserve].
     * - "canceled_routes" (int): 0/1 - Get tickets for cancelled flights (optional).
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the list of tickets.
     */
    public function getTickets(array $params = [])
    {
        // Check if required parameters are present
        if (empty($params['login']) || empty($params['password'])) {
            throw new \InvalidArgumentException('The "login" and "password" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_tickets_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_tickets'], function () use ($params) {
            return $this->makeRequest('/curl/get_tickets.php', $params);
        });
    }

    /**
     * Retrieve a list of tickets for carriers and dispatchers based on filters like date and type of date.
     * 
     * Required parameters:
     * - "login" (string): Your API login credentials.
     * - "password" (string): Your API password credentials.
     * 
     * Optional parameters:
     * - "date_from" (string): Start date for ticket creation or departure (format: yyyy-mm-dd).
     * - "date_until" (string): End date for ticket creation or departure (format: yyyy-mm-dd).
     * - "type_date" (string): Type of date [purchase, departure].
     *
     * @param array $params An associative array of parameters to pass to the API.
     * @return array The API response with the list of tickets for dispatchers.
     */
    public function getDispatcherTickets(array $params = [])
    {
        // Verificăm dacă parametrii necesari sunt furnizați
        if (empty($params['login']) || empty($params['password'])) {
            throw new \InvalidArgumentException('The "login" and "password" parameters are required.');
        }

        // Cache key based on parameters to avoid unnecessary repeated requests
        $cacheKey = 'busapi_get_dispatcher_tickets_' . md5(json_encode($params));

        // Use Laravel cache to store the response based on the cache time defined in config
        return Cache::remember($cacheKey, $this->config['cache_times']['get_dispatcher_tickets'], function () use ($params) {
            return $this->makeRequest('/curl_dispatcher/get_tickets.php', $params);
        });
    }




















}
