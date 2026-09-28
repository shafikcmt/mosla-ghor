# Server setup for image uploads (apply later, when you have server access)

The app already works without any of this: browsers shrink photos before upload
(`public/js/image-resize.js`), and the server stores WebP uploads as-is when its GD
build cannot read WebP. These changes only make the server side faster and more
complete. **Nothing in the app depends on them.**

## 1. Check what live has today

Run inside the PHP container:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_file_uploads"
php -r 'var_dump(gd_info()["WebP Support"] ?? false);'
```

And in the nginx container:

```bash
grep -R "client_max_body_size" /etc/nginx/ || echo "not set (nginx default is 1m)"
```

## 2. Dockerfile — GD with WebP

Add `libwebp-dev` to the `apk add` list and `--with-webp` to the GD configure line:

```dockerfile
RUN apk add --no-cache \
    ... \
    libwebp-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) ... gd ...
```

## 3. PHP upload and memory limits

Create `docker/php/uploads.ini` on the server:

```ini
upload_max_filesize = 12M
post_max_size = 64M
memory_limit = 512M
max_file_uploads = 25
```

Copy it into the image (Dockerfile):

```dockerfile
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
```

(or mount it in `docker-compose.yml` under the `app` service:
`- ./docker/php/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini:ro`).

Notes:
- `post_max_size` is per request. A product form with a 20-photo gallery can exceed it;
  the browser resize keeps each photo small (usually 100–500 KB), so 64M is plenty.
- `memory_limit = 512M` lets GD decode big phone photos (a 48 MP photo needs ~190 MB).
  Below that, the app simply stores such originals without processing.

## 4. nginx body size

In the server's `nginx.conf` (inside the `server { … }` block):

```nginx
client_max_body_size 64m;
```

Cloudflare's free plan allows up to 100 MB per request, so no change is needed there.

## 5. Rebuild and restart

```bash
docker compose build app
docker compose up -d app nginx
docker compose exec app php artisan optimize:clear
docker compose exec app php -r 'var_dump(gd_info()["WebP Support"] ?? false);'   # expect true
```

Admin → জেনারেল সেটিং → ছবি কম্প্রেশন shows "সার্ভার WebP: সমর্থিত" once it works
(the detection is cached for up to 24 hours; `php artisan cache:clear` refreshes it).

## 6. Optional: shrink old images

```bash
docker compose exec app php artisan images:optimize-existing          # dry run: lists savings
docker compose exec app php artisan images:optimize-existing --apply  # backs up to storage/app/image-backups/<date>/
```
