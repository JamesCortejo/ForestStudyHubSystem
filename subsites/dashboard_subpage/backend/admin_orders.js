document.addEventListener("DOMContentLoaded", () => {
  const userTableBody = document.getElementById("userTableBody");
  const orderDetailModal = new bootstrap.Modal("#orderDetailModal");
  const confirmModal = new bootstrap.Modal("#confirmActionModal");
  let currentOrderId = null;
  let pendingAction = null;

  // Event Delegation for View Buttons
  userTableBody.addEventListener("click", (e) => {
    const viewBtn = e.target.closest(".view-order-btn");
    if (viewBtn) {
      currentOrderId = viewBtn.dataset.orderId;
      loadOrderDetails(currentOrderId);
    }
  });

  // Status Action Handlers
  document
    .querySelector("#orderDetailModal .modal-footer")
    .addEventListener("click", (e) => {
      if (e.target.classList.contains("btn-success")) {
        pendingAction = "confirmed";
        confirmModal.show();
      } else if (e.target.classList.contains("btn-danger")) {
        pendingAction = "declined";
        confirmModal.show();
      }
    });

  // Confirm Proceed Handler
  document
    .getElementById("confirmProceed")
    .addEventListener("click", processStatusChange);

  function fetchOrders() {
    fetch("backend/admin_get_orders.php")
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((data) => {
        if (data.status === "success") {
          populateUserTable(data.data);
        } else {
          throw new Error(data.message || "Failed to load orders");
        }
      })
      .catch((error) => showAlert(error.message, "danger"));
  }

  function populateUserTable(orders) {
    userTableBody.innerHTML = orders
      .map(
        (order) => `
      <tr>
        <td>${order.order_id}</td>
        <td>${order.customer_name}</td>
        <td>
          <span class="badge ${getStatusBadgeClass(order.status)}">
            ${order.status}
          </span>
        </td>
        <td>
          <button class="btn btn-sm btn-primary view-order-btn" 
                  data-order-id="${order.order_id}">
            View Details
          </button>
        </td>
      </tr>
    `
      )
      .join("");

    if (orders.length === 0) {
      userTableBody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center">No pending orders found</td>
        </tr>
      `;
    }
  }

  function loadOrderDetails(orderId) {
    fetch(`backend/admin_get_order_details.php?order_id=${orderId}`)
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((data) => {
        if (data.status !== "success") throw new Error(data.message);

        const { order, items } = data.data;

        // Format total amount with PHP-style formatting
        const formattedTotal = parseFloat(order.total_amount).toLocaleString(
          "en-PH",
          {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }
        );

        // Update total display
        document.getElementById("orderTotalAmount").textContent =
          formattedTotal;

        // Update payment method
        document.getElementById("orderPaymentMethod").textContent =
          order.payment_method || "Not specified";

        // Populate products
        document.getElementById("productCardsContainer").innerHTML = items
          .map(
            (item) => `
    <div class="col-md-4">
        <div class="card mb-3 order-details-card">
            <div class="card-body">
                <h6 class="fw-bold text-primary mb-2">${item.product_name}</h6>
                <div class="text-muted small">
                    <p class="mb-1">Price: ₱${parseFloat(item.price).toFixed(
                      2
                    )}</p>
                    <p class="mb-1">Quantity: ${item.quantity}</p>
                </div>
                <div class="mt-2 text-end">
                    <small class="text-success fw-bold total-price">
                        Total: ₱${(item.price * item.quantity).toLocaleString(
                          "en-PH",
                          {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                          }
                        )}
                    </small>
                </div>
            </div>
        </div>
    </div>
`
          )
          .join("");

        // Populate notes (fixed syntax error here)
        const notesElement = document.getElementById("orderNotes");
        notesElement.textContent = order.notes || "No notes provided";
        notesElement.style.fontStyle = order.notes ? "normal" : "italic";

        orderDetailModal.show();
      })
      .catch((error) => {
        console.error("Error loading order details:", error);
        showAlert(`Failed to load order details: ${error.message}`, "danger");
      });
  }

  function processStatusChange() {
    confirmModal.hide();
    if (!currentOrderId || !pendingAction) {
      console.error("Missing required data:", {
        currentOrderId,
        pendingAction,
      });
      return;
    }

    console.log("Processing status change:", { currentOrderId, pendingAction });

    fetch("backend/update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        order_id: currentOrderId,
        status: pendingAction,
      }),
    })
      .then((response) => {
        console.log("Received response:", response);
        if (!response.ok) {
          console.error("HTTP error:", response.status);
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("Response data:", data);
        if (data.status === "success") {
          orderDetailModal.hide();
          fetchOrders();
          showAlert(`Order ${pendingAction} successfully!`, "success");
        } else {
          throw new Error(data.message || "Unknown server error");
        }
      })
      .catch((error) => {
        console.error("Error processing status change:", error);
        showAlert(error.message, "danger");
      })
      .finally(() => {
        currentOrderId = null;
        pendingAction = null;
      });
  }

  function getStatusBadgeClass(status) {
    const statusClasses = {
      pending: "bg-warning text-dark",
      confirmed: "bg-success",
      declined: "bg-danger",
    };
    return statusClasses[status] || "bg-secondary";
  }

  function showAlert(message, type) {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show fixed-top m-3`;
    alert.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.prepend(alert);

    setTimeout(() => alert.remove(), 5000);
  }

  // Initial load of orders
  fetchOrders();
});
