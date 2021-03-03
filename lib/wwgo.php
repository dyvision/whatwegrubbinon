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
                'access_type=offline',
                'redirect_uri=https://' . main_url . '/profile.php',
                'client_id=' . client_id
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
                    'client_id=' . client_id,
                    'client_secret=' . client_secret
                ];
            } else {
                $params = [
                    "refresh_token=$code",
                    "grant_type=$type",
                    'redirect_uri=https://' . main_url . '/profile.php',
                    'client_id=' . client_id,
                    'client_secret=' . client_secret
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
        function verify($token)
        {
            //create auth header
            $context = stream_context_create([
                "http" => [
                    "header" => "Authorization: Bearer $token"
                ]
            ]);

            //user info api
            $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json';

            //execute
            return file_get_contents($url, false, $context);
        }
    }
    class user
    {
        public $email;
        public $fullname;
        public $firstname;
        public $lastname;
        public $image;
        protected $id;
        protected $guid;
        protected $refresh_token;

        function __construct($id = null, $guid = null)
        {
            //get array
            $users = json_decode(file_get_contents(user_db_path), true);

            //search using main identifier
            $me = array_search($id, array_column($users, 'id'));

            //perform a comparitive function on the item number that was returned
            if ($users[$me]['id'] == $id and $users[$me]['guid'] == $guid) {

                //fill class properties
                $this->id = $users[$me]['id'];
                $this->guid = $users[$me]['guid'];
                $this->refresh_token = $users[$me]['refresh_token'];
                $this->email = $users[$me]['email'];
                $this->fullname = $users[$me]['fullname'];
                $this->firstname = $users[$me]['firstname'];
                $this->lastname = $users[$me]['lastname'];
                $this->image = $users[$me]['image'];

                //build response
                $result['message'] = 'User found, building class properties';
                $result['guid'] = $users[$me]['guid'];
            } else {

                //create a new user
                $new_user['id'] = $id;
                $new_user['guid'] = uniqid();


                //generate response
                $result['message'] = 'User not found, created new account';
                $result['guid'] = $new_user['guid'];


                //push into users array
                array_push($users, $new_user);

                //write the json version into the db
                $file = fopen(user_db_path, 'w');
                fwrite($file, json_encode($users));
                fclose($file);
            }

            //return the user's data
            return json_encode($result);
        }
        function get()
        {
            return $this;
        }
        function pull($token)
        {
            //create auth header
            $context = stream_context_create([
                "http" => [
                    "header" => "Authorization: Bearer $token"
                ]
            ]);

            //user info api
            $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json';

            //execute
            $pull = json_decode(file_get_contents($url, false, $context), true);

            return json_encode($pull);
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
