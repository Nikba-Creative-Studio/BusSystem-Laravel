<?php

use Tests\TestCase;
use Nikba\BusSystem\Facades\BusApi;

class BusApiServiceTest extends TestCase
{
    public function testGetPoints()
    {
        // Mock response data based on the example response
        $response = [
            [
                "point_id" => "90",
                "point_latin_name" => "Moskva",
                "point_ru_name" => "Москва",
                "point_ua_name" => "Москва",
                "point_name" => "Moskva",
                "country_name" => "Russia",
                "country_kod" => "RUS",
                "country_kod_two" => "RU",
                "country_id" => "5",
                "latitude" => "55.609899",
                "longitude" => "37.719727",
                "population" => "11500000",
                "point_name_detail" => "",
                "currency" => "RUB",
                "time_zone" => 3,
            ],
            [
                "point_id" => "2",
                "point_latin_name" => "Minsk",
                "point_ru_name" => "Минск",
                "point_ua_name" => "Минск",
                "point_name" => "Minsk",
                "country_name" => "Belarus",
                "country_kod" => "BLR",
                "country_kod_two" => "BY",
                "country_id" => "4",
                "latitude" => "53.8911344853093",
                "longitude" => "27.5510821236877",
                "population" => "11000000",
                "point_name_detail" => "",
                "currency" => "BYN",
                "time_zone" => 3,
            ],
            [
                "point_id" => "5775",
                "point_latin_name" => "Minsk airport",
                "point_ru_name" => "Минск аэропорт",
                "point_ua_name" => "Минск аэропорт",
                "point_name" => "Minsk airport",
                "country_name" => "Belarus",
                "country_kod" => "BLR",
                "country_kod_two" => "BY",
                "country_id" => "4",
                "latitude" => "53.889597",
                "longitude" => "28.032225",
                "population" => "10999999",
                "point_name_detail" => "",
                "currency" => "BYN",
                "time_zone" => 3,
            ],
        ];

        // Mocking the facade
        BusApi::shouldReceive('getPoints')
            ->once()
            ->with([
                'login' => 'test',
                'password' => 'test',
                'lang' => 'en',
                'country_id' => 1,
            ])
            ->andReturn($response);

        // Simulate the API request
        $result = BusApi::getPoints([
            'login' => 'test',
            'password' => 'test',
            'lang' => 'en',
            'country_id' => 1,
        ]);

        // Assert that the response matches the structure and data
        $this->assertEquals('90', $result[0]['point_id']);
        $this->assertEquals('Moskva', $result[0]['point_latin_name']);
        $this->assertEquals('Russia', $result[0]['country_name']);
        $this->assertEquals('RUB', $result[0]['currency']);
        $this->assertEquals('55.609899', $result[0]['latitude']);

        $this->assertEquals('2', $result[1]['point_id']);
        $this->assertEquals('Minsk', $result[1]['point_latin_name']);
        $this->assertEquals('Belarus', $result[1]['country_name']);
        $this->assertEquals('BYN', $result[1]['currency']);
    }

    public function testGetRoutes()
    {
        // Mock response data based on the example response
        $response = [
            [
                "trans" => "bus",
                "interval_id" => "local|100233|MzEwN3wyMDI0LTA5LTA5fHwyMDI0LTA5LTA5fE1ETHx8Mzk=|1725996600|2024-09-09T12:37:26||db9b1f21",
                "route_id" => "100233",
                "route_name" => "Praha - Kyiv",
                "carrier" => "Oles Trans Carrier",
                "comfort" => "wc,tv,coffee,1_baggage_free,sms_ticket,wifi,food,music,conditioner,220v",
                "price_one_way" => 1357.97,
                "currency" => "MDL",
                "date_from" => "2024-09-09",
                "time_from" => "22:30:00",
                "point_from" => "Praha",
                "station_from" => "Bus Station \"Florenc\"",
                "date_to" => "2024-09-10",
                "time_to" => "21:30:00",
                "point_to" => "Kiev",
                "station_to" => "Bus Station \"Kiev\", 32 S.Petlyry str. (Railway Station)",
                "free_seats_info" => [
                    "count" => [
                        "sitting" => 5,
                        "standing" => 0,
                    ],
                    "current_free_seats_typ" => "sitting",
                    "description" => "seat number is indicated by the driver when boarding the bus"
                ],
                "discounts" => [
                    [
                        "discount_id" => "3189",
                        "discount_name" => "10% The group of 6 people",
                        "discount_price" => 1222.17
                    ],
                    [
                        "discount_id" => "3190",
                        "discount_name" => "10% Pensioner 60 years old",
                        "discount_price" => 1222.17
                    ],
                ],
                "stations" => [
                    "departure" => [
                        [
                            "station_id" => "123",
                            "point_id" => "3",
                            "time" => "22:30:00",
                            "point_name" => "Praha",
                            "station_name" => "Bus Station \"Florenc\"",
                        ],
                    ],
                    "arrival" => [
                        [
                            "station_id" => "777",
                            "point_id" => "6",
                            "time" => "21:30:00",
                            "point_name" => "Kiev",
                            "station_name" => "Bus Station \"Kiev\", 32 S.Petlyry str. (Railway Station)",
                        ],
                    ],
                ],
            ]
        ];

        // Mocking the facade
        BusApi::shouldReceive('getRoutes')
            ->once()
            ->with([
                'id_from' => 1,
                'id_to' => 2,
                'date' => '2024-09-09',
                'trans' => 'bus'
            ])
            ->andReturn($response);

        // Simulate the API request
        $result = BusApi::getRoutes([
            'id_from' => 1,
            'id_to' => 2,
            'date' => '2024-09-09',
            'trans' => 'bus'
        ]);

        // Assert the response structure and data
        $this->assertEquals('bus', $result[0]['trans']);
        $this->assertEquals('100233', $result[0]['route_id']);
        $this->assertEquals('Praha - Kyiv', $result[0]['route_name']);
        $this->assertEquals('Oles Trans Carrier', $result[0]['carrier']);
        $this->assertEquals('MDL', $result[0]['currency']);
        $this->assertEquals(1357.97, $result[0]['price_one_way']);

        // Assert the free seats info
        $this->assertEquals(5, $result[0]['free_seats_info']['count']['sitting']);
        $this->assertEquals('sitting', $result[0]['free_seats_info']['current_free_seats_typ']);

        // Assert discounts
        $this->assertEquals('3189', $result[0]['discounts'][0]['discount_id']);
        $this->assertEquals('10% The group of 6 people', $result[0]['discounts'][0]['discount_name']);
        $this->assertEquals(1222.17, $result[0]['discounts'][0]['discount_price']);

        // Assert station info
        $this->assertEquals('Praha', $result[0]['stations']['departure'][0]['point_name']);
        $this->assertEquals('Kiev', $result[0]['stations']['arrival'][0]['point_name']);
    }
}