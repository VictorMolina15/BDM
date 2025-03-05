function mostrarDiv(id) {
    var div1 = document.getElementById('div1');
    var div2 = document.getElementById('div2');
    if (id === 'div1') {
        div1.style.display = 'block';
        div2.style.display = 'none';
        
    } else {
            div1.style.display = 'none';
            div2.style.display = 'block';
    }
}

function mostrarDiv2(id) {
    var divi1 = document.getElementById('divi1');
    var divi2 = document.getElementById('divi2');
    var divi3 = document.getElementById('divi3');

    if (id === 'divi1') {
        divi1.style.display = 'block';
        divi2.style.display = 'none';
        divi3.style.display = 'none';
        
    } else {
        if (id === 'divi2'){
            divi1.style.display = 'none';
            divi2.style.display = 'block';
            divi3.style.display = 'none';
        }else{
            divi1.style.display = 'none';
            divi2.style.display = 'none';
            divi3.style.display = 'block';

        }
           
    }
}

document.getElementById('file').addEventListener('change', function() {
    console.log('Evento de cambio de archivo detectado');
    var reader = new FileReader();

    reader.onload = function(e) {
        document.getElementById('image-preview').src = e.target.result;
    };

    reader.readAsDataURL(this.files[0]);
});

function toggleDropdown() {
    var dropdownMenu = document.getElementById("dropdownMenu");
    dropdownMenu.classList.toggle("show");
   
  }
  window.addEventListener('click', function(event) {
    var dropdownMenu = document.getElementById("dropdownMenu");
    var userPhoto = document.getElementById("userPhoto");
    
    // Verificar si el clic no ocurrió dentro del menú desplegable ni en la foto del usuario
    if (!dropdownMenu.contains(event.target) && event.target !== userPhoto) {
        // Si es así, cerrar el menú desplegable si está abierto
        if (dropdownMenu.classList.contains("show")) {
            dropdownMenu.classList.remove("show");
        }
    }
});

 