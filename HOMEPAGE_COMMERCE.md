# Homepage commerce update

## Structure and scope

The existing `/` route still uses `HomeController` and `home.blade.php`. The previous tall promotional hero, catalogue, combos, builders, reviews, large trust block and contact/footer made product discovery secondary.

The homepage now presents the shared header, compact settings-driven hero, active category tiles, searchable retail/wholesale catalogue, wholesale preview, selected products and compact benefits. Existing combos, order builders, real reviews, contact links and footer remain. A lower wholesale CTA opens the existing enquiry bag. Existing announcement, account, mobile menu and cart code remain in place.

New Blade partials: `home-hero`, `home-discovery`, `home-benefits`, `product-preview`, all under `resources/views/partials/storefront`. The existing `product-card` supports an optional preview mode without duplicated cart IDs. `public/css/storefront-home.css` scopes presentation to the homepage. `tests/Feature/HomepageCommerceTest.php` covers empty data, bounded discovery, unique IDs, both modes and hidden products.

## Behaviour and data

- Retail retains existing packs, pricing, stock and cart actions. Search filters the current category's loaded catalogue by Bengali/English product name; it does not introduce a search backend.
- Wholesale uses existing channel visibility, MOQ, delivery information and enquiry/detail routes. No invented wholesale prices or sales rankings.
- Approved vendor visibility remains governed by `Product::active()`. Vendor preview cards carry a marketplace label; no separate vendor query or approval bypass.
- Hero settings and existing product imagery are reused. Categories use their actual names with initial markers because no category image field exists. No fake banners, brands or products were introduced.
- Selected products reuse the existing ordering and eight-product limit. Their prices and variant prices are now eager loaded. Wholesale preview reuses the loaded catalogue and takes eight products.
- The existing full catalogue dataset remains because the cart and order builders depend on it. This is an existing scaling limitation: server pagination would require a separate coordinated change to those flows. New discovery sections are bounded.
- Images retain the shared contained artwork and fallback handling. Layout uses four/three/two card columns, compact spacing, Bengali typography, keyboard focus and reduced-motion support.

## Deployment

No schema changes or migrations are introduced by this homepage update. Deploy the changed controller, Blade partials and public CSS together. Build with `npm ci` and `npm run build` in the release directory and publish the complete generated `public/build` directory with its manifest; do not publish a manifest without its hashed assets. Clear/rebuild application caches using the project's normal release procedure, then smoke-test the homepage, product details, cart and enquiry bag before switching traffic.

For deployments also containing the earlier Product Editor work, follow `PRODUCT_EDITOR_RECOVERY.md`: back up first, ensure reviewed additive tags/pivot/variant migrations exist before application traffic reaches code depending on them, and never use destructive migration commands.

## Initial implementation checks

Verification: `php artisan test --compact` finished with 47 passing tests and one known pre-existing failure in `CustomerOtpLoginTest` (expects `/account`, receives `/`). All three new homepage tests passed (19 assertions); the Product Editor and storefront media suites passed (17 tests, 221 assertions). `npm run build` passed (55 modules). PHP syntax checks, product route listing and `git diff --check` passed. No live database migration or product-data mutation was performed.

Automated HTTP tests are not visual or interactive browser verification. Check desktop (1200px+), laptop, tablet and 375px mobile: hero CTAs; category links; retail/wholesale switching; name search and empty search results; grid/list preference; product detail links; pack selection and add-to-cart; enquiry actions; approved vendor visibility; header/mobile menu; footer; complete artwork and broken-image fallback; gallery zoom/keyboard controls; no horizontal overflow. Confirm the existing order builders and announcement still behave normally.

No production commands, commits or pushes were performed for this update.

## Browser QA follow-up — 2026-09-24

Actual headless Chromium QA ran against the local Laravel server. Network-enabled follow-up loaded the intended Bengali Google Fonts. No console errors or uncaught JavaScript exceptions remained. Local Vite CSS, homepage CSS, media CSS and media JavaScript all returned HTTP 200; no view contains a `filemtime` call. The homepage CSS has been explicitly staged to ensure it becomes tracked in the eventual commit. No commit or push was made.

