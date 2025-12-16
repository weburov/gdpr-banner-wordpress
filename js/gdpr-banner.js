document.addEventListener("DOMContentLoaded", function() {
    var banner = document.getElementById("weburov-gdpr-banner");
    var button = document.getElementById("weburov-gdpr-accept");

    if (!localStorage.getItem("weburov_gdpr_accepted")) {
        banner.style.display = "block";
    }

    button.addEventListener("click", function() {
        localStorage.setItem("weburov_gdpr_accepted", "true");
        banner.style.display = "none";
    });
});
