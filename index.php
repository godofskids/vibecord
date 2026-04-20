<?php
require "config/db.php";

if(isset($_SESSION['user_id'])){
    header("Location: app.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Vibecord</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="login-page">

<div class="login-container">

<div class="login-box">

<h2>Welcome back!</h2>
<p class="subtitle">Login to Vibecord</p>

<input id="user" placeholder="Username">
<input id="pass" type="password" placeholder="Password">

<button class="primary" onclick="login()">Login</button>
<button class="secondary" onclick="register()">Register</button>

<p class="small">Vibecord – Discord Clone</p>

</div>

</div>

<script>

function login(){

let username = document.getElementById("user").value
let password = document.getElementById("pass").value

fetch("api/login.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)
})
.then(r=>r.text())
.then(t=>{

console.log("LOGIN RESPONSE:",t)

if(t=="ok"){
location.href="app.php"
}
else if(t=="badpass"){
alert("Wrong password")
}
else if(t=="nouser"){
alert("User does not exist")
}
else{
alert("Server error: "+t)
}

})

}

function register(){

let username = document.getElementById("user").value
let password = document.getElementById("pass").value

fetch("api/register.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)
})
.then(r=>r.text())
.then(t=>{

console.log("REGISTER RESPONSE:",t)

if(t=="ok"){
alert("Registered! You can now login.")
}
else if(t=="user_exists"){
alert("Username already taken")
}
else if(t=="username_short"){
alert("Username too short")
}
else if(t=="password_short"){
alert("Password too short")
}
else{
alert("Server error: "+t)
}

})

}

</script>

</body>
</html>