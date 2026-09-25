"""Comparison proxy. Development only.

Usage: python3 dev/compare-proxy.py   (serves http://127.0.0.1:8766)

/design/* serves the Claude Design export from _design/, everything else proxies to
panmotors.local with absolute URLs rewritten, so the design and the WordPress site share
one origin and a comparison page (or dev/tests/compare.mjs) can load both side by side.
"""
import http.server, urllib.request, os, sys
DESIGN = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '_design')
UP = 'http://panmotors.local'
class H(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *a, **k): super().__init__(*a, directory=DESIGN, **k)
    def log_message(self, *a): pass
    def do_GET(self):
        if self.path.startswith('/design/'):
            self.path = self.path[len('/design'):]
            return super().do_GET()
        if self.path == '/rig':
            b = b'<!doctype html><title>rig</title><body></body>'
            self.send_response(200); self.send_header('Content-Type','text/html'); self.send_header('Content-Length',str(len(b))); self.end_headers(); self.wfile.write(b); return
        try:
            r = urllib.request.urlopen(urllib.request.Request(UP + self.path, headers={'User-Agent': 'rig'}))
            code, body, ctype = r.status, r.read(), r.headers.get('Content-Type','text/html')
        except urllib.error.HTTPError as e:
            code, body, ctype = e.code, e.read(), e.headers.get('Content-Type','text/html')
        if ctype.startswith(('text/', 'application/javascript', 'application/json')):
            body = body.replace(b'http://panmotors.local', b'').replace(b'http:\\/\\/panmotors.local', b'')
        self.send_response(code); self.send_header('Content-Type', ctype); self.send_header('Content-Length', str(len(body))); self.end_headers(); self.wfile.write(body)
http.server.ThreadingHTTPServer(('127.0.0.1', 8766), H).serve_forever()
