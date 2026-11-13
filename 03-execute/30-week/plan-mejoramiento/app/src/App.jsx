import { useCallback, useEffect, useMemo, useState } from 'react';
import { createClient } from './api/client.js';
import { clearSession, readSession, writeSession } from './api/session.js';
import { isReachable, routeFromHash, routesFor } from './routes.js';
import { SignIn } from './screens/SignIn.jsx';
import { EquipmentCatalog } from './screens/EquipmentCatalog.jsx';
import { Producers } from './screens/Producers.jsx';
import { RentEquipment } from './screens/RentEquipment.jsx';
import { Rentals } from './screens/Rentals.jsx';
import { Overdue } from './screens/Overdue.jsx';
import { MostRequested } from './screens/MostRequested.jsx';
import { Advisors } from './screens/Advisors.jsx';

const SCREENS = {
  equipment: EquipmentCatalog,
  producers: Producers,
  rent: RentEquipment,
  rentals: Rentals,
  overdue: Overdue,
  report: MostRequested,
  users: Advisors,
};

export function App() {
  const [session, setSession] = useState(readSession);
  const [route, setRoute] = useState(() => routeFromHash(window.location.hash));

  useEffect(() => {
    const onHashChange = () => setRoute(routeFromHash(window.location.hash));

    window.addEventListener('hashchange', onHashChange);

    return () => window.removeEventListener('hashchange', onHashChange);
  }, []);

  const signOut = useCallback(() => {
    clearSession();
    setSession(null);
  }, []);

  /*
   * The client is rebuilt when the session changes and not on every render:
   * the screens receive it as a prop, and a new object each render would reload
   * every list on every keystroke.
   */
  const client = useMemo(
    () =>
      createClient({
        tokenOf: () => session?.accessToken ?? null,
        onUnauthenticated: signOut,
      }),
    [session, signOut],
  );

  if (session === null) {
    return (
      <SignIn
        client={client}
        onSignedIn={(signedIn) => {
          writeSession(signedIn);
          setSession(signedIn);
        }}
      />
    );
  }

  const current = isReachable(route, session.role) ? route : 'equipment';
  const Screen = SCREENS[current];

  return (
    <div className="layout">
      <aside className="sidebar">
        <h1>film-gear</h1>
        <p className="who">
          {session.username} · {session.role === 'admin' ? 'administrador' : 'asesor'}
        </p>

        <nav>
          {routesFor(session.role).map((item) => (
            <a
              key={item.id}
              href={'#/' + item.id}
              aria-current={item.id === current ? 'page' : undefined}
            >
              {item.label}
            </a>
          ))}
        </nav>

        <p className="who" style={{ marginTop: 18 }}>
          <button className="secondary" type="button" onClick={signOut}>
            Salir
          </button>
        </p>
      </aside>

      <main>
        <Screen client={client} role={session.role} />
      </main>
    </div>
  );
}
