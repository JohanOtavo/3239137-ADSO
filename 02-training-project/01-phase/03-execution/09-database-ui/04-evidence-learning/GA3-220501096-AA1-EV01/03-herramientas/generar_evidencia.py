# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01
Genera, a partir del modelo conceptual declarado en modelo_datos.py y
modelo_relaciones.py:

  02-diagrama-eer/modelo_conceptual_hotel.sql   Script para ingeniería inversa (MySQL Workbench)
  02-diagrama-eer/diagrama_eer.md               Diagramas EER en notación Mermaid
  01-documentacion/02_diccionario_datos.md      Diccionario de datos
  01-documentacion/03_relaciones_cardinalidades.md

Uso:  python generar_evidencia.py
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from modelo_datos import (BD_NOMBRE, CAMPO_ID, CAMPOS_AUDITORIA, ENTIDADES, MODULOS)
from modelo_relaciones import (AUDITORIA_REL, ESPECIALIZACIONES, RELACIONES, RELACIONES_NM)

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DIR_DIAGRAMA = os.path.join(BASE, "02-diagrama-eer")
DIR_DOCS = os.path.join(BASE, "01-documentacion")

NOMBRE_MODULO = {k: n for k, n, _ in MODULOS}


# --------------------------------------------------------------------------
# Validación del modelo
# --------------------------------------------------------------------------
def validar():
    errores = []
    fks_declaradas = set()
    for r in RELACIONES:
        fks_declaradas.add((r["hijo"], r["fk"]))
        if r["hijo"] not in ENTIDADES:
            errores.append("Relación con entidad hija inexistente: %s" % r["hijo"])
        if r["padre"] not in ENTIDADES:
            errores.append("Relación con entidad padre inexistente: %s" % r["padre"])
        ent = ENTIDADES.get(r["hijo"])
        if ent:
            attr = [a for a in ent["attrs"] if a[0] == r["fk"]]
            if not attr:
                errores.append("La entidad %s no declara el atributo %s" % (r["hijo"], r["fk"]))
            else:
                nulo = attr[0][2]
                if nulo != r["opcional"]:
                    errores.append("Opcionalidad inconsistente en %s.%s" % (r["hijo"], r["fk"]))
                if r["card"] == "1:1" and "UK" not in attr[0][3]:
                    errores.append("Relación 1:1 sin UNIQUE en %s.%s" % (r["hijo"], r["fk"]))
    for nombre, ent in ENTIDADES.items():
        for a in ent["attrs"]:
            if "FK" in a[3] and (nombre, a[0]) not in fks_declaradas:
                errores.append("FK sin relación declarada: %s.%s" % (nombre, a[0]))
    if errores:
        print("MODELO INCONSISTENTE:")
        for e in errores:
            print("  - " + e)
        sys.exit(1)
    print("Validación del modelo: OK (%d entidades, %d relaciones)" % (len(ENTIDADES), len(RELACIONES)))


# --------------------------------------------------------------------------
# Utilidades
# --------------------------------------------------------------------------
def entidades_de(modulo):
    return [e for e in ENTIDADES.values() if e["modulo"] == modulo]


def orden_topologico():
    pendientes = list(ENTIDADES.keys())
    padres = {n: set() for n in ENTIDADES}
    for r in RELACIONES:
        if r["padre"] != r["hijo"]:
            padres[r["hijo"]].add(r["padre"])
    orden, resueltas = [], set()
    while pendientes:
        avance = False
        for n in list(pendientes):
            if padres[n] <= resueltas:
                orden.append(n)
                resueltas.add(n)
                pendientes.remove(n)
                avance = True
        if not avance:  # ciclo: se emite lo que queda tal cual
            orden.extend(pendientes)
            break
    return orden


def rels_de(hijo):
    return [r for r in RELACIONES if r["hijo"] == hijo]


def cardinalidad_texto(r):
    minimo = "0" if r["opcional"] else "1"
    if r["card"] == "1:1":
        return "1 : 0..1", "%s..1" % minimo
    return "1 : 0..N", "%s..1" % minimo


