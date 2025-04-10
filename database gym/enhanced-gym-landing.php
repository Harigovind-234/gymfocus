<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Premium Fitness Club</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      rel="stylesheet"
    />
    <style>
      :root {
        --primary: #ed563b;
        --primary-hover: #f97c5b;
        --dark: #232d39;
        --text-light: #ffffff;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Segoe UI", system-ui, sans-serif;
      }

      body {
        margin: 0;
        color: var(--text-light);
        background-color: var(--dark);
        line-height: 1.6;
      }

      .main-banner {
        position: relative;
        height: 100vh;
        overflow: hidden;
      }

      #bg-video {
        position: absolute;
        min-width: 100%;
        min-height: 100vh;
        max-width: 100%;
        max-height: 100vh;
        object-fit: cover;
        z-index: -1;
      }

      .video-overlay {
        position: absolute;
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.85));
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .logo-container {
        display: flex;
        justify-content: center; /* Centers horizontally */
        align-items: center; /* Centers vertically */
        margin-bottom: -20px;
      }

      .logo-container img {
        width: 30%;
        filter: drop-shadow(0 0 10px rgba(0, 0, 0, 0.5));
      }

      .caption {
        text-align: center;
        max-width: 800px;
        margin: auto;
        padding: 0 2rem;
      }

      .caption h6 {
        font-size: 1.25rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 3px;
        margin-bottom: 1rem;
        animation: fadeInDown 1s ease-out;
      }

      .caption h2 {
        font-size: 4.5rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 2px;
        line-height: 1.2;
        margin-bottom: 2rem;
        animation: fadeInUp 1s ease-out;
      }

      .caption h2 em {
        font-style: normal;
        color: var(--primary);
        font-weight: 900;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      }

      .main-button {
        display: flex;
        gap: 1rem;
        justify-content: center;
        animation: fadeIn 1.5s ease-out;
      }

      .main-button a {
        display: inline-block;
        padding: 1rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-light);
        background-color: var(--primary);
        text-transform: uppercase;
        text-decoration: none;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(237, 86, 59, 0.3);
      }

      .main-button a:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 86, 59, 0.4);
      }

      .form-container {
        margin: 2rem auto;
        padding: 2.5rem;
        background: rgba(35, 45, 57, 0.95);
        border-radius: 15px;
        max-width: 450px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
      }

      .form-container h3 {
        margin-bottom: 1.5rem;
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-light);
      }

      .form-container input {
        width: 100%;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-light);
        font-size: 1rem;
        transition: all 0.3s ease;
      }

      .form-container input:focus {
        outline: none;
        border-color: var(--primary);
        background: rgba(255, 255, 255, 0.1);
      }

      .form-container input::placeholder {
        color: rgba(255, 255, 255, 0.6);
      }

      .form-container button {
        width: 100%;
        padding: 1rem;
        font-size: 1rem;
        font-weight: 600;
        background-color: var(--primary);
        color: var(--text-light);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 1rem;
      }

      .form-container button:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
      }

      .toggle-link {
        display: inline-block;
        margin-top: 1rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
      }

      .toggle-link:hover {
        color: var(--primary);
      }

      .hidden {
        display: none;
      }

      @media (max-width: 768px) {
        .caption h2 {
          font-size: 3rem;
        }

        .caption h6 {
          font-size: 1rem;
        }

        .form-container {
          margin: 1rem;
          padding: 1.5rem;
        }
      }
    </style>
  </head>
  <body>
    <div class="main-banner" id="top">
      <video autoplay muted loop id="bg-video">
        <source src="assets/images/gym-video.mp4" type="video/mp4" />
      </video>

      <div class="video-overlay">
        <div class="caption">
          <h6 class="animate__animated animate__fadeInDown">
            <div class="logo-container">
              <img src="focusgymlogo.png" alt="Gym Logo" />
            </div>
            work harder, get stronger
          </h6>
          <h2 class="animate__animated animate__fadeInUp">
            easy with our <em>gym</em>
          </h2>
          <div class="main-button animate__animated animate__fadeIn">
              <div class="main-button animate__animated animate__fadeIn">
      <a href="http://localhost/miniproject2/database%20gym/login2.php">Login</a>
      <a href="http://localhost/miniproject2/database%20gym/register.php">Register</a>
             </div>

          </div>
        </div>
      </div>
    </div>
    <script>
      const loginSection = document.getElementById("Login");
      const registerSection = document.getElementById("Register");
      const toggleLinks = document.querySelectorAll(".toggle-link");

      toggleLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
          e.preventDefault();
          loginSection.classList.toggle("hidden");
          registerSection.classList.toggle("hidden");
        });
      });
    </script>
  </body>
</html>
