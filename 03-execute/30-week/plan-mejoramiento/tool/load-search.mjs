#!/usr/bin/env node
/**
 * NFR-1 - The equipment search answers in under 300 ms with 5,000 pieces.
 *
 * It fills the catalogue through the API and then measures the search, so the
 * number it reports is what a user's browser would wait for: routing,
 * middleware, token check, the query and the serialisation, over the network.
 * A query timed inside the database would answer a different question.
 *
 * The run reports the 95th percentile per access path and fails when the slow
 * one is over the budget. A mean would hide exactly the requests that matter:
 * nobody notices the fast half.
 */

import process from 'node:process';
import { fileURLToPath } from 'node:url';

import { createApi, queryString } from './api.mjs';

const API_URL = process.env.API_URL ?? 'http://localhost:8000/api/v1';
const ADMIN_USERNAME = process.env.ADMIN_USERNAME ?? 'admin';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? '';

/** The figure NFR-1 names, in milliseconds. */
const BUDGET_MS = Number(process.env.BUDGET_MS ?? 300);

const TARGET_EQUIPMENT = Number(process.env.TARGET_EQUIPMENT ?? 5000);

const SAMPLES = Number(process.env.SAMPLES ?? 60);

/**
 * How many creations run at once while filling the catalogue.
 *
 * Not a measurement, just the loading dock: one at a time would take minutes,
 * and hundreds at once would measure the runner's socket limit instead of the
 * API.
 */
const FILL_CONCURRENCY = Number(process.env.FILL_CONCURRENCY ?? 16);

const BRANDS = ['Sony', 'Canon', 'DJI', 'Rode', 'Zhiyun'];

const api = createApi({ apiUrl: API_URL });

/** A code the domain accepts: four to twenty characters of A-Z, digits or hyphens. */
export function modelCodeFor(index) {
  return `LOAD-${String(index).padStart(6, '0')}`;
}

export function equipmentFor(index, categoryIds) {
  const brand = BRANDS[index % BRANDS.length];

  return {
    name: `${brand} modelo ${index}`,
    brand,
    model_code: modelCodeFor(index),
    category_id: categoryIds[index % categoryIds.length],
    total_units: 1 + (index % 4),
  };
}

/**
 * The value at a percentile, nearest rank over a sorted copy.
 *
 * No interpolation on purpose: an interpolated p95 is a number no request ever
 * took, and the point of this report is to name a request that was slow.
 */
export function percentile(durations, fraction) {
  if (durations.length === 0) {
    return 0;
  }

  const sorted = [...durations].sort((left, right) => left - right);
  const rank = Math.ceil(fraction * sorted.length) - 1;

  return sorted[Math.min(Math.max(rank, 0), sorted.length - 1)];
}

/** Runs the tasks a few at a time, in order, and keeps every rejection. */
export async function inBatches(items, size, run) {
  const results = [];

  for (let start = 0; start < items.length; start += size) {
    const batch = items.slice(start, start + size);

    results.push(...await Promise.all(batch.map(run)));
  }

  return results;
}

/**
 * The access paths the search really gets asked for.
 *
 * The plain first page is the cheapest and the deep page the dearest, because
 * an offset still walks the rows it skips. Measuring only the first page would
 * report a budget nobody is ever inside.
 */
export function searchPaths(categoryIds) {
  return [
    { label: 'first page, no filter', query: { page: 1, size: 20 } },
    { label: 'name match', query: { q: 'modelo 4', page: 1, size: 20 } },
    { label: 'brand match', query: { q: 'zhiyun', page: 1, size: 20 } },
    { label: 'one category', query: { category_id: categoryIds[0], page: 1, size: 20 } },
    { label: 'category and term', query: { category_id: categoryIds[1], q: 'canon', page: 1, size: 20 } },
    { label: 'deep page', query: { page: 40, size: 50 } },
  ];
}

async function categoryIds() {
  const body = await api.expectOk('GET', '/categories', undefined, [200]);

  return body.items.map((category) => category.id);
}

