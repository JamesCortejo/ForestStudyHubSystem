$(document).ready(function () {
  // Initialize all functionality
  loadProducts();
  handleDeleteProduct();
  handleEditProduct();
  initializeFormSubmissions();
  initializeModals();
  initializeSearch();
  initializeFiltersAndSort();
});

// Filter and Sort Variables
let currentFilter = "all";
let currentSort = {
  field: null,
  order: "asc",
};
let allProducts = []; // Track all loaded products

// Initialize Filter and Sort Controls
function initializeFiltersAndSort() {
  // Category Filter
  $("#categoryFilter").on("change", function () {
    currentFilter = $(this).val();
    applyFiltersAndSort();
  });

  // Sort Controls
  $("#sortField, #sortOrder").on("change", function () {
    currentSort.field = $("#sortField").val();
    currentSort.order = $("#sortOrder").val();
    applyFiltersAndSort();
  });
}

// Apply Filters and Sorting
function applyFiltersAndSort() {
  const $container = $("#productsContainer");
  let $products = $container.find(".product-card-wrapper");
  let hasVisibleProducts = false;

  // First pass: Apply filters
  $products.each(function () {
    const $product = $(this);
    const matchesCategory =
      currentFilter === "all" || $product.data("category") === currentFilter;

    if (matchesCategory) {
      $product.show();
      hasVisibleProducts = true;
    } else {
      $product.hide();
    }
  });

  // Second pass: Sort visible products
  const $visibleProducts = $products.filter(":visible").sort((a, b) => {
    if (!currentSort.field) return 0;

    const aVal = getSortValue(a);
    const bVal = getSortValue(b);

    return currentSort.order === "asc" ? aVal - bVal : bVal - aVal;
  });

  // Reorder visible products while keeping hidden ones in place
  $visibleProducts.detach().appendTo($container);

  // Handle empty state
  toggleEmptyState(!hasVisibleProducts);
}

// Helper function to get sort values
function getSortValue(element) {
  const $el = $(element);
  switch (currentSort.field) {
    case "price":
      return parseFloat($el.data("price"));
    case "stock":
      return parseInt($el.data("stock"));
    default:
      return 0;
  }
}
// Empty state handler
function toggleEmptyState(isEmpty) {
  const $container = $("#productsContainer");
  if (isEmpty) {
    $container.html(`
      <div class="text-center py-5 text-muted">
        No products found matching your criteria
      </div>
    `);
  }
}

// Search Functionality
function initializeSearch() {
  let searchTimeout;

  $("#searchInput").on("input", function () {
    const searchTerm = $(this).val().trim();
    clearTimeout(searchTimeout);

    if (searchTerm.length === 0) {
      loadProducts();
      return;
    }

    $("#productsContainer").html(`
      <div class="text-center py-5">
        <div class="spinner-border" role="status"></div>
      </div>
    `);

    searchTimeout = setTimeout(() => {
      $.ajax({
        url: `backend/search_products.php?q=${encodeURIComponent(searchTerm)}`,
        method: "GET",
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            $("#productsContainer").empty();
            if (response.data.length > 0) {
              response.data.forEach((product) => addProductCard(product));
              applyFiltersAndSort();
            } else {
              $("#productsContainer").html(`
                <div class="text-center py-5 text-muted">
                  No results found for "${searchTerm}"
                </div>
              `);
            }
          }
        },
        error: function (xhr) {
          console.error("Search error:", xhr.responseText);
          showAlert("Search failed: " + xhr.statusText, "danger");
          loadProducts();
        },
      });
    }, 300);
  });

  $("#clearSearch").click(function () {
    $("#searchInput").val("").trigger("input");
  });
}

// Product Loading
function loadProducts() {
  $("#productsContainer").html(`
    <div class="text-center py-5">
      <div class="spinner-border" role="status"></div>
    </div>
  `);

  $.ajax({
    url: "backend/get_products.php",
    type: "GET",
    success: function (response) {
      if (response.status === "success") {
        $("#productsContainer").empty();
        if (response.data.length > 0) {
          allProducts = response.data; // Store raw product data
          response.data.forEach((product) => addProductCard(product));
          applyFiltersAndSort();
        } else {
          $("#productsContainer").html(`
            <div class="text-center py-5 text-muted">No products found</div>
          `);
        }
      } else {
        showAlert(
          "Failed to load products: " + (response.message || "Unknown error"),
          "danger"
        );
      }
    },
    error: function (xhr) {
      showAlert("Error loading products: " + xhr.statusText, "danger");
    },
  });
}

