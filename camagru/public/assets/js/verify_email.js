document.addEventListener("DOMContentLoaded", () => {
    const loadingMessage = document.querySelector(".loading-message");

    if (loadingMessage) {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get("token");

        if (token) {
            window.location.href = `/verify_email_action?token=${token}`;
        }
    }
});
