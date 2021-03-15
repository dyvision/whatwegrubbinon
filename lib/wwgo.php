<?php

//to be added
#logic to give recommendations
#front end button to delete recipe from wallet
#front end button for 'tried it'
#tags system

//ideas
#share class: add a "following" array to your user object of user id's / create a "following" list on your profile page / if you have another person's id it will include their recipe wallet when showing available recipes
#chrome extension to quickly add url with it's name (like a bookmark) and use the seo image as the reference image

namespace wwgo {

    //list of constants. making changes here will reflect everywhere
    const main_url = 'whatwegrubbinon.com';
    const redir_page = 'login';
    const client_id = '634372968316-6p6nf6j795lbja68pd6q35c74pqjb55s.apps.googleusercontent.com';
    const client_secret = 'yCe3n_MnFwWryOMvyvr_lTBx';
    //be sure to implode scopes
    const scopes = [
        'https%3A//www.googleapis.com/auth/userinfo.email',
        'https%3A//www.googleapis.com/auth/userinfo.profile',
        'openid'
    ];
    const recipe_db_path = '/var/www/html/db/whatwegrubbinon/recipes.json';
    const user_db_path = '/var/www/html/db/whatwegrubbinon/users.json';
    const email_db_path = '/var/www/html/db/whatwegrubbinon/emails.json';
    const site_filter = [
        'food',
        'recipe',
        'spice',
        'vegan',
        'vegetarian',
        'meat',
        'fish',
        'mushroom',
        'yum',
        'tasty',
        'epicurious',
        'delcicious',
        'delish',
        'grub',
        'cook',
        'bake'
    ];