// Product Card Creation
function addProductCard(product) {
  const cacheBuster = `?v=${new Date().getTime()}`;
  const basePath = product.image_path.startsWith("/")
    ? ""
    : "/ForrestStudy_Hub/";
  const imageUrl = `${basePath}${product.image_path}${cacheBuster}`;

  const formattedPrice = new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(product.price);

  const cardHtml = `
    <div class="col-md-4 mb-4 product-card-wrapper" 
         data-id="${product.id}"
         data-name="${product.product_name.toLowerCase()}"
         data-category="${product.category}"
         data-price="${product.price}"
         data-stock="${product.stock}">
      <div class="card product-card">
        <div class="image-container">
          <img src="${imageUrl}" 
               class="card-img-top" 
               alt="${product.product_name}"
               loading="lazy"
               onerror="this.src='/ForrestStudy_Hub/resources/default-product.jpg'">
        </div>
        <div class="card-body">
          <h5 class="card-title">${product.product_name}</h5>
          <p class="card-text description">${product.description}</p>
          <div class="product-details">
            <span class="badge bg-primary ">${formattedPrice}</span>
            <span class="badge bg-secondary stock-badge">${product.stock}</span>
            <span class="badge bg-info category-badge">${
              product.category
            }</span>
          </div>
          <div class="mt-3 d-flex justify-content-between action-buttons">
            <button class="btn btn-warning btn-sm edit-product" 
                    data-id="${product.id}">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-danger btn-sm delete-product" 
                    data-id="${product.id}">
              <i class="bi bi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>`;

  $(cardHtml).hide().prependTo("#productsContainer").fadeIn(300);
}

// Form Handling
function initializeFormSubmissions() {
  // Add Product Form
  $("#addProductForm").submit(function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    if (!this.checkValidity()) {
      this.classList.add("was-validated");
      return;
    }

    showConfirmationModal(formData);
  });

  // Edit Product Form
  $("#editProductForm").submit(function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    if (!this.checkValidity()) {
      this.classList.add("was-validated");
      return;
    }

    const confirmModal = new bootstrap.Modal("#editConfirmationModal");
    confirmModal.show();

    $("#confirmEditButton")
      .off("click")
      .on("click", function () {
        confirmModal.hide();
        submitEditForm(formData);
      });
  });
}

// Modal Handling
function initializeModals() {
  // Add Product Modal Cleanup
  $("#addProductManagerModal").on("hidden.bs.modal", function () {
    $(this).find("form")[0].reset();
    $(this).find("form").removeClass("was-validated");
  });

  // Edit Product Modal Cleanup
  $("#editProductModal").on("hidden.bs.modal", function () {
    $(this).find("form")[0].reset();
    $(this).find("form").removeClass("was-validated");
  });
}

// Confirmation Modals
function showConfirmationModal(formData) {
  const reader = new FileReader();
  reader.onload = function (e) {
    $("#confirmImagePreview").attr("src", e.target.result);
  };

  if (formData.get("image") instanceof File) {
    reader.readAsDataURL(formData.get("image"));
  }

  // Populate confirmation details
  $("#confirmName").text(formData.get("product_name"));
  $("#confirmDescription").text(formData.get("description"));
  $("#confirmPrice").text("₱" + parseFloat(formData.get("price")).toFixed(2));
  $("#confirmStock").text(formData.get("stock"));
  $("#confirmCategory").text(formData.get("category"));

  // Show modal
  const confirmationModal = new bootstrap.Modal("#confirmationModal");
  confirmationModal.show();

  // Handle confirmation
  $("#confirmAddButton")
    .off("click")
    .on("click", function () {
      confirmationModal.hide();
      submitProductForm(formData);
    });
}

// AJAX Operations
async function submitProductForm(formData) {
  const $submitBtn = $("#addProductForm").find('[type="submit"]');
  const productName = formData.get("product_name");

  try {
    // Check for existing product first
    const checkResponse = await $.ajax({
      url: `backend/check_product_exists.php?name=${encodeURIComponent(
        productName
      )}`,
      type: "GET",
    });

    if (checkResponse.exists) {
      showAlert("Product with this name already exists!", "danger");
      $submitBtn.prop("disabled", false).html("Add Product");
      return;
    }

    $submitBtn.prop("disabled", true).html(`
      <span class="spinner-border spinner-border-sm" role="status"></span> Adding...
    `);

    // Proceed with submission if not exists
    const addResponse = await $.ajax({
      url: "backend/add_product.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
    });

    if (addResponse.status === "success") {
      $("#addProductManagerModal").modal("hide");
      showAlert("Product added successfully!", "success");
      loadProducts();
    } else {
      showAlert("Error: " + (addResponse.message || "Unknown error"), "danger");
    }
  } catch (error) {
    showAlert(
      "Error: " + error.responseJSON?.message || error.statusText,
      "danger"
    );
  } finally {
    $submitBtn.prop("disabled", false).html("Add Product");
  }
}

