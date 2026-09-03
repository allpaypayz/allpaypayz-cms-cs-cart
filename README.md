# Allpaypayz for CS-Cart

**[⬇ Download the latest version](https://github.com/allpaypayz/allpaypayz-cms-cs-cart/archive/refs/heads/main.zip)** · [Browse the code](https://github.com/allpaypayz/allpaypayz-cms-cs-cart) · [MIT](LICENSE)

<sub>The archive is a snapshot of `main` — the current state of the plugin. Tagged releases will appear on the Releases page once the code leaves alpha.</sub>


CS-Cart addon that registers Allpaypayz as a payment processor and handles
redirect checkout + webhook notifications via [`allpaypayz/sdk`](https://github.com/allpaypayz/allpaypayz-sdk-php).

> Status: **alpha** (v0.1.0). Targets CS-Cart 4.10+ on PHP 8.1+.

## Install

1. Upload `cms-cs-cart/` into `app/addons/allpaypayz/` on the target shop.
2. From `app/addons/allpaypayz/` run:
   ```bash
   composer require allpaypayz/sdk guzzlehttp/guzzle
   ```
   The addon's `payments/allpaypayz.php` requires `vendor/autoload.php` on
   every invocation.
3. In the admin panel, go to **Add-ons → Manage add-ons → Allpaypayz Payments**
   and click *Install*.
4. Open **Administration → Payment methods → Add payment method** and pick
   **Allpaypayz** as the processor. Fill in API key, sign key, environment,
   payment method.

Webhook URL: `https://your-shop.example.com/index.php?dispatch=payment_notification.notify&payment=allpaypayz`.

## How it works

- `addon.xml` registers the addon and installs a row into
  `cscart_payment_processors` for Allpaypayz.
- `payments/allpaypayz.php` is the processor entry point. CS-Cart invokes it
  with `$processor_data` (settings) + `$order_info` (the placed order)
  during checkout, or with `PAYMENT_NOTIFICATION` set when a webhook
  arrives.
- Checkout path: builds the payload, calls
  `client->payments->createRedirect(...)` with
  `merchant_reference: CS-<order_id>`, redirects via `fn_redirect()` to
  the returned `checkout_url`.
- Webhook path: verifies the signature via
  `Allpaypayz\Webhooks::verify` and calls `fn_change_order_status()`:
  - `payment.succeeded` / `order.completed` → `P` (Processed)
  - failure / cancellation → `F` (Failed)
- `payments/allpaypayz.tpl` renders the per-method admin settings form.

## Files

```
cms-cs-cart/
├── README.md
├── composer.json
├── addon.xml                       — addon metadata + install SQL
├── payments/
│   ├── allpaypayz.php              — processor entry point
│   └── allpaypayz.tpl              — admin settings template
└── var/langs/{en,ru}/addons/allpaypayz.po
```

## License

MIT
