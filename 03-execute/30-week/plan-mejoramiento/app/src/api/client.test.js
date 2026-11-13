import { describe, expect, test, vi } from 'vitest';
import { createClient } from './client.js';
import { ApiError } from './ApiError.js';

/** A fetch stub, so the client is tested without a server. */
function respondWith({ status = 200, body = null, ok } = {}) {
  return vi.fn(async () => ({
    status,
    ok: ok ?? (status >= 200 && status < 300),
    json: async () => {
      if (body === null) {
        throw new Error('not json');
      }

      return body;
    },
    blob: async () => new Blob(['bytes']),
  }));
}

describe('the bearer header', () => {
  test('is attached when there is a token', async () => {
    // Arrange
    const fetchImpl = respondWith({ body: { items: [] } });
    const client = createClient({ baseUrl: '/api/v1', tokenOf: () => 'token-123', fetchImpl });

    // Act
    await client.get('/equipment');

    // Assert
    const [, options] = fetchImpl.mock.calls[0];
    expect(options.headers.Authorization).toBe('Bearer token-123');
  });

  test('is absent when nobody is signed in', async () => {
    const fetchImpl = respondWith({ body: {} });
    const client = createClient({ baseUrl: '/api/v1', tokenOf: () => null, fetchImpl });

    await client.post('/auth/login', { username: 'ana' });

    const [, options] = fetchImpl.mock.calls[0];
    expect(options.headers.Authorization).toBeUndefined();
  });
});

describe('the query string', () => {
  test('drops empty filters so a cleared field is not sent', async () => {
    const fetchImpl = respondWith({ body: { items: [] } });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    await client.get('/equipment', { query: { q: '', category_id: 'cat-1', page: 2 } });

    const [url] = fetchImpl.mock.calls[0];
    expect(url).toBe('/api/v1/equipment?category_id=cat-1&page=2');
  });

  test('is omitted entirely when every filter is empty', async () => {
    const fetchImpl = respondWith({ body: { items: [] } });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    await client.get('/equipment', { query: { q: '', category_id: null } });

    expect(fetchImpl.mock.calls[0][0]).toBe('/api/v1/equipment');
  });
});

describe('failures', () => {
  test('a business error arrives as an ApiError carrying the code', async () => {
    const fetchImpl = respondWith({
      status: 409,
      body: {
        code: 'EQUIPMENT_NOT_AVAILABLE',
        message: 'No quedan unidades disponibles.',
        details: [{ field: 'items[0].equipment_id', issue: 'sin unidades' }],
      },
    });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    await expect(client.post('/rentals', {})).rejects.toBeInstanceOf(ApiError);
  });

  test('the rejected fields are readable as a map for a form', async () => {
    const fetchImpl = respondWith({
      status: 422,
      body: {
        code: 'VALIDATION_ERROR',
        message: 'Falta el nombre.',
        details: [{ field: 'name', issue: 'falta o está vacío' }],
      },
    });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    try {
      await client.post('/equipment', {});
      expect.unreachable('A 422 must be thrown.');
    } catch (failure) {
      expect(failure.issuesByField()).toEqual({ name: 'falta o está vacío' });
    }
  });

  /**
   * The screens must not each remember that an expired token looks exactly like
   * a rejected password. The client reports it once.
   */
  test('a 401 calls back so the session can be dropped in one place', async () => {
    const onUnauthenticated = vi.fn();
    const fetchImpl = respondWith({ status: 401, body: { code: 'INVALID_CREDENTIALS', message: 'Token vencido.' } });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl, onUnauthenticated });

    await expect(client.get('/equipment')).rejects.toThrow();
    expect(onUnauthenticated).toHaveBeenCalledOnce();
  });

  /** A body that is not JSON is a server defect; the screen shows a message, not a stack. */
  test('a broken body becomes the generic message instead of a crash', async () => {
    const fetchImpl = respondWith({ status: 500, body: null });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    try {
      await client.get('/equipment');
      expect.unreachable('A 500 must be thrown.');
    } catch (failure) {
      expect(failure.code).toBe('INTERNAL_ERROR');
      expect(failure.message).toBe('No fue posible completar la operación.');
    }
  });

  test('a 204 answers with no body rather than failing to parse one', async () => {
    const fetchImpl = respondWith({ status: 204, body: null });
    const client = createClient({ baseUrl: '/api/v1', fetchImpl });

    await expect(client.delete('/equipment/1')).resolves.toBeNull();
  });
});