function submitEditForm(formData) {
  const $submitBtn = $("#editProductForm").find('[type="submit"]');
  $submitBtn.prop("disabled", true).html(`
    <span class="spinner-border spinner-border-sm" role="status"></span> Saving...
  `);

  $.ajax({
    url: "backend/update_product.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (response) {
      if (response.status === "success") {
        $("#editProductModal").modal("hide");
        showAlert("Product updated successfully!", "success");
        loadProducts();
      } else {
        showAlert("Error: " + (response.message || "Update failed"), "danger");
      }
    },
    error: function (xhr) {
      showAlert("Error: " + xhr.statusText, "danger");
    },
    complete: function () {
      $submitBtn.prop("disabled", false).html("Save Changes");
    },
  });
}

// Product Actions
function handleDeleteProduct() {
  $(document).on("click", ".delete-product", function () {
    const productId = $(this).data("id");
    const productCard = $(this).closest(".col-md-4");
    const productName = productCard.find(".card-title").text();

    const deleteModal = new bootstrap.Modal("#deleteConfirmationModal");
    $("#deleteConfirmationModal .modal-body").html(`
      Are you sure you want to delete <strong>${productName}</strong>? 
      This action cannot be undone.
    `);

    deleteModal.show();

    $("#confirmDeleteButton")
      .off("click")
      .on("click", function () {
        deleteProduct(productId, productCard);
        deleteModal.hide();
      });
  });
}

function deleteProduct(productId, productCard) {
  $.ajax({
    url: "backend/delete_product.php",
    type: "POST",
    data: { id: productId },
    dataType: "json",
    beforeSend: function () {
      productCard.css({ opacity: "0.5", "pointer-events": "none" });
    },
    success: function (response) {
      if (response.status === "success") {
        productCard.remove();
        showAlert("Product deleted successfully!", "success");
      } else {
        showAlert("Error: " + (response.message || "Delete failed"), "danger");
        productCard.css({ opacity: "1", "pointer-events": "auto" });
      }
    },
    error: function (xhr) {
      showAlert("Error: " + xhr.statusText, "danger");
      productCard.css({ opacity: "1", "pointer-events": "auto" });
    },
  });
}

function handleEditProduct() {
  $(document).on("click", ".edit-product", function () {
    const productId = $(this).data("id");
    const productCard = $(this).closest(".col-md-4");

    productCard.css({ opacity: "0.5", "pointer-events": "none" });

    $.ajax({
      url: `backend/get_products.php?id=${productId}`,
      type: "GET",
      success: function (response) {
        if (response.status === "success") {
          populateEditForm(response.data);
          new bootstrap.Modal("#editProductModal").show();
        } else {
          showAlert("Error loading product details", "danger");
        }
      },
      error: function (xhr) {
        showAlert("Error: " + xhr.statusText, "danger");
      },
      complete: function () {
        productCard.css({ opacity: "1", "pointer-events": "auto" });
      },
    });
  });
}

function populateEditForm(product) {
  // Construct correct image URL
  const baseUrl = window.location.origin;
  const imagePath = product.image_path.startsWith("/")
    ? product.image_path
    : `/${product.image_path}`;
  const cacheBuster = `?v=${new Date().getTime()}`;

  // Populate form fields
  $("#editProductId").val(product.id);
  $("#editProductName").val(product.product_name);
  $("#editDescription").val(product.description);
  $("#editPrice").val(parseFloat(product.price).toFixed(2));
  $("#editStock").val(product.stock);
  $("#editCategory").val(product.category);

  // Set image preview
  $("#currentImagePreview").attr("src", baseUrl + imagePath + cacheBuster);
}

// Utility Functions
function showAlert(message, type) {
  const alert = $(`
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  `);

  $(".main-content").prepend(alert);
  setTimeout(() => alert.alert("close"), 5000);
}
