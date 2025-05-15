document.addEventListener("DOMContentLoaded", function () {
  // DOM Elements
  const userSearchInput = document.getElementById("userSearchInput");
  const userSelect = document.getElementById("userSelect");
  const cubicleSelect = document.getElementById("cubicleSelect");
  const timeSelect = document.getElementById("timeSelect");
  const setTimerBtn = document.getElementById("setTimerBtn");
  const vacantCubiclesList = document.getElementById("vacantCubiclesList");
  const activeStudiesList = document.getElementById("activeStudiesList");
  const exceededTimeList = document.getElementById("exceededTimeList");

  // Global variables
  let activeSessions = [];
  let updateInterval;

  // Initialize the page
  loadUsers();
  loadCubicles();
  startSessionUpdater();

  // Event listeners
  userSearchInput.addEventListener("input", function () {
    const searchTerm = this.value.trim();
    loadUsers(searchTerm);
  });

  setTimerBtn.addEventListener("click", startTimerSession);
  document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", handleConfirmDelete);

  // End session handlers
  document
    .getElementById("confirmEndSessionBtn")
    .addEventListener("click", showEndConfirmation);
  document
    .getElementById("confirmEndSessionFinalBtn")
    .addEventListener("click", handleEndSessionConfirm);

  // Toast functions
  function showSuccess(message) {
    const toast = new bootstrap.Toast(document.getElementById("successToast"));
    document.getElementById("successMessage").textContent = message;
    toast.show();
  }

  function showError(message) {
    const toast = new bootstrap.Toast(document.getElementById("errorToast"));
    document.getElementById("errorMessage").textContent = message;
    toast.show();
  }

  // Load users with optional search term
  function loadUsers(searchTerm = "") {
    fetch(
      `backend/timer_manager.php?action=getUsers&search=${encodeURIComponent(
        searchTerm
      )}`
    )
      .then((response) => response.json())
      .then((users) => {
        userSelect.innerHTML = "";
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "Select a user";
        userSelect.appendChild(defaultOption);

        fetch("backend/timer_manager.php?action=getActiveSessions")
          .then((response) => response.json())
          .then((activeSessions) => {
            const busyUserIds = activeSessions.map(
              (session) => session.user_id
            );

            users.forEach((user) => {
              const option = document.createElement("option");
              option.value = user.id;
              option.textContent = `${user.name} (${user.username})`;

              if (busyUserIds.includes(user.id)) {
                option.disabled = true;
                option.textContent += " (IN SESSION)";
              }

              userSelect.appendChild(option);
            });
          });
      })
      .catch((error) => {
        console.error("Error loading users:", error);
        showError("Error loading users list");
      });
  }

  // Load available cubicles
  function loadCubicles() {
    fetch("backend/timer_manager.php?action=getCubicles")
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((cubicles) => {
        cubicleSelect.innerHTML = "";
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "Select a cubicle";
        cubicleSelect.appendChild(defaultOption);

        cubicles.sort((a, b) => a.cubicle_number - b.cubicle_number);

        cubicles.forEach((cubicle) => {
          const option = document.createElement("option");
          option.value = cubicle.cubicle_id;
          option.textContent = `(${cubicle.cubicle_number})`;
          cubicleSelect.appendChild(option);
        });

        updateVacantCubiclesList(cubicles);
      })
      .catch((error) => {
        console.error("Error loading cubicles:", error);
        showError("Failed to load cubicles");
      });
  }

  // Update vacant cubicles list
  function updateVacantCubiclesList(cubicles) {
    vacantCubiclesList.innerHTML = "";
    cubicles.sort((a, b) => a.cubicle_number - b.cubicle_number);

    cubicles.forEach((cubicle) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-center";
      li.innerHTML = `
        ${cubicle.cubicle_number}
        <span class="badge bg-success">Available</span>
      `;
      vacantCubiclesList.appendChild(li);
    });
  }

  function startTimerSession() {
    const userId = userSelect.value;
    const cubicleId = cubicleSelect.value;
    const timeSelected = timeSelect.value;
    const selectedOption = timeSelect.options[timeSelect.selectedIndex];
    const initialPrice = selectedOption.getAttribute("data-price");

    if (!userId || !cubicleId || !timeSelected) {
      showError("Please select a user, cubicle, and time");
      return;
    }

    const formData = new FormData();
    formData.append("action", "startTimer");
    formData.append("userId", userId);
    formData.append("cubicleId", cubicleId);
    formData.append("timeSelected", timeSelected);
    formData.append("initialPrice", initialPrice);

    fetch("backend/timer_manager.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw new Error(data.error || "Failed to start timer");
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          showSuccess("Timer started successfully!");
          loadCubicles();
          updateActiveSessions();
        }
      })
      .catch((error) => {
        console.error("Error starting timer:", error);
        showError(error.message);
      });
  }

  // Start session updater
  function startSessionUpdater() {
    updateActiveSessions();
    updateInterval = setInterval(updateActiveSessions, 1000);
  }

  // Update active sessions display
  function updateActiveSessions() {
    fetch("backend/timer_manager.php?action=getActiveSessions")
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((sessions) => {
        activeSessions = sessions;
        renderActiveSessions();
      })
      .catch((error) => {
        console.error("Error updating sessions:", error);
      });
  }

  // Render active and exceeded sessions
  function renderActiveSessions() {
    activeStudiesList.innerHTML = "";
    exceededTimeList.innerHTML = "";

    activeSessions.forEach((session) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-center";

      const userInfo = document.createElement("span");
      userInfo.textContent = `${session.firstname} ${session.lastname} (${session.cubicle_number})`;

      const timeInfo = document.createElement("div");
      timeInfo.className = "d-flex align-items-center gap-2";

      if (session.time_remaining > 0) {
        const timeSpan = document.createElement("span");
        timeSpan.className = "badge bg-primary rounded-pill";
        timeSpan.textContent = formatTime(session.time_remaining);

        const priceSpan = document.createElement("span");
        priceSpan.className = "badge bg-success rounded-pill";
        priceSpan.textContent = formatCurrency(session.total_bill);

        const removeBtn = document.createElement("button");
        removeBtn.className = "btn btn-sm btn-outline-danger";
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
        removeBtn.title = "Remove session";
        removeBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          showDeleteConfirmation(session.id);
        });

        timeInfo.appendChild(timeSpan);
        timeInfo.appendChild(priceSpan);
        timeInfo.appendChild(removeBtn);

        li.appendChild(userInfo);
        li.appendChild(timeInfo);
        activeStudiesList.appendChild(li);
      } else {
        const timeSpan = document.createElement("span");
        timeSpan.className = "badge bg-danger rounded-pill";
        timeSpan.textContent = `+${formatTime(session.exceeding_time)}`;

        const billSpan = document.createElement("span");
        billSpan.className = "badge bg-warning text-dark rounded-pill";
        billSpan.textContent = formatCurrency(session.total_bill);

        const infoBtn = document.createElement("button");
        infoBtn.className = "btn btn-sm btn-outline-primary ms-2";
        infoBtn.innerHTML = '<i class="bi bi-info-circle"></i>';
        infoBtn.title = "View session details";
        infoBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          showSessionDetails(session);
        });

        timeInfo.appendChild(timeSpan);
        timeInfo.appendChild(billSpan);
        timeInfo.appendChild(infoBtn);

        li.appendChild(userInfo);
        li.appendChild(timeInfo);
        exceededTimeList.appendChild(li);
      }
    });
  }

  // Format currency
  function formatCurrency(amount) {
    return "₱" + parseFloat(amount).toFixed(2);
  }

  // Session deletion handling
  function showDeleteConfirmation(sessionId) {
    const modal = new bootstrap.Modal(
      document.getElementById("confirmDeleteModal")
    );
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    confirmBtn.dataset.sessionId = sessionId;
    modal.show();
  }

  function handleConfirmDelete() {
    const sessionId = this.dataset.sessionId;
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("confirmDeleteModal")
    );

    const formData = new FormData();
    formData.append("action", "removeSession");
    formData.append("sessionId", sessionId);

    fetch("backend/timer_manager.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok)
          throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          showSuccess("Session removed successfully!");
          loadCubicles();
          updateActiveSessions();
        }
      })
      .catch((error) => {
        console.error("Error removing session:", error);
        showError(error.message);
      })
      .finally(() => {
        modal.hide();
      });
  }

  // Show session details in modal
  function showSessionDetails(session) {
    const bookedTime = Math.abs(
      strtotime(session.end_time) - strtotime(session.start_time)
    );

    const initialPrice = parseFloat(session.initial_price);
    const additionalCharges = parseFloat(session.total_bill) - initialPrice;

    document.getElementById(
      "modalUserName"
    ).textContent = `${session.firstname} ${session.lastname}`;
    document.getElementById("modalCubicleNumber").textContent =
      session.cubicle_number;
    document.getElementById("modalInitialPrice").textContent =
      formatCurrency(initialPrice);
    document.getElementById("modalAdditionalCharges").textContent =
      formatCurrency(additionalCharges);
    document.getElementById("modalTimeBooked").textContent =
      formatTime(bookedTime);
    document.getElementById("modalTimeExceeded").textContent = `+${formatTime(
      session.exceeding_time
    )}`;
    document.getElementById("modalTotalBill").textContent = formatCurrency(
      session.total_bill
    );

    // Set session ID on end session button
    const endSessionBtn = document.getElementById("confirmEndSessionBtn");
    endSessionBtn.dataset.sessionId = session.id;

    // Show details modal
    const detailsModal = new bootstrap.Modal(
      document.getElementById("sessionDetailsModal")
    );
    detailsModal.show();
  }

  // Show confirmation dialog
  function showEndConfirmation(event) {
    const sessionId = event.target.closest("button").dataset.sessionId;
    const confirmModal = new bootstrap.Modal(
      document.getElementById("confirmEndSessionModal")
    );

    // Set session ID on final confirmation button
    const confirmBtn = document.getElementById("confirmEndSessionFinalBtn");
    confirmBtn.dataset.sessionId = sessionId;

    // Show confirmation modal
    confirmModal.show();
  }

  // Handle final confirmation
  function handleEndSessionConfirm() {
    const sessionId = this.dataset.sessionId;
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("confirmEndSessionModal")
    );

    const formData = new FormData();
    formData.append("action", "endSession");
    formData.append("sessionId", sessionId);

    fetch("backend/timer_manager.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          showSuccess("Session ended successfully!");
          loadCubicles();
          updateActiveSessions();
        }
      })
      .catch((error) => {
        console.error("Error ending session:", error);
        showError(error.message);
      })
      .finally(() => {
        modal.hide();
      });
  }

  // Format time (seconds to HH:MM:SS)
  function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return [
      hours.toString().padStart(2, "0"),
      minutes.toString().padStart(2, "0"),
      secs.toString().padStart(2, "0"),
    ].join(":");
  }
});

// Helper function to convert datetime string to timestamp
function strtotime(datetimeString) {
  return new Date(datetimeString).getTime() / 1000;
}
