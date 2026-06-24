AWS Cognito - TODO (prepared for later setup)
===========================================

This file documents the steps to set up AWS Cognito so the backend can consume identity information
from ID tokens (prepared for later work). You asked to keep this as a reminder and implement the
other pieces now; run these steps when you create the Cognito user pool / app client.

1) Decide claim & attribute names
   - ID token: use standard claims for names: `given_name`, `family_name`
   - Custom attribute for class: `custom:klasse` (Cognito requires `custom:` prefix)
   - Backend will expect `custom:klasse` in the ID token (or fallback to other sources)

2) Cognito User Pool: create custom attribute
   - In the AWS console, open Cognito -> User Pools -> <your-pool> -> Attributes
   - Add custom attribute: `klasse` (will be referenced as `custom:klasse` in tokens)

3) Add user attributes to App client token configuration
   - Cognito -> App clients -> select your app client -> Show advanced settings -> OAuth -> Token configuration
   - Ensure `given_name`, `family_name` and `custom:klasse` are checked to be included in the ID token

4) Create / update users with attributes
   - When creating users, set `given_name`, `family_name` and `custom:klasse`
   - Example AWS CLI:
     aws cognito-idp admin-update-user-attributes `
       --user-pool-id <POOL_ID> `
       --username user@example.com `
       --user-attributes Name="given_name",Value="Max" Name="family_name",Value="Mustermann" Name="custom:klasse",Value="pbd2h24a"

5) Test flow
   - Authenticate a user via Cognito (Hosted UI or Authorization Code / SRP) and inspect the ID token
   - Verify ID token payload contains `given_name`, `family_name`, and `custom:klasse`

6) Backend integration notes (already prepared)
   - Backend will parse and verify ID tokens using JWKS (issuer & jwks url configured in env)
   - `TokenClaimResolver` service will be used to extract claims; Cognito will map to `custom:klasse`

7) Security caveats
   - In production always verify token signature (JWKS), issuer (`iss`) and audience (`aud`) and `exp`
   - Do not accept unverified tokens in production

File: %PROJECT_ROOT%/tmp/cognito_todo.md
Path (absolute): C:\bib\bib-App\bib_app\backends\timetable\timetable\tmp\cognito_todo.md
