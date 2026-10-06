Pan Motors — Events page
========================

Upload these next to your existing site files (same web root as index.html):

  events.html       events list
  event.html        single event page (event.html?e=0, ?e=1 …)
  support.js        page runtime
  image-slot.js     image component
  _ds/              design-system stylesheet + bundle
  uploads/          logo + the 11 images this page uses

If support.js, image-slot.js, _ds/ and uploads/ are already on the server
from the full site upload, only events.html is new — the rest can be skipped
or overwritten.

Page styles (fonts, animations, responsive rules) are inside events.html.
Links to index.html, cars.html, about.html, showroom.html and contact.html
expect those pages in the same folder.
