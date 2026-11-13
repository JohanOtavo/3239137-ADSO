import { useCallback, useEffect, useState } from 'react';

/**
 * Loading a paged list: the same five lines of state every list screen needs.
 *
 * Written once because the interesting part is not the fetch, it is keeping
 * `busy`, `error` and `data` consistent. A screen that forgets to clear the
 * error on a retry shows a stale failure over fresh rows.
 */
export function useList(load, dependencies) {
  const [state, setState] = useState({ busy: true, error: null, data: null });

  const run = useCallback(() => {
    let cancelled = false;

    setState((previous) => ({ ...previous, busy: true, error: null }));

    load()
      .then((data) => {
        if (!cancelled) {
          setState({ busy: false, error: null, data });
        }
      })
      .catch((error) => {
        if (!cancelled) {
          // The data is kept: an operator who filters badly should still see
          // the rows that were on screen, with the reason above them.
          setState((previous) => ({ busy: false, error, data: previous.data }));
        }
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, dependencies);

  useEffect(run, [run]);

  return { ...state, reload: run };
}
