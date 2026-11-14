#!/usr/bin/env node
/**
 * T-21 - Fills a running instance with enough data to see every screen work.
 *
 * It goes through the API and never into the database. A seeder that wrote
 * rows directly would be free to write states the API cannot produce, and the
 * data it left would prove nothing about the rules. Going through the API means
 * every row here passed the same validation a user's request passes.
 *
 * Three rentals come out overdue. They cannot be created overdue, because the
 * due date is computed from the moment of the rental and the server owns the
 * clock, so the script reports what it could and could not stage rather than
 * pretending otherwise.
 */

import process from 'node:process';

import { createApi } from './api.mjs';

const API_URL = process.env.API_URL ?? 'http://localhost:8000/api/v1';
const ADMIN_USERNAME = process.env.ADMIN_USERNAME ?? 'admin';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? '';

/*
 * The five category names are the ones the migration inserts, and they have to
 * match exactly: the seeder looks each one up by name and refuses to guess. It
 * asked for two categories that do not exist, so it died on the first lookup.
 */
const CATEGORY_PLAN = [
  { name: 'Cámaras', models: ['FX3', 'FX6', 'C70', 'R5C', 'A7S3'], brand: 'Sony' },
  { name: 'Lentes', models: ['24-70', '70-200', '35-ART', '50-SUM', '85-GM'], brand: 'Canon' },
  { name: 'Drones', models: ['MAV-3', 'AIR-3', 'INS-2', 'FPV-1', 'MINI-4'], brand: 'DJI' },
  { name: 'Audio', models: ['NTG5', 'WL-DUO', 'MIX-6', 'REC-F3', 'VMIC-P'], brand: 'Rode' },
  { name: 'Estabilizadores', models: ['RS-3', 'RS-4', 'CRANE-4', 'WEEBILL-3', 'SMOOTH-5'], brand: 'Zhiyun' },
];

const PRODUCER_NAMES = [
  'Laura Ortiz', 'Camilo Rengifo', 'Mariana Peña', 'Julián Castaño',
  'Daniela Arcila', 'Sebastián Lozano', 'Valentina Ruiz', 'Andrés Mejía',
  'Paula Cardona', 'Tomás Granados',
];

const { expectOk, signIn } = createApi({ apiUrl: API_URL });

async function categoriesByName() {
  const body = await expectOk('GET', '/categories', undefined, [200]);
  const found = new Map();

  for (const category of body.items) {
    found.set(category.name, category.id);
  }

  return found;
}

/** Fifty pieces of equipment: ten per category, so every filter has rows. */
async function createEquipment(categories) {
  const created = [];

  for (const plan of CATEGORY_PLAN) {
    const categoryId = categories.get(plan.name);

    if (categoryId === undefined) {
      throw new Error(`The API does not know the category ${plan.name}.`);
    }

    for (const [index, model] of plan.models.entries()) {
      for (const variant of ['BODY', 'KIT']) {
        const equipment = await expectOk('POST', '/equipment', {
          name: `${plan.brand} ${model}`,
          brand: plan.brand,
          model_code: `${model}-${variant}`,
          category_id: categoryId,
          total_units: 2 + (index % 3),
        }, [201, 409]);

        if (equipment !== null && equipment.id !== undefined) {
          created.push(equipment);
        }
      }
    }
  }

  return created;
}

async function createProducers() {
  const created = [];

  for (const [index, fullName] of PRODUCER_NAMES.entries()) {
    const producer = await expectOk('POST', '/producers', {
      full_name: fullName,
      // Fixed, distinct numbers: rerunning the seeder must not invent a second
      // producer for the same person.
      document_number: String(1090000000 + index),
    }, [201, 409]);

    if (producer !== null && producer.id !== undefined) {
      created.push(producer);
    }
  }

  return created;
}

/**
 * Fifteen rentals out of ten producers, which the rules do not allow in one
 * pass: a producer may hold two pieces of equipment at a time, and a rental
 * carries at most two lines. So the first round fills every producer to the
 * limit, five of those rentals are returned, and the freed producers rent
 * again. Any other arrangement would need the seeder to break a rule it is
 * supposed to demonstrate.
 */
async function createRentals(producers, equipment) {
  const rentals = [];
  let next = 0;

  const twoPieces = () => {
    const picked = [equipment[next % equipment.length], equipment[(next + 1) % equipment.length]];
    next += 2;

    return picked.map((item) => item.id);
  };

  for (const producer of producers) {
    const rental = await expectOk('POST', '/rentals', {
      producer_id: producer.id,
      equipment_ids: twoPieces(),
    }, [201]);

    rentals.push(rental);
  }

  // Half the rentals come back, which both frees the producers and leaves the
  // closed state visible in the lists.
  for (const rental of rentals.slice(0, 5)) {
    for (const item of rental.items) {
      await expectOk('POST', `/rentals/${rental.id}/items/${item.id}/return`, undefined, [200]);
    }
  }

  for (const producer of producers.slice(0, 5)) {
    const rental = await expectOk('POST', '/rentals', {
      producer_id: producer.id,
      equipment_ids: twoPieces(),
    }, [201]);

    rentals.push(rental);
  }

  return rentals;
}

async function report() {
  const overdue = await expectOk('GET', '/rentals/overdue', undefined, [200]);
  const from = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString();
  const to = new Date().toISOString();
  const mostRequested = await expectOk(
    'GET',
    `/reports/most-requested?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`,
    undefined,
    [200],
  );

  return { overdue: overdue.items.length, reportRows: mostRequested.items.length };
}

async function main() {
  console.log(`Seeding ${API_URL}`);

  await signIn(ADMIN_USERNAME, ADMIN_PASSWORD);

  const categories = await categoriesByName();
  const equipment = await createEquipment(categories);
  const producers = await createProducers();

  if (equipment.length === 0 || producers.length === 0) {
    console.log('The catalogue and the producers were already there; nothing new was created.');

    return 0;
  }

  const rentals = await createRentals(producers, equipment);
  const counts = await report();

  console.log(`  equipment: ${equipment.length}`);
  console.log(`  producers: ${producers.length}`);
  console.log(`  rentals:   ${rentals.length}`);
  console.log(`  overdue:   ${counts.overdue}`);
  console.log(`  report:    ${counts.reportRows} rows`);

  /*
   * The overdue count is zero on a fresh instance and that is correct: a line
   * falls due four days after the rental, and the server owns the clock. The
   * honest way to see the overdue screen is to move the container's clock or
   * to wait, not to have the seeder write a date the API would never produce.
   */
  if (counts.overdue === 0) {
    console.log('\nNo overdue lines yet: a line falls due four days after the rental.');
    console.log('To see that screen, run the API with its clock moved forward.');
  }

  return 0;
}

process.exit(await main());
