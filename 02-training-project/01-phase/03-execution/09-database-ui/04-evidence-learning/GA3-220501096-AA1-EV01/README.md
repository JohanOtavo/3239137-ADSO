# GA3-220501096-AA1-EV01 — Modelo conceptual de la base de datos

**Proyecto formativo:** sistema de hotelería · **Ficha:** ADSO 3239137
**Actividad:** GA3-220501096-AA1 — Construir el modelo conceptual de la base de datos del proyecto
a partir del modelo de datos del diseño.
**Guía:** `02-training-project/01-phase/03-execution/09-database-ui/01-learning-guide/Guia_Aprendizaje_ConstruccionBD.docx`
**Instrumento de evaluación:** `Instrumento_EV01_ModeloConceptualBD.docx` (misma carpeta de la guía).

## Qué contiene esta evidencia

| Archivo | Contenido |
|---|---|
| `GA3-220501096-AA1-EV01_Modelo_Conceptual_BD.docx` | Informe formal de la evidencia (26 páginas): portada, tabla de contenido, modelo por módulo, diccionario, relaciones, restricciones, decisiones de diseño, trazabilidad, autoevaluación y referencias. |
| `GA3-220501096-AA1-EV01_Modelo_Conceptual_BD.pdf` | El mismo informe en PDF, con la tabla de contenido ya actualizada. |
| `02-diagrama-eer/modelo_conceptual_hotel.sql` | Script del modelo (46 tablas, 55 llaves foráneas, restricciones UNIQUE y CHECK). Es el insumo para generar el diagrama EER en la herramienta CASE. |
| `02-diagrama-eer/diagrama_eer.md` | Diagrama EER general y por módulo en notación Mermaid; se renderiza directo en GitHub. |
| `02-diagrama-eer/img/` | Mapa de módulos (`eer_00`) y diagrama de cada módulo (`eer_01` … `eer_08`). |
| `01-documentacion/01_modelo_conceptual.md` | Versión en Markdown del informe. |
| `01-documentacion/02_diccionario_datos.md` | Diccionario de datos completo (46 entidades). |
| `01-documentacion/03_relaciones_cardinalidades.md` | Las 55 relaciones con cardinalidad, participación y acción referencial; relaciones N:M y especializaciones. |
| `01-documentacion/04_trazabilidad_y_validacion.md` | Matriz de trazabilidad, autoevaluación con la lista de chequeo, decisiones de diseño y pendientes. |
| `03-herramientas/` | Declaración del modelo y generadores (ver más abajo). |

## Paso pendiente: generar el diagrama en la herramienta CASE

El informe ya incluye los diagramas del modelo, pero el criterio 5 del instrumento pide que el
diagrama EER se elabore **con una herramienta CASE**. Ese paso se hace así, en MySQL Workbench 8.0:

1. Abrir MySQL Workbench.
2. Menú **File > Import > Reverse Engineer MySQL Create Script…**
3. Seleccionar `02-diagrama-eer/modelo_conceptual_hotel.sql`.
4. Marcar la casilla **Place imported objects on a diagram**.
5. **Execute > Next > Finish**. Workbench dibuja el EER completo con notación de pata de gallo.
6. Acomodar las tablas por módulo (opcionalmente usando los colores del mapa de módulos).
7. Guardar el modelo como `02-diagrama-eer/modelo_conceptual_hotel.mwb`.
8. Exportar la imagen con **File > Export > Export as PNG…** y guardarla como
   `02-diagrama-eer/img/eer_workbench.png`.

En pgModeler el equivalente es **Design > Import > Import database / SQL file**.

## Cómo regenerar la evidencia

El modelo está declarado una sola vez en `03-herramientas/modelo_datos.py` (entidades, atributos,
tipos y restricciones) y `03-herramientas/modelo_relaciones.py` (relaciones, cardinalidades,
especializaciones). De ahí se generan el script SQL, los diagramas, el diccionario y el informe, de
modo que la documentación no se desincronice del modelo.

Si se ajusta el modelo, se regenera todo con:

```bash
cd 03-herramientas && python generar_evidencia.py && python generar_diagramas.py && python generar_informe.py
```

Dependencias: `pip install python-docx matplotlib sqlglot` (sqlglot solo se usa para verificar que
el SQL generado sea sintácticamente válido; si no está instalado, esa verificación se omite).

Los generadores validan automáticamente que:

- toda llave foránea tenga su relación declarada y viceversa;
- la opcionalidad de la llave foránea coincida con la participación declarada;
- toda relación 1:1 tenga su restricción UNIQUE;
- las 46 entidades estén cubiertas por la matriz de trazabilidad;
- el script SQL sea válido para el dialecto MySQL.

## Resumen del modelo

- **46 entidades** en 8 módulos: parametrización (7), distribución (4), prestación de servicio (9),
  facturación (4), inventario (5), notificación (4), seguridad (9) y mantenimiento (4).
- **55 relaciones** con cardinalidad y acción referencial definidas.
- **6 relaciones N:M** resueltas con entidades asociativas.
- **2 especializaciones EER**: `mantenimiento_habitacion` (disyunta y total) y `persona`
  (solapada y parcial).
- Identificador `id` y siete campos de auditoría en todas las entidades, con eliminación lógica.

## Siguiente actividad

`GA3-220501096-AA2` — Implementar el modelo físico en el SGBD (evidencia GA3-220501096-AA2-EV02),
partiendo de `modelo_conceptual_hotel.sql`: tipos nativos, índices, llaves foráneas de auditoría,
scripts DML de datos de prueba y pruebas de integridad referencial.