    class auth
    {
        /**
         * Builds a Auth Class Client to use the functions
         */
        function __construct()
        {
            return;
        }
        /**
         * Redirects users to an authorization page where they can log in using their Google account. The redirect URI will bring them back to WWGO's login.php page after authorizing and it will provide an authorization code which is then used in the authentication function
         *
         * @return redirect
         */
        function authorize()
        {
            $params = [
                'scope=' . implode(' ', scopes),
                'response_type=code',
                'access_type=offline',
                'redirect_uri=https://' . main_url . '/' . redir_page,
                'client_id=' . client_id
            ];

            $paramstring = implode('&', $params);
            $url = "https://accounts.google.com/o/oauth2/v2/auth?$paramstring";
            header("location: $url");
        }
        /**
         * After running the authorize function and gaining access to the user's code it will use this to push out a request for that users access_token and refresh_token. The access token is used when a user is working directly with the Google API. The refresh token is something we save in our system so that if we run any form of Google integration on the backend we can act as that user and update their information by using this function to get another access token
         *
         * @param string $code The code or refresh_token you're using to gain access to your Google Access Token
         * @param string $type Either 'authorization_code' or 'refresh_token'
         * @return string - JSON - You receive a JSON formatted object back with fields like 'access_token' and 'refresh_token'
         */
        function authenticate($code, $type)
        {
            //set the url to the authenticate api
            $url = 'https://oauth2.googleapis.com/token';

            //set the parameters for what we're authorizing
            if ($type == 'authorization_code') {
                $params = [
                    "code=$code",
                    "grant_type=$type",
                    'redirect_uri=https://' . main_url . '/' . redir_page,
                    'client_id=' . client_id,
                    'client_secret=' . client_secret
                ];
            } else {
                $params = [
                    "refresh_token=$code",
                    "grant_type=$type",
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
        /**
         * This function confirms that user exists in Google
         *
         * @param string $token The access token for said user
         * @return string JSON - A JSON object with 'id','guid', and 'message' fields
         */
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
            $pull = json_decode(file_get_contents($url, false, $context), true);

            //open user db
            $users = json_decode(file_get_contents(user_db_path), true);

            //check to see if user exists
            $me = array_search($pull['id'], array_column($users, 'id'));

            if ($users[$me]['id'] == $pull['id']) {
                $result['id'] = $pull['id'];
                $result['guid'] = $users[$me]['guid'];
                $result['message'] = 'User found';
            } else {
                $result['id'] = $pull['id'];
                $result['guid'] = '';
                $result['message'] = 'User not found';
            }
            return json_encode($result);
        }
        /**
         * A function to confirm the user exists in the WWGO system without providing a token. If not found this will lock a person from accessing any of the protected pages. Use this on API pages
         *
         * @param string $apikey - The User's API Key which is also their Google ID
         * @param string $apisecret - The User's API Secret which is also their WWGO GUID
         * @return void
         */
        function api_verify($apikey, $apisecret)
        {
            //get array
            $users = json_decode(file_get_contents(user_db_path), true);

            //search using main identifier
            $me = array_search($apikey, array_column($users, 'id'));

            //perform a comparitive function on the item number that was returned
            if ($users[$me]['id'] == $apikey and $users[$me]['guid'] == $apisecret) {
                return json_encode($users[$me]);
            } else {
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Basic realm="Enter APIKEY and APISECRET"');
                exit("Access Denied: User not found.");
            }
        }
    }
    /**
     * A class to access user data. Functions include getting user data, syncing user data from Google, creating a user, logging a user in, and logging a user out
     * @var string $id The Accessor's User ID - This is the Google ID but is stored in WWGO as well to reference the user and their refresh token
     * @var string $access_token The Accessor's Google Access Token
     * @var string $refresh_token The Accessor's Google Refresh Token
     * @var string $email The User's Google Email
     * @var string $fullname The User's Google Full Name
     * @var string $firstname The User's Google First Name 
     * @var string $lastname The User's Google Last Name 
     * @var string $image The User's Google Profile Picture
     * @var string $guid The User's GUID - This is a WWGO secret to confirm the user is who they say they are. It is randomly generated on creation 
     * @link null No Public API Endpoint
     */
    class user
    {
        public $email;
        public $fullname;
        public $firstname;
        public $lastname;
        public $image;
        public $id;
        public $guid;
        protected $refresh_token;
        protected $access_token;

        /**
         * Constructing a User Class will look up a user based on their id and guid or store data in the class to create the user soon after. A token is always required at this point because it pulls data from Google for multiple functions. If you don't have you token yet use the Auth class and authorize/authenticate functions        
         * @param string $token Required - The User's Google Access Token. This is used in the pull function to grab data from Google (may integrate with other APIs later which is why it's required at this level and not simply in the pull function. If you don't have this use the Auth class and authorize/authenticate functions)
         * @param string $id Required - The User's Google ID. Used to lookup user or create a new user and have it sync with Google
         * @param string $guid Semi-Required - Required to use the User class for an existing user. Not required for creating a new user
         * @param string $refresh_token Semi-Required - Required for creating a new user. Not required for anything else. Will be overlooked if provided for an existing user
         * @return string JSON - Returns a json with a "message" field and the user's "guid"
         */
        function __construct($token, $id, $guid = null, $refresh_token = null)
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
                $this->access_token = $token;

                //build response
                $result['message'] = 'User found, building class properties';
                $result['guid'] = $users[$me]['guid'];
            } else {

                //create new user
                $this->refresh_token = $refresh_token;
                $this->id = $id;
                $this->guid = uniqid();


                //generate response
                $result['message'] = 'User not found';
                $result['guid'] = $this->guid;
            }

            //return the user's data
            return json_encode($result);
        }
        /**
         * Returns User's data
         *
         * @return string JSON - Returns a JSON formatted version of the User Class. See the class description for the returned variables
         */
        function get()
        {
            return json_encode($this);
        }
        /**
         * Uses the class's Google Access Token to retrieve and update the related user's User data in the user_db_path file
         *
         * @return void
         */
        function pull()
        {
            //create auth header
            $context = stream_context_create([
                "http" => [
                    "header" => "Authorization: Bearer $this->access_token"
                ]
            ]);

            //user info api
            $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json';

            //execute
            $pull = json_decode(file_get_contents($url, false, $context), true);

            //get array
            $users = json_decode(file_get_contents(user_db_path), true);

            //search using main identifier
            $me = array_search($this->id, array_column($users, 'id'));

            //perform a comparitive function on the item number that was returned
            if ($users[$me]['id'] == $this->id and $users[$me]['guid'] == $this->guid) {
                $this->email = $pull['email'];
                $this->fullname = $pull['name'];
                $this->firstname = $pull['given_name'];
                $this->lastname = $pull['family_name'];
                $this->image = $pull['picture'];

                $users[$me]['email'] = $pull['email'];
                $users[$me]['fullname'] = $pull['name'];
                $users[$me]['firstname'] = $pull['given_name'];
                $users[$me]['lastname'] = $pull['family_name'];
                $users[$me]['image'] = $pull['picture'];

                //write the json version into the db
                $file = fopen(user_db_path, 'w');
                fwrite($file, json_encode($users));
                fclose($file);
            }

            return;
        }
        /**
         * Creates a user object in the user_db_path file. Only logs the ID, GUID, and Refresh Token. To sync the user's data use the pull function
         *
         * @return void
         */
        function create()
        {
            //get user db
            $users = json_decode(file_get_contents(user_db_path), true);

            //create array for json
            $new_user['id'] = $this->id;
            $new_user['guid'] = $this->guid;
            $new_user['refresh_token'] = $this->refresh_token;

            //push into users array
            array_push($users, $new_user);

            //write the json version into the db
            $file = fopen(user_db_path, 'w');
            fwrite($file, json_encode($users));
            fclose($file);
        }
        /**
         * Logs the user into WWGO by creating the id, guid, and refresh cookies
         *
         * @return void
         */
        function login()
        {
            setcookie('id', $this->id, 0, '/');
            setcookie('guid', $this->guid, 0, '/');
            setcookie('refresh_token', $this->refresh_token, 0, '/');
        }
        /**
         * Logs the user out of WWGO by deleting the id, guid, and refresh cookies
         *
         * @return void
         */
        function logout()
        {
            setcookie('id', null, 0, '/');
            setcookie('guid', null, 0, '/');
            setcookie('refresh_token', null, 0, '/');
        }
    }
    /**
     * A class to access recipe data. Functions include getting recipe data, creating recipe data, and deleting recipe data
     * @var string $id The Accessor's User ID
     * @var string $rid The Recipe ID
     * @var string $name The Recipe's Name
     * @var string $url The Recipe's URL
     * @var string $image The Recipe's Image URL
     * @link https://whatwegrubbinon.com/api/recipe Public API Endpoint 
     */
    class recipe
    {
        public $rid;
        public $name;
        public $image;
        public $url;
        protected $id;

