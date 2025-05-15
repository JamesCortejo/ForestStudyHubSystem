$(document).ready(function () {
  // Load all report tables
  loadReportData("orders");
  loadReportData("cubicle_bookings");
  loadReportData("room_bookings");
  loadReportData("room_sessions");
  loadReportData("cubicle_sessions");

  // PDF download handler
  $(".btn-download").click(function (e) {
    e.preventDefault();
    const reportType = $(this).data("type");
    generatePDF(reportType);
  });
});

function loadReportData(type) {
  $.post("backend/get_reports_data.php", { type: type }, function (data) {
    const targetId = `${type.replace("_", "-")}-table`;
    $(`#${targetId}`).html(data);
  }).fail(function () {
    $(`#${type}-table`).html('<p class="text-danger">Error loading data</p>');
  });
}

function generatePDF(type) {
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
}
