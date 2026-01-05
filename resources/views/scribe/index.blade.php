<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.6.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.6.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-student-auth" class="tocify-header">
                <li class="tocify-item level-1" data-unique="student-auth">
                    <a href="#student-auth">Student Auth</a>
                </li>
                                    <ul id="tocify-subheader-student-auth" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-register">
                                <a href="#student-auth-POSTapi-v1-student-register">Register Student</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-verify-otp">
                                <a href="#student-auth-POSTapi-v1-student-verify-otp">Verify OTP</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-login">
                                <a href="#student-auth-POSTapi-v1-student-login">Login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-forgot-password">
                                <a href="#student-auth-POSTapi-v1-student-forgot-password">Forgot Password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-reset-password">
                                <a href="#student-auth-POSTapi-v1-student-reset-password">Reset Password</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-GETapi-v1-student-me">
                                <a href="#student-auth-GETapi-v1-student-me">Get Authenticated Student</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-logout">
                                <a href="#student-auth-POSTapi-v1-student-logout">Logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="student-auth-POSTapi-v1-student-refresh">
                                <a href="#student-auth-POSTapi-v1-student-refresh">Refresh Token</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-student-profile" class="tocify-header">
                <li class="tocify-item level-1" data-unique="student-profile">
                    <a href="#student-profile">Student Profile</a>
                </li>
                                    <ul id="tocify-subheader-student-profile" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="student-profile-PUTapi-v1-student-profile">
                                <a href="#student-profile-PUTapi-v1-student-profile">Update Profile</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-branches" class="tocify-header">
                <li class="tocify-item level-1" data-unique="branches">
                    <a href="#branches">Branches</a>
                </li>
                                    <ul id="tocify-subheader-branches" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="branches-GETapi-v1-branches">
                                <a href="#branches-GETapi-v1-branches">List Branches</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-example">
                                <a href="#endpoints-GETapi-v1-example">Example: Success response with data</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-example">
                                <a href="#endpoints-POSTapi-v1-example">Example: Created response (201)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-example-paginated">
                                <a href="#endpoints-GETapi-v1-example-paginated">Example: Paginated response</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-example-errors">
                                <a href="#endpoints-GETapi-v1-example-errors">Example: Error handling demonstrations</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: January 5, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Student Authentication and Management API. All authentication uses the students table with JWT tokens.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer Bearer {YOUR_JWT_TOKEN}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>To authenticate, include the JWT token in the Authorization header as: <code>Authorization: Bearer {your_token}</code>. You can obtain a token by logging in via the <code>POST /api/v1/student/login</code> endpoint.</p>

        <h1 id="student-auth">Student Auth</h1>

    <p>Authentication endpoints for students. All authentication uses the students table, not the default users table.</p>

                                <h2 id="student-auth-POSTapi-v1-student-register">Register Student</h2>

<p>
</p>

<p>Register a new student account. After registration, an OTP code will be sent to the provided email address.
The student status will be set to 'inactive' until email verification is completed.</p>
<p>In development environment, the OTP code is always <code>111111</code>.</p>