        /**
         * This function is used to construct the Recipe Class. The construct searches the user DB by ID to confirm the user exists in the system. The supporting class functions pull data based on the actual recipe
         * @param string $id The User ID
         * @return void Constructs a client to access recipes
         */
        function __construct($id)
        {
            $this->id = $id;
            return;
        }
        /**
         * This function does 2 things: If a RID is provided it will lookup a specific recipe and return a single JSON object. If RID is not provided it will return all related recipes according to the recipe client that was constructed
         * @param string $rid Not Required - The Recipe ID to lookup
         * @return string JSON - Returns either an array of all related recipes or an object of a single recipe
         */
        function get($rid = null)
        {
            if ($rid != null) {
                //get array
                $recipes = json_decode(file_get_contents(recipe_db_path), true);

                //search using main identifier
                $recipe = array_search($rid, array_column($recipes, 'rid'));

                //perform a comparitive function on the item number that was returned
                if ($recipes[$recipe]['rid'] == $rid and in_array($this->id, $recipes[$recipe]['id'])) {
                    $this->rid = $recipes[$recipe]['rid'];
                    $this->name = $recipes[$recipe]['name'];
                    $this->image = $recipes[$recipe]['image'];
                    $this->url = $recipes[$recipe]['url'];
                } else {
                    $this->name = 'Not Found';
                }
                return json_encode($this);
            } else {
                //get array
                $recipes = json_decode(file_get_contents(recipe_db_path), true);

                $output = [];

                foreach ($recipes as $recipe) {
                    if (in_array($this->id, $recipe['id'])) {
                        array_push($output, $recipe);
                    }
                }
                return json_encode($output);
            }
        }
        /**
         * This function creates a new recipe in the recipe_db_path DB
         * @param string $name The Recipe's Name
         * @param string $image The Recipe's Image URL (right click > copy image address)
         * @param string $url The Recipe's URL
         * @return string JSON - Returns a success message
         */
        function create($url)
        {
            //get array
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            $meta = get_meta_tags($url);

            $this->image = $meta['twitter:image'];

            if ($this->image == '') {
                $this->image = $meta['pinterest:media'];
            }

            if ($this->image == '') {
                $this->image = $meta['og:image'];
            }

            $this->name = $meta['twitter:title'];

            if ($this->name == '') {
                $this->name = $meta['pinterest:title'];
            }

            if ($this->name == '') {
                $this->name = $meta['og:title'];
            }

            $this->rid = uniqid();


            $this->url = 'https://justtherecipe.app/recipe?url=' . $url;

            $recipe['rid'] = $this->rid;
            $recipe['name'] = $this->name;
            $recipe['image'] = $this->image;
            $recipe['url'] = $this->url;
            $recipe['id'] = [$this->id];

            array_push($recipes, $recipe);

            $file = fopen(recipe_db_path, 'w');
            fwrite($file, json_encode($recipes));
            fclose($file);

            return json_encode($result['message'] = $this->rid . ' Created');
        }
        /**
         * This function deletes a recipe in the recipe_db_path DB
         * @param string $rid The Recipe's ID
         * @return string JSON - Returns a success message
         */
        function delete($rid)
        {
            //get array
            $recipes = json_decode(file_get_contents(recipe_db_path), true);
            //search using main identifier
            $recipe = array_search($rid, array_column($recipes, 'rid'));

            //perform a comparitive function on the item number that was returned
            if ($recipes[$recipe]['rid'] == $rid and in_array($this->id, $recipes[$recipe]['id'])) {
                $replace = array_search($this->id, $recipes[$recipe]['id']);
                unset($recipes[$recipe]['id'][$replace]);
            }
            $file = fopen(recipe_db_path, 'w');
            fwrite($file, json_encode($recipes));
            fclose($file);
            return json_encode($result['message'] = $rid . " Deleted");
        }
        function add_user($rid)
        {
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            //search using main identifier
            $recipe = array_search($rid, array_column($recipes, 'rid'));

            //perform a comparitive function on the item number that was returned
            if ($recipes[$recipe]['rid'] == $rid and !in_array($this->id, $recipes[$recipe]['id'])) {
                array_push($recipes[$recipe]['id'], $this->id);

                $file = fopen(recipe_db_path, 'w');
                fwrite($file, json_encode($recipes));
                fclose($file);
                return json_encode($result['message'] = $rid . " added to wallet");
            }
        }
        function explore()
        {
            return file_get_contents(recipe_db_path);
        }
    }
    class tag
    {
        function __construct()
        {
        }
    }
    class recommendation
    {
        public $id;
        public $tz;
        public $email;

