# Social authentication setup

NaSeRy supports Google, Facebook, and Apple OAuth. Create an OAuth application with each provider and add these callback URLs:

- Google: `http://localhost:8000/api/auth/social/google/callback`
- Facebook: `http://localhost:8000/api/auth/social/facebook/callback`
- Apple: `http://localhost:8000/api/auth/social/apple/callback`

For production, replace both the frontend and API hosts with their public HTTPS URLs.

Copy the matching variables from `.env.example` into `.env` and fill in the provider credentials. Apple accepts either a current client-secret JWT or the configured key ID, team ID, and absolute `.p8` private-key path. Clear cached configuration after changing credentials:

```bash
php artisan config:clear
```

The frontend redirects to the provider, the API validates a short-lived OAuth state, and the callback returns a single-use two-minute exchange code. Existing accounts are linked by verified provider email. New accounts are created only from the registration page and use its selected Client or Organizer role.
