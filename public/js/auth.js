document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("loginForm");
    if (!form) return; // Only run on pages with login form

    const submitBtn = document.getElementById("submitBtn");
    const btnText = document.getElementById("btnText");

    // Function to show error message on page
    function showError(message) {
        // Remove existing alerts
        const existingAlert = form.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create error alert
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert error';
        errorDiv.innerHTML = `<strong>⚠️ Authentication Failed</strong>${message}`;
        
        // Insert before form
        form.parentNode.insertBefore(errorDiv, form);
        
        // Scroll to error
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Function to show success message
    function showSuccess(message) {
        const existingAlert = form.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const successDiv = document.createElement('div');
        successDiv.className = 'alert success';
        successDiv.innerHTML = `<strong>✓ Success</strong>${message}`;
        form.parentNode.insertBefore(successDiv, form);
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        e.stopPropagation(); // Stop event from bubbling

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value;

        // Basic validation with visual feedback
        if (!username || !password) {
            if (!username) {
                document.getElementById("username").classList.add('shake');
                setTimeout(() => {
                    document.getElementById("username").classList.remove('shake');
                }, 500);
            }
            if (!password) {
                document.getElementById("password").classList.add('shake');
                setTimeout(() => {
                    document.getElementById("password").classList.remove('shake');
                }, 500);
            }
            showError("Please enter both username and password");
            return;
        }

        // Update UI
        btnText.textContent = "Authenticating...";
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.8';

        // Remove any existing error messages
        const existingAlert = form.parentNode.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        try {
            const formData = new FormData(form);
            formData.append('ajax', '1');

            const response = await fetch("api/login.php", {
                method: "POST",
                body: formData,
                credentials: 'same-origin' // Include cookies (session) in request
            });

            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Try to parse JSON
            let data;
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error("Response text:", text);
                throw new Error("Invalid response from server");
            }

            console.log("Login response:", data);

            if (data.success) {
                showSuccess("Login successful! Redirecting...");
                // Use replace instead of href to prevent back button issues
                // Longer delay to ensure session cookie is set and sent
                setTimeout(() => {
                    // Force a page reload to ensure cookies are sent
                    window.location.href = "dashboard.php";
                }, 500);
            } else {
                showError(data.error || "Login failed. Please check your credentials.");
                btnText.textContent = "Login";
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        } catch (error) {
            console.error("Login error:", error);
            showError("Network error. Please check your connection and try again.");
            btnText.textContent = "Login";
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    });

});