# --------------------------------------------------------------------------
# 1. Script SQL para la herramienta CASE
# --------------------------------------------------------------------------
def generar_sql():
    L = []
    L.append("-- =====================================================================")
    L.append("-- GA3-220501096-AA1-EV01 - Modelo conceptual de la base de datos")
    L.append("-- Proyecto formativo: sistema de hotelería - Ficha ADSO 3239137")
    L.append("--")
    L.append("-- Este script es el insumo para construir el diagrama EER en la")
    L.append("-- herramienta CASE. En MySQL Workbench:")
    L.append("--   File > Import > Reverse Engineer MySQL Create Script...")
    L.append("--   seleccionar este archivo y marcar \"Place imported objects on a diagram\".")
    L.append("--")
    L.append("-- Entidades: %d | Relaciones (llaves foráneas): %d" % (len(ENTIDADES), len(RELACIONES)))
    L.append("-- Convenciones: identificador y campos de auditoría comunes a todas las")
    L.append("-- entidades, según 05_id_y_auditoria.md del modelo de datos del diseño.")
    L.append("-- =====================================================================")
    L.append("")
    L.append("DROP SCHEMA IF EXISTS `%s`;" % BD_NOMBRE)
    L.append("CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" % BD_NOMBRE)
    L.append("USE `%s`;" % BD_NOMBRE)
    L.append("")

    modulo_actual = None
    for nombre in orden_topologico():
        ent = ENTIDADES[nombre]
        if ent["modulo"] != modulo_actual:
            modulo_actual = ent["modulo"]
        L.append("-- ---------------------------------------------------------------------")
        L.append("-- Entidad: %s   [módulo %s]" % (nombre, NOMBRE_MODULO[ent["modulo"]]))
        L.append("-- %s" % ent["desc"])
        L.append("-- ---------------------------------------------------------------------")
        L.append("CREATE TABLE `%s` (" % nombre)
        cuerpo = []
        cuerpo.append("  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '%s'" % CAMPO_ID[4])
        for a in ent["attrs"]:
            an, tipo, nulo, clave, desc = a
            cuerpo.append("  `%s` %s %s COMMENT '%s'" % (
                an, tipo, "NULL" if nulo else "NOT NULL", desc.replace("'", "''")))
        for a in CAMPOS_AUDITORIA:
            an, tipo, nulo, clave, desc = a
            extra = ""
            if an == "created_at":
                extra = " DEFAULT CURRENT_TIMESTAMP"
            elif an == "updated_at":
                extra = " ON UPDATE CURRENT_TIMESTAMP"
            elif an == "status":
                extra = " DEFAULT 'ACTIVE'"
            cuerpo.append("  `%s` %s %s%s COMMENT '%s'" % (
                an, tipo, "NULL" if nulo else "NOT NULL", extra, desc.replace("'", "''")))
        cuerpo.append("  PRIMARY KEY (`id`)")

        # UNIQUE de columna
        for a in ent["attrs"]:
            if "UK" in a[3]:
                cuerpo.append("  CONSTRAINT `uk_%s_%s` UNIQUE (`%s`)" % (nombre, a[0], a[0]))
        # UNIQUE compuestos
        for i, cols in enumerate(ent["uk"], start=1):
            cuerpo.append("  CONSTRAINT `uk_%s_%d` UNIQUE (%s)" % (
                nombre, i, ", ".join("`%s`" % c for c in cols)))
        # CHECK
        for i, chk in enumerate(ent["checks"], start=1):
            cuerpo.append("  CONSTRAINT `ck_%s_%d` CHECK (%s)" % (nombre, i, chk))
        # FK
        for r in rels_de(nombre):
            cuerpo.append("  CONSTRAINT `fk_%s_%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`id`)"
                          " ON DELETE %s ON UPDATE CASCADE" % (
                              nombre, r["padre"], r["fk"], r["padre"], r["on_delete"]))
        L.append(",\n".join(cuerpo))
        L.append(") ENGINE = InnoDB COMMENT = '%s';" % ent["desc"].replace("'", "''"))
        L.append("")

    L.append("-- ---------------------------------------------------------------------")
    L.append("-- Auditoría: created_by, updated_by y deleted_by referencian usuario.id.")
    L.append("-- Se documentan en el modelo conceptual y se implementan en el modelo")
    L.append("-- físico (GA3-220501096-AA2-EV02) para no saturar el diagrama EER con")
    L.append("-- tres aristas adicionales por cada entidad.")
    L.append("-- ---------------------------------------------------------------------")
    L.append("")
    ruta = os.path.join(DIR_DIAGRAMA, "modelo_conceptual_hotel.sql")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(L))
    print("Generado:", ruta)


# --------------------------------------------------------------------------
# 2. Diagramas Mermaid
# --------------------------------------------------------------------------
def tipo_base(tipo):
    return tipo.split("(")[0].split(" ")[0]


def conector(r):
    izq = "|o" if r["opcional"] else "||"
    der = "o|" if r["card"] == "1:1" else "o{"
    return "%s--%s" % (izq, der)


