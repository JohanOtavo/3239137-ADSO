/**
 * Where the token lives between page loads.
 *
 * sessionStorage and not localStorage: the token lasts eight hours on the
 * server, and leaving it in localStorage would keep it on a shared machine
 * after the tab is closed, which is longer than anybody intended.
 *
 * It is not a cookie because the API takes a bearer header, and a token that
 * is not sent automatically cannot be used by a cross-site request.
 */
const KEY = 'filmgear.session';

export function readSession() {
  try {
    const raw = window.sessionStorage.getItem(KEY);

    return raw === null ? null : JSON.parse(raw);
  } catch {
    // A corrupt or blocked store is not a crash: it means nobody is signed in.
    return null;
  }
}

export function writeSession(session) {
  try {
    window.sessionStorage.setItem(KEY, JSON.stringify(session));
  } catch {
    // Private browsing can refuse to store. The session still works for this
    // page load, which is better than refusing to sign in at all.
  }
}

export function clearSession() {
  try {
    window.sessionStorage.removeItem(KEY);
  } catch {
    // Nothing to do: there is no store to clear.
  }
}
