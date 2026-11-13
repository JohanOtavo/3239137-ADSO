/**
 * T-18 - The guard's own test.
 *
 * A guard nobody tested is a guard that silently passes everything, which is
 * the worst of the three possible states: the build is green and the rule is
 * not enforced. These two cases are the ones that matter, so both are checked:
 * Spanish in an identifier or a comment fails, and Spanish inside a message
 * does not.
 */

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';

function runOn(file) {
  return spawnSync(process.execPath, ['check-language.mjs', `fixtures/${file}`], {
    encoding: 'utf8',
  });
}

test('English code with a Spanish message passes', () => {
  const result = runOn('clean.php');

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /passed/);
});

test('a Spanish comment fails and names the line', () => {
  const result = runOn('spanish.php');

  assert.equal(result.status, 1);
  assert.match(result.stderr, /spanish\.php:3/);
  assert.match(result.stderr, new RegExp('equi' + 'po'));
});

test('a Spanish identifier fails even without a comment', () => {
  const result = runOn('spanish.php');

  assert.match(result.stderr, /spanish\.php:6/);
  assert.match(result.stderr, new RegExp('nom' + 'bre'));
});

/*
 * JSX needs its own cases. The text between tags is what a person reads, so it
 * is Spanish by contract, while the identifiers and comments around it are not.
 * A guard that read the text would flag every screen; a guard that skipped the
 * whole file would flag nothing.
 */
test('Spanish text between JSX tags passes', () => {
  const result = runOn('clean.jsx');

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /passed/);
});

test('a Spanish JSX component name and comment still fail', () => {
  const result = runOn('spanish.jsx');

  assert.equal(result.status, 1);
  assert.match(result.stderr, /spanish\.jsx:1/);
  assert.match(result.stderr, /spanish\.jsx:3/);
});