<span id="example-requests-POSTapi-v1-student-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"John Doe\",
    \"email\": \"john@example.com\",
    \"password\": \"password123\",
    \"phone\": \"+1234567890\",
    \"sex\": \"male\",
    \"branch_id\": 1,
    \"student_code\": \"STU001\",
    \"national_id\": \"123456789\",
    \"password_confirmation\": \"password123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "phone": "+1234567890",
    "sex": "male",
    "branch_id": 1,
    "student_code": "STU001",
    "national_id": "123456789",
    "password_confirmation": "password123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-register">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Registration successful. Please check your email for the OTP code.&quot;,
    &quot;data&quot;: null
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;The provided data is invalid.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_ERROR&quot;,
        &quot;details&quot;: {
            &quot;email&quot;: [
                &quot;The email has already been taken.&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-register" data-method="POST"
      data-path="api/v1/student/register"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-register"
                    onclick="tryItOut('POSTapi-v1-student-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-register"
                    onclick="cancelTryOut('POSTapi-v1-student-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-student-register"
               value="John Doe"
               data-component="body">
    <br>
<p>The student's full name. Example: <code>John Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-student-register"
               value="john@example.com"
               data-component="body">
    <br>
<p>The student's email address (must be unique). Example: <code>john@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-student-register"
               value="password123"
               data-component="body">
    <br>
<p>The password (minimum 8 characters). Example: <code>password123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-student-register"
               value="+1234567890"
               data-component="body">
    <br>
<p>optional The student's phone number. Example: <code>+1234567890</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sex</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sex"                data-endpoint="POSTapi-v1-student-register"
               value="male"
               data-component="body">
    <br>
<p>optional The student's gender. Must be one of: male, female. Example: <code>male</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>branch_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="branch_id"                data-endpoint="POSTapi-v1-student-register"
               value="1"
               data-component="body">
    <br>
<p>optional The ID of the branch. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>student_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="student_code"                data-endpoint="POSTapi-v1-student-register"
               value="STU001"
               data-component="body">
    <br>
<p>optional Unique student code. Example: <code>STU001</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>national_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="national_id"                data-endpoint="POSTapi-v1-student-register"
               value="123456789"
               data-component="body">
    <br>
<p>optional National ID number. Example: <code>123456789</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password_confirmation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password_confirmation"                data-endpoint="POSTapi-v1-student-register"
               value="password123"
               data-component="body">
    <br>
<p>Password confirmation. Example: <code>password123</code></p>
        </div>
        </form>

                    <h2 id="student-auth-POSTapi-v1-student-verify-otp">Verify OTP</h2>

<p>
</p>

<p>Verify the OTP code sent to the student's email. Upon successful verification:</p>
<ul>
<li>The student's email_verified_at will be set</li>
<li>The student's status will be changed to 'active'</li>
<li>A JWT token will be issued (auto-login)</li>
</ul>

<span id="example-requests-POSTapi-v1-student-verify-otp">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/verify-otp" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"john@example.com\",
    \"code\": \"111111\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/verify-otp"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john@example.com",
    "code": "111111"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-verify-otp">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Email verified successfully. You are now logged in.&quot;,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;student&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;John Doe&quot;,
            &quot;email&quot;: &quot;john@example.com&quot;,
            &quot;phone&quot;: &quot;+1234567890&quot;,
            &quot;sex&quot;: &quot;male&quot;,
            &quot;branch_id&quot;: 1,
            &quot;image&quot;: null,
            &quot;status&quot;: &quot;active&quot;,
            &quot;email_verified_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;,
            &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Invalid or expired OTP code.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_ERROR&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-verify-otp" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-verify-otp"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-verify-otp"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-verify-otp" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-verify-otp">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-verify-otp" data-method="POST"
      data-path="api/v1/student/verify-otp"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-verify-otp', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-verify-otp"
                    onclick="tryItOut('POSTapi-v1-student-verify-otp');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-verify-otp"
                    onclick="cancelTryOut('POSTapi-v1-student-verify-otp');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-verify-otp"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/verify-otp</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-verify-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-verify-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-student-verify-otp"
               value="john@example.com"
               data-component="body">
    <br>
<p>The student's email address. Example: <code>john@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-v1-student-verify-otp"
               value="111111"
               data-component="body">
    <br>
<p>The 6-digit OTP code. Example: <code>111111</code></p>
        </div>
        </form>

                    <h2 id="student-auth-POSTapi-v1-student-login">Login</h2>

<p>
</p>

<p>Authenticate a student and return a JWT token. The student must have status 'active' (email verified).</p>

<span id="example-requests-POSTapi-v1-student-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"john@example.com\",
    \"password\": \"password123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john@example.com",
    "password": "password123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-login">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Login successful.&quot;,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;student&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;John Doe&quot;,
            &quot;email&quot;: &quot;john@example.com&quot;,
            &quot;phone&quot;: &quot;+1234567890&quot;,
            &quot;sex&quot;: &quot;male&quot;,
            &quot;branch_id&quot;: 1,
            &quot;image&quot;: null,
            &quot;status&quot;: &quot;active&quot;,
            &quot;email_verified_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;,
            &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Invalid email or password.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Email address has not been verified. Please verify your email to continue.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;EMAIL_NOT_VERIFIED&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-login" data-method="POST"
      data-path="api/v1/student/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-login"
                    onclick="tryItOut('POSTapi-v1-student-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-login"
                    onclick="cancelTryOut('POSTapi-v1-student-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-student-login"
               value="john@example.com"
               data-component="body">
    <br>
<p>The student's email address. Example: <code>john@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-student-login"
               value="password123"
               data-component="body">
    <br>
<p>The student's password. Example: <code>password123</code></p>
        </div>
        </form>

                    <h2 id="student-auth-POSTapi-v1-student-forgot-password">Forgot Password</h2>

<p>
</p>

<p>Request a password reset OTP code. An OTP will be sent to the provided email address if it exists in the system.
For security reasons, the response is the same whether the email exists or not.</p>

<span id="example-requests-POSTapi-v1-student-forgot-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/forgot-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"john@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/forgot-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-forgot-password">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;If the email exists, an OTP code has been sent to your email address.&quot;,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-forgot-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-forgot-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-forgot-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-forgot-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-forgot-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-forgot-password" data-method="POST"
      data-path="api/v1/student/forgot-password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-forgot-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-forgot-password"
                    onclick="tryItOut('POSTapi-v1-student-forgot-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-forgot-password"
                    onclick="cancelTryOut('POSTapi-v1-student-forgot-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-forgot-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/forgot-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-student-forgot-password"
               value="john@example.com"
               data-component="body">
    <br>
<p>The student's email address. Example: <code>john@example.com</code></p>
        </div>
        </form>

                    <h2 id="student-auth-POSTapi-v1-student-reset-password">Reset Password</h2>

<p>
</p>

<p>Reset the student's password using the OTP code received via email.</p>

<span id="example-requests-POSTapi-v1-student-reset-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/reset-password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"john@example.com\",
    \"code\": \"111111\",
    \"password\": \"newpassword123\",
    \"password_confirmation\": \"newpassword123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/reset-password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john@example.com",
    "code": "111111",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-reset-password">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Password reset successfully. You can now login with your new password.&quot;,
    &quot;data&quot;: null
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Invalid or expired OTP code.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_ERROR&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-reset-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-reset-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-reset-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-reset-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-reset-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-reset-password" data-method="POST"
      data-path="api/v1/student/reset-password"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-reset-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-reset-password"
                    onclick="tryItOut('POSTapi-v1-student-reset-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-reset-password"
                    onclick="cancelTryOut('POSTapi-v1-student-reset-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-reset-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/reset-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-student-reset-password"
               value="john@example.com"
               data-component="body">
    <br>
<p>The student's email address. Example: <code>john@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-v1-student-reset-password"
               value="111111"
               data-component="body">
    <br>
<p>The 6-digit OTP code. Example: <code>111111</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-student-reset-password"
               value="newpassword123"
               data-component="body">
    <br>
<p>The new password (minimum 8 characters). Example: <code>newpassword123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password_confirmation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password_confirmation"                data-endpoint="POSTapi-v1-student-reset-password"
               value="newpassword123"
               data-component="body">
    <br>
<p>Password confirmation. Example: <code>newpassword123</code></p>
        </div>
        </form>

                    <h2 id="student-auth-GETapi-v1-student-me">Get Authenticated Student</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get the currently authenticated student's profile information.</p>

<span id="example-requests-GETapi-v1-student-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/student/me" \
    --header "Authorization: Bearer Bearer {YOUR_JWT_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/me"
);

const headers = {
    "Authorization": "Bearer Bearer {YOUR_JWT_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-student-me">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Student profile retrieved successfully.&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;John Doe&quot;,
        &quot;email&quot;: &quot;john@example.com&quot;,
        &quot;phone&quot;: &quot;+1234567890&quot;,
        &quot;sex&quot;: &quot;male&quot;,
        &quot;branch_id&quot;: 1,
        &quot;image&quot;: &quot;http://example.com/storage/students/image.jpg&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;email_verified_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;,
        &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Authentication required.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-student-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-student-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-student-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-student-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-student-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-student-me" data-method="GET"
      data-path="api/v1/student/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-student-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-student-me"
                    onclick="tryItOut('GETapi-v1-student-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-student-me"
                    onclick="cancelTryOut('GETapi-v1-student-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-student-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/student/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-student-me"
               value="Bearer Bearer {YOUR_JWT_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {YOUR_JWT_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-student-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-student-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="student-auth-POSTapi-v1-student-logout">Logout</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Invalidate the current JWT token, effectively logging out the student.</p>

<span id="example-requests-POSTapi-v1-student-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/logout" \
    --header "Authorization: Bearer Bearer {YOUR_JWT_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/logout"
);

const headers = {
    "Authorization": "Bearer Bearer {YOUR_JWT_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-logout">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Logged out successfully.&quot;,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-logout" data-method="POST"
      data-path="api/v1/student/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-logout"
                    onclick="tryItOut('POSTapi-v1-student-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-logout"
                    onclick="cancelTryOut('POSTapi-v1-student-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-student-logout"
               value="Bearer Bearer {YOUR_JWT_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {YOUR_JWT_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="student-auth-POSTapi-v1-student-refresh">Refresh Token</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a new JWT token using the current valid token. This extends the session without requiring re-authentication.</p>

<span id="example-requests-POSTapi-v1-student-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/student/refresh" \
    --header "Authorization: Bearer Bearer {YOUR_JWT_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/refresh"
);

const headers = {
    "Authorization": "Bearer Bearer {YOUR_JWT_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-student-refresh">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Token refreshed successfully.&quot;,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-student-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-student-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-student-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-student-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-student-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-student-refresh" data-method="POST"
      data-path="api/v1/student/refresh"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-student-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-student-refresh"
                    onclick="tryItOut('POSTapi-v1-student-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-student-refresh"
                    onclick="cancelTryOut('POSTapi-v1-student-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-student-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/student/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-student-refresh"
               value="Bearer Bearer {YOUR_JWT_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {YOUR_JWT_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-student-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-student-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="student-profile">Student Profile</h1>

    <p>Profile management endpoints for authenticated students.</p>

                                <h2 id="student-profile-PUTapi-v1-student-profile">Update Profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update the authenticated student's profile information. Supports updating name, phone, sex, and profile image.</p>

<span id="example-requests-PUTapi-v1-student-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost/api/v1/student/profile" \
    --header "Authorization: Bearer Bearer {YOUR_JWT_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "name=John Doe"\
    --form "phone=+1234567890"\
    --form "sex=male"\
    --form "image=@C:\Users\ahmednour\AppData\Local\Microsoft\WinGet\Packages\Astronomer.Astro_Microsoft.Winget.Source_8wekyb3d8bbwe\phpD416.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/student/profile"
);

const headers = {
    "Authorization": "Bearer Bearer {YOUR_JWT_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('name', 'John Doe');
body.append('phone', '+1234567890');
body.append('sex', 'male');
body.append('image', document.querySelector('input[name="image"]').files[0]);

fetch(url, {
    method: "PUT",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-student-profile">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Profile updated successfully.&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;John Doe&quot;,
        &quot;email&quot;: &quot;john@example.com&quot;,
        &quot;phone&quot;: &quot;+1234567890&quot;,
        &quot;sex&quot;: &quot;male&quot;,
        &quot;branch_id&quot;: 1,
        &quot;image&quot;: &quot;http://example.com/storage/students/image.jpg&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;email_verified_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;,
        &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Authentication required.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;UNAUTHORIZED&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-student-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-student-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-student-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-student-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-student-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-student-profile" data-method="PUT"
      data-path="api/v1/student/profile"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-student-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-student-profile"
                    onclick="tryItOut('PUTapi-v1-student-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-student-profile"
                    onclick="cancelTryOut('PUTapi-v1-student-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-student-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/student/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1-student-profile"
               value="Bearer Bearer {YOUR_JWT_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {YOUR_JWT_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-student-profile"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-student-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-student-profile"
               value="John Doe"
               data-component="body">
    <br>
<p>optional The student's full name. Example: <code>John Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-v1-student-profile"
               value="+1234567890"
               data-component="body">
    <br>
<p>optional The student's phone number. Example: <code>+1234567890</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sex</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sex"                data-endpoint="PUTapi-v1-student-profile"
               value="male"
               data-component="body">
    <br>
<p>optional The student's gender. Must be one of: male, female. Example: <code>male</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="image"                data-endpoint="PUTapi-v1-student-profile"
               value=""
               data-component="body">
    <br>
<p>optional Profile image (max 5MB). Must be a valid image file. Example: <code>C:\Users\ahmednour\AppData\Local\Microsoft\WinGet\Packages\Astronomer.Astro_Microsoft.Winget.Source_8wekyb3d8bbwe\phpD416.tmp</code></p>
        </div>
        </form>

                <h1 id="branches">Branches</h1>

    <p>Read-only API for retrieving branch information. No authentication required.</p>

                                <h2 id="branches-GETapi-v1-branches">List Branches</h2>

<p>
</p>

<p>Get a paginated list of branches with optional filtering by name search and active status.</p>

<span id="example-requests-GETapi-v1-branches">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/branches?q=Main&amp;active=1&amp;per_page=15&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/branches"
);

const params = {
    "q": "Main",
    "active": "1",
    "per_page": "15",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-branches">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Branches retrieved successfully.&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Main Branch&quot;,
            &quot;is_active&quot;: true,
            &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;North Branch&quot;,
            &quot;is_active&quot;: true,
            &quot;created_at&quot;: &quot;2026-01-15T12:00:00+00:00&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 2,
            &quot;last_page&quot;: 1,
            &quot;from&quot;: 1,
            &quot;to&quot;: 2
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-branches" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-branches"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-branches"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-branches" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-branches">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-branches" data-method="GET"
      data-path="api/v1/branches"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-branches', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-branches"
                    onclick="tryItOut('GETapi-v1-branches');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-branches"
                    onclick="cancelTryOut('GETapi-v1-branches');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-branches"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/branches</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-branches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-branches"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-branches"
               value="Main"
               data-component="query">
    <br>
<p>optional Search branches by name. Example: <code>Main</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>active</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="active"                data-endpoint="GETapi-v1-branches"
               value="1"
               data-component="query">
    <br>
<p>optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-branches"
               value="15"
               data-component="query">
    <br>
<p>optional Number of items per page. Default: 15. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-branches"
               value="1"
               data-component="query">
    <br>
<p>optional Page number. Example: <code>1</code></p>
            </div>
                </form>

                <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-v1-example">Example: Success response with data</h2>

<p>
</p>

<p>GET /api/v1/example</p>

<span id="example-requests-GETapi-v1-example">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/example" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/example"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-example">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Users retrieved successfully&quot;,
    &quot;data&quot;: {
        &quot;users&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;John Doe&quot;,
                &quot;email&quot;: &quot;john@example.com&quot;
            },
            {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Jane Smith&quot;,
                &quot;email&quot;: &quot;jane@example.com&quot;
            }
        ],
        &quot;timestamp&quot;: &quot;2026-01-05T22:57:10+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-example" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-example"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-example"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-example" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-example">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-example" data-method="GET"
      data-path="api/v1/example"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-example', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-example"
                    onclick="tryItOut('GETapi-v1-example');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-example"
                    onclick="cancelTryOut('GETapi-v1-example');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-example"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/example</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-example"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-example"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-example">Example: Created response (201)</h2>

<p>
</p>

<p>POST /api/v1/example</p>

<span id="example-requests-POSTapi-v1-example">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/example" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"email\": \"kunde.eloisa@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/example"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "email": "kunde.eloisa@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-example">
</span>
<span id="execution-results-POSTapi-v1-example" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-example"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-example"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-example" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-example">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-example" data-method="POST"
      data-path="api/v1/example"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-example', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-example"
                    onclick="tryItOut('POSTapi-v1-example');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-example"
                    onclick="cancelTryOut('POSTapi-v1-example');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-example"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/example</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-example"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-example"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-example"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-example"
               value="kunde.eloisa@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>kunde.eloisa@example.com</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-example-paginated">Example: Paginated response</h2>

<p>
</p>

<p>GET /api/v1/example/paginated</p>

<span id="example-requests-GETapi-v1-example-paginated">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/example/paginated" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/example/paginated"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-example-paginated">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Users retrieved successfully&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Test User&quot;,
            &quot;email&quot;: &quot;test@example.com&quot;,
            &quot;email_verified_at&quot;: &quot;2026-01-04T01:48:00.000000Z&quot;,
            &quot;created_at&quot;: &quot;2026-01-04T01:48:00.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2026-01-04T01:48:00.000000Z&quot;,
            &quot;branch_id&quot;: null,
            &quot;created_by&quot;: null,
            &quot;updated_by&quot;: null,
            &quot;primary_role_id&quot;: null
        }
    ],
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 1,
            &quot;last_page&quot;: 1,
            &quot;from&quot;: 1,
            &quot;to&quot;: 1
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-example-paginated" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-example-paginated"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-example-paginated"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-example-paginated" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-example-paginated">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-example-paginated" data-method="GET"
      data-path="api/v1/example/paginated"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-example-paginated', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-example-paginated"
                    onclick="tryItOut('GETapi-v1-example-paginated');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-example-paginated"
                    onclick="cancelTryOut('GETapi-v1-example-paginated');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-example-paginated"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/example/paginated</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-example-paginated"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-example-paginated"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-example-errors">Example: Error handling demonstrations</h2>

<p>
</p>

<p>GET /api/v1/example/errors?type={validation|not_found|business|internal}</p>

<span id="example-requests-GETapi-v1-example-errors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/example/errors" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/example/errors"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-example-errors">
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;The provided data is invalid.&quot;,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;VALIDATION_ERROR&quot;,
        &quot;details&quot;: {
            &quot;email&quot;: [
                &quot;The email field is required.&quot;
            ],
            &quot;name&quot;: [
                &quot;The name field is required.&quot;
            ]
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-example-errors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-example-errors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-example-errors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-example-errors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-example-errors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-example-errors" data-method="GET"
      data-path="api/v1/example/errors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-example-errors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-example-errors"
                    onclick="tryItOut('GETapi-v1-example-errors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-example-errors"
                    onclick="cancelTryOut('GETapi-v1-example-errors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-example-errors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/example/errors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-example-errors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-example-errors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
