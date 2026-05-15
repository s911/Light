(function () {
    var cookieName = "stage_wishlist_ids";

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

    function updateCount() {
        var count = readCookie().length;
        document.querySelectorAll("[data-wishlist-count]").forEach(function (node) {
            node.textContent = String(count);
        });
    }

    function updateButtonStates() {
        var ids = readCookie();
        document.querySelectorAll("[data-wishlist-toggle][data-product-id]").forEach(function (btn) {
            var id = btn.getAttribute("data-product-id");
            btn.textContent = ids.indexOf(id) >= 0 ? "Remove Wishlist" : "Add Wishlist";
        });
    }

    function toggleProduct(productId) {
        var ids = readCookie();
        var idx = ids.indexOf(productId);
        var added = false;
        if (idx >= 0) {
            ids.splice(idx, 1);
        } else {
            ids.push(productId);
            added = true;
        }
        writeCookie(ids);
        syncToServer(productId);
        updateCount();
        updateButtonStates();
        return added;
    }

    function syncToServer(productId) {
        if (typeof stageWishlistConfig !== "object" || stageWishlistConfig.loggedIn !== "1") {
            return;
        }
        var data = new URLSearchParams();
        data.append("action", "stage_wishlist_toggle");
        data.append("product_id", String(productId));
        data.append("nonce", stageWishlistConfig.nonce || "");
        fetch(stageWishlistConfig.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: data.toString()
        }).catch(function () {
            // Keep cookie behavior even if ajax sync fails.
        });
    }

    document.addEventListener("click", function (evt) {
        var btn = evt.target.closest("[data-wishlist-toggle]");
        if (!btn) {
            return;
        }
        evt.preventDefault();
        var productId = btn.getAttribute("data-product-id");
        if (!productId) {
            return;
        }
        toggleProduct(productId);
    });

    document.addEventListener("DOMContentLoaded", function () {
        updateCount();
        updateButtonStates();
    });
})();
