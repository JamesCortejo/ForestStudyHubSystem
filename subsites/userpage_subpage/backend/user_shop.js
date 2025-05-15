document.addEventListener("DOMContentLoaded", () => {
  initializeApp();
});

// Global state
let cart = JSON.parse(localStorage.getItem("cart")) || [];
let searchController = null;
let lastSearchTimestamp = 0;
let allProducts = [];
const SEARCH_DEBOUNCE = 300;

function initializeApp() {
  // Try localStorage first, then sessionStorage
  cart =
    JSON.parse(localStorage.getItem("cart")) ||
    JSON.parse(sessionStorage.getItem("cart")) ||
    [];

  // Preserve cart in sessionStorage when moving between pages
  sessionStorage.setItem("cart", JSON.stringify(cart));

  loadProducts();
  setupEventListeners();
  updateCartIndicator();
}

function setupEventListeners() {
  const searchInput = document.getElementById("searchInput");
  searchInput.addEventListener("input", handleSearchInput);
  document.getElementById("clearSearch").addEventListener("click", clearSearch);
  document
    .getElementById("categoryFilter")
    .addEventListener("change", applyFilters);
  document.getElementById("priceSort").addEventListener("change", applyFilters);
  document
    .getElementById("cartModal")
    .addEventListener("shown.bs.modal", renderCart);
  document
    .getElementById("checkoutButton")
    .addEventListener("click", handleCheckout);
  document
    .getElementById("cartItems")
    .addEventListener("click", handleCartAction);
}

async function loadProducts() {
  try {
    showLoadingSkeleton();
    const response = await fetch("backend/get_products.php");
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const { status, data, message } = await response.json();
    if (status === "success") {
      allProducts = data;
      displayProducts(data);
      applyFilters();
    } else {
      throw new Error(message || "Failed to load products");
    }
  } catch (error) {
    showErrorToast(error.message);
    console.error("Load products error:", error);
  }
}

function handleSearchInput(event) {
  const searchTerm = event.target.value.trim();
  if (searchController) searchController.abort();

  if (!searchTerm) {
    loadProducts();
    return;
  }

  const now = Date.now();
  if (now - lastSearchTimestamp < SEARCH_DEBOUNCE) return;
  lastSearchTimestamp = now;

  executeSearch(searchTerm);
}

