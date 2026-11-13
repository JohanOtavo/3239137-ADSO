import { useState } from 'react';
import { Failure } from '../components/Feedback.jsx';
import { Field } from '../components/Field.jsx';

/** US-01 - The only public screen. */
export function SignIn({ client, onSignedIn }) {
  const [form, setForm] = useState({ username: '', password: '' });
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  const change = (name, value) => setForm((previous) => ({ ...previous, [name]: value }));

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);

    try {
      const issued = await client.post('/auth/login', form);

      onSignedIn({ accessToken: issued.access_token, role: issued.role, username: form.username });
    } catch (failure) {
      // The message is the server's and says nothing about which half was
      // wrong, which is the point: naming the field would confirm a username.
      setError(failure);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="sign-in">
      <form className="panel stack" onSubmit={submit}>
        <div>
          <h2>film-gear</h2>
          <p className="hint">Alquiler de equipo de rodaje</p>
        </div>

        <Failure error={error} />

        <Field
          label="Usuario"
          name="username"
          value={form.username}
          onChange={change}
          autoComplete="username"
          required
        />
        <Field
          label="Contraseña"
          name="password"
          type="password"
          value={form.password}
          onChange={change}
          autoComplete="current-password"
          required
        />

        <button type="submit" disabled={busy}>
          {busy ? 'Entrando…' : 'Entrar'}
        </button>
      </form>
    </div>
  );
}