def generar_mermaid():
    L = []
    L.append("# Diagrama EER del sistema de hotelería (notación Mermaid)")
    L.append("")
    L.append("Evidencia **GA3-220501096-AA1-EV01**. Los diagramas de esta página se")
    L.append("renderizan directamente en GitHub y reproducen el mismo modelo que se")
    L.append("construye en la herramienta CASE a partir de")
    L.append("`modelo_conceptual_hotel.sql`.")
    L.append("")
    L.append("- Entidades: **%d** | Relaciones: **%d**" % (len(ENTIDADES), len(RELACIONES)))
    L.append("- Notación: pata de gallo. `||` = uno y solo uno, `|o` = cero o uno,")
    L.append("  `o{` = cero o muchos.")
    L.append("")
    L.append("## 1. Diagrama general (entidades y relaciones)")
    L.append("")
    L.append("```mermaid")
    L.append("erDiagram")
    for nombre in sorted(ENTIDADES):
        L.append("    %s {" % nombre)
        L.append("        BIGINT id PK")
        L.append("    }")
    for r in RELACIONES:
        L.append("    %s %s %s : \"%s\"" % (r["padre"], conector(r), r["hijo"], r["fk"]))
    L.append("```")
    L.append("")

    for clave, nombre_mod, _ in MODULOS:
        ents = sorted(entidades_de(clave), key=lambda e: e["nombre"])
        L.append("## Módulo %s" % nombre_mod)
        L.append("")
        L.append("```mermaid")
        L.append("erDiagram")
        externas = []
        propias = set(e["nombre"] for e in ents)
        for e in ents:
            L.append("    %s {" % e["nombre"])
            L.append("        BIGINT id PK \"Identificador del registro\"")
            for a in e["attrs"]:
                marca = a[3].replace("FK,UK", "FK").replace(",", "")
                marca = marca if marca in ("PK", "FK", "UK") else ""
                comentario = "%s %s" % (a[1], "NULL" if a[2] else "NOT NULL")
                L.append("        %s %s %s \"%s\"" % (tipo_base(a[1]), a[0], marca, comentario))
            L.append("    }")
        for r in RELACIONES:
            if r["hijo"] in propias or r["padre"] in propias:
                if r["hijo"] in propias and r["padre"] in propias:
                    L.append("    %s %s %s : \"%s\"" % (r["padre"], conector(r), r["hijo"], r["fk"]))
                else:
                    externas.append(r)
        for r in externas:
            L.append("    %s %s %s : \"%s\"" % (r["padre"], conector(r), r["hijo"], r["fk"]))
        L.append("```")
        L.append("")
        if externas:
            L.append("> Relaciones con otros módulos: " + "; ".join(
                "`%s` (%s) &rarr; `%s` (%s)" % (
                    r["hijo"], NOMBRE_MODULO[ENTIDADES[r["hijo"]]["modulo"]],
                    r["padre"], NOMBRE_MODULO[ENTIDADES[r["padre"]]["modulo"]])
                for r in externas) + ".")
            L.append("")

    ruta = os.path.join(DIR_DIAGRAMA, "diagrama_eer.md")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(L))
    print("Generado:", ruta)


# --------------------------------------------------------------------------
# 3. Diccionario de datos
# --------------------------------------------------------------------------
def generar_diccionario():
    L = []
    L.append("# Diccionario de datos - Modelo conceptual del sistema de hotelería")
    L.append("")
    L.append("Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO 3239137.")
    L.append("")
    L.append("Contiene **%d entidades** y **%d relaciones**. Todas las entidades incluyen el "
             "identificador `id` y los campos de auditoría descritos en la sección final."
             % (len(ENTIDADES), len(RELACIONES)))
    L.append("")
    L.append("## Convenciones")
    L.append("")
    L.append("| Marca | Significado |")
    L.append("|---|---|")
    L.append("| PK | Llave primaria |")
    L.append("| FK | Llave foránea |")
    L.append("| UK | Restricción de unicidad |")
    L.append("| NN | No admite valores nulos |")
    L.append("")
    for clave, nombre_mod, _ in MODULOS:
        L.append("## Módulo %s" % nombre_mod)
        L.append("")
        for e in sorted(entidades_de(clave), key=lambda x: x["nombre"]):
            L.append("### %s" % e["nombre"])
            L.append("")
            L.append("%s" % e["desc"])
            L.append("")
            L.append("| Atributo | Tipo de dato | Nulo | Clave | Descripción |")
            L.append("|---|---|---|---|---|")
            L.append("| id | BIGINT UNSIGNED | NN | PK | %s |" % CAMPO_ID[4])
            for a in e["attrs"]:
                L.append("| %s | %s | %s | %s | %s |" % (
                    a[0], a[1], "" if a[2] else "NN", a[3], a[4]))
            L.append("")
            restricciones = []
            for cols in e["uk"]:
                restricciones.append("UNIQUE (%s)" % ", ".join(cols))
            for chk in e["checks"]:
                restricciones.append("CHECK (%s)" % chk)
            if restricciones:
                L.append("**Restricciones adicionales:** " + "; ".join(restricciones) + ".")
                L.append("")
            rs = rels_de(e["nombre"])
            if rs:
                L.append("**Llaves foráneas:** " + "; ".join(
                    "`%s` &rarr; `%s.id` (%s)" % (r["fk"], r["padre"], r["card"]) for r in rs) + ".")
                L.append("")
    L.append("## Campos comunes de auditoría")
    L.append("")
    L.append("Presentes en las %d entidades del modelo." % len(ENTIDADES))
    L.append("")
    L.append("| Atributo | Tipo de dato | Nulo | Clave | Descripción |")
    L.append("|---|---|---|---|---|")
    for a in CAMPOS_AUDITORIA:
        L.append("| %s | %s | %s | %s | %s |" % (a[0], a[1], "" if a[2] else "NN", a[3], a[4]))
    L.append("")
    L.append("> %s" % AUDITORIA_REL["nota"])
    L.append("")
    ruta = os.path.join(DIR_DOCS, "02_diccionario_datos.md")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(L))
    print("Generado:", ruta)