async function countEquipment() {
  const body = await api.expectOk('GET', '/equipment?size=1', undefined, [200]);

  return body.total;
}

/**
 * Brings the catalogue up to the target, creating only what is missing.
 *
 * A 409 is accepted and counted as present: the run has to be repeatable
 * against an instance it already filled, or nobody will run it twice.
 */
async function fillCatalogue(ids) {
  const already = await countEquipment();

  if (already >= TARGET_EQUIPMENT) {
    return { already, created: 0 };
  }

  const missing = [];

  for (let index = already; index < TARGET_EQUIPMENT; index += 1) {
    missing.push(index);
  }

  process.stdout.write(`Filling ${missing.length} pieces of equipment`);

  let created = 0;

  await inBatches(missing, FILL_CONCURRENCY, async (index) => {
    const body = await api.expectOk('POST', '/equipment', equipmentFor(index, ids), [201, 409]);

    if (body !== null && body.id !== undefined) {
      created += 1;
    }

    if (created % 500 === 0) {
      process.stdout.write('.');
    }
  });

  process.stdout.write('\n');

  return { already, created };
}

/** One request, timed end to end. The status is checked so a 500 never reads as fast. */
async function timeSearch(query) {
  const started = performance.now();

  await api.expectOk('GET', `/equipment${queryString(query)}`, undefined, [200]);

  return performance.now() - started;
}

async function measure(path) {
  // One warm request outside the sample: the first call of a path pays for the
  // connection and the query plan, and that cost is not what a user sees all
  // day.
  await timeSearch(path.query);

  const durations = [];

  for (let sample = 0; sample < SAMPLES; sample += 1) {
    durations.push(await timeSearch(path.query));
  }

  return {
    label: path.label,
    p50: percentile(durations, 0.5),
    p95: percentile(durations, 0.95),
    worst: Math.max(...durations),
  };
}

function milliseconds(value) {
  return `${value.toFixed(1)} ms`;
}

function reportRow(row) {
  const verdict = row.p95 <= BUDGET_MS ? 'within' : 'OVER';

  console.log(
    `  ${row.label.padEnd(22)} p50 ${milliseconds(row.p50).padStart(9)}`
    + `  p95 ${milliseconds(row.p95).padStart(9)}`
    + `  worst ${milliseconds(row.worst).padStart(9)}  ${verdict}`,
  );
}

async function main() {
  if (ADMIN_PASSWORD === '') {
    console.error('ADMIN_PASSWORD is not set; the load test has no way to sign in.');

    return 1;
  }

  console.log(`Measuring ${API_URL} against a ${BUDGET_MS} ms budget (NFR-1)`);

  await api.signIn(ADMIN_USERNAME, ADMIN_PASSWORD);

  const ids = await categoryIds();
  const filled = await fillCatalogue(ids);
  const total = await countEquipment();

  console.log(`\nCatalogue: ${total} pieces (${filled.created} created in this run)`);
  console.log(`${SAMPLES} samples per path\n`);

  const rows = [];

  for (const path of searchPaths(ids)) {
    rows.push(await measure(path));
  }

  rows.forEach(reportRow);

  if (total < TARGET_EQUIPMENT) {
    console.log(`\nThe catalogue holds ${total} pieces, under the ${TARGET_EQUIPMENT} NFR-1 asks for.`);
    console.log('The numbers above are real but they do not answer the requirement.');

    return 1;
  }

  const over = rows.filter((row) => row.p95 > BUDGET_MS);

  if (over.length > 0) {
    console.log(`\nNFR-1 not met: ${over.map((row) => row.label).join(', ')} over ${BUDGET_MS} ms at p95.`);

    return 1;
  }

  console.log(`\nNFR-1 met: every path under ${BUDGET_MS} ms at the 95th percentile.`);

  return 0;
}

/*
 * Only when run as a script. The test imports the pure parts of this file, and
 * an unguarded call here would have the test try to reach an API.
 */
if (process.argv[1] === fileURLToPath(import.meta.url)) {
  process.exit(await main());
}
