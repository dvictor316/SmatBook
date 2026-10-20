# Automatic agent bank payouts

Agents save their bank details in **Earnings & Wallet**. Saving enables automatic payouts and submits any eligible balance. New paid subscription commissions trigger the same transfer flow. The scheduler checks eligible balances every five minutes. The configured minimum payout still applies.

After deploying, run:

```bash
cd /var/www/smartprobook.com
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
```

The migration enables automatic payouts for existing agent profiles. It does not send transfers during migration.

Ensure the server has one Laravel scheduler cron entry (do not duplicate an existing entry):

```cron
* * * * * cd /var/www/smartprobook.com && php artisan schedule:run >> /dev/null 2>&1
```

For Paystack bank transfers, configure the live secret key and fund the Paystack balance. In Paystack Preferences, disable **Confirm transfers before sending** for unattended transfers. Configure the webhook URL as `https://smartprobook.com/api/paystack/webhook`; the application validates its signature and marks commissions paid on `transfer.success`.

A submitted transfer remains processing until the provider confirms success. A failed transfer releases its commissions for a later attempt. An inconclusive transfer keeps its commissions reserved to avoid sending the same money twice; review it before retrying.

Provider documentation: [Single transfers](https://paystack.com/docs/transfers/single-transfers/) and [Transfer errors](https://paystack.com/docs/api/errors/transfer/).
