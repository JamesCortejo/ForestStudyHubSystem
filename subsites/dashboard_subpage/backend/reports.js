$(document).ready(function () {
  const initDataTable = (containerId) => {
    const $container = $(`#${containerId}`);
    const $table = $container.find("table");

    if ($.fn.DataTable.isDataTable($table)) {
      $table.DataTable().destroy();
    }

    $table.DataTable({
      paging: true,
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      searching: true,
      ordering: true,
      responsive: true,
      autoWidth: false,
      dom: '<"top"<"d-flex justify-content-between mb-3"l<"ms-auto"f>>>rt<"bottom"ip>',
      language: {
        search: "Search:",
        paginate: {
          previous: "‹",
          next: "›",
        },
      },
    });
  };

  const loadReportData = (type) => {
    const contentId = `${type}-table-content`; // Removed underscore replacement
    const $content = $(`#${contentId}`);

    $content.html('<div class="loading-spinner">Loading...</div>');

    $.ajax({
      url: "backend/get_reports_data.php",
      method: "POST",
      data: { type: type },
      success: function (response) {
        $content.html(response);
        initDataTable(contentId);
      },
      error: function (xhr) {
        $content.html(
          `<div class="alert alert-danger">Error: ${xhr.statusText}</div>`
        );
      },
    });
  };

  const reportTypes = [
    "orders",
    "cubicle_bookings",
    "room_bookings",
    "room_sessions",
    "cubicle_sessions",
  ];

  reportTypes.forEach((type) => {
    loadReportData(type);
  });

  document.querySelectorAll(".btn-download").forEach((btn) => {
    btn.addEventListener("click", function () {
      const type = this.dataset.type;
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "backend/generate_pdf.php";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "type";
      input.value = type;

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    });
  });
});