async function executeSearch(searchTerm) {
  try {
    showLoadingSkeleton();
    searchController = new AbortController();
    const response = await fetch(
      `backend/search_products.php?q=${encodeURIComponent(searchTerm)}`,
      { signal: searchController.signal }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const { status, data, message } = await response.json();
    if (status === "success") {
      allProducts = data;
      animateProductTransition(() => {
        displayProducts(data);
        applyFilters();
      });
    } else {
      throw new Error(message || "Search failed");
    }
  } catch (error) {
    if (error.name !== "AbortError") {
      showErrorToast(error.message);
      console.error("Search error:", error);
    }
  } finally {
    searchController = null;
  }
}

function displayProducts(products) {
  const container = document.getElementById("productsContainer");
  const fragment = document.createDocumentFragment();

  products.forEach((product, index) => {
    const card = createProductCard(product, index);
    fragment.appendChild(card);
  });

  container.replaceChildren(fragment);
}

function createProductCard(product, index) {
  const card = document.createElement("div");
  card.className = "col product-card-entry";
  card.dataset.category = product.category;
  card.dataset.price = product.price;
  card.dataset.stock = product.stock;

  card.innerHTML = `
    <div class="card h-100 product-card">
      <img src="${product.image_path}" 
           class="card-img-top" 
           alt="${product.product_name}"
           loading="lazy"
           onerror="this.src='/path/to/default-image.jpg'">
      <div class="card-body">
        <h5 class="card-title mb-3">${product.product_name}</h5>
        <p class="card-text text-muted mb-4">${product.description}</p>
        <div class="product-meta d-flex justify-content-between align-items-end">
          <div class="price-display">
            <div class="text-muted small">Price</div>
            <h2 class="text-primary mb-0">₱${parseFloat(product.price).toFixed(
              2
            )}</h2>
          </div>
          <div class="stock-display text-end">
            <div class="text-muted small">Available Stock</div>
            <h3 class="mb-0 ${
              product.stock > 0 ? "text-success" : "text-danger"
            }">
              ${product.stock}
            </h3>
          </div>
        </div>
      </div>
      <div class="card-footer bg-transparent">
        <button class="btn btn-success w-100 add-to-cart py-2" 
                data-id="${product.id}" 
                ${product.stock <= 0 ? "disabled" : ""}>
          ${product.stock <= 0 ? "Out of Stock" : "Add to Cart"}
        </button>
      </div>
    </div>
  `;

  card.style.animationDelay = `${index * 50}ms`;
  card.querySelector(".add-to-cart").addEventListener("click", addToCart);
  return card;
}

function addToCart(event) {
  const button = event.target;
  const productId = button.dataset.id;

  fetchProductDetails(productId)
    .then((product) => {
      if (product.stock <= 0) throw new Error("Product is out of stock");

      const existingItem = cart.find((item) => item.id === product.id);
      if (existingItem) {
        existingItem.quantity++;
      } else {
        cart.push({ ...product, quantity: 1 });
      }

      updateCartStorage();
      showCartNotification(product.product_name);
      updateProductAvailability();
    })
    .catch((error) => {
      showErrorToast(error.message);
      console.error("Add to cart error:", error);
    });
}

function handleCartAction(event) {
  const button = event.target.closest("[data-action]");
  if (!button) return;

  const productId = button.dataset.id;
  const action = button.dataset.action;
  const itemIndex = cart.findIndex((item) => item.id == productId);

  if (itemIndex === -1) return;

  const cartItem = cart[itemIndex];

  switch (action) {
    case "increment":
      if (cartItem.quantity < cartItem.stock) {
        cartItem.quantity++;
      } else {
        showErrorToast(`Cannot exceed available stock (${cartItem.stock})`);
      }
      break;

    case "decrement":
      if (cartItem.quantity > 1) {
        cartItem.quantity--;
      } else {
        cart.splice(itemIndex, 1);
      }
      break;

    case "remove":
      cart.splice(itemIndex, 1);
      break;
  }

  updateCartStorage();
  renderCart();
}

async function fetchProductDetails(productId) {
  const response = await fetch(`backend/get_products.php?id=${productId}`);
  if (!response.ok) throw new Error("Failed to fetch product details");

  const { status, data, message } = await response.json();
  if (status !== "success") throw new Error(message);

  return data;
}

function updateCartStorage() {
  localStorage.setItem("cart", JSON.stringify(cart));
  updateCartIndicator();
  updateProductAvailability();
}

function updateCartIndicator() {
  const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  document.getElementById("cartCount").textContent = cartCount;
}

function renderCart() {
  const container = document.getElementById("cartItems");
  container.innerHTML =
    cart.length > 0
      ? generateCartContent()
      : '<p class="text-muted text-center">Your cart is empty</p>';
}

function generateCartContent() {
  return cart
    .map(
      (item) => `
    <div class="cart-item d-flex align-items-center mb-3">
      <img src="${item.image_path}" 
           class="me-3 rounded" 
           width="60" 
           height="60"
           alt="${item.product_name}">
      <div class="flex-grow-1">
        <h6 class="mb-1">${item.product_name}</h6>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <span class="me-2">₱${(item.price * item.quantity).toFixed(
              2
            )}</span>
            <div class="btn-group">
              <button class="btn btn-sm btn-outline-secondary" 
                      data-id="${item.id}" 
                      data-action="decrement">−</button>
              <span class="btn btn-sm disabled">${item.quantity}</span>
              <button class="btn btn-sm btn-outline-secondary" 
                      data-id="${item.id}" 
                      data-action="increment">+</button>
            </div>
          </div>
          <button class="btn btn-danger btn-sm ms-2" 
                  data-id="${item.id}" 
                  data-action="remove">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>
  `
    )
    .join("");
}

function updateProductAvailability() {
  document.querySelectorAll(".add-to-cart").forEach((button) => {
    const productId = button.dataset.id;
    const productCard = button.closest(".product-card-entry");
    const stock = parseInt(productCard.dataset.stock);
    const cartItem = cart.find((item) => item.id == productId);

    // Disable if reaching stock limit
    const currentInCart = cartItem ? cartItem.quantity : 0;
    button.disabled = currentInCart >= stock || stock <= 0;

    // Update button text
    button.textContent =
      currentInCart >= stock || stock <= 0 ? "Out of Stock" : "Add to Cart";
  });
}

function applyFilters() {
  const category = document
    .getElementById("categoryFilter")
    .value.toLowerCase();
  const sortOrder = document.getElementById("priceSort").value;

  let filteredProducts = allProducts.filter(
    (product) =>
      category === "all" || product.category.toLowerCase() === category
  );

  if (sortOrder) {
    filteredProducts.sort((a, b) =>
      sortOrder === "asc" ? a.price - b.price : b.price - a.price
    );
  }

  animateProductTransition(() => displayProducts(filteredProducts));
}

function animateProductTransition(callback) {
  const container = document.getElementById("productsContainer");
  container.style.opacity = "0";
  container.style.pointerEvents = "none";

  requestAnimationFrame(() => {
    callback();
    requestAnimationFrame(() => {
      container.style.opacity = "1";
      container.style.pointerEvents = "all";
    });
  });
}

function showLoadingSkeleton() {
  const container = document.getElementById("productsContainer");
  const skeleton = `
    <div class="col">
        <div class="card h-100 loading-skeleton">
            <div class="card-img-top"></div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text"></p>
                <div class="d-flex justify-content-between">
                    <span class="badge"></span>
                    <span class="badge"></span>
                </div>
            </div>
        </div>
    </div>`.repeat(6);

  container.innerHTML = skeleton;
}

function showCartNotification(productName) {
  const toast = document.createElement("div");
  toast.className = "toast align-items-center text-bg-success border-0";
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${productName} added to cart</div>
      <button type="button" 
              class="btn-close btn-close-white me-2 m-auto" 
              data-bs-dismiss="toast"></button>
    </div>
  `;

  document.body.appendChild(toast);
  bootstrap.Toast.getOrCreateInstance(toast).show();
  setTimeout(() => toast.remove(), 3000);
}

function showErrorToast(message) {
  const toast = document.createElement("div");
  toast.className = "toast align-items-center text-bg-danger border-0";
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" 
              class="btn-close btn-close-white me-2 m-auto" 
              data-bs-dismiss="toast"></button>
    </div>
  `;

  document.body.appendChild(toast);
  bootstrap.Toast.getOrCreateInstance(toast).show();
  setTimeout(() => toast.remove(), 5000);
}

function clearSearch() {
  document.getElementById("searchInput").value = "";
  loadProducts();
}

function handleCheckout() {
  if (cart.length === 0) {
    showErrorToast("Your cart is empty");
    return;
  }

  fetch("backend/save_cart.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cart: cart }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        // Don't clear localStorage here
        window.location.href = "shop_checkout.php";
      } else {
        throw new Error(data.message || "Failed to save cart");
      }
    })
    .catch((error) => {
      showErrorToast(error.message);
    });
}
