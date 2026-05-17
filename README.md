cd /path/to/third-party
php artisan seed:vendor-data SZARYA8X

php artisan payments:simulate-success --limit=1

## Testing reset (run with Kashtre resets)

```bash
# Light: visit_identity_verifications only
php artisan testing:clear-order-data --confirm

# Full: authorizations, visits, pre-auths, audit logs, etc.
php artisan testing:clear-order-data --confirm --full
```

With Kashtre (from kashtre repo README), a full local retest often looks like:

```bash
# In kashtre/
php artisan service-queues:reset --all --force && \
php artisan suspense:clear --force && \
php artisan reset:account-statements --confirm && \
php artisan testing:clear-order-data --confirm --full --reset-balances

# In third-party/
php artisan testing:clear-order-data --confirm --full
```
