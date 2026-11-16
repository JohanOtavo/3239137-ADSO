#!/usr/bin/env node
/**
 * T-23 and WCAG 2.2 - The colour pairs both stylesheets actually render.
 *
 * The palette changed in one afternoon and was judged by eye. Eyes are not
 * good at this: a pair at 3.9:1 looks fine on the laptop it was designed on
 * and disappears on a projector, which is where this project gets shown.
 *
 * The values are read out of the stylesheets rather than copied here, so a
 * colour that moves is measured where it moved to. A token that is renamed or
 * removed fails the run instead of being skipped quietly.
 */

import { readFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import process from 'node:process';

const HERE = dirname(fileURLToPath(import.meta.url));

/** Normal text under WCAG AA. Large text would be 3, and none of these are. */
const AA_NORMAL = 4.5;

export function parseColor(raw) {
  const value = raw.trim();

  const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

  if (hex !== null) {
    const digits = hex[1].length === 3
      ? hex[1].split('').map((digit) => digit + digit).join('')
      : hex[1];

    return {
      rgb: [0, 2, 4].map((at) => parseInt(digits.slice(at, at + 2), 16)),
      alpha: 1,
    };
  }

  const rgba = value.match(/^rgba?\(([^)]+)\)$/i);

  if (rgba !== null) {
    const parts = rgba[1].split(',').map((part) => Number(part.trim()));

    if (parts.length >= 3 && parts.slice(0, 3).every((part) => Number.isFinite(part))) {
      return {
        rgb: parts.slice(0, 3),
        alpha: parts.length > 3 && Number.isFinite(parts[3]) ? parts[3] : 1,
      };
    }
  }

  return null;
}

/** Flattens a translucent colour over what is behind it, which is what glass does. */
export function flatten(front, back) {
  if (front.alpha >= 1) {
    return front.rgb;
  }

  return front.rgb.map((channel, at) => Math.round(channel * front.alpha + back[at] * (1 - front.alpha)));
}

function channelLuminance(value) {
  const scaled = value / 255;

  return scaled <= 0.03928 ? scaled / 12.92 : ((scaled + 0.055) / 1.055) ** 2.4;
}

export function luminance(rgb) {
  return 0.2126 * channelLuminance(rgb[0])
    + 0.7152 * channelLuminance(rgb[1])
    + 0.0722 * channelLuminance(rgb[2]);
}

export function contrast(foreground, background) {
  const [lighter, darker] = [luminance(foreground), luminance(background)].sort((one, other) => other - one);

  return (lighter + 0.05) / (darker + 0.05);
}

/** Every `--token: value;` declared in a stylesheet. */
export function tokensIn(css) {
  const found = new Map();

  for (const match of css.matchAll(/--([a-z0-9-]+)\s*:\s*([^;]+);/gi)) {
    const color = parseColor(match[2]);

    if (color !== null) {
      found.set(match[1], color);
    }
  }

  return found;
}

/**
 * The pairs each surface puts on screen.
 *
 * `on` is read back to front: the last entry is the furthest back, and each
 * translucent layer in front of it is flattened over what it covers. That is
 * the only honest way to measure text on glass.
 */
const SURFACES = [
  {
    label: 'page',
    stylesheet: '../page/styles.css',
    pairs: [
      { text: 'ink', on: ['night'], what: 'body text' },
      { text: 'ink', on: ['glass', 'night'], what: 'text on a glass card' },
      { text: 'muted', on: ['night'], what: 'the lead paragraph' },
      { text: 'muted', on: ['glass', 'night'], what: 'card text' },
      { text: 'muted', on: ['glass-strong', 'night'], what: 'card text on the deeper glass' },
      { text: 'plasma-mint', on: ['night'], what: 'the eyebrow' },
      { text: 'night', on: ['plasma-mint'], what: 'a category tag' },
      { text: 'night', on: ['plasma-cyan'], what: 'a category tag at the other end of its gradient' },
    ],
  },
  {
    label: 'app',
    stylesheet: '../app/src/styles.css',
    pairs: [
      { text: 'ink', on: ['surface'], what: 'body text on a panel' },
      { text: 'ink', on: ['page'], what: 'body text on the page' },
      { text: 'muted', on: ['surface'], what: 'labels and table headers' },
      { text: 'muted', on: ['page'], what: 'labels outside a panel' },
      { text: 'accent', on: ['surface'], what: 'a secondary button' },
      { text: 'danger', on: ['surface'], what: 'an error notice' },
      { text: 'warning', on: ['surface'], what: 'a warning' },
      { text: 'ok', on: ['surface'], what: 'a success notice' },
    ],
  },
];

/** White on the filled controls, which take their colour from a gradient. */
const WHITE = { rgb: [255, 255, 255], alpha: 1 };

/** Resolves a stack of token names into one opaque colour. */
export function resolve(stack, tokens) {
  const colors = stack.map((name) => {
    const color = tokens.get(name);

    if (color === undefined) {
      throw new Error(`The token --${name} is not declared any more.`);
    }

    return color;
  });

  let behind = colors[colors.length - 1].rgb;

  for (let at = colors.length - 2; at >= 0; at -= 1) {
    behind = flatten(colors[at], behind);
  }

  return behind;
}

export function measure(surface, css) {
  const tokens = tokensIn(css);

  const rows = surface.pairs.map((pair) => {
    const text = tokens.get(pair.text);

    if (text === undefined) {
      throw new Error(`The token --${pair.text} is not declared any more.`);
    }

    const background = resolve(pair.on, tokens);
    const foreground = flatten(text, background);

    return {
      label: `${surface.label}: ${pair.what}`,
      ratio: contrast(foreground, background),
    };
  });

  // The filled button and the current menu entry put white on the accent.
  if (surface.label === 'app') {
    for (const name of ['accent', 'accent-ink']) {
      rows.push({
        label: `app: white on --${name}`,
        ratio: contrast(WHITE.rgb, resolve([name], tokens)),
      });
    }
  }

  return rows;
}

async function main() {
  const rows = [];

  for (const surface of SURFACES) {
    const css = await readFile(join(HERE, surface.stylesheet), 'utf8');

    rows.push(...measure(surface, css));
  }

  const failures = rows.filter((row) => row.ratio < AA_NORMAL);

  for (const row of rows) {
    const verdict = row.ratio < AA_NORMAL ? 'FAIL' : 'pass';

    console.log(`${verdict}  ${row.ratio.toFixed(2)}:1  ${row.label}`);
  }

  if (failures.length > 0) {
    console.error(`\n${failures.length} pair(s) under ${AA_NORMAL}:1, which is WCAG AA for normal text.`);

    return 1;
  }

  console.log(`\nContrast check passed: ${rows.length} pairs, all at or above ${AA_NORMAL}:1.`);

  return 0;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  process.exit(await main());
}
