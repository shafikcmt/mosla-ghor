MoslaMart — phone mockup spice photos
=====================================

The home page "App ডাউনলোড করুন" phone mockup pulls its 4 product-card photos
from this folder. Drop optimized SQUARE images here using these exact names:

  cumin.webp      → জিরা  (Cumin)
  cardamom.webp   → এলাচ  (Cardamom)
  cinnamon.webp   → দারুচিনি (Cinnamon)
  clove.webp      → লবঙ্গ (Clove)

Notes
-----
- .webp is preferred; .jpg / .jpeg / .png / .avif also work (first match wins,
  in that order). So cumin.jpg works too if you don't have webp.
- Use a roughly square crop (e.g. 400×400). The card displays them with
  object-cover, so off-square images are cropped, never stretched.
- Keep each file small (~20–60 KB) for fast mobile load.
- Until a file exists, that card automatically shows the brand gradient
  placeholder, so the page never breaks.

Defined in: resources/views/home.blade.php  (search "spices/")
