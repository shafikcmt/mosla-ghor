# Meta Pixel + Conversions API — how MoslaMart tracks

Pixel ID(s), the CAPI token and the Test Events code live in **Admin → Marketing / Tracking** (token is encrypted in the DB; never in `.env`, HTML, JS or logs).

## Content ID scheme (all funnel events)
`content_type = product`, `id = "{product_id}-{product_prices.id}"` (a priced pack/variant). Fallback when no pack is known (wholesale-enquiry Lead): `"{product_id}"`.
SKU is **not** used: `products.sku` / `product_variants.sku` are nullable and not unique. The catalog feed (below) publishes the same ids.
`contents[].quantity` = packs, `item_price` = price of one pack, `value` = Σ price × qty, `num_items` = Σ qty.
Purchase `value` = order grand total (delivery and discount included); `order_id` = order number.

## Events
| Event | Browser | CAPI | event_id | Trigger |
|---|---|---|---|---|
| PageView | ✔ | – | – | every public page |
| ViewContent | ✔ | ✔ (humans only) | `vc-…` | product page |
| Search | ✔ | – | – | home search settled (1.5 s) / Enter, ≥ 2 chars |
| AddToWishlist | ✔ (next page) | ✔ | `wl-…` | new wishlist row created |
| AddToCart | ✔ | ✔ (`POST /meta/add-to-cart`, values re-derived server-side) | `atc-…` | new cart line or quantity increase |
| InitiateCheckout | ✔ | ✔ | `ic-…` | review step (once per unchanged cart / 30 min) |
| AddPaymentInfo | ✔ deferred | – | `pi-…` | "Place order" pressed with a payment method |
| Purchase | ✔ success page, once | ✔ at order creation | order number | order placed (see below) |
| Lead | ✔ | ✔ (+ bot leads CAPI only) | `lead-{id}` / `lead-combo-{id}` | wholesale / combo enquiry saved |
| CompleteRegistration | ✔ | ✔ | `reg-{userId}` | self-registration only |
| Contact | ✔ | ✔ (`POST /meta/contact`) | `ct-…` | WhatsApp / tel link click |

Vendor pixels are browser-only; their event ids are `{id}-v{vendorId}`.

**Purchase policy (unchanged business behaviour):** Purchase = successfully *placed* order. Orders are created with `payment_status = pending` (COD and manual bKash/Nagad/Rocket alike). A "payment-confirmed" Purchase would need an admin payment-verification hook; not introduced.
**CompleteRegistration** deliberately excludes accounts auto-created for guest checkout / enquiries.

## Operations
* `test_event_code` is honoured for **2 hours** after saving, then CAPI returns to normal reporting automatically.
* `TRUSTED_PROXIES` (default `*`) — behind a local Cloudflare Tunnel set `127.0.0.1,::1` so visitors cannot spoof `X-Forwarded-For` (used for `client_ip_address`).
* `META_EXCLUDE_IPS` — office/developer IPs never tracked. Logged-in admins are excluded unless enabled in the admin screen.
* Consent: the pixel loads unconditionally (disclosed in the privacy policy). For EU/UK-targeted ads add a consent gate (`fbq('consent','revoke')` until accepted, and skip CAPI without consent) before launching there.

## Catalog feed (`/feeds/meta.xml`)
RSS 2.0 + `g:` namespace, built live from the database (`MetaFeedController`). One item per **active retail pack**:
`g:id` = `{product_id}-{product_prices.id}` (identical to the Pixel / CAPI `content_ids`), `g:item_group_id` = product id (packs show as variants of one product),
price as `420.00 BDT` (`compare_price` → regular `g:price` + `g:sale_price`), availability from stock, link = product page, `additional_image_link` from the gallery.
Left out: inactive / wholesale-only products, products without a real main photo, packs with price 0, and vendor products when the platform pixel scope is "own".

Setup: Commerce Manager → Catalog → Data sources → Data feed → "Scheduled feed" → URL `https://<your-domain>/feeds/meta.xml`, schedule hourly or daily.
After the first fetch check **Diagnostics** for rejected items (usually missing/low-resolution images). Then use the catalog in Advantage+ catalog ads.

## Facebook ads launch checklist
Already in the codebase: Pixel + CAPI events (above), catalog feed, `facebook-domain-verification` field (Admin → Site meta tags), OG / product OG tags (price, currency, availability), sitemap, privacy / terms / data-deletion pages.
Public policy pages Meta reviewers look for: `/privacy-policy`, `/terms`, `/refund-policy`, `/delivery-policy`, `/about`, `/data-deletion` — all linked in the footer and listed in `/sitemap.xml`.

To do in the Meta / admin dashboards (cannot be done from code):
1. Business Settings → Brand Safety → Domains → add `moslamart.com`, copy the verification tag into Admin → Site meta tags.
2. Admin → Marketing / Tracking: enter Pixel ID, CAPI token; send a Test Event and confirm PageView / ViewContent / AddToCart / Purchase show "Browser + Server" with matching event ids.
3. Events Manager → Aggregated Event Measurement: prioritise Purchase > InitiateCheckout > AddToCart > ViewContent.
4. Commerce Manager → add the feed `https://moslamart.com/feeds/meta.xml` (hourly) and fix any Diagnostics warnings.
5. Make sure the Facebook page URL, phone and WhatsApp number are filled in Admin → Settings (they appear on the legal pages).
6. Ad links: use the product page URL (not `/admin`, not checkout); `fbclid` is picked up automatically for CAPI matching.
