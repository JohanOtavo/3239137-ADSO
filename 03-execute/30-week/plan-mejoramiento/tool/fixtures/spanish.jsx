/** Muestra el equipo disponible. */
export function FilaDeEquipo({ equipo, alSeleccionar }) {
  const nombreDelEquipo = equipo.name;

  return <tr><td>{nombreDelEquipo}</td></tr>;
}
