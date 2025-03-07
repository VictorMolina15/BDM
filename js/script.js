const $btnSignIn= document.querySelector('.sign-in-btn'),
      $btnSignUp = document.querySelector('.sign-up-btn'),  
      $signUp = document.querySelector('.sign-up'),
      $signIn  = document.querySelector('.sign-in');

document.addEventListener('click', e => {
    if (e.target === $btnSignIn || e.target === $btnSignUp) {
        $signIn.classList.toggle('active');
        $signUp.classList.toggle('active')
    }
});

let show = document.getElementById("msgs_container");
let users_list = document.getElementById("sel_user");
let active_users = document.getElementById("users_container");
let name_error = document.getElementById("name_error");
let msg_error = document.getElementById("msg_error");
let user_error = document.getElementById("user_error");
let dataBase = [];

//========================================================== Send messages sector ==========================================================

function send(){
    var msg = document.getElementById("msg_input");
    var id = parseInt(document.getElementById("sel_user").value)-1;
    var timestamp = currentTime();

    if(msg.value.length == 0){        
        msg_error.innerHTML = "Escoje el usuario!";
        msg.focus()
    } else if(users_list.value == 0) {
	msg_error.innerHTML = "";
        user_error.innerHTML = "Escoje el usuario!";
    } else {        
        show.innerHTML += `${msg.value}<br><msg style="color:${dataBase[id].color};">por @${dataBase[id].name}</msg> - ${timestamp}<hr>`
        msg.value = "";
        msg_error.innerHTML = "";
        user_error.innerHTML = "";
        msg.focus()
    }
}

function inputLorem(){
    var msg = document.getElementById("msg_input");
    msg.value = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc eget iaculis nunc. In egestas posuere leo in fermentum. Etiam vel risus congue, ullamcorper tortor eu, dapibus tortor.";
}

//========================================================== Sign up sector ==========================================================

function signUp(){
    var color_input = document.getElementById("color_input");
    var user_input = document.getElementById("user_input");
    var list_item = document.createElement("option");

    if (user_input.value.length == 0){
        name_error.innerHTML = "Nombre";
        user_input.focus();
    } else {
        name_error.innerHTML = "";
        var user = {
            name: user_input.value,
            color: color_input.value,
        }
        list_item.text = user.name;    
        list_item.value = selValue();
        users_list.appendChild(list_item);
        dataBase.push(user);
        active_users.innerHTML += `<name style="color: ${user.color};">${user.name}</name><hr>`;
        user_input.value = "";
        color_input.value = "#000000";
    }
}

function selValue(){
    var total = document.getElementById("sel_user");    
    for (i = 0 ; i < total.length ; i++){
        continue;
    }
    return i; 
}

function signUpRNG(){
    var user_input = document.getElementById("user_input");
    var names = [
        [
            "João","Pedro","Lucas","Vinicius","Douglas","Felipe","Arnaldo","Gabriel","Michael"
        ],[
            "Da Silva","Ferreira","Moraes","Braga","Andrade","Barbosa","Campos","Duarte","Gomes"
        ]
    ];
    var rng1 = Math.floor(Math.random() * 9);
    var rng2 = Math.floor(Math.random() * 9);
    user_input.value = `${names[0][rng1]} ${names[1][rng2]}`

    //var rng_user = `${names[0][rng1]} ${names[1][rng2]}`;
    //var list_item = document.createElement("option");
    //list_item.text = rng_user;
    //users_list.appendChild(list_item);
    //active_users.innerHTML += `${rng_user}.<br>`;    
}

//========================================================== Timestamp Function ==========================================================

function currentTime(x){
    let date = new Date();

    function zeroNumber(y){
        var zeroZero = (y < 10) ? "0" : "";
        return zeroZero + y;
    }

    let sec = zeroNumber(date.getSeconds());
    let min = zeroNumber(date.getMinutes());
    let hour = zeroNumber(date.getHours());
    
    x = `${hour}:${min}:${sec}`;
    return x;
}



