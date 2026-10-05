# Bot API + Meta Conversions API

Mosla Ghor exposes a small, token-protected API for the **separate social-media
automation app** (Messenger / comment auto-reply, daily post generator), and sends
server-side **Conversions API (CAPI)** events for the platform Meta Pixel.

After deploying: `php artisan migrate --force` (adds `bot_api_tokens`, `bot_leads`
and the CAPI columns on `marketing_settings`).

---

## 1. Conversions API setup (Admin → Settings → মার্কেটিং / ট্র্যাকিং)

1. Meta Events Manager → your Pixel → **Settings → Conversions API → Generate access token**.
2. Admin panel: tick **Conversions API চালু করুন**, paste the token, save.
   The token is stored encrypted and is never shown again. Leave the field blank on
   later saves to keep it.
3. Test: Events Manager → **Test Events** → copy the test code → paste in
   *Test event code* → save → click **Conversions API টেস্ট ইভেন্ট পাঠান**.
   A `PageView` should appear under Server events.
4. **Remove the test event code when done.** While it is set, every server event
   goes to Test Events only.

What is sent (platform pixel only, same `event_id` as the browser event so Meta de-duplicates):

| Event | When | event_id |
|---|---|---|
| `Purchase` | order placed (`OrderController@store`), not dependent on the success page loading | order number |
| `Lead` | single-product wholesale enquiry | `lead-{enquiry id}` |
| `Lead` | paykari combo enquiry | `lead-combo-{id}` |
| `CompleteRegistration` | customer sign-up | `reg-{user id}` |
| `Lead` (`action_source: chat`) | lead posted by the bot (below) | `bot-lead-{lead id}` |

User data: SHA-256 hashed phone (`8801…`), email, first name, customer id, country
`bd`, plus IP, user agent and `_fbp` / `_fbc` cookies. The platform scope ("own"
products only) and the logged-in-admin exclusion apply exactly as for the browser pixel.
Events are sent after the response, so checkout never waits on Meta. The last success
or error is shown on the settings page.

---

## 2. Bot API

Create a token in **Admin → Customers & Support → Bot API ও সোশ্যাল লিড**. It is
shown once. Put it in the bot app's `.env`. Revoke it there at any time.

```
Base URL:  https://<your-domain>/api/bot/v1
Header:    Authorization: Bearer mgb_xxxxxxxx
Accept:    application/json
```

Rate limit: 120 requests/minute per token. Errors are JSON: `401` bad or revoked
token, `403` missing ability, `422` validation errors.

### Abilities

| Ability | Endpoints |
|---|---|
| `products:read` | `GET /products`, `GET /products/{id}`, `GET /prices` |
| `catalog:read` | `GET /catalog/highlights` |
| `orders:read` | `GET /orders/status` |
| `leads:write` | `POST /leads` |

### `GET /ping`
Checks the token. Returns `{ ok, token, abilities, site: { name, url, whatsapp } }`.

### `GET /products?q=জিরা&limit=10`
Searches active products by Bangla/English name, slug or SKU.

```json
{ "data": [ {
  "id": 12, "name": "জিরা", "name_bn": "জিরা", "name_en": "Cumin", "category": "মসলা",
  "url": "https://…/products/cumin", "image": "https://…/storage/products/cumin.webp",
  "in_stock": true, "is_wholesale": false, "moq": null,
  "packs": [ { "price_id": 41, "label": "250g", "variant": null, "price": 290, "compare_price": null } ]
} ] }
```

### `GET /products/{id}`
Returns the same fields plus `short_description` and `delivery_time`. Inactive
products return `404`.

Products also include `retail_price_per_kg` and a `wholesale` block (see `/prices`).

### `GET /prices?q=জিরা&customer_type=wholesale&limit=10`
Prices for one customer type (`retail` = খুচরা, default; `wholesale` = পাইকারি) plus a
ready Bangla `reply` — the same text the admin sees on **Admin → Products → প্রাইস বোর্ড**.
Admins set the prices there (or in the product form, section 05).

```json
{ "customer_type": "wholesale", "data": [ {
  "id": 12, "name": "জিরা", "url": "https://…/products/cumin?mode=wholesale", "image": "https://…", "in_stock": true,
  "retail":    { "price_per_kg": 600, "packs": [ { "label": "250g", "price": 160 } ] },
  "wholesale": { "price_per_kg": 500, "moq": "10 কেজি",
                 "unit_prices": [ { "unit": "bag", "unit_label": "ব্যাগ", "kg": 25, "price": 12500 } ],
                 "unit_conversions": [ "1 ব্যাগ = 25 কেজি" ], "delivery_time": "৩–৫ দিন", "payment_terms": null },
  "reply": "জিরা — পাইকারি দাম\n• প্রতি কেজি: ৳500\n• প্রতি ব্যাগ (25 কেজি): ৳12500\n…"
} ] }
```

`retail` is `null` for wholesale-only products and `wholesale` is `null` when the product is
not sold wholesale. `wholesale.price_per_kg` is `null` when no পাইকারি price is set (the
reply then says a quotation will be given). Unit prices are computed from the product's
unit conversions (e.g. 1 carton = 10 packets, 1 packet = 500 g → 1 carton = 5 kg).

### `GET /catalog/highlights`
Content for daily posts: `best_sellers_30d` (by order count, excluding cancelled
orders), `new_arrivals`, and `combos` (active admin combos with price and items).

### `GET /orders/status?phone=01712345678[&order_number=MM-123]`
Returns the latest 3 orders for the phone, or the one matching `order_number`.
Status fields only: `order_number, placed_at, status, status_label, payment_status,
grand_total, items_count, courier, tracking_id, sent_to_courier_at, delivered_at`,
plus `track_url`. No names, addresses or items.

> Privacy: anyone who knows a phone number can learn that number's order statuses
> through the bot. Have the bot answer only for the number the customer sends in
> the chat, and send full details only when the order number is also given.

### `POST /leads`
Creates a lead for the team to follow up. It appears on the admin page and is
sent to Meta as a `Lead` if CAPI is on.

| Field | Rules |
|---|---|
| `source` | required: `messenger` \| `facebook_comment` \| `whatsapp` \| `instagram` \| `other` |
| `phone` | required, BD mobile (`01…`, `+880…` or Bengali digits accepted) |
| `message` | required, ≤ 2000 |
| `customer_name`, `page_name` | optional, ≤ 100 |
| `product_id` | optional, existing product id (from `/products`) |
| `quantity` | optional text, e.g. `"5 kg"` |
| `address` | optional, ≤ 500 |
| `external_ref` | optional, ≤ 100. Your own id (e.g. `psid:mid`); a retry with the same value returns the existing lead with `"duplicate": true` |

Response: `201 { "data": { "id": 7, "status": "new" }, "duplicate": false }`.

### Example (Node 18+)

```js
const api = (path, init = {}) =>
  fetch(`${process.env.MOSLAGHOR_API}/api/bot/v1${path}`, {
    ...init,
    headers: { Authorization: `Bearer ${process.env.MOSLAGHOR_BOT_TOKEN}`,
               Accept: 'application/json', 'Content-Type': 'application/json', ...init.headers },
  }).then(r => r.json());

const { data } = await api('/products?q=' + encodeURIComponent('জিরা'));
await api('/leads', { method: 'POST', body: JSON.stringify({
  source: 'messenger', phone: '01712345678', message: '৫ কেজি জিরা চাই',
  product_id: data[0]?.id, quantity: '5 kg', external_ref: `${psid}:${mid}` }) });
```
