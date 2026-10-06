<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sun Son Solar Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/style.css'); ?>">

</head>
<body>
  <main class="page">
    <section class="left" style="background-image: url('<?= base_url('css/images/panel-background.png') ?>');">
      <div class="left-content">
        <a class="logo" href="#" aria-label="Sun Son Solar home">
          <img src="<?= base_url('css/images/logo.png') ?>" alt="Sun Son Solar">    
          <span>Sun Son Solar</span>
        </a>
        <div class="hero">
          <h1>Get started with your<br>customer profile</h1>
          <p>Sign in or register to access customer service requests, maintenance, or aging in for field technician site operations.</p>
        </div>
        <div class="rating">
          <div aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
          <p>&ldquo;Sun Son Solar: Built on Trust, Powered by the Sun.&rdquo;</p>
          <span>&mdash; Founder of Sun Son Solar, Katherine Singraw</span>
        </div>
      </div>
    </section>

    <section class="right">
      <div class="form">
        <header class="heading">
          <h2 id="title">Create Account</h2>
          <p>Complete your registration to join our global platform.</p>
        </header>

        <form id="registrationForm">
          <div class="field">
            <label for="firstName">First Name <b>*</b></label>
            <input class="formControl" id="firstName" name="firstName" placeholder="e.g. John" required>
          </div>

          <div class="field">
            <label for="middleName">Middle Name</label>
            <input class="formControl" id="middleName" name="middleName" placeholder="e.g. Robert">
          </div>

          <div class="field">
            <label for="lastName">Last Name <b>*</b></label>
            <input class="formControl" id="lastName" name="lastName" placeholder="e.g. Doe" required>
          </div>

          <div class="field">
            <label for="birthdate">Birthdate <b>*</b></label>
            <input class="formControl" id="birthdate" name="birthdate" type="date" required>
          </div>

          <div class="field">
            <label for="gender">Gender <b>*</b></label>
            <select class="formSelect" id="gender" name="gender" required>
              <option value="" selected disabled>Select Gender</option>
              <option>Female</option>
              <option>Male</option>
              <option>Prefer not to say</option>
            </select>
          </div>
          
          <div class="field">
            <label for="email">Email Address <b>*</b></label>
            <input class="formControl" id="email" name="email" type="email" placeholder="john.doe@company.com" required>
          </div>

          <div class="field">
            <label for="phone">Phone Number</label>
            <input class="formControl" id="phone" name="phone" type="tel" placeholder="+1 (555) 000-0000">
          </div>

          <div class="field">
            <label for="address">Street Address <b>*</b></label>
            <input class="formControl" id="address" name="address" placeholder="Plaza Rizal, Justice Ramon Jabson, Pasig, 1600 Metro Manila" required>
          </div>
          <div class="field">
            <label for="username">Username <b>*</b></label>
            <input class="formControl" id="username" name="username" placeholder="john_doe" required>
          </div>

          <div class="field">
            <label for="password">Password <b>*</b></label>

            <div class="password">
              <input class="formControl" id="password" name="password" type="password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, and a number." required>
              <button type="button" id="togglePassword" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
          </div>

          <div class="terms">
            <input id="terms" class="checkInput" type="checkbox" required>
            <label for="terms">I agree to the <a href="">Terms of Service</a> and <a href="">Privacy Policy</a>.</label>
          </div>
          <button class="button" type="submit">Register</button>
        </form>
        
        <p class="signin">Already have an account? <a href="">Sign in instead</a></p>
      </div>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="<?= base_url('js/script.js'); ?>"></script>


</body>
</html>