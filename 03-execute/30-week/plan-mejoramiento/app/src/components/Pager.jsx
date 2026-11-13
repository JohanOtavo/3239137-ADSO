/**
 * Paging over the envelope every list endpoint returns.
 *
 * It computes the last page rather than trusting the caller, so a page beyond
 * the end cannot be reached by clicking.
 */
export function Pager({ page, size, total, onPage, busy }) {
  const lastPage = Math.max(1, Math.ceil(total / Math.max(size, 1)));
  const from = total === 0 ? 0 : (page - 1) * size + 1;
  const to = Math.min(page * size, total);

  return (
    <div className="pager">
      <button
        className="secondary"
        type="button"
        disabled={busy || page <= 1}
        onClick={() => onPage(page - 1)}
      >
        Anterior
      </button>
      <span>
        {from}–{to} de {total}
      </span>
      <button
        className="secondary"
        type="button"
        disabled={busy || page >= lastPage}
        onClick={() => onPage(page + 1)}
      >
        Siguiente
      </button>
    </div>
  );
}
