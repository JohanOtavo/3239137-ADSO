import assert from 'node:assert/strict';
import { test } from 'node:test';

import { contrast, flatten, luminance, measure, parseColor, resolve, tokensIn } from './check-contrast.mjs';

/*
 * The maths is checked against values the WCAG documents state, not against
 * what this file happens to compute. A contrast checker that agrees with
 * itself is worth nothing.
 */

test('black on white is the maximum the scale allows', () => {
  assert.equal(contrast([0, 0, 0], [255, 255, 255]).toFixed(2), '21.00');
});

test('a colour against itself is the minimum', () => {
  assert.equal(contrast([18, 52, 86], [18, 52, 86]).toFixed(2), '1.00');
});

test('the published borderline greys land where WCAG says', () => {
  // 767676 on white is the lightest grey that still passes AA; 777777 is the
  // first one that does not.
  assert.ok(contrast([118, 118, 118], [255, 255, 255]) >= 4.5);
  assert.ok(contrast([119, 119, 119], [255, 255, 255]) < 4.5);
});

test('the order of the two colours does not change the ratio', () => {
  const one = contrast([10, 20, 30], [240, 240, 240]);
  const other = contrast([240, 240, 240], [10, 20, 30]);

  assert.equal(one.toFixed(4), other.toFixed(4));
});

test('white is brighter than black', () => {
  assert.ok(luminance([255, 255, 255]) > luminance([0, 0, 0]));
});

test('a hex colour is read in both lengths', () => {
  assert.deepEqual(parseColor('#fff').rgb, [255, 255, 255]);
  assert.deepEqual(parseColor('#0a2a28').rgb, [10, 42, 40]);
  assert.equal(parseColor('#0a2a28').alpha, 1);
});

test('an rgba colour keeps its alpha', () => {
  const color = parseColor('rgba(10, 42, 40, 0.55)');

  assert.deepEqual(color.rgb, [10, 42, 40]);
  assert.equal(color.alpha, 0.55);
});

test('something that is not a colour is not guessed at', () => {
  assert.equal(parseColor('999px'), null);
  assert.equal(parseColor('cubic-bezier(0.16, 1, 0.3, 1)'), null);
});

test('a half transparent white over black lands in the middle', () => {
  const flat = flatten({ rgb: [255, 255, 255], alpha: 0.5 }, [0, 0, 0]);

  assert.deepEqual(flat, [128, 128, 128]);
});

test('an opaque colour ignores what is behind it', () => {
  assert.deepEqual(flatten({ rgb: [1, 2, 3], alpha: 1 }, [255, 255, 255]), [1, 2, 3]);
});

test('only the colour tokens are read out of a stylesheet', () => {
  const tokens = tokensIn(':root { --ink: #eaf5f1; --r-card: 18px; --glass: rgba(10, 42, 40, 0.55); }');

  assert.equal(tokens.size, 2);
  assert.deepEqual(tokens.get('ink').rgb, [234, 245, 241]);
  assert.equal(tokens.get('glass').alpha, 0.55);
});

test('a stack of layers is flattened back to front', () => {
  const tokens = tokensIn(':root { --front: rgba(255, 255, 255, 0.5); --back: #000000; }');

  assert.deepEqual(resolve(['front', 'back'], tokens), [128, 128, 128]);
});

test('a token that was renamed stops the run instead of being skipped', () => {
  const tokens = tokensIn(':root { --ink: #000000; }');

  assert.throws(() => resolve(['night'], tokens), /--night is not declared/);
});

test('a pair that is too close is reported as a failure', () => {
  const surface = {
    label: 'fixture',
    pairs: [{ text: 'faint', on: ['paper'], what: 'grey on white' }],
  };

  const rows = measure(surface, ':root { --faint: #aaaaaa; --paper: #ffffff; }');

  assert.equal(rows.length, 1);
  assert.ok(rows[0].ratio < 4.5, `expected a failing ratio, got ${rows[0].ratio}`);
});

test('a pair that is far apart passes', () => {
  const surface = {
    label: 'fixture',
    pairs: [{ text: 'strong', on: ['paper'], what: 'near black on white' }],
  };

  const rows = measure(surface, ':root { --strong: #111111; --paper: #ffffff; }');

  assert.ok(rows[0].ratio >= 4.5);
});
