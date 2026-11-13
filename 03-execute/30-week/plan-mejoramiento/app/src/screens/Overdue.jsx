import { Empty, Failure, Loading } from '../components/Feedback.jsx';
import { useList } from '../useList.js';
import { formatDays, formatInstant } from '../format.js';

/**
 * US-08 - Lines past their due date.
 *
 * Not paged, on purpose: the list is meant to be short, and an overdue list
 * long enough to need paging is a problem to act on rather than to browse.
 */
export function Overdue({ client }) {
  const overdue = useList(() => client.get('/rentals/overdue'), [client]);
  const rows = overdue.data?.items ?? [];

  return (
    <>
      <h2>Vencidos</h2>
      <p className="hint">Equipo que pasó su fecha de devolución y sigue afuera.</p>

      <Failure error={overdue.error} />

      <Loading busy={overdue.busy && overdue.data === null}>
        {rows.length === 0 ? (
          <Empty>No hay equipo vencido. Nada por reclamar.</Empty>
        ) : (
          <div className="panel">
            <p className="notice error" role="status">
              {rows.length === 1 ? 'Hay 1 línea vencida.' : 'Hay ' + rows.length + ' líneas vencidas.'}
            </p>

            <table>
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Productor</th>
                  <th>Venció</th>
                  <th>Atraso</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.item_id}>
                    <td>{row.equipment_name}</td>
                    <td>{row.producer_name}</td>
                    <td>{formatInstant(row.due_at)}</td>
                    <td>
                      <span className="tag overdue">{formatDays(row.days_overdue)}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Loading>
    </>
  );
}
