/**
 * Dates and states, formatted once.
 *
 * The API sends instants in UTC with a Z suffix and the operator reads local
 * time, so the conversion belongs in one place. Four screens doing it inline is
 * how one of them ends up showing UTC.
 */
const STATUS_LABELS = {
  open: 'Abierto',
  overdue: 'Vencido',
  closed: 'Cerrado',
};

export function formatInstant(value) {
  if (value === undefined || value === null || value === '') {
    return '—';
  }

  const moment = new Date(value);

  if (Number.isNaN(moment.getTime())) {
    // A date the server sent and the browser cannot read is not worth a crash:
    // the raw value is more useful on screen than a blank cell.
    return String(value);
  }

  return moment.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
}

export function formatStatus(status) {
  return STATUS_LABELS[status] ?? status;
}

/** Spanish needs the singular, and "1 días" is the kind of detail that gets noticed. */
export function formatDays(days) {
  return days === 1 ? '1 día' : days + ' días';
}

/**
 * A date input gives a day; the API wants an instant.
 *
 * The end of a range is widened to the last moment of that day, because a
 * filter that reads "hasta el 12" and silently stops at 00:00 misses every
 * rental recorded that day. The start is the first moment of its day.
 */
export function dayToInstant(day, edge = 'start') {
  if (day === undefined || day === null || day === '') {
    return '';
  }

  const time = edge === 'end' ? 'T23:59:59.999Z' : 'T00:00:00.000Z';
  const moment = new Date(day + time);

  return Number.isNaN(moment.getTime()) ? '' : moment.toISOString();
}
