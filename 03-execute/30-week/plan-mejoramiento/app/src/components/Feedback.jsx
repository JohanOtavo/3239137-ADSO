/**
 * The three things every screen has to say: it is loading, it failed, or it
 * found nothing.
 *
 * They live in one place because "nothing found" and "the request failed" look
 * the same on an empty table, and telling them apart is the difference between
 * an operator retrying and an operator widening the filter.
 */
export function Failure({ error }) {
  if (error === null || error === undefined) {
    return null;
  }

  const issues = typeof error.issuesByField === 'function' ? error.details : [];

  return (
    <p className="notice error" role="alert">
      {error.message}
      {issues.length > 0 && (
        <span>
          {' '}
          ({issues.map((issue) => issue.field + ': ' + issue.issue).join('; ')})
        </span>
      )}
    </p>
  );
}

export function Empty({ children }) {
  return <p className="notice empty">{children}</p>;
}

export function Done({ children }) {
  if (children === null || children === undefined) {
    return null;
  }

  return (
    <p className="notice ok" role="status">
      {children}
    </p>
  );
}

export function Loading({ busy, children }) {
  return busy ? <p className="hint">Cargando…</p> : children;
}
