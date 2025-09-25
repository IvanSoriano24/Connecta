(function() {
    if (localStorage.getItem("sidebar-collapsed") === "true") {
        document.addEventListener("DOMContentLoaded", () => {
            document.getElementById("sidebar").classList.add("collapsed");
        });
    }
})();