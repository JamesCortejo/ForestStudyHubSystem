class SettingsManager {
  constructor() {
    this.ready = this.init();
    this.enablePush = document.getElementById("enablePush");
    this.enableSound = document.getElementById("enableSound");
    this.saveButton = document.getElementById("saveSettings");
    this.profileInput = document.getElementById("profilePicture");
    this.profilePreview = document.getElementById("profilePreview");

    if (
      !this.enablePush ||
      !this.enableSound ||
      !this.saveButton ||
      !this.profileInput
    ) {
      console.error("Settings elements not found in DOM");
      return;
    }

    this.settings = {
      push: true,
      sound: true,
    };
  }

  async init() {
    await this.loadSettings();
    this.setupListeners();
    this.applySettings();
  }

  async loadSettings() {
    try {
      const response = await fetch(
        "/ForrestStudy_Hub/subsites/userpage_subpage/backend/settings.php"
      );
      const settings = await response.json();

      this.settings = {
        push: Boolean(settings.enable_push),
        sound: Boolean(settings.enable_sound),
      };

      this.enablePush.checked = this.settings.push;
      this.enableSound.checked = this.settings.sound;

      // Update profile images
      const profilePic = settings.profile_pic || "default-avatar.jpg";
      this.updateProfileImages(profilePic);

      localStorage.setItem("userSettings", JSON.stringify(this.settings));
    } catch (error) {
      console.error("Settings load error:", error);
      const localSettings = JSON.parse(localStorage.getItem("userSettings"));
      if (localSettings) {
        this.settings = localSettings;
        this.enablePush.checked = this.settings.push;
        this.enableSound.checked = this.settings.sound;
      }
    }
  }

  updateProfileImages(profilePic) {
    const newPath = `/ForrestStudy_Hub/resources/pfpFolder/${profilePic}`;
    this.profilePreview.src = newPath;
    document.querySelectorAll(".profile-image").forEach((img) => {
      img.src = newPath;
    });
  }

  async saveSettings() {
    try {
      const formData = new FormData();
      formData.append("push", this.settings.push ? "1" : "0");
      formData.append("sound", this.settings.sound ? "1" : "0");

      if (this.profileInput.files.length > 0) {
        formData.append("profile_pic", this.profileInput.files[0]);
      }

      const response = await fetch(
        "/ForrestStudy_Hub/subsites/userpage_subpage/backend/settings.php",
        {
          method: "POST",
          body: formData,
        }
      );

      const result = await response.json();
      if (!result.success) throw new Error(result.error || "Save failed");

      if (result.profile_pic) {
        this.updateProfileImages(result.profile_pic);
      }

      localStorage.setItem("userSettings", JSON.stringify(this.settings));
      return true;
    } catch (error) {
      console.error("Settings save error:", error);
      throw error;
    }
  }

  applySettings() {
    if (window.notificationSettings) {
      window.notificationSettings = this.settings;
    }
  }

  setupListeners() {
    this.enablePush.addEventListener("change", (e) => {
      this.settings.push = e.target.checked;
    });

    this.enableSound.addEventListener("change", (e) => {
      this.settings.sound = e.target.checked;
    });

    this.profileInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          this.profilePreview.src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    this.saveButton.addEventListener("click", async () => {
      try {
        await this.saveSettings();
        this.applySettings();
        showToast("Settings saved successfully!", "success");
      } catch (error) {
        showToast(error.message || "Failed to save settings", "error");
      }
    });

    window.addEventListener("storage", (e) => {
      if (e.key === "userSettings") {
        this.settings = JSON.parse(e.newValue);
        this.enablePush.checked = this.settings.push;
        this.enableSound.checked = this.settings.sound;
      }
    });
  }
}

const settingsManager = new SettingsManager();
