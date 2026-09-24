# Catalogue toolbar, Hero Slides and SEO

## What changed

1. **Toolbar root cause:** the visible label above the search input increased its height, the search field was capped at 440px, and mode/view buttons used different padding and sizing.
2. **Toolbar alignment:** existing IDs and commerce handlers remain. Desktop uses mode buttons, an expanding search form with a submit button, and Grid/List controls on one aligned row. All controls measure 46px high. Tablet wraps search onto its own row; mobile stacks mode, search, view controls, then the existing category chips. Search still filters on input and now also supports form submission.
3. **Slider architecture:** lightweight `public/js/home-slider.js`, with no added dependency. Each slide has text left and contained artwork right, stacked on mobile. Six-second autoplay, previous/next buttons, dots, keyboard controls and a pause button are provided. Hover, focus and hidden tabs suspend autoplay; manual navigation stops it. Reduced motion disables automatic playback initially and removes transitions. Inactive slides are inert and hidden from assistive technology. One slide has no controls/timer; zero slides retain the previous hero.
4. **Slide data:** one query loads only active `HeroSlide` records, ordered by `sort_order`, then ID. No product queries or visibility rules changed. Existing fallback imagery still uses approved/public products and `ProductMedia::url()`; slide images use the same resolver. The first image has high fetch priority; later images are lazy loaded.
5. **Admin Hero Slides:** Settings → Hero Slides has a preview list, create/edit forms, active checkbox, numeric ordering and confirmed deletion. Image upload accepts JPG/PNG/WebP up to 5MB. Editing without an upload preserves the image. Replacement stores first, commits the database, then removes an unreferenced owned image. Failure rolls back new uploads; request-supplied filesystem paths are never accepted. Both CTA fields are optional pairs and reuse the announcement URL validator.
6. **Website Name:** retained the existing `site_name` setting, Admin field and shared branding. Fallback remains configured name → application name → existing default. No historical product, vendor or legal content was replaced.
7. **Tagline:** retained the existing optional `site_tagline` setting and header usage. General Settings remains dedicated to pricing; no competing identity fields were added there.
8. **Meta title:** new optional `meta_title` key in Website Settings, maximum 70 characters. Homepage uses it when supplied; page-specific storefront titles retain the configured site-name suffix. Missing defaults use site name and tagline.
9. **Meta description:** new optional `meta_description` key, maximum 200 characters. Shared SEO output chooses page-specific description, configured default, then a safe store description. Product and FAQ descriptions now flow through this partial so each page has only one title and description tag. Existing product keyword and social metadata remain.
10. **Settings reuse:** Site Identity, SEO and Announcement all use `WebsiteSetting`, its existing Admin controller/page and the same keyed settings cache. No additional settings table or service was created.
11. **Database:** added `2026_09_24_000001_create_hero_slides_table.php`, with slide copy, image path, CTA pairs, active state and sort order. The migration was applied locally using its explicit path; only an empty new table was added. Existing migrations/data were not reset, seeded or overwritten.
12. **Announcement:** preserved existing ON/OFF, message, label and safe-link behavior. Its partial and validation were not redesigned.
13. **Cache:** existing model save hooks invalidate the request-level settings cache. Admin updates appear on the next request without manual cache clearing. Slides are queried fresh and have no separate stale cache.

## Files changed in this stage

New:

- `app/Models/HeroSlide.php`
- `app/Http/Controllers/Admin/HeroSlideController.php`
- `database/migrations/2026_09_24_000001_create_hero_slides_table.php`
- `public/js/home-slider.js`
- `resources/views/admin/hero-slides/index.blade.php`
- `resources/views/admin/hero-slides/form.blade.php`
- `resources/views/partials/storefront/home-slider.blade.php`
- `resources/views/partials/storefront/home-hero-fallback.blade.php`
- `resources/views/partials/storefront/seo.blade.php`
- `tests/Feature/HeroSlideTest.php`
- `tests/Feature/WebsiteSeoTest.php`
- This report.

