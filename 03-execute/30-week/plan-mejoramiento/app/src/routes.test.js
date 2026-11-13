import { describe, expect, test } from 'vitest';
import { isReachable, routeFromHash, routesFor } from './routes.js';

describe('reading the hash', () => {
  test('an empty hash lands on the catalogue', () => {
    expect(routeFromHash('')).toBe('equipment');
  });

  test('a known route is read with or without the slash', () => {
    expect(routeFromHash('#overdue')).toBe('overdue');
    expect(routeFromHash('#/overdue')).toBe('overdue');
  });

  /** A typed or stale hash must not leave the operator on a blank screen. */
  test('an unknown route falls back instead of rendering nothing', () => {
    expect(routeFromHash('#/invented')).toBe('equipment');
  });
});

describe('the menu', () => {
  test('an advisor does not see the admin screens', () => {
    const ids = routesFor('advisor').map((route) => route.id);

    expect(ids).not.toContain('users');
    expect(ids).toContain('rentals');
  });

  test('an admin sees every screen', () => {
    expect(routesFor('admin')).toHaveLength(7);
  });

  test('a route outside the role is reported as unreachable', () => {
    expect(isReachable('users', 'advisor')).toBe(false);
    expect(isReachable('users', 'admin')).toBe(true);
  });
});
