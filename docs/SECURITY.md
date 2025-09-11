# Security Practices

- Global TenantScope on all queries.
- TOTP 2FA required for admins.
- Device link requires fresh 2FA.
- Tokens scoped + expiring.
- HMAC verification for webhooks.
- Audit logs: logins, 2FA changes, playlist CRUD, token issue/revoke.
