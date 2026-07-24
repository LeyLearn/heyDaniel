<!-- Credentials -->
<div class="credential-container">

    <!-- Logo -->
    <div class="credential-logo">
        <img src="../../Assets/Logo/logo.svg" alt="heyDaniel Logo">
    </div>
    <!-- Login -->
    <div class="credential-login" style="display: block;">

        <h2>Login to your account</h2>
        <h3>Welcome back! Sign in to continue.</h3>

        <div class="credential-error" style="display: none;">
        </div>

        <div class="input-container">

            <div class="input-wrapper">
                <img src="../../Assets/Icons/user.svg" alt="heyDaniel user">
                <input
                    type="email"
                    id="login-email"
                    class="login-email"
                    name="email"
                    placeholder="Email address"
                    autocomplete="email"
                    required>
            </div>

            <div class="input-wrapper">
                <img src="../../Assets/Icons/lock.svg" alt="heyDaniel password">
                <input
                    type="password"
                    id="login-password"
                    class="login-password"
                    name="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required>
            </div>

        </div>

        <button class="primary-button Primary_Btn login-bnt" type="button">
            Login
        </button>

        <p class="forgot-password">
            <a href="#" id="show-forgot" class="show-forgot">Forgot your password?</a>
        </p>

    </div>

    <!-- Forgot Password -->
    <div class="credential-forgot hidden" style="display: none;">

        <h2>Reset your password</h2>
        <h3 class="forgot-step-request">Enter your email and we'll send you a reset code.</h3>
        <h3 class="forgot-step-reset hidden" style="display: none;">Enter the code we sent to your email and choose a new password.</h3>

        <div class="credential-error" style="display: none;">
        </div>

        <div class="forgot-step-request">
            <div class="input-container">
                <div class="input-wrapper">
                    <img src="../../Assets/Icons/email.svg" alt="heyDaniel email">
                    <input
                        type="email"
                        id="forgot-email"
                        class="forgot-email"
                        name="email"
                        placeholder="Email address"
                        autocomplete="email"
                        required>
                </div>
            </div>

            <button class="primary-button Primary_Btn forgot-send-code-bnt" type="button">
                Send reset code
            </button>
        </div>

        <div class="forgot-step-reset hidden" style="display: none;">
            <div class="input-container">
                <div class="input-wrapper">
                    <img src="../../Assets/Icons/lock.svg" alt="heyDaniel code">
                    <input
                        type="text"
                        id="forgot-code"
                        class="forgot-code"
                        name="code"
                        placeholder="6-digit code"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code">
                </div>

                <div class="input-wrapper">
                    <img src="../../Assets/Icons/lock.svg" alt="heyDaniel password">
                    <input
                        type="password"
                        id="forgot-new-password"
                        class="forgot-new-password"
                        name="new_password"
                        placeholder="New password"
                        autocomplete="new-password">
                </div>

                <div class="input-wrapper">
                    <img src="../../Assets/Icons/lock.svg" alt="heyDaniel confirm password">
                    <input
                        type="password"
                        id="forgot-confirm-password"
                        class="forgot-confirm-password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        autocomplete="new-password">
                </div>
            </div>

            <button class="primary-button Primary_Btn forgot-reset-password-bnt" type="button">
                Reset password
            </button>
        </div>

        <p class="switch-auth">
            <a href="#" id="show-login-from-forgot" class="show-login-from-forgot">Back to login</a>
        </p>

    </div>

    <!-- Register -->
    <div class="credential-register hidden" style="display: none;">

        <h2>Create an account</h2>
        <h3>Join us today—it's quick and easy.</h3>

        <div class="credential-error" style="display: none;">
        </div>
        <div class="input-container">

            <div class="input-wrapper">
                <img src="../../Assets/Icons/user.svg" alt="heyDaniel user">
                <input
                    type="text"
                    id="register-name"
                    class="register-name"
                    name="name"
                    placeholder="Full name"
                    autocomplete="name"
                    required>
            </div>

            <div class="input-wrapper">
                <img src="../../Assets/Icons/email.svg" alt="heyDaniel email">
                <input
                    type="email"
                    id="register-email"
                    class="register-email"
                    name="email"
                    placeholder="Email address"
                    autocomplete="email"
                    required>
            </div>

            <div class="input-wrapper">
                <img src="../../Assets/Icons/lock.svg" alt="heyDaniel password">
                <input
                    type="password"
                    id="register-password"
                    class="register-password"
                    name="password"
                    placeholder="Create a password"
                    autocomplete="new-password"
                    required>
            </div>

        </div>

        <button class="primary-button Primary_Btn register-bnt" type="button">
            Register
        </button>

    </div>

    <!-- Divider -->
    <div class="credential-divider">
        <span>or</span>
    </div>

    <!-- Google -->
    <div class="google-button-wrapper">
        <button class="google-button" type="button">
            <img src="../../Assets/Icons/google.svg" alt="heyDaniel google">
            Continue with Google
        </button>
        <div id="google-signin-overlay"></div>
    </div>

    <!-- Switch -->
    <p class="switch-auth">
        New to heyDaniel ? 
        <a href="#" id="show-register" class="show-register">Create an account</a>
    </p>

    <!-- Footnote -->
    <hr class="credential-footnote-divider">
    <p class="credential-footnote">
        By continuing, you agree with heyDaniel's
        <a href="#"><strong>Term of Use</strong></a>
        and
        <a href="#"><strong>Privacy Policy</strong></a>.
    </p>

</div>