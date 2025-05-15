// session-timer.js
let notificationState = JSON.parse(
  sessionStorage.getItem("notificationState") ||
    JSON.stringify({
      hasSentZeroNotification: false,
      lastNotifiedInterval: -1,
      lastSessionEnd: null,
    })
);

function handleNoSession() {
  notificationState = {
    hasSentZeroNotification: false,
    lastNotifiedInterval: -1,
    lastSessionEnd: null,
  };

  const timerDisplay = document.getElementById("userTimerDisplay");
  const sessionInfo = document.getElementById("sessionInfo");
  const progressBar = document.getElementById("timeProgressBar");

  if (timerDisplay) timerDisplay.textContent = "No current sessions";
  if (sessionInfo) sessionInfo.innerHTML = "";
  if (progressBar) {
    progressBar.style.width = "0%";
    progressBar.style.backgroundColor = "";
  }

  sessionStorage.removeItem("notificationState");
}

function checkSession() {
  fetch("/ForrestStudy_Hub/subsites/userpage_subpage/backend/user_session.php")
    .then((response) => {
      if (!response.ok) throw new Error("Network error");
      return response.json();
    })
    .then((data) => {
      if (data.success && data.session) {
        handleSessionData(data.session);
      } else {
        handleNoSession();
      }
    })
    .catch((error) => {
      console.error("Session check error:", error);
      handleNoSession();
    });
}

function handleSessionData(session) {
  const sessionEnd = new Date(session.end_time).getTime();

  if (sessionEnd !== notificationState.lastSessionEnd) {
    notificationState = {
      hasSentZeroNotification: false,
      lastNotifiedInterval: -1,
      lastSessionEnd: sessionEnd,
    };
  }

  if (session.time_remaining <= 0) {
    handleExceededSession(session);
  } else {
    notificationState.hasSentZeroNotification = false;
    notificationState.lastNotifiedInterval = -1;
  }

  sessionStorage.setItem(
    "notificationState",
    JSON.stringify(notificationState)
  );
  updateTimerUI(session);
}

function handleExceededSession(session) {
  const exceedingTime = session.exceeding_time;

  if (!notificationState.hasSentZeroNotification) {
    if (typeof window.sendNotification === "function") {
      window.sendNotification(
        "Session Expired",
        "⏰ Time's up! Please wrap up your session",
        { sound: true }
      );
    }
    notificationState.hasSentZeroNotification = true;
    notificationState.lastNotifiedInterval = 0;
  }

  const currentInterval = Math.floor(exceedingTime / 180);
  if (currentInterval > notificationState.lastNotifiedInterval) {
    const minutesExceeded = currentInterval * 3;
    if (typeof window.sendNotification === "function") {
      window.sendNotification(
        "Session Exceeded",
        `🚨 Session exceeded by ${minutesExceeded} minutes`,
        { sound: true }
      );
    }
    notificationState.lastNotifiedInterval = currentInterval;
  }
}

function updateTimerUI(session) {
  const timerDisplay = document.getElementById("userTimerDisplay");
  const sessionInfo = document.getElementById("sessionInfo");
  const progressBar = document.getElementById("timeProgressBar");

  if (!timerDisplay) return;

  const displaySeconds =
    session.time_remaining > 0
      ? session.time_remaining
      : session.exceeding_time;

  timerDisplay.textContent = formatTime(displaySeconds);

  if (sessionInfo) {
    sessionInfo.innerHTML = `
        <span class="badge ${
          session.session_type === "cubicle" ? "bg-success" : "bg-primary"
        } me-2">
          ${session.session_type.toUpperCase()}
        </span>
        <span class="session-location">${
          session.location || "Unknown Location"
        }</span>`;
  }

  if (progressBar) {
    if (session.time_remaining > 0) {
      const percentage =
        (session.time_remaining / session.total_duration) * 100;
      progressBar.style.width = `${percentage}%`;
      progressBar.style.backgroundColor =
        session.session_type === "cubicle" ? "#28a745" : "#0d6efd";
    } else {
      progressBar.style.width = "100%";
      progressBar.style.backgroundColor = "#ffc107";
    }
  }
}

function formatTime(totalSeconds) {
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;
  return [hours, minutes, seconds]
    .map((unit) => String(unit).padStart(2, "0"))
    .join(":");
}

// Initialize with safe notification check
if (typeof window.sendNotification !== "function") {
  window.sendNotification = () => console.warn("Notifications not initialized");
}

setInterval(checkSession, 1000);
checkSession();
