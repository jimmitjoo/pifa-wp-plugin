<?php

class API
{
    // http://pifa.jimmie/api/channels/1/feeds
    public $root = 'https://resilient-brook-fdgmnomofbq9.vapor-farm-c1.com/';

    private function connect($endpoint)
    {
        $curl = curl_init();
        $url = $this->root . $endpoint;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . get_option('pifa_api_key'),
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            //Only show errors while testing
            //echo "cURL Error #:" . $err;
            return $err;
        } else {
            //The API returns data in JSON format, so first convert that to an array of data objects
            return json_decode($response);
        }
    }

    public function channels()
    {
        return $this->connect('api/channels');
    }

    public function feeds($channel)
    {
        return $this->connect('api/channels/' . $channel . '/feeds');
    }

    public function feed($feedId, $page = 1)
    {
        return $this->connect('api/feed/' . $feedId . '?page=' . $page);
    }

    public function product($productId)
    {
        return $this->connect('api/feed/1/product/' . $productId);
    }

    public function createFeedLink($channel)
    {
        return $this->root . 'channels/' . $channel . '/create';
    }
}