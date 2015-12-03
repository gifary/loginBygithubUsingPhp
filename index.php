<?php
  define('OAUTH2_CLIENT_ID', '76e0d5a36d6736d62283');
  define('OAUTH2_CLIENT_SECRET', 'ee12e0cb3b8685f1fa51f50a40d0ebbe4f6cc0b4');
  $authorizeURL = 'https://github.com/login/oauth/authorize';
  $tokenURL = 'https://github.com/login/oauth/access_token';
  $apiURLBase = 'https://api.github.com/';

  //create connection to database
  $servername = "mysql.idhostinger.com";
  $usernameDb = "u354712113_gif";
  $passwordDb = "123456";
  $database = "u354712113_git";

  // Create connection
  $conn = new mysqli($servername, $usernameDb, $passwordDb,$database);

  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }
  session_start();
  // Start the login process by sending the user to Github's authorization page
  if(get('action') == 'login') {
    // Generate a random hash and store in the session for security
    $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
    unset($_SESSION['access_token']);
    $params = array(
      'client_id' => OAUTH2_CLIENT_ID,
      'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
      'scope' => 'user',
      'state' => $_SESSION['state']
    );
    // Redirect the user to Github's authorization page
    header('Location: ' . $authorizeURL . '?' . http_build_query($params));
    echo "masuk login";
    die();
  }
  // When Github redirects the user back here, there will be a "code" and "state" parameter in the query string
  if(isset($_GET['code']))
  {
    // Verify the state matches our stored state
    if(!get('state') || $_SESSION['state'] != get('state')) {
      header('Location: ' . $_SERVER['PHP_SELF']);
      die();
    }
    //echo "\n masuk kode ";
    // Exchange the auth code for a token
    $token = apiRequest($tokenURL, array(
      'client_id' => OAUTH2_CLIENT_ID,
      'client_secret' => OAUTH2_CLIENT_SECRET,
      'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
      'state' => $_SESSION['state'],
      'code' => $_GET['code']
    ));
    $_SESSION['access_token'] = $token->access_token;
     echo  $token->access_token;
     //print_r($token);
     echo "\n";

     //$data = apiRequest($apiURLBase.'user?access_token='.$token->access_token);
     //print_r($data);
     header('Location: ' . $_SERVER['PHP_SELF']);
  }
  if(isset($_GET['email']) && isset($_GET['username']) && isset($_GET['password'])){
    //update info user
    echo "Berhasil Update";
          $sql = 'update users set username="'.$_GET['username'].'", password="'.$_GET['password'].'" where email="'.$_GET['email'].'"';
          $result = $conn->query($sql);
          //header('Location: ' . $_SERVER['PHP_SELF']);
  }
  if(session('access_token')) {
         //get github profile
        $request = array('method' => 'GET',
                    'header' => array(
                                      "Accept: application/json",
                                      "User-Agent: login"),
                    'url' => $apiURLBase.'user/emails?access_token='.$_SESSION['access_token'],
                    'url2' => $apiURLBase.'user?access_token='.$_SESSION['access_token']
        );
        
        $body = getBody($request['url'],$request['header']);
        $data=(array)json_decode($body,true);
        //echo "\n ini data";
        //echo $data[0]['email'];
        $email= $data[0]['email'];
        
        
        $body = getBody($request['url2'],$request['header']);
        $data=json_decode($body);
        //echo "\n ini data";
        //echo $data->login;
        $username =$data->login;

        
        $password ="";
        $sql = "SELECT * FROM users WHERE email='".$email."'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
            $username= $row['username'];
            $email =$row['email'];
            $password = $row['password'];
          }
        }else{
           echo "masuk insert"; 
           $sql = "INSERT INTO users (username,email) VALUES ('$username','$email')";
           $result = $conn->query($sql);
        }

        echo '<form action="index.php">';
        echo 'Username :<br>';
        echo  '<input type="text" name="username" value="'.$username. '">';
        echo '<br>';
        echo  'Email:<br>';
        echo '<input type="text" name="email" value="'.$email. '" readonly>';
        echo '<br>';
        echo  'Password:<br>';
        echo '<input type="password" name="password" value="'.$password. '">';
        echo '<br>';
        echo '<input type="submit" value="Update">';
        echo  '</form>';
        
  } else {
    echo '<h3>Not logged in</h3>';
    echo '<p><a href="?action=login">Log In by Github</a></p>';
  }
  
  function getBody($url,$headers){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_len);
        $body = substr($response, $header_len);
        curl_close($ch);

        return $body;
  }
  function apiRequest($url, $post=FALSE, $headers=array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if($post){
      $headers[] = 'Accept: application/json';
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
  }
  function get($key, $default=NULL) {
    return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
  }
  function session($key, $default=NULL) {
    return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
  }