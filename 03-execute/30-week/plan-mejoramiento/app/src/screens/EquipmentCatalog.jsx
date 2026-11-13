import { useState } from 'react';
import { Empty, Failure, Loading } from '../components/Feedback.jsx';
import { Pager } from '../components/Pager.jsx';
import { useList } from '../useList.js';
import { EquipmentForm } from './EquipmentForm.jsx';

/** US-04 and US-03 - Search the catalogue; an admin also creates and retires. */
export function EquipmentCatalog({ client, role }) {
  const [filters, setFilters] = useState({ q: '', categoryId: '' });
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState(null);

  const categories = useList(() => client.get('/categories'), [client]);

  const equipment = useList(
    () => client.get('/equipment', { query: { q: filters.q, category_id: filters.categoryId, page } }),
    [client, filters.q, filters.categoryId, page],
  );

  // Changing a filter goes back to the first page. Staying on page four of a
  // narrower result is how a search looks empty when it is not.
  function filter(name, value) {
    setFilters((previous) => ({ ...previous, [name]: value }));
    setPage(1);
  }

  async function retire(item) {
    if (!window.confirm('¿Dar de baja «' + item.name + '»?')) {
      return;
    }

    try {
      await client.delete('/equipment/' + item.id);
      equipment.reload();
    } catch (failure) {
      window.alert(failure.message);
    }
  }

  return (
    <>
      <h2>Catálogo</h2>
      <p className="hint">Equipo activo. Las unidades disponibles las calcula el servidor.</p>

      {role === 'admin' && (
        <EquipmentForm
          client={client}
          categories={categories.data?.items ?? []}
          editing={editing}
          onDone={() => {
            setEditing(null);
            equipment.reload();
          }}
          onCancel={() => setEditing(null)}
        />
      )}

      <div className="panel">
        <div className="filters">
          <div>
            <label htmlFor="q">Nombre o marca</label>
            <input
              id="q"
              value={filters.q}
              onChange={(event) => filter('q', event.target.value)}
              placeholder="Sony, lente, dron…"
            />
          </div>
          <div>
            <label htmlFor="category">Categoría</label>
            <select
              id="category"
              value={filters.categoryId}
              onChange={(event) => filter('categoryId', event.target.value)}
            >
              <option value="">Todas</option>
              {(categories.data?.items ?? []).map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      <Failure error={equipment.error} />

      <Loading busy={equipment.busy && equipment.data === null}>
        {(equipment.data?.items ?? []).length === 0 ? (
          <Empty>No hay equipo que coincida con el filtro.</Empty>
        ) : (
          <div className="panel">
            <table>
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Código</th>
                  <th>Categoría</th>
                  <th>Disponibles</th>
                  {role === 'admin' && <th />}
                </tr>
              </thead>
              <tbody>
                {equipment.data.items.map((item) => (
                  <tr key={item.id}>
                    <td>
                      {item.name}
                      <br />
                      <span className="hint">{item.brand}</span>
                    </td>
                    <td>{item.model_code}</td>
                    <td>{item.category.name}</td>
                    <td>
                      {item.available_units} de {item.total_units}
                    </td>
                    {role === 'admin' && (
                      <td>
                        <button className="secondary" type="button" onClick={() => setEditing(item)}>
                          Editar
                        </button>{' '}
                        <button className="secondary" type="button" onClick={() => retire(item)}>
                          Dar de baja
                        </button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>

            <Pager
              page={equipment.data.page}
              size={equipment.data.size}
              total={equipment.data.total}
              busy={equipment.busy}
              onPage={setPage}
            />
          </div>
        )}
      </Loading>
    </>
  );
}
