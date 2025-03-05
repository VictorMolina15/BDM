// Array de prueba - se debe agregar desde la dase de datos
const wishlist = [
    {
        name: "Favoritos",
        products: [
            { name: "Alimento para perro 2.5kg", seller: "PetCo", price: "200", image: "ALIMENTOSCAT.jpg", category: "alimento" },
            { name: "Correa", seller: "Petco", price: "80", image: "CORREAS.jpg", category: "accesorios" },
            { name: "Jaula para mascota", seller: "Veterinarias Allende", price: "1500", image: "JAULAS.jpg", category: "accesorios" },
            { name: "Juguetes para mascota", seller: "Miguel Nuñez", price: "50", image: "JUGUETES.jpg", category: "juguetes" }
        ]
    },
    {
        name: "Alimentos",
        products: [
            { name: "Alimento para gato 1kg", seller: "Whiskas", price: "99", image: "ALIMENTOSCAT.jpg", category: "alimento" },
            { name: "Alimento para hurón 500g", seller: "PetCo", price: "135", image: "../huron.jpg", category: "alimento" },
        ]
    }
];


const select = document.getElementById("list-profile");
const productList = document.getElementById("product-container");

// ** Rellenar el select con las listas de deseos disponibles **
function fillWhishlistOptions() {
    wishlist.forEach(list => {
        let option = document.createElement("option");
        option.value = list.name;
        option.textContent = list.name;
        select.appendChild(option);
    });
}

// Función para mostrar productos según la categoría seleccionada
function renderProducts(selectedList) {
    productList.innerHTML = ""; // Limpia la lista antes de agregar nuevos productos

    let filteredProducts =  wishlist.filter(list => list.name === selectedList);

    filteredProducts.forEach(list => {

        // Mostrar los productos dentro de la lista
        list.products.forEach(product => {
            const item = document.createElement("div");
            item.classList.add("product-item");

            item.innerHTML = `
                <img src="../recursos/categorias/${product.image}" alt="${product.name}" class="product-img">
                <div class="product-info">
                    <a href="#" class="link-item">${product.name}<a>
                    <p>Vendedor: <a href="#" class="link-seller">${product.seller}</a></p>
                    <p>Precio: <label style="color: #4caf50">$${product.price}<label></p>
                </div>
            `;

            productList.appendChild(item);
        });
    });
}

// Escuchar cambios en el `<select>` y actualizar la lista
select.addEventListener("change", () => {
    renderProducts(select.value);
});

// Cargar todos los productos al inicio
//fillWhishlistOptions(); -- habilitar cuando se manejen variables de la bd
renderProducts(select.value);
