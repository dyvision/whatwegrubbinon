<?php

namespace wwgo {

    //list of constants. making changes here will reflect everywhere
    const main_url = 'whatwegrubbinon.com';
    const client_id = '634372968316-6p6nf6j795lbja68pd6q35c74pqjb55s.apps.googleusercontent.com';
    const client_secret = 'yCe3n_MnFwWryOMvyvr_lTBx';
    //be sure to implode scopes
    const scopes = [
        'https%3A//www.googleapis.com/auth/userinfo.email',
        'https%3A//www.googleapis.com/auth/userinfo.profile',
        'openid'
    ];
    const food_db_path = '';
    const user_db_path = '';

    class auth
    {
        function __construct()
        {
            return;
        }
        function authorize()
        {
            $params = [
                'scope=' . implode(' ', scopes),
                'response_type=code',
                'redirect_uri=https://' . main_url . '/profile.php',
                'client_id=' . client_id,
                'access_type=offline'
            ];

            $paramstring = implode('&', $params);
            $url = "https://accounts.google.com/o/oauth2/v2/auth?$paramstring";
            header("location: $url");
        }
        function authenticate($code, $type)
        {
            //set the url to the authenticate api
            $url = 'https://oauth2.googleapis.com/token';

            //set the parameters for what we're authorizing
            if ($type == 'authorization_code') {
                $params = [
                    "code=$code",
                    "grant_type=$type",
                    'redirect_uri=https://' . main_url . '/profile.php',
                    'client_id='.client_id,
                    'client_secret='.client_secret
                ];
            } else {
                $params = [
                    "refresh_token=$code",
                    "grant_type=$type",
                    'redirect_uri=https://' . main_url . '/profile.php',
                    'client_id='.client_id,
                    'client_secret='.client_secret
                ];
            }

            //make it a string
            $body = implode('&', $params);

            //build authorization header
            $header = array(
                'Content-Type: application/x-www-form-urlencoded'
            );

            //send the post request

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $header,
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
        }
    }
    class user
    {
        function __construct()
        {
        }
    }
    class food
    {
        function __construct()
        {
        }
    }
    class tag
    {
        function __construct()
        {
        }
    }
}
