document.addEventListener("DOMContentLoaded", function () {
  // DOM Elements
  const userSearchInput = document.getElementById("roomsSearchUsers");
  const userSelect = document.getElementById("roomsUserSelect");
  const addUserBtn = document.getElementById("addUserRooms");
  const usersInRoom = document.getElementById("usersInRoom");
  const roomSelect = document.getElementById("roomSelect");
  const timeSelectRooms = document.getElementById("timerSelectRooms");
  const setTimerBtnRooms = document.getElementById("setRoomTimerBtn");
  const vacantRoomsList = document.getElementById("vacantRoomsList");

  // Global variables
  let selectedUsers = [];
  let activeRoomSessions = [];
  let updateInterval;

  // Price configuration
  const timeOptions = [
    { label: "1 hour", seconds: 3600, price: 85 },
    { label: "1.5 hours", seconds: 5400, price: 95 },
    { label: "2 hours", seconds: 7200, price: 105 },
    { label: "2.5 hours", seconds: 9000, price: 115 },
    { label: "3 hours", seconds: 10800, price: 125 },
    { label: "3.5 hours", seconds: 12600, price: 135 },
    { label: "4 hours", seconds: 14400, price: 145 },
  ];

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

  // Initialize
  initializeTimeOptions();
  initializeUsers();
  loadRooms();
  startSessionUpdater();

  // Event Listeners
  addUserBtn.addEventListener("click", addUserToSession);
  userSearchInput.addEventListener("input", debounce(searchUsers, 300));
  setTimerBtnRooms.addEventListener("click", startRoomSession);
  document
    .getElementById("confirmRoomDeleteBtn")
    .addEventListener("click", handleRoomDeleteConfirm);

  // New event listener for End Session button in details modal
  document
    .getElementById("confirmEndRoomSessionBtn")
    .addEventListener("click", function () {
      const modal = document.getElementById("sessionDetailsModalRooms");
      const sessionId = modal.dataset.sessionId;
      if (sessionId) {
        showDeleteConfirmation(sessionId);
        const modalInstance = bootstrap.Modal.getInstance(modal);
        modalInstance.hide();
      }
    });

  function debounce(func, timeout = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), timeout);
    };
  }

  function clearErrors() {
    document.querySelectorAll(".text-danger").forEach((el) => el.remove());
  }

  function initializeTimeOptions() {
    timeSelectRooms.innerHTML =
      "<option selected disabled>Select Time</option>";
    timeOptions.forEach((option) => {
      const element = document.createElement("option");
      element.value = option.seconds;
      element.setAttribute("data-price", option.price);
      element.textContent = `${option.label} - ₱${option.price}`;
      timeSelectRooms.appendChild(element);
    });
  }

  async function initializeUsers() {
    userSearchInput.value = "";
    await searchUsers();
  }

  async function searchUsers() {
    const searchTerm = userSearchInput.value.trim();
    try {
      const response = await fetch(
        `backend/timer_manager_rooms.php?action=searchUsers&query=${encodeURIComponent(
          searchTerm
        )}`
      );

      const users = await response.json();
      if (!response.ok)
        throw new Error(users.error || "Failed to search users");
      updateUserSelect(users);
    } catch (error) {
      console.error("Search error:", error);
      showError(error.message);
      userSelect.innerHTML = '<option value="">Error loading users</option>';
    }
  }

  function updateUserSelect(users) {
    userSelect.innerHTML = '<option value="">Select a user</option>';
    if (!Array.isArray(users)) return;

    users.forEach((user) => {
      if (selectedUsers.some((u) => u.id === user.id.toString())) return;
      const option = document.createElement("option");
      option.value = user.id;
      option.textContent = `${user.name} (${user.username})`;
      userSelect.appendChild(option);
    });

    if (userSelect.options.length === 1) {
      const option = document.createElement("option");
      option.textContent = "No users found";
      option.disabled = true;
      userSelect.appendChild(option);
    }
  }

  function addUserToSession() {
    clearErrors();
    const selectedOption = userSelect.options[userSelect.selectedIndex];
    if (!selectedOption.value) {
      showError("Please select a user from the list");
      return;
    }

    const user = {
      id: selectedOption.value,
      name: selectedOption.textContent.split(" (")[0],
    };

    if (selectedUsers.some((u) => u.id === user.id)) {
      showError("This user is already added");
      return;
    }

    selectedUsers.push(user);
    renderSelectedUsers();
    userSelect.value = "";
    userSearchInput.value = "";
  }

  function renderSelectedUsers() {
    usersInRoom.innerHTML = "";
    selectedUsers.forEach((user) => {
      const div = document.createElement("div");
      div.className =
        "d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded";
      div.innerHTML = `
        <span>${user.name}</span>
        <button class="btn btn-sm btn-danger remove-user" data-id="${user.id}">
            <i class="bi bi-x"></i>
        </button>
      `;
      usersInRoom.appendChild(div);
    });

    document.querySelectorAll(".remove-user").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const userId = e.target.closest("button").dataset.id;
        selectedUsers = selectedUsers.filter((u) => u.id !== userId);
        renderSelectedUsers();
      });
    });
  }

  async function loadRooms() {
    try {
      const response = await fetch(
        "backend/timer_manager_rooms.php?action=getRooms"
      );
      const rooms = await response.json();
      if (!response.ok) throw new Error(rooms.error || "Failed to load rooms");
      updateRoomSelect(rooms);
      updateVacantRooms(rooms);
    } catch (error) {
      console.error("Room load error:", error);
      showError(error.message);
    }
  }

  function updateRoomSelect(rooms) {
    roomSelect.innerHTML = "<option selected disabled>Select Room</option>";
    rooms.forEach((room) => {
      const option = document.createElement("option");
      option.value = room.room_id;
      option.textContent = `Room ${room.room_number} (${room.current_occupancy}/${room.capacity})`;
      roomSelect.appendChild(option);
    });
  }

  function updateVacantRooms(rooms) {
    vacantRoomsList.innerHTML = "";
    rooms.forEach((room) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-center";
      li.innerHTML = `
        Room ${room.room_number}
        <span class="badge ${
          room.status === "available" ? "bg-success" : "bg-warning"
        }">
            ${room.status.replace("_", " ")} (${room.current_occupancy}/${
        room.capacity
      })
        </span>
      `;
      vacantRoomsList.appendChild(li);
    });
  }

  async function startRoomSession() {
    clearErrors();
    let isValid = true;

    if (selectedUsers.length === 0) {
      showError("Please select at least 1 user");
      isValid = false;
    }

    if (!roomSelect.value) {
      showError("Please select a room");
      isValid = false;
    }

    if (!timeSelectRooms.value) {
      showError("Please select a time duration");
      isValid = false;
    }

    if (!isValid) return;

    setTimerBtnRooms.disabled = true;
    setTimerBtnRooms.innerHTML =
      '<span class="spinner-border spinner-border-sm"></span> Starting...';

    try {
      const price =
        timeSelectRooms.options[timeSelectRooms.selectedIndex].getAttribute(
          "data-price"
        );
      const formData = new FormData();
      formData.append("action", "startRoomSession");
      formData.append("roomId", roomSelect.value);
      formData.append("timeSelected", timeSelectRooms.value);
      formData.append("initialPrice", price);
      formData.append("users", JSON.stringify(selectedUsers.map((u) => u.id)));

      const response = await fetch("backend/timer_manager_rooms.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "Server error");

      if (result.success) {
        showSuccess("Session started successfully!");
        selectedUsers = [];
        renderSelectedUsers();
        loadRooms();
      }
    } catch (error) {
      showError(error.message);
    } finally {
      setTimerBtnRooms.disabled = false;
      setTimerBtnRooms.textContent = "Set Timer";
    }
  }

  function startSessionUpdater() {
    updateRoomSessions();
    updateInterval = setInterval(updateRoomSessions, 1000);
  }

  async function updateRoomSessions() {
    try {
      const response = await fetch(
        "backend/timer_manager_rooms.php?action=getActiveRoomSessions"
      );
      const sessions = await response.json();
      if (!response.ok)
        throw new Error(sessions.error || "Failed to update sessions");
      activeRoomSessions = sessions;
      renderActiveSessions();
    } catch (error) {
      console.error("Session update error:", error);
    }
  }

  function renderActiveSessions() {
    const activeList = document.getElementById("activeStudiesListRooms");
    const exceededList = document.getElementById("exceededTimeListRooms");
    activeList.innerHTML = "";
    exceededList.innerHTML = "";

    activeRoomSessions.forEach((session) => {
      session.time_remaining = parseInt(session.time_remaining);
      session.exceeding_time = parseInt(session.exceeding_time);
      const totalBill = parseFloat(session.total_bill || 0);

      const li = document.createElement("li");
      li.className = "list-group-item";
      li.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Room ${session.room_number}</strong>
                <div class="text-muted small">Users: ${session.users.join(
                  ", "
                )}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${
                  session.time_remaining > 0
                    ? `
                    <span class="badge bg-primary">${formatTime(
                      session.time_remaining
                    )}</span>
                    <span class="badge bg-success">₱${totalBill.toFixed(
                      2
                    )}</span>
                    <button class="btn btn-sm btn-danger" data-session-id="${
                      session.session_id
                    }" onclick="showDeleteConfirmation(${session.session_id})">
                        <i class="bi bi-trash"></i>
                    </button>
                `
                    : `
                    <span class="badge bg-danger">+${formatTime(
                      session.exceeding_time
                    )}</span>
                    <span class="badge bg-warning">₱${totalBill.toFixed(
                      2
                    )}</span>
                    <button class="btn btn-sm btn-primary" onclick="showSessionDetails(${
                      session.session_id
                    })">
                        <i class="bi bi-info-circle"></i>
                    </button>
                `
                }
            </div>
        </div>
      `;
      (session.time_remaining > 0 ? activeList : exceededList).appendChild(li);
    });
  }

  function formatTime(seconds) {
    seconds = Math.max(0, Math.floor(seconds));
    const hrs = Math.floor(seconds / 3600)
      .toString()
      .padStart(2, "0");
    const mins = Math.floor((seconds % 3600) / 60)
      .toString()
      .padStart(2, "0");
    const secs = (seconds % 60).toString().padStart(2, "0");
    return `${hrs}:${mins}:${secs}`;
  }

  window.showSessionDetails = async (sessionId) => {
    try {
      const response = await fetch(
        `backend/timer_manager_rooms.php?action=getSessionDetails&sessionId=${sessionId}`
      );
      const details = await response.json();
      if (!response.ok)
        throw new Error(details.error || "Failed to load session details");

      // Store session ID in modal dataset
      const modalElement = document.getElementById("sessionDetailsModalRooms");
      modalElement.dataset.sessionId = sessionId;

      document.getElementById("modalRoomNumber").textContent =
        details.room_number || "N/A";
      document.getElementById("modalParticipantCount").textContent =
        details.users?.length.toString() || "0";
      document.getElementById("modalRoomTimeBooked").textContent = formatTime(
        details.total_duration || 0
      );
      document.getElementById(
        "modalRoomTimeExceeded"
      ).textContent = `+${formatTime(Math.abs(details.exceeding_time || 0))}`;

      const userList = document.getElementById("modalUserList");
      userList.innerHTML =
        details.users
          ?.map(
            (user) => `
        <tr>
          <td>${user.name}</td>
          <td>${user.username}</td>
          <td>₱${(parseFloat(user.initial_share) || 0).toFixed(2)}</td>
          <td>₱${(parseFloat(user.overtime_share) || 0).toFixed(2)}</td>
          <td>₱${(
            parseFloat(user.initial_share) + parseFloat(user.overtime_share)
          ).toFixed(2)}</td>
        </tr>
      `
          )
          .join("") || '<tr><td colspan="5">No users found</td></tr>';

      document.getElementById("modalGrandTotal").textContent = `₱${(
        parseFloat(details.total_bill) || 0
      ).toFixed(2)}`;
      new bootstrap.Modal(
        document.getElementById("sessionDetailsModalRooms")
      ).show();
    } catch (error) {
      showError(`Error showing details: ${error.message}`);
    }
  };

  window.showDeleteConfirmation = (sessionId) => {
    const modal = new bootstrap.Modal(
      document.getElementById("confirmRoomDeleteModal")
    );
    document.getElementById("confirmRoomDeleteBtn").dataset.sessionId =
      sessionId;
    modal.show();
  };

  function handleRoomDeleteConfirm() {
    const sessionId = this.dataset.sessionId;
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("confirmRoomDeleteModal")
    );

    const formData = new FormData();
    formData.append("action", "endRoomSession");
    formData.append("sessionId", sessionId);

    fetch("backend/timer_manager_rooms.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((result) => {
        if (!result.success)
          throw new Error(result.error || "Failed to end session");
        showSuccess("Session ended successfully!");
        updateRoomSessions();
        loadRooms();
      })
      .catch((error) => showError(error.message))
      .finally(() => modal.hide());
  }
});
