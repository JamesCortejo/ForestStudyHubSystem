document.addEventListener("DOMContentLoaded", function () {
  let charts = {};

  function initCharts(data) {
    Object.values(charts).forEach((chart) => chart.destroy());

    // Session Distribution Chart
    charts.sessionDistribution = new Chart(
      document.getElementById("sessionDistributionChart"),
      {
        type: "pie",
        data: {
          labels: ["Study Cubicles", "Study Rooms"],
          datasets: [
            {
              data: [
                data.sessionDistribution.cubicle,
                data.sessionDistribution.room,
              ],
              backgroundColor: ["#0d6efd", "#198754"],
            },
          ],
        },
        options: {
          plugins: {
            tooltip: {
              callbacks: {
                label: function (context) {
                  return `${context.label}: ${context.raw} sessions`;
                },
              },
            },
          },
        },
      }
    );

    // Sales Breakdown Chart
    const salesLabels = Object.keys(data.salesBreakdown);
    charts.salesBreakdown = new Chart(
      document.getElementById("salesBreakdownChart"),
      {
        type: "pie",
        data: {
          labels: salesLabels,
          datasets: [
            {
              data: Object.values(data.salesBreakdown),
              backgroundColor: [
                "#ffc107",
                "#fd7e14",
                "#6c757d",
                "#20c997",
                "#0dcaf0",
              ],
            },
          ],
        },
      }
    );

    // Hourly Usage Trends Chart
    const durationLabels = Object.keys(data.hourlyUsage);
    const durationCounts = Object.values(data.hourlyUsage);
    charts.hourlyUsage = new Chart(
      document.getElementById("hourlyUsageChart"),
      {
        type: "bar",
        data: {
          labels: durationLabels,
          datasets: [
            {
              label: "Number of Bookings",
              data: durationCounts,
              backgroundColor: "#0d6efd",
            },
          ],
        },
        options: {
          indexAxis: "x",
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 },
            },
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function (context) {
                  return `${context.dataset.label}: ${context.raw}`;
                },
              },
            },
          },
        },
      }
    );

    // Top Items Chart
    const items = Object.entries(data.topItems).sort((a, b) => b[1] - a[1]);
    charts.topItems = new Chart(document.getElementById("topItemsChart"), {
      type: "bar",
      data: {
        labels: items.map((i) => i[0]),
        datasets: [
          {
            label: "Quantity Sold",
            data: items.map((i) => i[1]),
            backgroundColor: "#20c997",
          },
        ],
      },
      options: {
        scales: { y: { beginAtZero: true } },
      },
    });
  }

  // Fetch data and update dashboard
  fetch(
    "/ForrestStudy_Hub/subsites/dashboard_subpage/backend/dashboard_data.php"
  )
    .then((response) => response.json())
    .then((data) => {
      // Update quick stats
      document.getElementById("cubicleSessions").textContent =
        data.currentSessions.cubicle;
      document.getElementById("roomSessions").textContent =
        data.currentSessions.room;
      document.getElementById("shopSales").textContent = `₱${Number(
        data.todaySales
      ).toFixed(2)}`;
      document.getElementById(
        "cubicleBills"
      ).textContent = `₱${data.cubicleRevenue.toFixed(2)}`;
      document.getElementById(
        "roomBills"
      ).textContent = `₱${data.roomRevenue.toFixed(2)}`;
      document.getElementById("totalProducts").textContent = data.totalProducts;

      // Update booking stats
      document.getElementById("totalBookings").textContent =
        data.bookings.total;
      document.getElementById("pendingBookings").textContent =
        data.bookings.pending;
      document.getElementById("approvedBookings").textContent =
        data.bookings.approved;

      // Update purchase stats
      document.getElementById("totalPurchases").textContent =
        data.purchases.total;
      document.getElementById("pendingPurchases").textContent =
        data.purchases.pending;
      document.getElementById("confirmedPurchases").textContent =
        data.purchases.confirmed;

      // Update top products carousel
      const carouselInner = document.querySelector(
        "#topProductsCarousel .carousel-inner"
      );
      carouselInner.innerHTML = "";

      data.topProducts.forEach((product, index) => {
        const activeClass = index === 0 ? "active" : "";
        const cacheBuster = `?v=${new Date().getTime()}`;
        const basePath = product.image_path.startsWith("/")
          ? ""
          : "/ForrestStudy_Hub/";
        const imageUrl = `${basePath}${product.image_path}${cacheBuster}`;

        const item = document.createElement("div");
        item.className = `carousel-item ${activeClass}`;
        item.innerHTML = `
                    <div class="d-flex flex-column align-items-center">
                        <img src="${imageUrl}" 
                             class="d-block mb-3 product-image" 
                             alt="${product.product_name}"
                             onerror="this.src='/ForrestStudy_Hub/resources/default-product.jpg'"
                             style="max-height: 200px; object-fit: contain">
                        <h4 class="text-black">${product.product_name}</h4>
                        <p class="text-black">Sold: ${product.total_sold}</p>
                    </div>
                `;
        carouselInner.appendChild(item);
      });

      if (data.topProducts.length > 0) {
        new bootstrap.Carousel(document.getElementById("topProductsCarousel"), {
          interval: 5000,
        });
      }

      initCharts(data);
    })
    .catch((error) => console.error("Error:", error));
});

window.logout = function () {
  fetch("../php/logout.php", {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "Cache-Control": "no-cache",
    },
  })
    .then((response) => {
      if (!response.ok) throw new Error("Logout failed");
      window.location.href = "../index.php?logout=" + Date.now();
    })
    .catch((error) => {
      console.error("Logout error:", error);
      window.location.href = "../index.php?logout=force";
    });
};
