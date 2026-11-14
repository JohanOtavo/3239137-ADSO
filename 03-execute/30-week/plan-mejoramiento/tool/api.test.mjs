import assert from 'node:assert/strict';
import { test } from 'node:test';

import { ApiError, createApi, queryString } from './api.mjs';

/** A fetch that records what it was asked and answers what the test wants. */
function fakeFetch(answers) {
  const calls = [];

  const fetchImpl = async (url, options) => {
    calls.push({ url, options });

    const answer = answers.shift() ?? { status: 200, body: {} };

    return {
      status: answer.status,
      json: async () => {
        if (answer.body === undefined) {
          throw new Error('not json');
        }

        return answer.body;
      },
    };
  };

  return { fetchImpl, calls };
}

test('the client refuses to be built without a base URL', () => {
  assert.throws(() => createApi({}), /base URL/);
});

test('the token travels on every call once the sign in succeeded', async () => {
  const { fetchImpl, calls } = fakeFetch([
    { status: 200, body: { access_token: 'a-token', role: 'admin' } },
    { status: 200, body: { items: [] } },
  ]);

  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  const role = await api.signIn('root', 'a-password');
  await api.expectOk('GET', '/equipment', undefined, [200]);

  assert.equal(role, 'admin');
  assert.equal(calls[0].options.headers.Authorization, undefined);
  assert.equal(calls[1].options.headers.Authorization, 'Bearer a-token');
});

test('an empty password stops the run before any request', async () => {
  const { fetchImpl, calls } = fakeFetch([]);
  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  await assert.rejects(() => api.signIn('root', ''), /password is not set/);
  assert.equal(calls.length, 0);
});

test('an unexpected status carries the status and the body', async () => {
  const { fetchImpl } = fakeFetch([
    { status: 409, body: { code: 'MODEL_CODE_TAKEN', message: 'Ya existe.' } },
  ]);

  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  await assert.rejects(
    () => api.expectOk('POST', '/equipment', { name: 'one' }, [201]),
    (failure) => {
      assert.ok(failure instanceof ApiError);
      assert.equal(failure.status, 409);
      assert.equal(failure.body.code, 'MODEL_CODE_TAKEN');

      return true;
    },
  );
});

test('an expected status is not an error, which is how a rerun stays quiet', async () => {
  const { fetchImpl } = fakeFetch([{ status: 409, body: { code: 'MODEL_CODE_TAKEN' } }]);
  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  const body = await api.expectOk('POST', '/equipment', {}, [201, 409]);

  assert.equal(body.code, 'MODEL_CODE_TAKEN');
});

test('a 204 answers no body and does not try to parse one', async () => {
  const { fetchImpl } = fakeFetch([{ status: 204, body: undefined }]);
  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  const body = await api.expectOk('DELETE', '/equipment/one', undefined, [204]);

  assert.equal(body, null);
});

test('a body that is not json reads as null instead of throwing', async () => {
  const { fetchImpl } = fakeFetch([{ status: 500, body: undefined }]);
  const api = createApi({ apiUrl: 'http://api.test/v1', fetchImpl });

  const result = await api.call('GET', '/health', undefined);

  assert.equal(result.status, 500);
  assert.equal(result.body, null);
});

test('the query string leaves out what was not asked for', () => {
  assert.equal(queryString({ page: 1, q: undefined, category_id: '' }), '?page=1');
  assert.equal(queryString({ q: null }), '');
  assert.equal(queryString({ q: 'dos palabras' }), '?q=dos%20palabras');
});
