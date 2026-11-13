import { useState } from 'react';
import { Empty, Failure, Loading } from '../components/Feedback.jsx';
import { useList } from '../useList.js';
import { dayToInstant } from '../format.js';

function monthsAgo(count) {
  const moment = new Date();

  moment.setMonth(moment.getMonth() - count);

  return moment.toISOString().slice(0, 10);
}

/**
 * US-10 - Most requested equipment over a period.
 *
 * The range defaults to the last three months rather than opening empty: a
 * report that needs two dates before it shows anything is a report nobody runs.
 * The names come from the rental lines, not from the catalogue, so a closed
 * period keeps reading what the equipment was called at the time.
 */
export function MostRequested({ client }) {
  const [range, setRange] = useState({ from: monthsAgo(3), to: new Date().toISOString().slice(0, 10) });
  const [applied, setApplied] = useState(range);

  const report = useList(
    () =>
      client.get('/reports/most-requested', {
        query: { from: dayToInstant(applied.from), to: dayToInstant(applied.to, 'end') },
      }),
    [client, applied.from, applied.to],
  );

  const rows = report.data?.items ?? [];
  const mostRequested = rows[0]?.times_rented ?? 0;

  return (
    <>
      <h2>Más solicitados</h2>
      <p className="hint">Cuántas veces salió cada equipo en el periodo. Sin datos de productores.</p>

      <form
        className="panel"
        onSubmit={(event) => {
          event.preventDefault();
          setApplied(range);
        }}
      >
        <div className="filters">
          <div>
            <label htmlFor="report-from">Desde</label>
            <input
              id="report-from"
              type="date"
              value={range.from}
              onChange={(event) => setRange((previous) => ({ ...previous, from: event.target.value }))}
              required
            />
          </div>
          <div>
            <label htmlFor="report-to">Hasta</label>
            <input
              id="report-to"
              type="date"
              value={range.to}
              onChange={(event) => setRange((previous) => ({ ...previous, to: event.target.value }))}
              required
            />
          </div>
          <div>
            <button type="submit" disabled={report.busy}>
              {report.busy ? 'Calculando…' : 'Ver reporte'}
            </button>
          </div>
        </div>
      </form>

      <Failure error={report.error} />

      <Loading busy={report.busy && report.data === null}>
        {rows.length === 0 ? (
          <Empty>No hubo alquileres en ese periodo.</Empty>
        ) : (
          <div className="panel">
            <table>
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Categoría</th>
                  <th>Veces alquilado</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.equipment_id}>
                    <td>{row.equipment_name}</td>
                    <td>{row.category_name}</td>
                    <td>
                      {row.times_rented}
                      {/* A bar read off the top row: the shape of the list matters more than the number. */}
                      <div
                        aria-hidden="true"
                        style={{
                          marginTop: 4,
                          height: 6,
                          borderRadius: 3,
                          background: 'var(--accent)',
                          width: Math.round((row.times_rented / Math.max(mostRequested, 1)) * 100) + '%',
                        }}
                      />
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
