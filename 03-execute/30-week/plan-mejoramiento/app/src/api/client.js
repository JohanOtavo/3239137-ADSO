import { ApiError } from './ApiError.js';

/**
 * The single door to the API.
 *
 * Every request goes through here, which is what makes two things possible in
 * one place: the bearer header is never forgotten, and a 401 always means the
 * same thing to the screens.
 */
const BASE_URL = import.meta.env?.VITE_API_URL ?? 'http://localhost:8080/api/v1';

export function createClient({ baseUrl = BASE_URL, tokenOf, onUnauthenticated, fetchImpl } = {}) {
  const send = fetchImpl ?? ((...args) => fetch(...args));

  async function request(method, path, { body, query, binary = false } = {}) {
    const token = tokenOf?.() ?? null;
    const url = baseUrl + path + queryString(query);

    const response = await send(url, {
      method,
      headers: {
        Accept: binary ? '*/*' : 'application/json',
        ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
        ...(token === null ? {} : { Authorization: 'Bearer ' + token }),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (response.status === 401) {
      // Handled once, here: every screen would otherwise have to remember that
      // an expired token looks exactly like a rejected password.
      onUnauthenticated?.();
    }

    if (response.status === 204) {
      return null;
    }

    if (binary && response.ok) {
      return response.blob();
    }

    const payload = await readJson(response);

    if (!response.ok) {
      throw new ApiError(
        response.status,
        payload?.code ?? 'INTERNAL_ERROR',
        payload?.message ?? 'No fue posible completar la operación.',
        payload?.details,
      );
    }

    return payload;
  }

  return {
    request,
    get: (path, options) => request('GET', path, options),
    post: (path, body) => request('POST', path, { body }),
    put: (path, body) => request('PUT', path, { body }),
    delete: (path) => request('DELETE', path),
  };
}

/** Drops empty parameters, so a cleared filter does not become `?q=`. */
function queryString(query) {
  if (query === undefined || query === null) {
    return '';
  }

  const parameters = new URLSearchParams();

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') {
      continue;
    }

    parameters.set(key, String(value));
  }

  const encoded = parameters.toString();

  return encoded === '' ? '' : '?' + encoded;
}

/**
 * A body that is not JSON is a defect on the server side, and the screens must
 * not crash on it: they show the generic message instead.
 */
async function readJson(response) {
  try {
    return await response.json();
  } catch {
    return null;
  }
}
