// booking.js - Complete Debugged Version
document.addEventListener("DOMContentLoaded", function () {
  const BASE_URL = "/ForrestStudy_Hub/subsites/userpage_subpage/backend/";
  const bookingModal = document.getElementById("bookingModal");
  let currentBookingType = "cubicle";
  let isSubmitting = false;

  // ================== Utility Functions ==================
  function showErrorToUser(message, isSuccess = false) {
    console.log(`User feedback: ${message}`, isSuccess ? "✅" : "❌");
    const errorDiv =
      document.getElementById("bookingError") || createErrorElement();
    errorDiv.innerHTML = `
      <div class="alert ${
        isSuccess ? "alert-success" : "alert-danger"
      } alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi ${
            isSuccess ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill"
          } me-2"></i>
          <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    errorDiv.classList.remove("d-none");
  }

  function createErrorElement() {
    const div = document.createElement("div");
    div.id = "bookingError";
    div.className = "mt-3";
    document.querySelector("#bookingModal .modal-body").prepend(div);
    return div;
  }

  function clearForm(type) {
    const form = document.getElementById(`${type}Form`);
    if (form) {
      form.reset();
      form.querySelector("select").selectedIndex = 0;
      console.log(`Cleared ${type} form`);
    }
  }

  // ================== Data Loading ==================
  async function loadLocations() {
    try {
      console.log("Loading locations...");
      const response = await fetch(`${BASE_URL}get_locations.php`);

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const data = await response.json();
      console.log("Locations response:", data);

      if (!data.success) {
        throw new Error(data.error || "Failed to load locations");
      }

      populateLocationDropdown("selectCubicle", data.cubicles);
      populateLocationDropdown("selectRoom", data.rooms);
    } catch (error) {
      console.error("Location loading error:", error);
      showErrorToUser("Failed to load locations. Please refresh the page.");
    }
  }

  function populateLocationDropdown(selectId, locations) {
    const dropdown = document.getElementById(selectId);
    if (!dropdown) return;

    dropdown.innerHTML = `
      <option value="" selected disabled>
        ${selectId.includes("Cubicle") ? "Select Cubicle" : "Select Room"}
      </option>
      ${locations
        .map(
          (loc) => `
        <option value="${loc.id}">${loc.number} - ${
            loc.status || "Available"
          }</option>
      `
        )
        .join("")}
    `;
    console.log(`Populated ${selectId} with ${locations.length} items`);
  }

  // ================== Availability Handling ==================
  async function loadAvailability() {
    try {
      console.log("Loading availability...");
      const response = await fetch(`${BASE_URL}get_availability.php`);

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const data = await response.json();
      console.log("Availability data:", data);

      if (!data.success) {
        throw new Error(data.error || "Failed to load availability");
      }

      populateAvailabilityTable("cubicle", data.cubicles);
      populateAvailabilityTable("room", data.rooms);
    } catch (error) {
      console.error("Availability error:", error);
      showErrorToUser("Failed to load availability data. Please try again.");
    }
  }

  function populateAvailabilityTable(type, bookings) {
    const tbody = document.getElementById(`${type}Availability`);
    if (!tbody) return;

    tbody.innerHTML =
      bookings.length > 0
        ? bookings
            .map(
              (booking) => `
          <tr class="${
            booking.status === "booked" ? "table-warning" : "table-success"
          }">
            <td>${booking.location_number}</td>
            <td>
              <span class="badge ${
                booking.status === "confirmed" ? "bg-success" : "bg-warning"
              }">
                ${booking.status.toUpperCase()}
              </span>
            </td>
            <td>${new Date(booking.start).toLocaleDateString()}</td>
            <td>
              ${new Date(booking.start).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
              })} - 
              ${new Date(booking.end).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
              })}
            </td>
          </tr>
        `
            )
            .join("")
        : `<tr><td colspan="4" class="text-center text-muted py-3">No ${type} bookings found</td></tr>`;

    console.log(`Updated ${type} availability with ${bookings.length} entries`);
  }

  // ================== Booking Submission ==================
  async function submitBooking(type) {
    if (isSubmitting) {
      console.warn("Prevented duplicate submission");
      return;
    }

    try {
      isSubmitting = true;
      const form = document.getElementById(`${type}Form`);

      if (!form) {
        throw new Error(`${type} form not found`);
      }

      const formData = new FormData(form);
      formData.append("type", type);
      console.log(
        "Submitting form data:",
        Object.fromEntries(formData.entries())
      );

      // Validate booking time
      const bookingTime = new Date(formData.get("booking_time"));
      if (bookingTime < new Date()) {
        throw new Error("Cannot book in the past");
      }

      // Show loading state
      const submitButton = form.querySelector('button[type="submit"]');
      submitButton.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Booking...
      `;
      submitButton.disabled = true;

      const response = await fetch(`${BASE_URL}submit_booking.php`, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      console.log("Server response status:", response.status);
      const data = await response.json();
      console.log("Server response data:", data);

      if (!response.ok || !data.success) {
        throw new Error(data.error || "Booking failed. Please try again.");
      }

      showErrorToUser(`Booking confirmed! ID: ${data.booking_id}`, true);
      clearForm(type);
      await loadAvailability();
    } catch (error) {
      console.error("Submission error:", error);
      showErrorToUser(error.message);
    } finally {
      isSubmitting = false;
      const submitButton = document.querySelector(
        `#${type}Form button[type="submit"]`
      );
      if (submitButton) {
        submitButton.innerHTML = `Book ${
          type.charAt(0).toUpperCase() + type.slice(1)
        }`;
        submitButton.disabled = false;
      }
    }
  }

  // ================== Event Handlers ==================
  function handleFormSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    console.log("Form submit triggered for:", currentBookingType);

    if (!event.target.checkValidity()) {
      console.warn("Form validation failed");
      event.target.reportValidity();
      return;
    }

    submitBooking(currentBookingType);
  }

  // In handleTabChange function - CORRECTED VERSION
  function handleTabChange(event) {
    try {
      console.log("Tab change event:", event);

      // Get the activated tab using event.target (not relatedTarget)
      const activeTab = event.target;
      if (!activeTab) {
        console.error("No active tab found in event");
        return;
      }

      const target = activeTab.getAttribute("data-bs-target");
      currentBookingType = target === "#cubicle" ? "cubicle" : "room";
      console.log("Switched to tab:", currentBookingType);

      // Force redraw of tables
      loadAvailability().catch((error) => {
        console.error("Availability refresh failed:", error);
      });
    } catch (error) {
      console.error("Tab change error:", error);
      showErrorToUser("Failed to switch tabs. Please try again.");
    }
  }

  // ================== Modal Lifecycle ==================
  function initializeModal() {
    try {
      console.log("Initializing booking modal...");

      // Verify elements exist before adding listeners
      const forms = document.querySelectorAll("#bookingModal form");
      const tabButtons = document.querySelectorAll("#bookingTabs button");

      if (forms.length === 0 || tabButtons.length === 0) {
        throw new Error("Modal elements not found");
      }

      // Form handlers
      forms.forEach((form) => {
        form.addEventListener("submit", handleFormSubmit);
      });

      // Tab handlers
      tabButtons.forEach((button) => {
        button.addEventListener("show.bs.tab", handleTabChange);
      });

      // Load initial data with error handling
      loadLocations()
        .then(loadAvailability)
        .catch((error) => {
          console.error("Initial data load failed:", error);
          showErrorToUser("Failed to load initial data");
        });
    } catch (error) {
      console.error("Modal initialization failed:", error);
      showErrorToUser("Failed to initialize booking system");
    }
  }

  function cleanupModal() {
    console.log("Cleaning up modal...");

    // Remove event listeners
    document.querySelectorAll("#bookingModal form").forEach((form) => {
      form.removeEventListener("submit", handleFormSubmit);
    });

    document.querySelectorAll("#bookingTabs button").forEach((button) => {
      button.removeEventListener("show.bs.tab", handleTabChange);
    });

    // Reset state
    currentBookingType = "cubicle";
    isSubmitting = false;
  }

  // ================== Event Listeners ==================
  bookingModal.addEventListener("shown.bs.modal", initializeModal);
  bookingModal.addEventListener("hidden.bs.modal", cleanupModal);

  // Initial cleanup if modal is already present
  if (bookingModal.classList.contains("show")) {
    cleanupModal();
    initializeModal();
  }
});
