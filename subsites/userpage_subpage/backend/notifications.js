// notifications.js
document.addEventListener("DOMContentLoaded", async () => {
  await settingsManager.ready;
  initializeNotificationSystem();
  setupRealTimeSync();
  requestNotificationPermission();
  updateNotificationBadge();
});

let audioEnabled = true;
const notificationSound = new Audio("/ForrestStudy_Hub/resources/alert.mp3");
notificationSound.preload = "auto";
let notifications = [];
let updateNotificationPanel;

function initializeNotificationSystem() {
  const notificationList = document.getElementById("notificationList");
  const clearNotificationsBtn = document.getElementById(
    "clearNotificationsBtn"
  );

  // Initialize with safe JSON parsing
  notifications = JSON.parse(localStorage.getItem("notifications") || "[]");

  updateNotificationPanel = () => {
    if (!notificationList) return;

    notificationList.innerHTML = "";
    notifications
      .slice()
      .reverse()
      .forEach((notification, index) => {
        const li = document.createElement("li");
        li.className = `list-group-item notification-item ${
          !notification.read ? "unread" : ""
        }`;
        li.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <div class="ms-2 me-auto">
            <div class="fw-bold">${notification.title}</div>
            ${notification.message}
          </div>
          <small class="text-muted">${formatTimeAgo(
            notification.timestamp
          )}</small>
        </div>
      `;

        li.addEventListener("click", () => {
          if (!notification.read) {
            notification.read = true;
            notifications[notifications.length - 1 - index] = notification;
            localStorage.setItem(
              "notifications",
              JSON.stringify(notifications)
            );
            li.classList.remove("unread");
            updateNotificationPanel();
            updateNotificationBadge(); // Update badge when marking as read
          }
        });

        notificationList.appendChild(li);
      });
  };

  if (clearNotificationsBtn) {
    clearNotificationsBtn.addEventListener("click", (e) => {
      e.preventDefault();
      notifications = [];
      localStorage.setItem("notifications", JSON.stringify(notifications));
      updateNotificationPanel();
      updateNotificationBadge(); // Update badge when clearing
    });
  }

  window.sendNotification = (title, message, options = {}) => {
    const userSettings = JSON.parse(localStorage.getItem("userSettings")) || {
      push: true,
      sound: true,
    };

    // Always show in-page notifications
    const newNotification = {
      title,
      message,
      timestamp: Date.now(),
      read: false,
      id: Math.random().toString(36).slice(2, 9),
      type: options.type || "general",
    };

    notifications = [newNotification, ...notifications];
    if (notifications.length > 20) notifications.length = 20;
    localStorage.setItem("notifications", JSON.stringify(notifications));
    updateNotificationPanel();
    updateNotificationBadge(); // Update badge on new notification

    if ((userSettings.sound || options.force) && options.sound !== false) {
      playNotificationSound();
    }

    if ((userSettings.push || options.force) && options.alert !== false) {
      handleBrowserNotification(title, message);
    }
  };

  updateNotificationPanel();
}

// Notification badge functions
function updateNotificationBadge() {
  const badge = document.getElementById("notificationBadge");
  if (!badge) return;

  const unreadCount = notifications.filter((n) => !n.read).length;
  badge.textContent = unreadCount > 0 ? unreadCount : "";
  badge.style.display = unreadCount > 0 ? "block" : "none";
}

function getUnreadCount() {
  return notifications.filter((n) => !n.read).length;
}

function handleBrowserNotification(title, message) {
  if (typeof Notification === "undefined") return;

  if (Notification.permission === "granted") {
    if (document.visibilityState !== "visible") {
      const notification = new Notification(title, {
        body: message,
        icon: "/ForrestStudy_Hub/resources/notification-icon.png",
        requireInteraction: true,
      });

      notification.onclick = () => {
        window.focus();
        notification.close();
      };
    }
  } else if (Notification.permission !== "denied") {
    Notification.requestPermission().then((permission) => {
      if (permission === "granted") {
        showBrowserNotification(title, message);
      }
    });
  }
}

function setupRealTimeSync() {
  window.addEventListener("storage", (event) => {
    if (event.key === "notifications") {
      notifications = JSON.parse(event.newValue || "[]");
      updateNotificationPanel();
      updateNotificationBadge(); // Sync badge across tabs
    }
  });
}

function playNotificationSound() {
  if (!audioEnabled) return;
  try {
    notificationSound.currentTime = 0;
    notificationSound.play().catch(() => {
      audioEnabled = false;
    });
  } catch (error) {
    audioEnabled = false;
  }
}

function formatTimeAgo(timestamp) {
  const now = Date.now();
  const diff = now - timestamp;
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return "Just now";
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  return `${hours}h ago`;
}

function requestNotificationPermission() {
  if (
    typeof Notification !== "undefined" &&
    Notification.permission === "default"
  ) {
    Notification.requestPermission();
  }
}

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") {
    notifications = JSON.parse(localStorage.getItem("notifications") || "[]");
    updateNotificationPanel();
    updateNotificationBadge(); // Update badge when returning to tab
  }
});
