(function() {
    if (localStorage.getItem("sidebar-collapsed") === "true") {
        // en vez de meterlo al <html>, ponlo directo al sidebar
        document.addEventListener("DOMContentLoaded", () => {
            document.getElementById("sidebar").classList.add("collapsed");
        });
    }
})();
