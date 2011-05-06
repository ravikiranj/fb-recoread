<?php
    //include_once('/home/ravikira/public_html/drupal/sites/all/hacks/fb-recoread/php-sdk/src/facebook.php');
    include_once('/home/ravikiran/ravikiranj.net/drupal/sites/all/hacks/fb-recoread/php-sdk/src/facebook.php');

    class fbrecoread{
        /* Private variables and Constants */
        const APP_ID = '212318288788000';
        const APP_SECRET = '7ae17a52b5f43df287327293644ff364';
        private $fb = null; 
        private $session = null;

        public function __construct($args = array()){
            // Create our Application instance (replace this with your appId and secret).
            $this->fb = new Facebook(array(
              'appId'  => self::APP_ID,
              'secret' => self::APP_SECRET,
              'cookie' => true,
            ));
            if(!$this->fb){
                echo "Unable to instantiate Facebook SDK";
                exit;
            }
            $this->displayHTML();
        }

        function showConnectToFB($sessionExpired = false){
            $params = array();
            $params = array("req_perms" => "user_interests,user_likes,user_status,friends_interests,friends_likes,friends_status",
                            "fbconnect" => 0);
            $loginUrl = $this->fb->getLoginUrl($params);
            $sessionExpiredMsg = '';
            if($sessionExpired){
$sessionExpiredMsg = <<<SESSIONEXPIRED
<h3 style="color: red; padding-bottom: 20px;">Your Facebook session has expired.</h3>
SESSIONEXPIRED;
            }
            $html =<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Recoread</title>
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css"> 
    <style type="text/css">
#doc{
    border: 1px solid #C0C0C0;
    border-radius: 10px 10px 10px 10px;
    padding: 25px;
    width: 780px;
    margin-top: 200px;
}
#hd{
    text-align: center;
    margin: auto;
    margin-bottom: 25px;
}
#bd{
    text-align: center;
    margin: auto;
}
h3{
    font-size: 110%;
}
    </style>
</head>

<body>
<!-- the id on the containing div determines the page width. -->
<!-- #doc = 750px; #doc2 = 950px; #doc3 = 100%; #doc4 = 974px -->
<div id="doc">                  
    <div id="hd">
        {$sessionExpiredMsg}
        <h3>You will need to authorize "Read What You Like" application by connecting with Facebook</h3>            
    </div>
    <div id="bd">
        <a href="{$loginUrl}"><img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif"></a>              
    </div>
    <div id="ft">
    </div>
