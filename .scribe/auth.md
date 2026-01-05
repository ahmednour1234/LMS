# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_JWT_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

To authenticate, include the JWT token in the Authorization header as: <code>Authorization: Bearer {your_token}</code>. You can obtain a token by logging in via the <code>POST /api/v1/student/login</code> endpoint.
