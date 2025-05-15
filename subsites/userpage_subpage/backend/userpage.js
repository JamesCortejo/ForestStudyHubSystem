document.addEventListener("DOMContentLoaded", function () {
  // Add this to your existing carousel initialization
  function initializeMobileCarousel() {
    const carousel = new bootstrap.Carousel("#mobileTopProducts", {
      interval: false, // Disable auto-cycling
      wrap: true,
      touch: true,
      keyboard: true,
    });

    // Fix width calculation
    const items = document.querySelectorAll(
      "#mobileTopProducts .carousel-item"
    );
    items.forEach((item) => {
      item.style.width = `${
        document.querySelector("#mobileTopProducts").offsetWidth
      }px`;
    });

    // Window resize handler
    window.addEventListener("resize", () => {
      items.forEach((item) => {
        item.style.width = `${
          document.querySelector("#mobileTopProducts").offsetWidth
        }px`;
      });
    });
  }

  fetch("/ForrestStudy_Hub/subsites/userpage_subpage/backend/top_products.php")
    .then((response) => response.json())
    .then((products) => {
      renderDesktopProducts(products);
      renderMobileCarousel(products);
    })
    .catch((error) => console.error("Error:", error));

  function renderDesktopProducts(products) {
    const container = document.getElementById("desktopTopProducts");
    if (!container) return;

    container.innerHTML = products
      .map(
        (product) => `
      <div class="col-lg-4 mb-4">
        <div class="card border-0 h-100 text-center">
          <img src="${getImageUrl(product.image_path)}" 
               class="card-img-top product-image p-2" 
               alt="${product.product_name}"
               style="height: 200px; object-fit: contain"
               onerror="this.onerror=null;this.src='/ForrestStudy_Hub/resources/default-product.jpg'">
          <div class="card-body">
            <h5 class="card-title">${product.product_name}</h5>
            <a href="userpage_subpage/shop.php" class="btn btn-primary mt-2">View Shop</a>
          </div>
        </div>
      </div>
    `
      )
      .join("");
  }

  function renderMobileCarousel(products) {
    const container = document.querySelector(
      "#mobileTopProducts .carousel-inner"
    );
    if (!container) return;

    container.innerHTML = products
      .map(
        (product, index) => `
      <div class="carousel-item ${index === 0 ? "active" : ""}">
        <div class="card border-0 h-100 text-center">
          <img src="${getImageUrl(product.image_path)}" 
               class="d-block w-100 product-image" 
               alt="${product.product_name}"
               style="height: 250px; object-fit: contain"
               onerror="this.onerror=null;this.src='/ForrestStudy_Hub/resources/default-product.jpg'">
          <div class="card-body">
            <h5 class="card-title">${product.product_name}</h5>
            <a href="userpage_subpage/shop.php" class="btn btn-primary">View Shop</a>
          </div>
        </div>
      </div>
    `
      )
      .join("");

    if (products.length > 1) {
      new bootstrap.Carousel("#mobileTopProducts", {
        interval: 5000,
      });
    }
  }

  function getImageUrl(imagePath) {
    const base = imagePath.startsWith("/") ? "" : "/ForrestStudy_Hub/";
    return `${base}${imagePath}`;
  }
});
