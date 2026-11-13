/**
 * Hash routing in a dozen lines, instead of a router dependency.
 *
 * The hash is enough for seven screens and it survives a reload, which matters
 * more than it sounds: an operator who refreshes while looking at the overdue
 * list should still be looking at the overdue list.
 */
export const ROUTES = [
  { id: 'equipment', label: 'Catálogo', roles: ['admin', 'advisor'] },
  { id: 'producers', label: 'Productores', roles: ['admin', 'advisor'] },
  { id: 'rent', label: 'Registrar alquiler', roles: ['admin', 'advisor'] },
  { id: 'rentals', label: 'Alquileres', roles: ['admin', 'advisor'] },
  { id: 'overdue', label: 'Vencidos', roles: ['admin', 'advisor'] },
  { id: 'report', label: 'Más solicitados', roles: ['admin', 'advisor'] },
  { id: 'users', label: 'Asesores', roles: ['admin'] },
];

const DEFAULT_ROUTE = 'equipment';

export function routeFromHash(hash) {
  const id = String(hash ?? '').replace(/^#\/?/, '');
  const known = ROUTES.some((route) => route.id === id);

  return known ? id : DEFAULT_ROUTE;
}

/**
 * A route the role cannot reach falls back to the catalogue rather than
 * showing an empty screen. The API would refuse the request anyway; this only
 * keeps the menu honest.
 */
export function routesFor(role) {
  return ROUTES.filter((route) => route.roles.includes(role));
}

export function isReachable(routeId, role) {
  return routesFor(role).some((route) => route.id === routeId);
}