| Viewport | Grid columns | Horizontal overflow | Hero height |
| --- | --- | --- | --- |
| 1366 × 768 | 4 | None | 374px |
| 1440 × 900 | 4 | None | 374px |
| 1920 × 900 | 4 | None | 374px |
| 768 × 900 | 3 | None | 357px |
| 820 × 900 | 3 | None | 329px |
| 375 × 900 | 2 | None | 495px |
| 390 × 900 | 2 | None | 495px |
| 430 × 900 | 2 | None | 495px |

Small fixes made during QA:

- `home.blade.php`: replaced external runtime Tailwind CDN dependence with the existing Vite CSS entry. The sandbox's blocked CDN revealed that no local utility stylesheet had been loaded. Also guarded grid/list switching when the empty catalogue has no grid containers.
- `storefront-home.css`: tablet header uses the existing menu drawer; corrected the search-label selector; reduced mobile fallback-hero spacing; narrowed price styling so missing-image artwork keeps its gold text; restored white text on selected pack labels. Styles remain homepage-scoped, without new `!important` overrides.
- `home-hero.blade.php`: labelled the wholesale CTA accurately instead of reusing an old setting that said “view combos”.
- `HomepageCommerceTest.php`: added vendor approval/lazy-loading regression coverage and an assertion against the CDN dependency.
- This report records the QA findings. Temporary scripts, screenshots and the empty-page fixture remain in ignored `test-results/`.

Functional results:

- Both displayed categories (`spices`, `dry-fruits`) returned 200 and retained wholesale mode. Name-based category fallbacks remain readable.
- Bangla `জিরা` and English `Cumin` searches each found one product; nonsense text produced the empty state; clearing restored all seven retail products.
- Retail pack selection reached the expected price ID, one add created one cart item, and repeating it did not duplicate the line. Builder pack changes synchronized with the shared cart.
- Wholesale mode showed its one eligible product and zero visible retail-button groups. Quantity controls changed 5kg to 10kg; the enquiry bag stored 10kg. No enquiry/order was submitted.
- All eight unique preview detail URLs returned 200. Preview markup has no cart-control IDs or additional click listeners. The rendered homepage has no duplicate IDs and exactly one H1.
- Approved/pending/rejected vendor visibility was checked using isolated SQLite test fixtures; pending and rejected products remained absent. Existing Product Editor visibility tests also passed.
- The live catalogue has no product artwork to visually compare. Browser-only square, portrait, landscape and edge-text image fixtures verified `scale-down` containment within the same 167 × 150px mobile frame, preserving aspect ratio. Missing-image placeholders were visually checked; no live product media was modified.
- An empty homepage rendered using SQLite `:memory:` was loaded in Chromium. Search and grid/list controls produced no exceptions or overflow. No live schema or records were changed.

The full catalogue remains the primary mode-aware product section: seven products locally, four desktop/two mobile columns, plus its alternative list DOM. Preview sections remain capped at eight and do not duplicate interactive controls. Actual order: header, hero, categories, catalogue, wholesale, selected products, benefits, existing combos/builders, wholesale CTA, contact and footer (reviews appear only when available). The retained builders account for much of the page length; no additional promotional sections were added. A much larger future catalogue still needs coordinated pagination rather than removal of required cart data.

Query review: required category, active-price and active-variant-price relations are eager loaded. Wholesale previews reuse the existing collection. Selected products retain their separate bounded query because they are independent of category filtering. No new per-card relationship query was introduced; lazy-loading prevention also ran in the vendor regression test. This was not a production-scale load benchmark.

Final checks: focused homepage/Product Editor/media suites **21 passed, 245 assertions**. Full suite **48 passed, 1 known pre-existing OTP redirect failure**, 340 assertions. `npm run build` passed (55 modules). Relevant PHP syntax and staged/unstaged whitespace checks passed.

Remaining manual verification: Safari/iOS and Android touch behaviour, final visual sign-off with actual uploaded artwork, authenticated customer flow and real checkout/enquiry submission in an appropriate staging environment. Headless QA deliberately did not submit orders or modify live data. Publish the complete Vite build and public CSS with the Blade changes; the manifest alone is insufficient.
