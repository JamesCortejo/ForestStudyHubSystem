document.addEventListener("DOMContentLoaded", () => {
  let currentUserId = null;
  let allUsers = [];

  // Load initial users
  loadUsers();

  // Event Listeners
  document.getElementById("saveUserBtn").addEventListener("click", addUser);
  document
    .getElementById("updateUserBtn")
    .addEventListener("click", updateUser);
  document
    .getElementById("deleteUserBtn")
    .addEventListener("click", deleteUser);
  document.getElementById("addAnotherUser").addEventListener("click", () => {
    $("#addModal").modal("show");
  });

  // Event delegation for dynamic buttons
  document.addEventListener("click", (e) => {
    const deleteBtn = e.target.closest(".delete-btn");
    const editBtn = e.target.closest(".edit-btn");

    if (editBtn) {
      currentUserId = editBtn.dataset.id;
      openEditModal(currentUserId);
    }
    if (deleteBtn) {
      currentUserId = deleteBtn.dataset.id;
      $("#deleteModal").modal("show");
    }
  });

  // Search functionality
  document.getElementById("searchUsers").addEventListener("input", (e) => {
    filterUsers(e.target.value.toLowerCase().trim());
  });

  async function loadUsers() {
    try {
      const response = await fetch("backend/get_users.php");
      const { success, data, message } = await response.json();

      if (!success) throw new Error(message);

      allUsers = data;
      populateUsers(data);
    } catch (error) {
      showError(error.message);
    }
  }

  function populateUsers(users) {
    const onlineList = document.getElementById("online-users-list");
    const offlineList = document.getElementById("offline-users-list");

    onlineList.innerHTML = "";
    offlineList.innerHTML = "";

    users.forEach((user) => {
      const row = `
                <tr>
                    <td>${user.firstname} ${user.lastname}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.phone_number || "-"}</td>
                    <td>${user.role}</td>
                    <td>${user.status === "active" ? "Online" : "Offline"}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${
                          user.id
                        }">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${
                          user.id
                        }">Delete</button>
                    </td>
                </tr>
            `;

      if (user.status === "active") {
        onlineList.insertAdjacentHTML("beforeend", row);
      } else {
        offlineList.insertAdjacentHTML("beforeend", row);
      }
    });
  }

  async function openEditModal(userId) {
    try {
      const response = await fetch("backend/get_users.php");
      const { success, data, message } = await response.json();

      if (!success) throw new Error(message);

      const user = data.find((u) => u.id == userId);
      if (!user) throw new Error("User not found");

      document.getElementById("editFirstName").value = user.firstname;
      document.getElementById("editLastName").value = user.lastname;
      document.getElementById("editUsername").value = user.username;
      document.getElementById("editEmail").value = user.email;
      document.getElementById("editPhoneNumber").value =
        user.phone_number || "";
      document.getElementById("editStatus").value = user.status;

      $("#editModal").modal("show");
    } catch (error) {
      showError(error.message);
    }
  }

  async function addUser() {
    const formData = {
      firstname: document.getElementById("addUserFirstname").value,
      lastname: document.getElementById("addUserLastname").value,
      username: document.getElementById("addUsername").value,
      password: document.getElementById("addPassword").value,
      email: document.getElementById("addUserEmail").value,
      phone: document.getElementById("addUserPhone").value,
      role: document.getElementById("addUserRole").value,
      status: document.getElementById("addUserStatus").value,
    };

    try {
      const response = await fetch("backend/add_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      const { success, message } = await response.json();

      if (!success) throw new Error(message);

      $("#addModal").modal("hide");
      showSuccess("User created successfully");
      loadUsers();
    } catch (error) {
      showError(error.message);
    }
  }

  async function updateUser() {
    const formData = {
      id: currentUserId,
      firstname: document.getElementById("editFirstName").value,
      lastname: document.getElementById("editLastName").value,
      username: document.getElementById("editUsername").value,
      email: document.getElementById("editEmail").value,
      phone: document.getElementById("editPhoneNumber").value,
      status: document.getElementById("editStatus").value,
    };

    try {
      const response = await fetch("backend/update_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      const { success, message } = await response.json();

      if (!success) throw new Error(message);

      $("#editModal").modal("hide");
      showSuccess("User updated successfully");
      loadUsers();
    } catch (error) {
      showError(error.message);
    }
  }

  async function deleteUser() {
    if (!currentUserId) {
      showError("No user selected for deletion");
      return;
    }

    try {
      const response = await fetch("backend/delete_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: currentUserId }),
      });

      const { success, message } = await response.json();

      if (!success) throw new Error(message);

      $("#deleteModal").modal("hide");
      showSuccess("User deleted successfully");
      currentUserId = null;
      loadUsers();
    } catch (error) {
      showError(error.message);
    }
  }

  function filterUsers(searchTerm) {
    if (!searchTerm) {
      populateUsers(allUsers);
      return;
    }

    const filtered = allUsers.filter((user) => {
      return Object.values(user).some((value) => {
        const strValue = String(value || "").toLowerCase();
        return strValue.includes(searchTerm);
      });
    });

    populateUsers(filtered);
  }

  function showError(message) {
    const errorToast = new bootstrap.Toast(
      document.getElementById("errorToast")
    );
    document.getElementById("errorMessage").textContent = message;
    errorToast.show();
  }

  function showSuccess(message) {
    const successToast = new bootstrap.Toast(
      document.getElementById("successToast")
    );
    document.getElementById("successMessage").textContent = message;
    successToast.show();
  }
});