Updated:

- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/Admin/WebsiteSettingController.php`
- `routes/web.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/website-settings.blade.php`
- `resources/views/home.blade.php`
- `resources/views/partials/storefront/home-hero.blade.php`
- `resources/views/storefront/layout.blade.php`
- `resources/views/storefront/product-detail.blade.php`
- `resources/views/faq.blade.php`
- `public/css/storefront-home.css`
- Generated Vite assets/manifest.

The shared Admin layout now loads existing compiled Vite CSS instead of the runtime Tailwind CDN. Browser inspection found the CDN unavailable and the forms unstyled; the local stylesheet restores the existing utility-based layout without adding a framework. Existing Alpine dependencies were left unchanged. Other pre-existing uncommitted work was preserved; no commit or push was made.

## Verification

14. **New tests:** ten tests cover slide ordering/visibility, one/zero states, one H1/unique IDs, Admin CRUD, ordering/deactivation, image preservation/replacement/deletion, invalid uploads, unsafe URLs, DB-failure cleanup, shared/unowned image protection, SEO save/cache refresh, length validation and duplicate metadata prevention. Existing identity, announcement and vendor-approval tests remain passing.
15. **Focused results:** 47 passed, 423 assertions across announcement, identity/hero, homepage, slide, SEO, Product Editor and storefront media suites.
16. **Full suite:** 69 passed, 1 confirmed pre-existing failure, 503 assertions. `CustomerOtpLoginTest.php:74` expects `/account` but receives `/`; the same failure was confirmed before this stage. No new failing tests.
17. **Build/checks:** `npm.cmd run build` passed (55 modules). PHP syntax checks, JavaScript syntax check, Blade compilation, and staged/unstaged `git diff --check` passed. The `.cmd` entry avoids the local PowerShell restriction on npm.ps1.
18. **Browser QA:** Chromium checked 1366×768, 1440×900, 768×900, 375×900, 390×900 and 430×900. No horizontal overflow or page exceptions. Slider height was 404px desktop/tablet and 513px mobile using the QA copy. Images used scale-down and stayed contained. Autoplay, manual navigation, keyboard navigation, manual pause and reduced motion passed. One-slide and zero-slide states passed. Desktop toolbar controls shared the same top position and 46px height; responsive wrapping matched the intended order.

Admin browser saves verified website name, meta title/description, slide creation, editing, ordering, disabling and image replacement. Desktop/mobile screenshots were inspected. Website Settings, slide list/form and Product Editor had no overflow at 1366px and 390px with local CSS loaded.

Commerce browser checks passed Bangla/English search, no-results state, clearing search, mode switching, category links, Grid/List, retail pack selection and cart deduplication. Wholesale quantity changed from 5kg to 10kg and the enquiry bag retained 10kg; detail links returned 200. No order or enquiry was submitted.

QA used `test-results/stage2-qa.sqlite` and an isolated media directory. Demo slides and the QA Admin account were not inserted into the normal database. Screenshots and measurements are under ignored `test-results/stage2-*`.

19. **Remaining manual verification:** final copy/artwork approval with real slide uploads, Safari/iOS/Android touch and screen-reader review. Very long configured titles/descriptions may intentionally make the hero taller; concise promotional copy is recommended. Existing unrelated CDN dependencies outside the shared Admin stylesheet were not rewritten.
20. **Deployment:** apply the additive hero-slides migration before serving code that queries it. Deploy the controller/model, Blade changes, `public/js/home-slider.js`, tracked homepage CSS, and complete Vite build together. Ensure the existing public storage link and upload permissions are valid. Refresh compiled views through the normal deployment process. Add slides in Admin; the previous hero stays active until a slide is enabled. No settings seed rerun or manual settings cache clear is needed.

READY FOR VISUAL REVIEW
