/** A good comment, in English. */
export function EquipmentRow({ item, onPick }) {
  return (
    <tr>
      <td>Equipo disponible: {item.name}</td>
      <td>
        Quedan {item.availableUnits} unidades afuera por productor.
        Vence a los 4 días.
      </td>
      <td>
        <button type="button" onClick={() => onPick(item)}>
          Agregar
        </button>
      </td>
    </tr>
  );
}
