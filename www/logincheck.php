<?php session_start();// Starting Session
// Establishing Connection with Server by passing server_name, user_id and password as a parameter
$connection = mysql_connect("localhost", "root", "@casi123");
// Selecting Database
$db = mysql_select_db("login", $connection);
// Storing Session
$user_check=$_SESSION['login_user'];
// SQL Query To Fetch Complete Information Of User
$ses_sql=mysql_query("select username from users where username='$user_check'", $connection);
$row = mysql_fetch_assoc($ses_sql);
$login_session =$row['username'];
if(!isset($login_session)){
mysql_close($connection); // Closing Connection
echo "<script type='text/javascript'> document.location = 'login.php'; </script>"; // Redirecting To Login Page
}
?>
