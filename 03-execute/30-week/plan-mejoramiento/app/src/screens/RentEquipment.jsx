import { useState } from 'react';
import { Done, Failure, Loading } from '../components/Feedback.jsx';
import { useList } from '../useList.js';
import { formatInstant } from '../format.js';

const MAX_ITEMS = 2;

/**
 * US-06 - Recording a rental.
 *
 * The limits are shown, not enforced here. The server owns them: the producer
 * who already has two pieces out, the equipment with no units left, the
 * duplicate line. The screen states the rule so the operator is not surprised,
 * and reports the server's answer when it refuses.
 */
export function RentEquipment({ client }) {
  const [producerQuery, setProducerQuery] = useState('');
  const [producerId, setProducerId] = useState('');
  const [equipmentQuery, setEquipmentQuery] = useState('');
  const [picked, setPicked] = useState([]);
  const [error, setError] = useState(null);
  const [done, setDone] = useState(null);
  const [busy, setBusy] = useState(false);

  const producers = useList(
    () => client.get('/producers', { query: { q: producerQuery, size: 10 } }),
    [client, producerQuery],
  );

  const equipment = useList(
    () => client.get('/equipment', { query: { q: equipmentQuery, size: 10 } }),
    [client, equipmentQuery],
  );

  function pick(item) {
    if (picked.length >= MAX_ITEMS || picked.some((chosen) => chosen.id === item.id)) {
      return;
    }

    setPicked((previous) => [...previous, item]);
  }

  function drop(itemId) {
    setPicked((previous) => previous.filter((item) => item.id !== itemId));
  }

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setDone(null);

    try {
      const rental = await client.post('/rentals', {
        producer_id: producerId,
        equipment_ids: picked.map((item) => item.id),
      });

      setDone('Alquiler registrado. Vence el ' + formatInstant(rental.items[0]?.due_at) + '.');
      setPicked([]);
      setProducerId('');
      equipment.reload();
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy(false);
    }
  }

  const ready = producerId !== '' && picked.length > 0;

  return (
    <>
      <h2>Registrar alquiler</h2>
      <p className="hint">
        Hasta {MAX_ITEMS} equipos por alquiler, y hasta {MAX_ITEMS} equipos afuera por productor. Vence a los
        4 días.
      </p>

      <Failure error={error} />
      <Done>{done}</Done>

      <form className="panel stack" onSubmit={submit}>
        <div>
          <label htmlFor="rent-producer-q">Buscar productor</label>
          <input
            id="rent-producer-q"
            value={producerQuery}
            onChange={(event) => setProducerQuery(event.target.value)}
            placeholder="Nombre o documento"
          />
        </div>

        <Loading busy={producers.busy && producers.data === null}>
          <div>
            <label htmlFor="rent-producer">Productor</label>
            <select
              id="rent-producer"
              value={producerId}
              onChange={(event) => setProducerId(event.target.value)}
              required
            >
              <option value="">Seleccione…</option>
              {(producers.data?.items ?? []).map((producer) => (
                <option key={producer.id} value={producer.id}>
                  {producer.full_name} · {producer.document_number}
                </option>
              ))}
            </select>
          </div>
        </Loading>

        <div>
          <label htmlFor="rent-equipment-q">Buscar equipo</label>
          <input
            id="rent-equipment-q"
            value={equipmentQuery}
            onChange={(event) => setEquipmentQuery(event.target.value)}
            placeholder="Nombre o marca"
          />
        </div>

        <table>
          <thead>
            <tr>
              <th>Equipo</th>
              <th>Disponibles</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {(equipment.data?.items ?? []).map((item) => (
              <tr key={item.id}>
                <td>
                  {item.name} <span className="hint">{item.model_code}</span>
                </td>
                <td>{item.available_units}</td>
                <td>
                  <button
                    className="secondary"
                    type="button"
                    disabled={
                      item.available_units === 0 ||
                      picked.length >= MAX_ITEMS ||
                      picked.some((chosen) => chosen.id === item.id)
                    }
                    onClick={() => pick(item)}
                  >
                    Agregar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <div>
          <strong>Equipo elegido ({picked.length} de {MAX_ITEMS})</strong>
          {picked.length === 0 ? (
            <p className="hint">Agregue al menos un equipo.</p>
          ) : (
            <ul>
              {picked.map((item) => (
                <li key={item.id}>
                  {item.name} ({item.model_code}){' '}
                  <button className="secondary" type="button" onClick={() => drop(item.id)}>
                    Quitar
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div>
          <button type="submit" disabled={busy || !ready}>
            {busy ? 'Registrando…' : 'Registrar alquiler'}
          </button>
        </div>
      </form>
    </>
  );
}

