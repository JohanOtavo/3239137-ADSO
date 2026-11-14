import assert from 'node:assert/strict';
import { test } from 'node:test';

import { equipmentFor, inBatches, modelCodeFor, percentile, searchPaths } from './load-search.mjs';

/*
 * The parts worth testing are the ones where a mistake is silent: a percentile
 * off by one reports a budget that was never met, and a model code the domain
 * refuses stops the run with a 422 that looks like an API fault.
 */

test('the model code fits what the domain accepts', () => {
  for (const index of [0, 1, 4999, 999999]) {
    const code = modelCodeFor(index);

    assert.match(code, /^[A-Z0-9-]{4,20}$/);
  }
});

test('every piece of equipment in the fill carries a distinct code', () => {
  const codes = new Set();

  for (let index = 0; index < 5000; index += 1) {
    codes.add(modelCodeFor(index));
  }

  assert.equal(codes.size, 5000);
});

test('the equipment spreads over the categories it was given', () => {
  const ids = ['one', 'two', 'three'];
  const used = new Set();

  for (let index = 0; index < 9; index += 1) {
    used.add(equipmentFor(index, ids).category_id);
  }

  assert.deepEqual([...used].sort(), ['one', 'three', 'two']);
});

test('the total units stay inside what the domain allows', () => {
  for (let index = 0; index < 100; index += 1) {
    const units = equipmentFor(index, ['one']).total_units;

    assert.ok(units >= 1, `index ${index} asked for ${units} units`);
  }
});

test('the percentile is a value some request actually took', () => {
  const durations = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

  assert.equal(percentile(durations, 0.5), 50);
  assert.equal(percentile(durations, 0.95), 100);
  assert.equal(percentile(durations, 0.1), 10);
});

test('the percentile does not care about the order it was handed', () => {
  assert.equal(percentile([90, 10, 50, 30, 70], 0.5), 50);
});

test('an empty sample reports zero instead of NaN', () => {
  assert.equal(percentile([], 0.95), 0);
});

test('the paths cover a plain page, a term, a category and a deep page', () => {
  const labels = searchPaths(['one', 'two']).map((path) => path.label);

  assert.ok(labels.includes('first page, no filter'));
  assert.ok(labels.includes('deep page'));
  assert.equal(searchPaths(['one', 'two']).length, 6);
});

test('the batches run every item and keep the order of the results', async () => {
  const seen = [];

  const results = await inBatches([1, 2, 3, 4, 5], 2, async (value) => {
    seen.push(value);

    return value * 2;
  });

  assert.deepEqual(results, [2, 4, 6, 8, 10]);
  assert.equal(seen.length, 5);
});

test('a rejection inside a batch is not swallowed', async () => {
  await assert.rejects(
    () => inBatches([1, 2], 2, async (value) => {
      if (value === 2) {
        throw new Error('the API refused');
      }

      return value;
    }),
    /the API refused/,
  );
});
