(function () {
    document.addEventListener("click", function (evt) {
        var toggle = evt.target.closest("[data-nav-toggle]");
        if (!toggle) {
            return;
        }
        evt.preventDefault();
        var nav = document.querySelector(".main-nav");
        if (!nav) {
            return;
        }
        nav.classList.toggle("is-open");
        var expanded = nav.classList.contains("is-open") ? "true" : "false";
        toggle.setAttribute("aria-expanded", expanded);
    });
})();