        function __construct($id)
        {
            $this->id = $id;
            return;
        }
        function generate()
        {
            //get array
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            $output = [];

            foreach ($recipes as $recipe) {
                if (in_array($this->id, $recipe['id'])) {
                    array_push($output, $recipe);
                }
            }
            return json_encode($output[rand(0, count($output) - 1)]);
        }
        function get()
        {
            return file_get_contents(email_db_path);
        }
        function send($rid, $email)
        {
            //get array
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            //search using main identifier
            $recipe = array_search($rid, array_column($recipes, 'rid'));

            //perform a comparitive function on the item number that was returned
            if ($recipes[$recipe]['rid'] == $rid) {
                $msg = "<body style='background:url('https://whatwegrubbinon.com/style/profile.jpg')'>
                
                <center style='padding: 0%;
                box-shadow: 2px 4px 8px #00000054;
                margin: 1%;
                font-family: helvetica;
                display:inline-block;
                transition: ease 300ms;
                min-width: 80%;
                max-width: 80%;
                padding: 10px;
                max-height: 90%;
                min-height: 90%;
                vertical-align: top;
                text-align: left;
                overflow-y: visible;'>

                <h1 style='font-size:72px;'>What We Grubbin' On</h1>

                <a href='" . $recipes[$recipe]['url'] . "'>

                <h1>Here's your recommendation: " . $recipes[$recipe]['name'] . "</h1></a>
                
                <h1>Looking to try something new? <a href='https://whatwegrubbinon.com/explore'>Check out our new Explore page.</a></h1>
                
                <h1>Explore recipes other users have in their recipe wallet.</h1>
                
                <h1>Add recipes to your collection with one click!</h1>
                

                </center>
                
                </body>";

                $url = 'https://prod-31.eastus2.logic.azure.com:443/workflows/1393bae12b3248d6a0f355e6ef0a444f/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=w2UOyo3iOiC9_bUKVYPEwM_IYYYYPvRc7QYN1t-HaNw';
                $sub = 'What We Grubbin\' On: ' . $recipes[$recipe]['name'];

                $post['subject'] = $sub;
                $post['body'] = $msg;
                $post['to'] = $email;

                $body = json_encode($post);

                $header = array(
                    'Content-Type: application/json'
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
        function create($id, $tz, $type, $email)
        {
            //get array
            $recs = json_decode(file_get_contents(email_db_path), true);

            $this->tid = uniqid();
            $this->id = $id;
            $this->tz = $tz;
            $this->type = $type;
            $this->email = $email;

            $rec['tid'] = $this->tid;
            $rec['id'] = $id;
            $rec['tz'] = $tz;
            $rec['type'] = $type;
            $rec['email'] = $email;

            array_push($recs, $rec);

            $file = fopen(email_db_path, 'w');
            fwrite($file, json_encode($recs));
            fclose($file);

            $result['message'] = $this->tid . ' Created';
            $result['data'] = $this;

            return json_encode($result);
        }
        function delete($tid)
        {
            $new_recs = [];
            $recs = json_decode(file_get_contents(email_db_path), true);
            foreach ($recs as $rec) {
                if ($rec['tid'] == $tid and $rec['id'] == $this->id) {
                } else {
                    array_push($new_recs, $rec);
                }
            }
            $file = fopen(email_db_path, 'w');
            fwrite($file, json_encode($new_recs));
            fclose($file);
            return json_encode($result['message'] = $tid . " Deleted");
        }
    }
    class visual
    {
        function __construct()
        {
            return;
        }
        function header()
        {
            if (isset($_COOKIE['id'])) {
                $header = "<div id='navbar'>
                <a class='navbar-item' href='/'><h3 style='display:inline-block;margin:0;color: rgb(0 226 157);font-size:30px;'>WW</h3><h3 style='margin:0;display:inline-block;font-size:30px;'>GO</h3></a>
                <h3 class='navbar-item-right' onclick='logout();'><a>Logout</a></h3>
                <h3 class='navbar-item-right'><a href='profile'>Profile</a></h3>
                <h3 class='navbar-item-right'><a href='explore'>Explore</a></h3>
                </div>";
            } else {
                $header = "<div id='navbar'>
                <a class='navbar-item' href='/'><h3 style='display:inline-block;margin:0;color: rgb(0 226 157);font-size:30px;'>WW</h3><h3 style='margin:0;display:inline-block;font-size:30px;'>GO</h3></a>
                <h3 class='navbar-item-right'><a href='authorize'>Login with Google</a></h3>
                </div>";
            }
            return $header;
        }
        function timezone()
        {
            echo '<select name="tz" width="100%">
            <option timeZoneId="1" gmtAdjustment="GMT-12:00" useDaylightTime="0" value="-12">(GMT-12:00) International Date Line West</option>
            <option timeZoneId="2" gmtAdjustment="GMT-11:00" useDaylightTime="0" value="-11">(GMT-11:00) Midway Island, Samoa</option>
            <option timeZoneId="3" gmtAdjustment="GMT-10:00" useDaylightTime="0" value="-10">(GMT-10:00) Hawaii</option>
            <option timeZoneId="4" gmtAdjustment="GMT-09:00" useDaylightTime="1" value="-9">(GMT-09:00) Alaska</option>
            <option timeZoneId="5" gmtAdjustment="GMT-08:00" useDaylightTime="1" value="-8">(GMT-08:00) Pacific Time (US & Canada)</option>
            <option timeZoneId="6" gmtAdjustment="GMT-08:00" useDaylightTime="1" value="-8">(GMT-08:00) Tijuana, Baja California</option>
            <option timeZoneId="7" gmtAdjustment="GMT-07:00" useDaylightTime="0" value="-7">(GMT-07:00) Arizona</option>
            <option timeZoneId="8" gmtAdjustment="GMT-07:00" useDaylightTime="1" value="-7">(GMT-07:00) Chihuahua, La Paz, Mazatlan</option>
            <option timeZoneId="9" gmtAdjustment="GMT-07:00" useDaylightTime="1" value="-7">(GMT-07:00) Mountain Time (US & Canada)</option>
            <option timeZoneId="10" gmtAdjustment="GMT-06:00" useDaylightTime="0" value="-6">(GMT-06:00) Central America</option>
            <option timeZoneId="11" gmtAdjustment="GMT-06:00" useDaylightTime="1" value="-6">(GMT-06:00) Central Time (US & Canada)</option>
            <option timeZoneId="12" gmtAdjustment="GMT-06:00" useDaylightTime="1" value="-6">(GMT-06:00) Guadalajara, Mexico City, Monterrey</option>
            <option timeZoneId="13" gmtAdjustment="GMT-06:00" useDaylightTime="0" value="-6">(GMT-06:00) Saskatchewan</option>
            <option timeZoneId="14" gmtAdjustment="GMT-05:00" useDaylightTime="0" value="-5">(GMT-05:00) Bogota, Lima, Quito, Rio Branco</option>
            <option timeZoneId="15" gmtAdjustment="GMT-05:00" useDaylightTime="1" value="-5">(GMT-05:00) Eastern Time (US & Canada)</option>
            <option timeZoneId="16" gmtAdjustment="GMT-05:00" useDaylightTime="1" value="-5">(GMT-05:00) Indiana (East)</option>
            <option timeZoneId="17" gmtAdjustment="GMT-04:00" useDaylightTime="1" value="-4">(GMT-04:00) Atlantic Time (Canada)</option>
            <option timeZoneId="18" gmtAdjustment="GMT-04:00" useDaylightTime="0" value="-4">(GMT-04:00) Caracas, La Paz</option>
            <option timeZoneId="19" gmtAdjustment="GMT-04:00" useDaylightTime="0" value="-4">(GMT-04:00) Manaus</option>
            <option timeZoneId="20" gmtAdjustment="GMT-04:00" useDaylightTime="1" value="-4">(GMT-04:00) Santiago</option>
            <option timeZoneId="21" gmtAdjustment="GMT-03:30" useDaylightTime="1" value="-3.5">(GMT-03:30) Newfoundland</option>
            <option timeZoneId="22" gmtAdjustment="GMT-03:00" useDaylightTime="1" value="-3">(GMT-03:00) Brasilia</option>
            <option timeZoneId="23" gmtAdjustment="GMT-03:00" useDaylightTime="0" value="-3">(GMT-03:00) Buenos Aires, Georgetown</option>
            <option timeZoneId="24" gmtAdjustment="GMT-03:00" useDaylightTime="1" value="-3">(GMT-03:00) Greenland</option>
            <option timeZoneId="25" gmtAdjustment="GMT-03:00" useDaylightTime="1" value="-3">(GMT-03:00) Montevideo</option>
            <option timeZoneId="26" gmtAdjustment="GMT-02:00" useDaylightTime="1" value="-2">(GMT-02:00) Mid-Atlantic</option>
            <option timeZoneId="27" gmtAdjustment="GMT-01:00" useDaylightTime="0" value="-1">(GMT-01:00) Cape Verde Is.</option>
            <option timeZoneId="28" gmtAdjustment="GMT-01:00" useDaylightTime="1" value="-1">(GMT-01:00) Azores</option>
            <option timeZoneId="29" gmtAdjustment="GMT+00:00" useDaylightTime="0" value="0">(GMT+00:00) Casablanca, Monrovia, Reykjavik</option>
            <option timeZoneId="30" gmtAdjustment="GMT+00:00" useDaylightTime="1" value="0">(GMT+00:00) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London</option>
            <option timeZoneId="31" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna</option>
            <option timeZoneId="32" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague</option>
            <option timeZoneId="33" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) Brussels, Copenhagen, Madrid, Paris</option>
            <option timeZoneId="34" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb</option>
            <option timeZoneId="35" gmtAdjustment="GMT+01:00" useDaylightTime="1" value="1">(GMT+01:00) West Central Africa</option>
            <option timeZoneId="36" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Amman</option>
            <option timeZoneId="37" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Athens, Bucharest, Istanbul</option>
            <option timeZoneId="38" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Beirut</option>
            <option timeZoneId="39" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Cairo</option>
            <option timeZoneId="40" gmtAdjustment="GMT+02:00" useDaylightTime="0" value="2">(GMT+02:00) Harare, Pretoria</option>
            <option timeZoneId="41" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius</option>
            <option timeZoneId="42" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Jerusalem</option>
            <option timeZoneId="43" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Minsk</option>
            <option timeZoneId="44" gmtAdjustment="GMT+02:00" useDaylightTime="1" value="2">(GMT+02:00) Windhoek</option>
            <option timeZoneId="45" gmtAdjustment="GMT+03:00" useDaylightTime="0" value="3">(GMT+03:00) Kuwait, Riyadh, Baghdad</option>
            <option timeZoneId="46" gmtAdjustment="GMT+03:00" useDaylightTime="1" value="3">(GMT+03:00) Moscow, St. Petersburg, Volgograd</option>
            <option timeZoneId="47" gmtAdjustment="GMT+03:00" useDaylightTime="0" value="3">(GMT+03:00) Nairobi</option>
            <option timeZoneId="48" gmtAdjustment="GMT+03:00" useDaylightTime="0" value="3">(GMT+03:00) Tbilisi</option>
            <option timeZoneId="49" gmtAdjustment="GMT+03:30" useDaylightTime="1" value="3.5">(GMT+03:30) Tehran</option>
            <option timeZoneId="50" gmtAdjustment="GMT+04:00" useDaylightTime="0" value="4">(GMT+04:00) Abu Dhabi, Muscat</option>
            <option timeZoneId="51" gmtAdjustment="GMT+04:00" useDaylightTime="1" value="4">(GMT+04:00) Baku</option>
            <option timeZoneId="52" gmtAdjustment="GMT+04:00" useDaylightTime="1" value="4">(GMT+04:00) Yerevan</option>
            <option timeZoneId="53" gmtAdjustment="GMT+04:30" useDaylightTime="0" value="4.5">(GMT+04:30) Kabul</option>
            <option timeZoneId="54" gmtAdjustment="GMT+05:00" useDaylightTime="1" value="5">(GMT+05:00) Yekaterinburg</option>
            <option timeZoneId="55" gmtAdjustment="GMT+05:00" useDaylightTime="0" value="5">(GMT+05:00) Islamabad, Karachi, Tashkent</option>
            <option timeZoneId="56" gmtAdjustment="GMT+05:30" useDaylightTime="0" value="5.5">(GMT+05:30) Sri Jayawardenapura</option>
            <option timeZoneId="57" gmtAdjustment="GMT+05:30" useDaylightTime="0" value="5.5">(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi</option>
            <option timeZoneId="58" gmtAdjustment="GMT+05:45" useDaylightTime="0" value="5.75">(GMT+05:45) Kathmandu</option>
            <option timeZoneId="59" gmtAdjustment="GMT+06:00" useDaylightTime="1" value="6">(GMT+06:00) Almaty, Novosibirsk</option>
            <option timeZoneId="60" gmtAdjustment="GMT+06:00" useDaylightTime="0" value="6">(GMT+06:00) Astana, Dhaka</option>
            <option timeZoneId="61" gmtAdjustment="GMT+06:30" useDaylightTime="0" value="6.5">(GMT+06:30) Yangon (Rangoon)</option>
            <option timeZoneId="62" gmtAdjustment="GMT+07:00" useDaylightTime="0" value="7">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
            <option timeZoneId="63" gmtAdjustment="GMT+07:00" useDaylightTime="1" value="7">(GMT+07:00) Krasnoyarsk</option>
            <option timeZoneId="64" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi</option>
            <option timeZoneId="65" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Kuala Lumpur, Singapore</option>
            <option timeZoneId="66" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Irkutsk, Ulaan Bataar</option>
            <option timeZoneId="67" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Perth</option>
            <option timeZoneId="68" gmtAdjustment="GMT+08:00" useDaylightTime="0" value="8">(GMT+08:00) Taipei</option>
            <option timeZoneId="69" gmtAdjustment="GMT+09:00" useDaylightTime="0" value="9">(GMT+09:00) Osaka, Sapporo, Tokyo</option>
            <option timeZoneId="70" gmtAdjustment="GMT+09:00" useDaylightTime="0" value="9">(GMT+09:00) Seoul</option>
            <option timeZoneId="71" gmtAdjustment="GMT+09:00" useDaylightTime="1" value="9">(GMT+09:00) Yakutsk</option>
            <option timeZoneId="72" gmtAdjustment="GMT+09:30" useDaylightTime="0" value="9.5">(GMT+09:30) Adelaide</option>
            <option timeZoneId="73" gmtAdjustment="GMT+09:30" useDaylightTime="0" value="9.5">(GMT+09:30) Darwin</option>
            <option timeZoneId="74" gmtAdjustment="GMT+10:00" useDaylightTime="0" value="10">(GMT+10:00) Brisbane</option>
            <option timeZoneId="75" gmtAdjustment="GMT+10:00" useDaylightTime="1" value="10">(GMT+10:00) Canberra, Melbourne, Sydney</option>
            <option timeZoneId="76" gmtAdjustment="GMT+10:00" useDaylightTime="1" value="10">(GMT+10:00) Hobart</option>
            <option timeZoneId="77" gmtAdjustment="GMT+10:00" useDaylightTime="0" value="10">(GMT+10:00) Guam, Port Moresby</option>
            <option timeZoneId="78" gmtAdjustment="GMT+10:00" useDaylightTime="1" value="10">(GMT+10:00) Vladivostok</option>
            <option timeZoneId="79" gmtAdjustment="GMT+11:00" useDaylightTime="1" value="11">(GMT+11:00) Magadan, Solomon Is., New Caledonia</option>
            <option timeZoneId="80" gmtAdjustment="GMT+12:00" useDaylightTime="1" value="12">(GMT+12:00) Auckland, Wellington</option>
            <option timeZoneId="81" gmtAdjustment="GMT+12:00" useDaylightTime="0" value="12">(GMT+12:00) Fiji, Kamchatka, Marshall Is.</option>
            <option timeZoneId="82" gmtAdjustment="GMT+13:00" useDaylightTime="0" value="13">(GMT+13:00) Nuku\'alofa</option>
            </select>';
        }
    }
    class misc
    {
        public $filter;

        function __construct()
        {
            $this->filter = site_filter;
            return;
        }
        function randomize_list(int $list_count)
        {
            return json_encode(shuffle(range(0, $list_count)));
        }
        function scan_content($type = 'image', $link)
        {
            $types = ['image', 'link'];

            $body = "
            {
                \"requests\":[
                  {
                    \"image\":{
                      \"source\":{
                        \"imageUri\":
                          \"$link\"
                      }
                    },
                    \"features\":[
                      {
                        \"type\":\"SAFE_SEARCH_DETECTION\"
                      }
                    ]
                  }
                ]
              }
            ";

            $url = 'https://vision.googleapis.com/v1/images:annotate?key=AIzaSyCIQeqBIaZBZl8I3-cpn_ZVOsX4RMatXmE';

            $header = array(
                'Content-Type: application/json'
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
        function filter_url($url)
        {
            foreach ($this->filter as $item) {
                if (strpos($url, $item) === false) {
                } else {
                    return true;
                }
            }
            return false;
        }
    }
}
