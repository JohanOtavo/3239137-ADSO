import { describe, expect, test } from 'vitest';
import { dayToInstant, formatDays, formatInstant, formatStatus } from './format.js';

describe('instants', () => {
  test('an absent date shows a dash rather than "Invalid Date"', () => {
    expect(formatInstant(null)).toBe('—');
    expect(formatInstant(undefined)).toBe('—');
    expect(formatInstant('')).toBe('—');
  });

  /** A date the browser cannot read is more useful raw than blank. */
  test('an unreadable date falls back to the raw value', () => {
    expect(formatInstant('not-a-date')).toBe('not-a-date');
  });

  test('a UTC instant is rendered, not passed through', () => {
    const rendered = formatInstant('2026-11-12T14:15:00Z');

    expect(rendered).not.toBe('2026-11-12T14:15:00Z');
    expect(rendered).toMatch(/2026/);
  });
});

describe('states', () => {
  test('the three states of the contract have a label', () => {
    expect(formatStatus('open')).toBe('Abierto');
    expect(formatStatus('overdue')).toBe('Vencido');
    expect(formatStatus('closed')).toBe('Cerrado');
  });

  /** An unknown state is shown as it came, so a contract change is visible. */
  test('an unknown state is not hidden', () => {
    expect(formatStatus('paused')).toBe('paused');
  });
});

describe('days', () => {
  test('one day is singular', () => {
    expect(formatDays(1)).toBe('1 día');
  });

  test('anything else is plural', () => {
    expect(formatDays(0)).toBe('0 días');
    expect(formatDays(5)).toBe('5 días');
  });
});

describe('a day from a date input', () => {
  test('an empty day is sent as no filter at all', () => {
    expect(dayToInstant('')).toBe('');
    expect(dayToInstant(null)).toBe('');
  });

  test('the start of a range is the first moment of the day', () => {
    expect(dayToInstant('2026-11-12')).toBe('2026-11-12T00:00:00.000Z');
  });

  /**
   * The end has to be the last moment of the day. Stopping at 00:00 would drop
   * every rental recorded on the day the operator asked for.
   */
  test('the end of a range is the last moment of the day', () => {
    expect(dayToInstant('2026-11-12', 'end')).toBe('2026-11-12T23:59:59.999Z');
  });

  test('an unreadable day is sent as no filter rather than as a bad date', () => {
    expect(dayToInstant('12/11/2026')).toBe('');
  });
});
