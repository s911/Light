(function () {
    var cookieName = "stage_compare_ids";
    var maxItems = 4;

    function readCookie() {
        var all = document.cookie ? document.cookie.split(";") : [];
        for (var i = 0; i < all.length; i++) {
            var part = all[i].trim();
            if (part.indexOf(cookieName + "=") === 0) {
                var raw = decodeURIComponent(part.substring(cookieName.length + 1));
                return raw ? raw.split(",").map(function (x) { return x.trim(); }).filter(Boolean) : [];
            }
        }
        return [];
    }

    function writeCookie(ids) {
        document.cookie = cookieName + "=" + encodeURIComponent(ids.join(",")) + "; path=/; max-age=2592000";
    }

    function updateCompareCount() {
        var count = readCookie().length;
        var nodes = document.querySelectorAll("[data-compare-count]");
        nodes.forEach(function (node) {
            node.textContent = String(count);
        });
    }

    function updateButtonStates() {
        var ids = readCookie();
        var buttons = document.querySelectorAll("[data-compare-toggle][data-product-id]");
        buttons.forEach(function (btn) {
            var id = btn.getAttribute("data-product-id");
            if (ids.indexOf(id) >= 0) {
                btn.textContent = "Remove Compare";
            } else {
                btn.textContent = "Add Compare";
            }
        });
    }

    function toggleProduct(productId) {
        var ids = readCookie();
        var idx = ids.indexOf(productId);
        var added = false;
        if (idx >= 0) {
            ids.splice(idx, 1);
        } else {
            if (ids.length >= maxItems) {
                alert("You can compare up to " + maxItems + " products.");
                return;
            }
            ids.push(productId);
            added = true;
        }
        writeCookie(ids);
        updateCompareCount();
        updateButtonStates();
        return added;
    }

    document.addEventListener("click", function (evt) {
        var btn = evt.target.closest("[data-compare-toggle]");
        if (!btn) {
            return;
        }
        evt.preventDefault();
        var productId = btn.getAttribute("data-product-id");
        if (!productId) {
            return;
        }
        var added = toggleProduct(productId);
        if (added === true) {
            btn.textContent = "Remove Compare";
        } else if (added === false) {
            btn.textContent = "Add Compare";
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        updateCompareCount();
        updateButtonStates();
    });
})();
