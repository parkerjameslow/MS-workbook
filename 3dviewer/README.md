# Self-hosted Online 3D Viewer

Mirror of https://3dviewer.net for iframe-embedding inside the workbook's
Art tab preview modal. Same engine as the npm `online-3d-viewer` package,
same UI as the public site.

## Why self-hosted

Their public site has an anti-embed guard
(`if (window.self !== window.top) { ... show "iframe not supported" }`)
plus a cross-origin XHR for the model file. Both block our use case.
Hosting the assets at `wb.marketsculpt.com/3dviewer/` removes both:
- iframe loads aren't blocked (we patched the guard out — `window.self
  !== window.top` → `false`)
- the model URL is same-origin from the iframe's perspective so no CORS

## Patches applied to upstream

1. `o3dv/o3dv.website.min.js` — guard `window.self!==window.top`
   replaced with literal `false` so iframe loads always run.
2. `index.html` — removed Google Analytics (`gtag`/`googletagmanager`),
   AdSense (`adsbygoogle`/`pagead2`), `<link rel=canonical>` pointing
   back at 3dviewer.net, and `og:url` meta. Kept all functional code.

No other changes — engine + UI + plugins are upstream as-is. See
`LICENSE.md` for the original MIT license.

## Source

Pulled from https://3dviewer.net at v0.18.0. To refresh:
1. `curl https://3dviewer.net/...` for each of: `index.html`,
   `o3dv/o3dv.website.min.{css,js}`, `plugins/{headerbuttons,print3d}.js`,
   `assets/images/*`
2. Copy `envmaps/` from the engine release zip
   (https://github.com/kovacsv/Online3DViewer/releases)
3. Re-apply both patches above
