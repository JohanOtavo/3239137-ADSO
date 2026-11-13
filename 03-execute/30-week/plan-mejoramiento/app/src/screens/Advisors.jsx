import { useState } from 'react';
import { Done, Failure } from '../components/Feedback.jsx';
import { Field } from '../components/Field.jsx';

/**
 * US-02 - An administrator creates an advisor.
 *
 * The role is fixed to advisor and not offered as a choice. The API refuses to
 * grant admin through any endpoint (AC-02.3), so a dropdown with that option
 * would be a control whose only purpose is to be rejected.
 */
export function Advisors({ client }) {
  const [form, setForm] = useState({ username: '', password: '' });
  const [error, setError] = useState(null);
  const [done, setDone] = useState(null);
  const [busy, setBusy] = useState(false);

  const change = (name, value) => setForm((previous) => ({ ...previous, [name]: value }));

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setDone(null);

    try {
      const created = await client.post('/users', { ...form, role: 'advisor' });

      setDone('Asesor «' + created.username + '» creado.');
      setForm({ username: '', password: '' });
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy(false);
    }
  }

  const issues = error?.issuesByField?.() ?? {};

  return (
    <>
      <h2>Asesores</h2>
      <p className="hint">
        El rol de administrador no se otorga por la API: lo provisiona el despliegue.
      </p>

      <form className="panel stack" onSubmit={submit}>
        <Failure error={error} />
        <Done>{done}</Done>

        <Field
          label="Usuario"
          name="username"
          value={form.username}
          onChange={change}
          issues={issues}
          autoComplete="off"
          required
        />
        <Field
          label="Contraseña"
          name="password"
          type="password"
          value={form.password}
          onChange={change}
          issues={issues}
          autoComplete="new-password"
          required
        />

        <div>
          <button type="submit" disabled={busy}>
            {busy ? 'Creando…' : 'Crear asesor'}
          </button>
        </div>
      </form>
    </>
  );
}
