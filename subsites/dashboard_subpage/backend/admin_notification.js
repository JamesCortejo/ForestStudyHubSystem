document.addEventListener("DOMContentLoaded", () => {
  const ADMIN_STORAGE_KEY = "adminNotifications";
  const BASE_PATH = "/ForrestStudy_Hub/subsites/dashboard_subpage/backend/";
  let adminNotifications =
    JSON.parse(localStorage.getItem(ADMIN_STORAGE_KEY)) || [];
  let lastClearedTimestamp = 0;
  const notificationBadge = document.getElementById("adminNotificationBadge");
  const notificationList = document.getElementById("adminNotificationsList");

  // Audio handling
  let notificationSound = null;
  try {
    notificationSound = new Audio("/ForrestStudy_Hub/resources/admin.mp3");
    notificationSound.preload = "auto";
  } catch (error) {
    console.warn("Notification sound error:", error);
  }

  // Initial setup
  updateBadge();
  renderNotifications();
  startPolling();

  document
    .getElementById("adminClearNotifications")
    ?.addEventListener("click", clearAllNotifications);

  function startPolling() {
    checkNotifications();
    setInterval(checkNotifications, 30000);
  }

  async function checkNotifications() {
    try {
      const [bookingsRes, ordersRes] = await Promise.all([
        fetch(`${BASE_PATH}admin_booking_notifications.php?t=${Date.now()}`),
        fetch(`${BASE_PATH}admin_order_notifications.php?t=${Date.now()}`),
      ]);

      const process = async (response, type) => {
        if (!response.ok) return null;
        const data = await response.json();
        if (data?.success) {
          return processNotifications(data, type);
        }
        return null;
      };

      await Promise.all([
        process(bookingsRes, "booking"),
        process(ordersRes, "order"),
      ]);
    } catch (error) {
      console.error("Check Error:", error);
    }
  }

  async function fetchData(endpoint) {
    try {
      const response = await fetch(
        `${BASE_PATH}${endpoint}?cache=${Date.now()}`
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const contentType = response.headers.get("content-type");
      if (!contentType?.includes("application/json")) {
        throw new Error("Invalid content type received");
      }

      const data = await response.json();

      if (!data?.success) {
        throw new Error("Invalid response structure");
      }

      return data;
    } catch (error) {
      console.error(`Failed to fetch ${endpoint}:`, error.message);
      return null;
    }
  }

  function processNotifications(data, type) {
    const currentTime = Date.now();
    const newNotifications = [];

    try {
      // 1. Extract relevant messages
      if (type === "booking") {
        if (data.cubicle?.message) {
          newNotifications.push(createNotification(data.cubicle.message, type));
        }
        if (data.room?.message) {
          newNotifications.push(createNotification(data.room.message, type));
        }
      } else if (type === "order" && data.order?.message) {
        newNotifications.push(createNotification(data.order.message, type));
      }

      // 2. Advanced filtering
      const filtered = newNotifications.filter((n) => {
        // 2a. Check against existing notifications
        const isDuplicate = adminNotifications.some(
          (existing) =>
            existing.message === n.message && existing.type === n.type
        );

        // 2b. Check against clear timestamp
        const isBeforeClear = n.timestamp <= lastClearedTimestamp;

        // 2c. Check expiration (5 minutes)
        const isExpired = currentTime - n.timestamp > 300000;

        // 2d. Only keep if passes all checks
        return !isDuplicate && !isBeforeClear && !isExpired;
      });

      // 3. Add new notifications
      if (filtered.length > 0) {
        adminNotifications = [
          ...filtered,
          ...adminNotifications.slice(0, 49 - filtered.length),
        ];

        localStorage.setItem(
          ADMIN_STORAGE_KEY,
          JSON.stringify(adminNotifications)
        );
        updateUI();

        // 4. Play sound only for unread notifications
        if (filtered.some((n) => !n.read)) {
          playAlertSound();
        }

        // 5. Show browser notifications
        showBrowserNotifications(filtered);
      }
    } catch (error) {
      console.error("Notification processing error:", {
        error: error.message,
        data,
        type,
        currentTime,
        lastClearedTimestamp,
      });
    }
  }

  function createNotification(message, type) {
    return {
      id: Date.now(),
      title: type === "order" ? "New Order" : "New Booking",
      message,
      type,
      timestamp: Date.now(), // Already correct (milliseconds)
      read: false,
    };
  }

  async function clearAllNotifications() {
    try {
      // Step 1: Immediately clear local notifications
      adminNotifications = [];
      localStorage.setItem(
        ADMIN_STORAGE_KEY,
        JSON.stringify(adminNotifications)
      );
      updateUI();

      // Step 2: Clear backend timestamps
      const response = await fetch(
        `${BASE_PATH}clear_notifications.php?t=${Date.now()}`
      );
      if (!response.ok) throw new Error("Clear failed");

      const data = await response.json();
      if (!data?.success) throw new Error("Backend clearance failed");

      // Step 3: Update cleared timestamp (convert seconds to milliseconds)
      lastClearedTimestamp = data.clear_time * 1000;
      console.log("Clear timestamp set to:", new Date(lastClearedTimestamp));

      // Step 4: Prevent immediate re-fetch
      await new Promise((resolve) => setTimeout(resolve, 5000)); // 5-second delay

      // Step 5: Refresh notifications with new timestamps
      await checkNotifications();
    } catch (error) {
      console.error("Clear error:", error);
      alert(`Clear failed: ${error.message}`);
    }
  }

  function updateUI() {
    renderNotifications();
    updateBadge();
  }

  function renderNotifications() {
    notificationList.innerHTML = adminNotifications
      .map(
        (notification) => `
            <div class="alert alert-sm ${
              !notification.read ? "alert-warning" : "alert-light"
            } 
                 mb-2 py-2 px-3 d-flex justify-content-between align-items-center"
                 role="button"
                 data-id="${notification.id}">
                <div class="d-flex align-items-center">
                    <i class="bi ${
                      notification.type === "order" ? "bi-cart" : "bi-calendar"
                    } me-2"></i>
                    <div>
                        <div class="small fw-bold">${notification.title}</div>
                        ${notification.message}
                    </div>
                </div>
                <small class="text-muted ms-3">${formatTime(
                  new Date(notification.timestamp)
                )}</small>
            </div>
        `
      )
      .join("");

    notificationList.querySelectorAll(".alert").forEach((alert) => {
      alert.addEventListener("click", () => markAsRead(alert.dataset.id));
    });
  }

  function markAsRead(id) {
    adminNotifications = adminNotifications.map((n) =>
      n.id === Number(id) ? { ...n, read: true } : n
    );
    localStorage.setItem(ADMIN_STORAGE_KEY, JSON.stringify(adminNotifications));
    updateUI();
  }

  function updateBadge() {
    const unreadCount = adminNotifications.filter((n) => !n.read).length;
    notificationBadge.classList.toggle("d-none", unreadCount === 0);
    notificationBadge.textContent = unreadCount > 9 ? "9+" : unreadCount;
  }

  function formatTime(date) {
    return date.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  }

  function playAlertSound() {
    if (!notificationSound) return;

    try {
      notificationSound.currentTime = 0;
      notificationSound.play().catch((error) => {
        console.warn("Audio playback prevented:", error);
      });
    } catch (error) {
      console.error("Sound error:", error);
    }
  }

  function showBrowserNotifications(notifications) {
    if (!("Notification" in window)) return;

    const showNotification = (title, body) => {
      new Notification(title, {
        body,
        icon: "/ForrestStudy_Hub/resources/notification-icon.png",
      });
    };

    if (Notification.permission === "granted") {
      notifications.forEach((n) => showNotification(n.title, n.message));
    } else if (Notification.permission !== "denied") {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          notifications.forEach((n) => showNotification(n.title, n.message));
        }
      });
    }
  }

  function showUserAlert(message) {
    const alert = document.createElement("div");
    alert.className = "alert alert-danger alert-dismissible fade show";
    alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
    document.body.prepend(alert);
  }

  function debugNotifications() {
    console.group("Notification Debug");
    console.log("Local Storage:", localStorage.getItem(ADMIN_STORAGE_KEY));
    console.log("Current Time:", Date.now());
    console.log("Last Cleared:", lastClearedTimestamp);
    console.table(adminNotifications);
    console.groupEnd();
  }

  // Call this when needed
  document
    .querySelector("#debugButton")
    ?.addEventListener("click", debugNotifications);
});
