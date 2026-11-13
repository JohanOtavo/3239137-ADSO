import { useState } from 'react';
import { Empty, Failure, Loading } from '../components/Feedback.jsx';
import { Pager } from '../components/Pager.jsx';
import { useList } from '../useList.js';
import { dayToInstant, formatInstant, formatStatus } from '../format.js';

/** US-07 and US-08 - Consulting rentals and returning one line at a time. */
export function Rentals({ client }) {
  const [filters, setFilters] = useState({ from: '', to: '', status: '' });
  const [page, setPage] = useState(1);
  const [returning, setReturning] = useState(null);

  const rentals = useList(
    () =>
      client.get('/rentals', {
        query: {
          from: dayToInstant(filters.from),
          to: dayToInstant(filters.to, 'end'),
          status: filters.status,
          page,
        },
      }),
    [client, filters.from, filters.to, filters.status, page],
  );

  function filter(name, value) {
    setFilters((previous) => ({ ...previous, [name]: value }));
    setPage(1);
  }

  /**
   * One line at a time, as the contract puts it. The button is disabled while
   * the request is in flight: a double click would send the same return twice,
   * and the second one answers 409.
   */
  async function returnItem(rental, item) {
    setReturning(item.id);

    try {
      await client.post('/rentals/' + rental.id + '/items/' + item.id + '/return');
      rentals.reload();
    } catch (failure) {
      window.alert(failure.message);
    } finally {
      setReturning(null);
    }
  }

  return (
    <>
      <h2>Alquileres</h2>
      <p className="hint">El estado lo calcula el servidor: abierto, vencido o cerrado.</p>

      <div className="panel">
        <div className="filters">
          <div>
            <label htmlFor="from">Desde</label>
            <input id="from" type="date" value={filters.from} onChange={(event) => filter('from', event.target.value)} />
          </div>
          <div>
            <label htmlFor="to">Hasta</label>
            <input id="to" type="date" value={filters.to} onChange={(event) => filter('to', event.target.value)} />
          </div>
          <div>
            <label htmlFor="status">Estado</label>
            <select id="status" value={filters.status} onChange={(event) => filter('status', event.target.value)}>
              <option value="">Todos</option>
              <option value="open">Abierto</option>
              <option value="overdue">Vencido</option>
              <option value="closed">Cerrado</option>
            </select>
          </div>
        </div>
      </div>

      <Failure error={rentals.error} />

      <Loading busy={rentals.busy && rentals.data === null}>
        {(rentals.data?.items ?? []).length === 0 ? (
          <Empty>No hay alquileres en ese rango.</Empty>
        ) : (
          <div className="panel">
            <table>
              <thead>
                <tr>
                  <th>Alquiler</th>
                  <th>Estado</th>
                  <th>Líneas</th>
                </tr>
              </thead>
              <tbody>
                {rentals.data.items.map((rental) => (
                  <tr key={rental.id}>
                    <td>
                      {formatInstant(rental.rented_at)}
                      <br />
                      <span className="hint">{rental.id.slice(0, 8)}</span>
                    </td>
                    <td>
                      <span className={'tag ' + rental.status}>{formatStatus(rental.status)}</span>
                    </td>
                    <td>
                      {rental.items.map((item) => (
                        <div key={item.id} style={{ marginBottom: 6 }}>
                          {item.equipment_name} <span className="hint">{item.model_code}</span>
                          <br />
                          <span className="hint">
                            Vence {formatInstant(item.due_at)} ·{' '}
                            {item.returned_at === null
                              ? 'sin devolver'
                              : 'devuelto ' + formatInstant(item.returned_at)}
                          </span>
                          {item.returned_at === null && (
                            <>
                              {' '}
                              <button
                                className="secondary"
                                type="button"
                                disabled={returning === item.id}
                                onClick={() => returnItem(rental, item)}
                              >
                                {returning === item.id ? 'Devolviendo…' : 'Devolver'}
                              </button>
                            </>
                          )}
                        </div>
                      ))}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <Pager
              page={rentals.data.page}
              size={rentals.data.size}
              total={rentals.data.total}
              busy={rentals.busy}
              onPage={setPage}
            />
          </div>
        )}
      </Loading>
    </>
  );
}

