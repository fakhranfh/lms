# Local HTTPS for *.lms.local

Camera/screen-share APIs (`getUserMedia`, `getDisplayMedia`) used by the
proctoring pre-flight checks require a secure context. Plain `http://` on a
non-localhost hostname (like `school.lms.local`) is blocked by the browser.

## One-time setup

1. Install [mkcert](https://github.com/FiloSottile/mkcert) (a prebuilt binary
   works fine, no admin rights needed for a per-user install):

   ```
   curl -L -o mkcert.exe https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-windows-amd64.exe
   ```

2. Trust the local CA (registers it in your Windows user cert store so
   browsers trust it without warnings):

   ```
   ./mkcert.exe -install
   ```

3. Generate a certificate covering the dev domains, saved into `.cert/`
   (already git-ignored):

   ```
   mkdir .cert && cd .cert
   ../mkcert.exe school.lms.local lms.local admin.lms.local "*.lms.local" localhost 127.0.0.1
   ```

## Everyday use

Run these in two terminals:

```
composer run dev          # serves the app on http://*.lms.local (port 80)
bash bin/https-proxy.sh   # TLS proxy: https://*.lms.local (port 443) -> port 80
```

Then browse to `https://school.lms.local`.
