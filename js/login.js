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
            if (data.success) {
                window.location.href = "./home";
            } else {
                document.getElementById("errorMsg").textContent = "Credenziali non valide";
            }
        });
});