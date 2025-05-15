document.addEventListener("DOMContentLoaded", function () {
  const cubicleBody = document.getElementById("cubicleBookingBody");
  const roomBody = document.getElementById("roomBookingBody");
  let currentAction = null;
  let currentBookingId = null;
  let currentBookingType = null;

  // Modal instances
  let bookingManagerModal = null;
  let confirmModal = null;

  // Initialize modals
  document.querySelectorAll(".modal").forEach((modalEl) => {
    if (modalEl.id === "bookingManagerModal") {
      bookingManagerModal = new bootstrap.Modal(modalEl);
    }
    if (modalEl.id === "confirmActionModal") {
      confirmModal = new bootstrap.Modal(modalEl);
    }
  });

  // Booking Manager Modal show event
  document
    .getElementById("bookingManagerModal")
    .addEventListener("shown.bs.modal", () => {
      loadBookings();
      document.body.classList.add("modal-open");
    });

  // Confirmation Modal hidden event
  document
    .getElementById("confirmActionModal")
    .addEventListener("hidden.bs.modal", () => {
      if (bookingManagerModal) {
        bookingManagerModal.show();
      }
    });

  function loadBookings() {
    fetch("backend/admin_booking.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          populateTable(cubicleBody, data.cubicles, "cubicle");
          populateTable(roomBody, data.rooms, "room");
        } else {
          showError(
            "Failed to load bookings: " + (data.error || "Unknown error")
          );
        }
      })
      .catch((error) => showError("Network error: " + error.message));
  }

  function populateTable(tbody, bookings, type) {
    tbody.innerHTML = bookings
      .map(
        (booking) => `
            <tr id="booking-${booking.booking_id}">
                <td>
                    <button id="accept-${booking.booking_id}" 
                            class="btn btn-success btn-sm action-btn" 
                            data-type="${type}"
                            data-booking="${booking.booking_id}">
                        Accept
                    </button>
                    <button id="decline-${booking.booking_id}" 
                            class="btn btn-danger btn-sm action-btn ms-2" 
                            data-type="${type}"
                            data-booking="${booking.booking_id}">
                        Decline
                    </button>
                </td>
                <td>${booking.username}</td>
                <td>${
                  type === "cubicle"
                    ? booking.cubicle_number
                    : booking.room_number
                }</td>
                <td>${booking.duration} hours</td>
                <td>${new Date(booking.booking_time).toLocaleDateString()}</td>
                <td>${new Date(booking.booking_time).toLocaleTimeString()}</td>
            </tr>
        `
      )
      .join("");

    tbody.querySelectorAll(".action-btn").forEach((btn) => {
      btn.addEventListener("click", handleAction);
    });
  }

  function handleAction(event) {
    const button = event.target;
    const row = button.closest("tr");
    const bookingId = button.dataset.booking;
    const bookingType = button.dataset.type;
    const action = button.id.startsWith("accept-") ? "accept" : "decline";

    currentAction = action;
    currentBookingId = bookingId;
    currentBookingType = bookingType;

    const bookingDetails = {
      user: row.cells[1].textContent,
      location: row.cells[2].textContent,
      date: row.cells[4].textContent,
      time: row.cells[5].textContent,
    };

    document.getElementById("actionType").textContent = action;
    document.getElementById("actionType").className =
      action === "accept" ? "text-success" : "text-danger";
    document.getElementById("confirmUserName").textContent =
      bookingDetails.user;
    document.getElementById("confirmBookingType").textContent =
      bookingType === "cubicle" ? "Study Cubicle" : "Study Room";
    document.getElementById("confirmLocation").textContent =
      bookingDetails.location;
    document.getElementById("confirmBookingDate").textContent =
      bookingDetails.date;
    document.getElementById("confirmBookingTime").textContent =
      bookingDetails.time;

    // Hide booking manager before showing confirmation
    bookingManagerModal.hide();
    setTimeout(() => confirmModal.show(), 300);
  }

  document
    .getElementById("confirmActionBtn")
    .addEventListener("click", function () {
      if (!currentAction || !currentBookingId || !currentBookingType) return;

      fetch("backend/update_booking_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          id: currentBookingId,
          type: currentBookingType,
          action: currentAction,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            document.getElementById(`booking-${currentBookingId}`).remove();
            confirmModal.hide();
            bookingManagerModal.show();
          } else {
            showError(
              "Failed to update booking: " + (data.error || "Unknown error")
            );
          }
        })
        .catch((error) => showError("Update failed: " + error.message))
        .finally(() => {
          currentAction = null;
          currentBookingId = null;
          currentBookingType = null;
        });
    });

  function showError(message) {
    const alert = document.createElement("div");
    alert.className = "alert alert-danger alert-dismissible fade show mt-3";
    alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
    document.querySelector("#bookingManagerModal .modal-body").prepend(alert);
  }
});
