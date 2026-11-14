/**
 * The HTTP client the scripts share.
 *
 * Both the seeder and the load test talk to the same API with the same token
 * and the same loud failure, and a second copy of that is a second place for
 * the Authorization header to be forgotten.
 *
 * Nothing here touches the database. A script that wrote rows directly could
 * write states the API cannot produce, and the data it left would prove
 * nothing about the rules.
 */

export class ApiError extends Error {
  constructor(method, path, status, body) {
    const detail = body === null ? '' : ` ${JSON.stringify(body)}`;

    super(`${method} ${path} answered ${status}.${detail}`);

    this.name = 'ApiError';
    this.status = status;
    this.body = body;
  }
}

export function createApi({ apiUrl, fetchImpl = fetch } = {}) {
  if (typeof apiUrl !== 'string' || apiUrl === '') {
    throw new Error('createApi needs the API base URL.');
  }

  let accessToken = null;

  async function call(method, path, body) {
    const response = await fetchImpl(`${apiUrl}${path}`, {
      method,
      headers: {
        ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
        ...(accessToken === null ? {} : { Authorization: `Bearer ${accessToken}` }),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (response.status === 204) {
      return { status: response.status, body: null };
    }

    const payload = await response.json().catch(() => null);

    return { status: response.status, body: payload };
  }

  /** Throws on an unexpected status: a script that swallows one leaves a half-filled instance. */
  async function expectOk(method, path, body, expected) {
    const result = await call(method, path, body);

    if (!expected.includes(result.status)) {
      throw new ApiError(method, path, result.status, result.body);
    }

    return result.body;
  }

  async function signIn(username, password) {
    if (password === '') {
      throw new Error('The administrator password is not set; there is no way to sign in.');
    }

    const body = await expectOk('POST', '/auth/login', { username, password }, [200]);

    accessToken = body.access_token;

    return body.role;
  }

  return { call, expectOk, signIn };
}

/** Query string without the empty values, so a missing filter is absent and not blank. */
export function queryString(values) {
  const pairs = Object.entries(values)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);

  return pairs.length === 0 ? '' : `?${pairs.join('&')}`;
}