# --------------------------------------------------------------------------
# 4. Relaciones y cardinalidades
# --------------------------------------------------------------------------
def generar_relaciones_md():
    L = []
    L.append("# Relaciones y cardinalidades del modelo conceptual")
    L.append("")
    L.append("Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO 3239137.")
    L.append("")
    L.append("La cardinalidad se expresa en notación mín..máx desde cada extremo. "
             "La entidad hija es la que contiene la llave foránea.")
    L.append("")
    L.append("| # | Entidad padre | Entidad hija | Llave foránea | Cardinalidad | Participación de la hija | ON DELETE | Lectura de negocio |")
    L.append("|---|---|---|---|---|---|---|---|")
    for i, r in enumerate(RELACIONES, start=1):
        card, part = cardinalidad_texto(r)
        L.append("| %d | %s | %s | %s | %s | %s | %s | %s |" % (
            i, r["padre"], r["hijo"], r["fk"], card, part, r["on_delete"], r["lectura"]))
    L.append("")
    L.append("## Relaciones muchos a muchos resueltas con entidad asociativa")
    L.append("")
    L.append("| Entidad A | Entidad B | Entidad asociativa | Lectura |")
    L.append("|---|---|---|---|")
    for a, b, assoc, lectura in RELACIONES_NM:
        L.append("| %s | %s | %s | %s |" % (a, b, assoc, lectura))
    L.append("")
    L.append("## Especializaciones (constructos del modelo EER)")
    L.append("")
    for esp in ESPECIALIZACIONES:
        L.append("### %s" % esp["supertipo"])
        L.append("")
        L.append("- **Subtipos:** %s" % ", ".join(esp["subtipos"]))
        L.append("- **Discriminador:** %s" % esp["discriminador"])
        L.append("- **Restricción:** %s" % esp["restriccion"])
        L.append("- **Nota:** %s" % esp["nota"])
        L.append("")
    L.append("## Relación transversal de auditoría")
    L.append("")
    L.append("- **Columnas:** %s" % ", ".join(AUDITORIA_REL["columnas"]))
    L.append("- **Entidad referenciada:** %s" % AUDITORIA_REL["padre"])
    L.append("- **Cardinalidad:** %s" % AUDITORIA_REL["card"])
    L.append("- **Lectura:** %s" % AUDITORIA_REL["lectura"])
    L.append("- **Nota:** %s" % AUDITORIA_REL["nota"])
    L.append("")
    ruta = os.path.join(DIR_DOCS, "03_relaciones_cardinalidades.md")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(L))
    print("Generado:", ruta)


def validar_sql():
    """Verifica que el script generado sea SQL válido para el dialecto MySQL.

    Requiere el paquete sqlglot (pip install sqlglot). Si no está instalado se
    omite la verificación, porque no es indispensable para generar la evidencia.
    """
    try:
        import sqlglot
    except ImportError:
        print("Verificación sintáctica omitida: sqlglot no está instalado.")
        return
    ruta = os.path.join(DIR_DIAGRAMA, "modelo_conceptual_hotel.sql")
    with open(ruta, encoding="utf-8") as f:
        script = f.read()
    sentencias = sqlglot.parse(script, read="mysql")
    sentencias = [s for s in sentencias if s is not None]
    print("Verificación sintáctica MySQL: OK (%d sentencias analizadas)" % len(sentencias))


if __name__ == "__main__":
    validar()
    generar_sql()
    validar_sql()
    generar_mermaid()
    generar_diccionario()
    generar_relaciones_md()