</div>
</body>
</html>
HTML;
            echo $html;
        }

        public function displayHTML(){
            try {
                $this->session = $this->fb->getSession();
            }catch(FacebookApiException $e){
                error_log($e);
                echo $e;
                //Session expired or user de-authenticated the app
                $this->showConnectToFB(true);
            }
            if(!$this->session){
               //No credentials present, show user the login screen 
               $this->showConnectToFB();
               return;
            }
            try {
                $uid = $this->fb->getUser();
                $me = $this->fb->api('/me');
                $musicInterests = $this->fb->api('/me/music');
                $musicArr = null;
                if(!empty($musicInterests['data'])){
                    foreach($musicInterests['data'] as $item){
                        $musicArr[] = $item["name"];
                    }
                }
                if(!empty($musicArr)){
                    $this->displayRecoNews($musicArr);
                }else {
                    echo "No recommended news could be retrieved as your music interests are not declared/present in Facebook";
                }
                /*
                 * Below code demonstrates how to get additional data from the FB API's
                 *
                 */
                /* 
                $feed = $this->fb->api('/me/home');
                $streamLimit = 10;
                $streamQuery = <<<STREAMQUERY
{
"basicinfo": "SELECT uid,name,pic_square FROM user WHERE uid=me()",
"friendsinfo" : "SELECT uid, name, pic_square FROM user WHERE uid = me() OR uid IN (SELECT uid2 FROM friend WHERE uid1 = me())"
}
STREAMQUERY;
                $streamParams = array(
                                'method' => 'fql.multiquery',
                                'queries' => $streamQuery
                               );   
                $streamResult = $this->fb->api($streamParams);                
                
                $interests = $this->fb->api('/me/interests');
                $movies = $this->fb->api('/me/movies');
                $music = $this->fb->api('/me/music');
                $books = $this->fb->api('/me/books');
                */
                
            }catch(FacebookApiException $e) {
                error_log($e);
                echo $e;
                //Session expired or user de-authenticated the app
                $this->showConnectToFB(true);
            }
            // login or logout url will be needed depending on current user state.
            if ($me) {
              $logoutUrl = $this->fb->getLogoutUrl();
              //echo "User Info </br><pre>"; var_dump($me); echo "</pre>";
              //echo "Music </br><pre>"; var_dump($musicInterests); echo "</pre>";
              $html =<<<HTML
HTML;
            }
        }
        
        public function displayRecoNews($musicArr){
           $musicStr = "'" . implode(" music','", $musicArr) . "'"; // "'string1 music','string2 music','string3 music'"
           //echo "<pre>";var_dump($musicStr); echo "</pre>";
           //Code to access YQL using PHP
           $yql_base_url = "http://query.yahooapis.com/v1/public/yql";
           $yql_query = "select * from google.news where (q in ({$musicStr}))"; //YQL query to retrieve search results

           $yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query) . "&env=store://datatables.org/alltableswithkeys&format=json";
           //echo "<pre>";var_dump($yql_query_url); echo "</pre>";	   
           $result = $this->getResultFromYQL($yql_query_url);
           if(!empty($result["query"]["results"]["results"])){
               $this->parseAndDisplayNewsResults($result["query"]["results"]["results"], $musicArr);
           }           
        }
        
        public function getResultFromYQL($yql_query) {
    	    $session = curl_init($yql_query);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);   
            $json = curl_exec($session);
            curl_close($session);
            return json_decode($json, true);
        }
        public function parseAndDisplayNewsResults($results, $musicArr){
            $musicInterests = '<ul><h3>Music Interests</h3>';
            foreach($musicArr as $item){
                $musicInterests .= <<<MARKUP
<li>{$item}</li>
MARKUP;
            }
            $musicInterests .= '</ul>';
            $articles = '';
            foreach($results as $r){
                $title = $r['title'];
                $content = $r['content'];
                $url = $r['unescapedUrl'];
                $publisher = $r['publisher'];
                $pubDate = $r['publishedDate'];
                $articles .= <<<MARKUP
<div class="article">
    <div class="article-hd"><h2><a href="{$url}" target="_blank">{$title}</a><h5>  -  {$pubDate} by {$publisher}</h5></h2></div>
    <p>{$content}</p>
</div>
MARKUP;
        }
            $markup = <<<MARKUP
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Recoread</title>
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css"> 
    <style type="text/css">
#doc{
    border: 1px solid #C0C0C0;
    border-radius: 10px 10px 10px 10px;
    padding: 25px;
    width: 780px;
    margin-top: 50px;
}
#hd{
    text-align: center;
    margin: auto;
    margin-bottom: 25px;
}
#hd h1{
    font-size: 135%;
}
#bd{
    margin: auto;
    height: 500px;
    overflow-x: hidden;
    overflow-y: auto;
}
h3{
    font-size: 110%;
}
.article{
    margin: 5px 5px 15px;
}
.article h2{
    font-size: 120%;
    display: inline;
}
.article h5{
    display: inline;
    font-size: 87%;
    padding-left: 25px;
}
.article .article-hd{
    padding-bottom: 10px;
    color: gray;
}
#music-interests{
    float: left;
    margin: 10px;
    padding: 8px;
    border: 2px solid #C0C0C0;
    -moz-border-radius: 9px; /* FF1+ */
    -webkit-border-radius: 9px; /* Saf3-4, iOS 1+, Android 1.5+ */
    border-radius: 9px; /* Opera 10.5, IE9, Saf5, Chrome, FF4 */
}
#music-interests h3{
    font-size: 110%;
    font-style: italic;
    margin: 2px;
    padding: 2px;
}
    </style>
</head>

<body>
<!-- the id on the containing div determines the page width. -->
<!-- #doc = 750px; #doc2 = 950px; #doc3 = 100%; #doc4 = 974px -->
<div id="doc">                  
    <div id="hd">
        <h1>Music Interests Recoread</h1>
    </div>
    <div id="bd">
    <div id="music-interests">{$musicInterests}</div>
    <div id="music-articles">{$articles}</div>
    <div id="ft"></div>
</body>
</html>
MARKUP;
            echo $markup;
        }
    }
?>
