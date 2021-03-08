<?php

//to be added
#logic to give recommendations
#front end button to delete recipe from wallet
#front end button for 'tried it'
#tags system

//ideas
#share class: add a "following" array to your user object of user id's / create a "following" list on your profile page / if you have another person's id it will include their recipe wallet when showing available recipes
#a food/ideas page: a selection of everyone's recipes. "see what other users like:" / The ability to click "add to recipe wallet"
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
                return;
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
        function create($name, $image, $url)
        {
            //get array
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            $this->rid = uniqid();
            $this->name = $name;
            $this->image = $image;
            $this->url = $url;

            $recipe['rid'] = uniqid();
            $recipe['name'] = $name;
            $recipe['image'] = $image;
            $recipe['url'] = $url;
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
            $new_list = [];

            //search using main identifier
            foreach ($recipes as $recipe) {

                //perform a comparitive function on the item number that was returned
                if ($recipe['rid'] == $rid and in_array($this->id, $recipe['id'])) {
                } else {
                    array_push($new_list, $recipe);
                }
            }
            $file = fopen(recipe_db_path, 'w');
            fwrite($file, json_encode($new_list));
            fclose($file);
            return json_encode($result['message'] = $rid . " Deleted");
        }
        function add_user($rid)
        {
            $recipes = json_decode(file_get_contents(recipe_db_path), true);

            //search using main identifier
            $recipe = array_search($rid, array_column($recipes, 'rid'));

            //perform a comparitive function on the item number that was returned
            if ($recipes[$recipe]['rid'] == $rid and in_array($this->id, $recipes[$recipe]['id'])) {
                array_push($recipes[$recipe]['id'], $this->id);

                $file = fopen(recipe_db_path, 'w');
                fwrite($file, json_encode($recipes));
                fclose($file);
                return json_encode($result['message'] = $rid . " added to wallet");
            }
        }
        function explore(){
            return file_get_contents(recipe_db_path);
        }
    }
    class tag
    {
        function __construct()
        {
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
                </div>";
            } else {
                $header = "<div id='navbar'>
                <a class='navbar-item' href='/'><h3 style='display:inline-block;margin:0;color: rgb(0 226 157);font-size:30px;'>WW</h3><h3 style='margin:0;display:inline-block;font-size:30px;'>GO</h3></a>
                <h3 class='navbar-item-right'><a href='authorize'>Login with Google</a></h3>
                </div>";
            }
            return $header;
        }
    }
}
