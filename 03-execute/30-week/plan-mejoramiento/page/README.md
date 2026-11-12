# page

Public presentation site for **film-gear**. It tells visitors what the product is.
It does **not** talk to the API.

## Run it

Double-click `index.html`. There is no build step and no dependencies.

To serve it over HTTP instead:

```bash
python -m http.server 8777
```

## Files

| File | What it is |
|---|---|
| `index.html` | The page: hero, what it solves, catalogue categories, how a rental works, audience |
| `styles.css` | Own styles, no framework |
| `favicon.svg` | Single inline SVG icon |

## How it was verified

Served locally and measured in the browser at a 375px viewport:

| Check | Result |
|---|---|
| Horizontal overflow | None. `scrollWidth` and `clientWidth` both 375 |
| Elements wider than the screen | None |
| External requests | One: `styles.css`. No API, no CDN, no remote fonts |
| Document language | `es` |

## Decisions

- **No framework and no build.** Three static files open anywhere and do not rot.
- **Markup, class names and file names in English; visible copy in Spanish.** The
  copy is what a reader sees, so it follows the product's language; everything
  else is code.
- **The copy names problems, not features.** "Nobody has to ask where the second
  FX3 is" instead of "inventory module".
- **Accessibility from the first commit.** Skip link, visible focus ring, a real
  ordered list for the steps, and the step number drawn with a CSS counter so
  screen readers are not told it twice.
