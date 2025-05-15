// checkout.js
document.addEventListener("DOMContentLoaded", () => {
  // Initialize elements and modal
  const checkoutForm = document.getElementById("checkoutForm");
  const confirmBtn = document.getElementById("confirmPurchase");
  const modalTotal = document.getElementById("modalTotal");
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  const confirmationModal = new bootstrap.Modal("#confirmationModal");
  const totalElement = document.getElementById("totalAmount");

  // Initial checks
  if (!document.querySelector(".cart-item")) {
    window.location.href = "../shop.php?error=empty_cart";
    return;
  }

  // Backup total calculation
  if (totalElement && totalElement.textContent === "₱0.00") {
    recalculateTotalFromDOM();
  }

  // Form submission handler
  if (checkoutForm) {
    checkoutForm.addEventListener("submit", (e) => {
      e.preventDefault();

      // Update modal with current total
      modalTotal.textContent = totalElement.textContent.replace("₱", "");
      confirmationModal.show();
    });
  }

  // Confirm purchase handler
  if (confirmBtn) {
    confirmBtn.addEventListener("click", async () => {
      const submitBtn = checkoutForm.querySelector('button[type="submit"]');
      const originalText = confirmBtn.innerHTML;

      try {
        confirmBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm" 
                          role="status" 
                          aria-hidden="true">
                    </span> Processing...
                `;
        confirmBtn.disabled = true;

        const formData = {
          paymentMethod: document.getElementById("paymentMethod").value,
          notes: document.getElementById("notes").value,
          total: parseFloat(totalElement.dataset.rawTotal),
        };

        const response = await fetch("backend/process_checkout.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
          },
          body: JSON.stringify(formData),
        });

        const data = await response.json();

        // 𝗖𝗮𝗿𝘁 𝗖𝗹𝗲𝗮𝗿𝗶𝗻𝗴 𝗛𝗲𝗿𝗲
        if (data.status === "success") {
          // Clear all cart storage
          localStorage.removeItem("cart");
          sessionStorage.removeItem("cart");

          // Close modal and redirect
          confirmationModal.hide();
          window.location.href = `../userpage.php?order_id=${data.orderId}`;
        } else {
          throw new Error(data.message || "Payment failed");
        }
      } catch (error) {
        showErrorToast(error.message);
        console.error("Checkout error:", error);
      } finally {
        confirmBtn.innerHTML = "Yes";
        confirmBtn.disabled = false;
      }
    });
  }
});

function recalculateTotalFromDOM() {
  let total = 0;
  document.querySelectorAll(".cart-item").forEach((item) => {
    const priceElement = item.querySelector("span:first-child");
    const [priceStr, quantityStr] = priceElement.textContent.split(" × ");

    // Clean numerical values
    const price = parseFloat(priceStr.replace(/[^0-9.]/g, ""));
    const quantity = parseInt(quantityStr.replace(/[^0-9]/g, ""));

    if (!isNaN(price) && !isNaN(quantity)) {
      total += price * quantity;
    }
  });

  const totalElement = document.getElementById("totalAmount");
  if (totalElement) {
    totalElement.textContent = "₱" + total.toFixed(2);
    totalElement.dataset.rawTotal = total;
  }
}

window.logout = function () {
  fetch("../../php/logout.php", {
    method: "POST",
    credentials: "same-origin",
  })
    .then((response) => {
      if (response.ok)
        window.location.href = "../../index.php?logout=" + Date.now();
      else throw new Error("Logout failed");
    })
    .catch((error) => {
      console.error("Logout error:", error);
      alert("Logout failed. Please try again.");
    });
};

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
  new bootstrap.Toast(toast).show();
  setTimeout(() => toast.remove(), 5000);
}
