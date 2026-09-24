Pan Motors — homepage
=====================

Upload the CONTENTS of this folder to your web root (e.g. public_html/):

  index.html
  support.js
  image-slot.js
  _ds/          design-system stylesheet + bundle
  uploads/      photographs, videos, logo

Keep the folder structure exactly as it is — all paths are relative.
Open https://your-domain/ and the page loads index.html.

Notes
-----
- Needs an internet connection on first load: React and the Google Fonts
  (Bodoni Moda, Archivo) are fetched from public CDNs.
- Any static host works (cPanel, Apache, Nginx, Netlify, Vercel, S3).
  No PHP, database or build step required.
- Serve over HTTPS so the hero video autoplays on all browsers.
- The contact form is front-end only: it shows a confirmation but does not
  send mail. Wire it to your mail script or a form service before launch.
- robots.txt in this folder blocks all search engines (demo). The page also
  carries a noindex meta tag. Delete robots.txt and remove the meta tag from
  index.html when you go live.
- Recommended server settings: enable gzip/brotli and long cache headers
  for /uploads and /_ds.
