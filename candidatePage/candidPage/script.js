document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById("themeModeToggle");
    const sunIcon = document.querySelector(".theme-icon-light");
    const moonIcon = document.querySelector(".theme-icon-dark");

    // Load saved mode
    let savedMode = localStorage.getItem("theme") || "light";
    applyTheme(savedMode);

    toggleBtn.addEventListener("click", () => {
        const currentMode = document.body.classList.contains("dark-theme") ? "dark" : "light";
        const newMode = currentMode === "light" ? "dark" : "light";

        applyTheme(newMode);
        localStorage.setItem("theme", newMode);
    });

    function applyTheme(mode) {
        if (mode === "dark") {
            document.body.classList.add("dark-theme");
            sunIcon.classList.add("d-none");
            moonIcon.classList.remove("d-none");
        } else {
            document.body.classList.remove("dark-theme");
            sunIcon.classList.remove("d-none");
            moonIcon.classList.add("d-none");
        }
    }
});

document.addEventListener("DOMContentLoaded", function() {
    // Get tab from URL
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');

    if (tab) {
        // Deactivate all tabs
        const tabButtons = document.querySelectorAll('#sidebar-wrapper .list-group-item');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabPanes.forEach(pane => pane.classList.remove('show', 'active'));

        // Activate selected tab
        const tabBtn = document.querySelector(`#${tab}TabBtn`);
        const tabPane = document.querySelector(`#${tab}`);

        if (tabBtn && tabPane) {
            tabBtn.classList.add('active');
            tabPane.classList.add('show', 'active');
        }
    }
});

document.addEventListener("DOMContentLoaded", function() {
    const wrapper = document.getElementById("wrapper");
    const toggleBtn = document.getElementById("menu-toggle");

    toggleBtn.addEventListener("click", function() {
        wrapper.classList.toggle("sidebar-collapsed");
    });
});