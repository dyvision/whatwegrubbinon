<?php

namespace wwgo {
    class auth
    {
        function __construct()
        {
            return;
        }
        function authorize()
        {
            $parameters = [
                'scope=https%3A//www.googleapis.com/auth/userinfo.email https%3A//www.googleapis.com/auth/userinfo.profile openid',
                'response_type=code',
                'redirect_uri=https://whatwegrubbinon.com/profile.php',
                'client_id=634372968316-6p6nf6j795lbja68pd6q35c74pqjb55s.apps.googleusercontent.com'
            ];

            $paramstring = implode('&', $parameters);
            $url = "https://accounts.google.com/o/oauth2/v2/auth?$paramstring";
            header("location: $url");
        }
        function authenticate()
        {
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
