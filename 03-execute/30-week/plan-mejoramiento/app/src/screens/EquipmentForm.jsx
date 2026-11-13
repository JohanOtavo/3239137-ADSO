import { useEffect, useState } from 'react';
import { Done, Failure } from '../components/Feedback.jsx';
import { Field } from '../components/Field.jsx';

const BLANK = { name: '', brand: '', model_code: '', category_id: '', total_units: '' };

/**
 * US-03 - Creating and replacing equipment, admin only.
 *
 * The model code is editable when creating and read-only when editing: it
 * identifies the equipment, and the API refuses to change it (AC-03.3). Showing
 * it as editable and then failing would be a worse way to say the same thing.
 */
export function EquipmentForm({ client, categories, editing, onDone, onCancel }) {
  const [form, setForm] = useState(BLANK);
  const [error, setError] = useState(null);
  const [done, setDone] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (editing === null) {
      setForm(BLANK);

      return;
    }

    setForm({
      name: editing.name,
      brand: editing.brand,
      model_code: editing.model_code,
      category_id: editing.category.id,
      total_units: String(editing.total_units),
    });
    setError(null);
    setDone(null);
  }, [editing]);

  const change = (name, value) => setForm((previous) => ({ ...previous, [name]: value }));

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setDone(null);

    const payload = {
      name: form.name,
      brand: form.brand,
      category_id: form.category_id,
      total_units: Number(form.total_units),
    };

    try {
      if (editing === null) {
        await client.post('/equipment', { ...payload, model_code: form.model_code });
        setDone('Equipo registrado.');
      } else {
        await client.put('/equipment/' + editing.id, payload);
        setDone('Equipo actualizado.');
      }

      setForm(BLANK);
      onDone();
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy(false);
    }
  }

  const issues = error?.issuesByField?.() ?? {};

  return (
    <form className="panel stack" onSubmit={submit}>
      <strong>{editing === null ? 'Registrar equipo' : 'Editar «' + editing.name + '»'}</strong>

      <Failure error={error} />
      <Done>{done}</Done>

      <div className="filters">
        <Field label="Nombre" name="name" value={form.name} onChange={change} issues={issues} required />
        <Field label="Marca" name="brand" value={form.brand} onChange={change} issues={issues} required />
        <Field
          label="Código de modelo"
          name="model_code"
          value={form.model_code}
          onChange={change}
          issues={issues}
          readOnly={editing !== null}
          required
        />
        <div>
          <label htmlFor="category_id">Categoría</label>
          <select
            id="category_id"
            value={form.category_id}
            onChange={(event) => change('category_id', event.target.value)}
            required
          >
            <option value="">Seleccione…</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          {issues.category_id !== undefined && <p className="field-issue">{issues.category_id}</p>}
        </div>
        <Field
          label="Unidades totales"
          name="total_units"
          type="number"
          min="1"
          value={form.total_units}
          onChange={change}
          issues={issues}
          required
        />
        <div>
          <button type="submit" disabled={busy}>
            {busy ? 'Guardando…' : 'Guardar'}
          </button>
          {editing !== null && (
            <>
              {' '}
              <button className="secondary" type="button" onClick={onCancel}>
                Cancelar
              </button>
            </>
          )}
        </div>
      </div>
    </form>
  );
}
