import { useState } from 'react';
import { Done, Empty, Failure, Loading } from '../components/Feedback.jsx';
import { Field } from '../components/Field.jsx';
import { Pager } from '../components/Pager.jsx';
import { useList } from '../useList.js';

/** US-05 - Producers, with the count of equipment still out. */
export function Producers({ client }) {
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [form, setForm] = useState({ full_name: '', document_number: '' });
  const [error, setError] = useState(null);
  const [done, setDone] = useState(null);
  const [busy, setBusy] = useState(false);
  const [detail, setDetail] = useState(null);

  const producers = useList(
    () => client.get('/producers', { query: { q: query, page } }),
    [client, query, page],
  );

  const change = (name, value) => setForm((previous) => ({ ...previous, [name]: value }));

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setDone(null);

    try {
      await client.post('/producers', form);
      setForm({ full_name: '', document_number: '' });
      setDone('Productor registrado.');
      producers.reload();
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy(false);
    }
  }

  /*
   * The count of equipment out comes from the detail endpoint and not from the
   * list: it is the number an advisor needs before approving a rental, and
   * putting it in every row would mean a count query per row.
   */
  async function openDetail(producer) {
    setDetail({ id: producer.id, loading: true });

    try {
      setDetail({ ...(await client.get('/producers/' + producer.id)), loading: false });
    } catch (failure) {
      setDetail(null);
      window.alert(failure.message);
    }
  }

  const issues = error?.issuesByField?.() ?? {};

  return (
    <>
      <h2>Productores</h2>
      <p className="hint">Quien retira el equipo. El documento es único.</p>

      <form className="panel stack" onSubmit={submit}>
        <strong>Registrar productor</strong>

        <Failure error={error} />
        <Done>{done}</Done>

        <div className="filters">
          <Field
            label="Nombre completo"
            name="full_name"
            value={form.full_name}
            onChange={change}
            issues={issues}
            required
          />
          <Field
            label="Documento"
            name="document_number"
            value={form.document_number}
            onChange={change}
            issues={issues}
            required
          />
          <div>
            <button type="submit" disabled={busy}>
              {busy ? 'Guardando…' : 'Guardar'}
            </button>
          </div>
        </div>
      </form>

      <div className="panel">
        <div className="filters">
          <div>
            <label htmlFor="producer-q">Nombre o documento</label>
            <input
              id="producer-q"
              value={query}
              onChange={(event) => {
                setQuery(event.target.value);
                setPage(1);
              }}
            />
          </div>
        </div>
      </div>

      <Failure error={producers.error} />

      <Loading busy={producers.busy && producers.data === null}>
        {(producers.data?.items ?? []).length === 0 ? (
          <Empty>No hay productores que coincidan.</Empty>
        ) : (
          <div className="panel">
            <table>
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Documento</th>
                  <th>Equipo afuera</th>
                </tr>
              </thead>
              <tbody>
                {producers.data.items.map((producer) => (
                  <tr key={producer.id}>
                    <td>{producer.full_name}</td>
                    <td>{producer.document_number}</td>
                    <td>
                      {detail?.id === producer.id && detail.loading === false ? (
                        <strong>{detail.open_rentals}</strong>
                      ) : (
                        <button className="secondary" type="button" onClick={() => openDetail(producer)}>
                          Consultar
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <Pager
              page={producers.data.page}
              size={producers.data.size}
              total={producers.data.total}
              busy={producers.busy}
              onPage={setPage}
            />
          </div>
        )}
      </Loading>
    </>
  );
}
