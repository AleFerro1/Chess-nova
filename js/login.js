document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    fetch("./loginExe", {
            method: "POST",
            body: new URLSearchParams({
                username: username,
                password: password
            })
        })
        .then(res => res.json())
        .then(data => {
            const errorEl = document.getElementById("errorMsg");

            switch (data.success) {
                case "success":
                    window.location.href = "./home";
                    break;
                case "not_verified":
                    errorEl.textContent = "Devi verificare la tua email prima di accedere";
                    break;
                case "rate_limited":
                    errorEl.textContent = "Troppi tentativi. Riprova tra qualche minuto.";
                    break;
                default:
                    errorEl.textContent = "Credenziali non valide";
            }
        })
        .catch(() => {
            document.getElementById("errorMsg").textContent = "Errore di rete, riprova.";
        });
});