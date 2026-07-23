document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("signupForm").addEventListener("submit", function(e) {
        e.preventDefault();

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const email = document.getElementById("email").value.trim();

        const errorBox = document.getElementById("errorMsg");
        const errorText = document.getElementById("errorText");

        // reset errore
        errorBox.classList.remove("show");
        errorText.textContent = "";

        // controlli frontend
        if (password !== confirmPassword) {
            errorText.textContent = "Passwords do not match";
            errorBox.classList.add("show");
            return;
        }

        if (password.length < 8) {
            errorText.textContent = "Password should have at least 8 characters";
            errorBox.classList.add("show");
            return;
        }
        const contieneNumero = /\d/.test(password);
        
        if(!contieneNumero){ 
          errorText.textContent = "Password should have at least 1 number";
          errorBox.classList.add("show");
          return;
        }

        fetch("/signin-exe", {
                method: "POST",
                body: new URLSearchParams({
                    username,
                    password,
                    email
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = "/login?verify=1";
                    return;
                }

                let msg = "Server error";

                if (data.username) msg = "Invalid username or already in use";
                else if (data.email) msg = "Invalid email or already in use";
                else if (data.password) msg = "Invalid password";

                errorText.textContent = msg;
                errorBox.classList.add("show");
            });
    });
});