document
  .getElementById("historyModal")
  .addEventListener("shown.bs.modal", function () {
    // Format date to 12-hour format
    const formatDateTime = (dateString) => {
      const options = {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
      };
      return new Date(dateString).toLocaleString("en-US", options);
    };

    // Format seconds to hours and minutes
    const formatTime = (seconds) => {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      return `${hours}h ${minutes}m`;
    };

    // Load purchase history (corrected path)
    fetch(
      "/ForrestStudy_Hub/subsites/userpage_subpage/backend/purchase_history.php"
    )
      .then((response) => response.json())
      .then((data) => {
        const tbody = document.getElementById("purchase-history-body");
        tbody.innerHTML = data
          .map(
            (purchase) => `
                <tr>
                    <td>${formatDateTime(purchase.created_at)}</td>
                    <td>₱${parseFloat(purchase.total_amount).toFixed(2)}</td>
                    <td>${purchase.payment_method}</td>
                </tr>
            `
          )
          .join("");
      });

    // Load cubicle sessions (corrected path)
    fetch(
      "/ForrestStudy_Hub/subsites/userpage_subpage/backend/cubicle_history.php"
    )
      .then((response) => response.json())
      .then((data) => {
        const tbody = document.getElementById("cubicle-history-body");
        tbody.innerHTML = data
          .map(
            (session) => `
                <tr>
                    <td>${formatDateTime(session.start_time)}</td>
                    <td>${formatDateTime(session.end_time)}</td>
                    <td>${session.cubicle}</td>
                    <td>${formatTime(session.time_remaining)}</td>
                    <td>${formatTime(session.exceeding_time)}</td>
                    <td>₱${parseFloat(session.total_bill).toFixed(2)}</td>
                </tr>
            `
          )
          .join("");
      });

    // Load room sessions (corrected path)
    fetch(
      "/ForrestStudy_Hub/subsites/userpage_subpage/backend/room_history.php"
    )
      .then((response) => response.json())
      .then((data) => {
        const tbody = document.getElementById("room-history-body");
        tbody.innerHTML = data
          .map(
            (session) => `
                <tr>
                    <td>${formatDateTime(session.start_time)}</td>
                    <td>${formatDateTime(session.end_time)}</td>
                    <td>${session.room}</td>
                    <td>${formatTime(session.time_remaining)}</td>
                    <td>${formatTime(session.exceeding_time)}</td>
                    <td>₱${parseFloat(session.total_bill).toFixed(2)}</td>
                </tr>
            `
          )
          .join("");
      });
  });
