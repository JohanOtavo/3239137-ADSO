# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01
Genera el informe formal de la evidencia en dos formatos, a partir del mismo
contenido:

  GA3-220501096-AA1-EV01_Modelo_Conceptual_BD.docx
  01-documentacion/01_modelo_conceptual.md
  01-documentacion/04_trazabilidad_y_validacion.md

Requiere python-docx (pip install python-docx).

Uso:  python generar_informe.py
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor

import contenido_informe as C
from modelo_datos import CAMPOS_AUDITORIA, ENTIDADES, MODULOS
from modelo_relaciones import (AUDITORIA_REL, ESPECIALIZACIONES, RELACIONES, RELACIONES_NM)

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DIR_DOCS = os.path.join(BASE, "01-documentacion")
DIR_IMG = os.path.join(BASE, "02-diagrama-eer", "img")
NOMBRE_MODULO = {k: n for k, n, _ in MODULOS}


# ==========================================================================
# Tablas del informe
# ==========================================================================
def tabla_modulos():
    filas = []
    for clave, nombre, _ in MODULOS:
        ents = sorted(e["nombre"] for e in ENTIDADES.values() if e["modulo"] == clave)
        filas.append([nombre, str(len(ents)), ", ".join(ents)])
    filas.append(["Total", str(len(ENTIDADES)), ""])
    return ["Módulo", "Entidades", "Detalle"], filas


def tabla_auditoria():
    filas = [["id", "BIGINT UNSIGNED", "NN", "PK",
              "Identificador único del registro (autoincremental)."]]
    for a in CAMPOS_AUDITORIA:
        filas.append([a[0], a[1], "" if a[2] else "NN", a[3], a[4]])
    return ["Atributo", "Tipo de dato", "Nulo", "Clave", "Descripción"], filas


def tabla_relaciones():
    filas = []
    for i, r in enumerate(RELACIONES, start=1):
        card = "1 : 0..1" if r["card"] == "1:1" else "1 : 0..N"
        part = "0..1" if r["opcional"] else "1..1"
        filas.append([str(i), r["padre"], r["hijo"], r["fk"], card, part, r["on_delete"]])
    return ["#", "Entidad padre", "Entidad hija", "Llave foránea", "Card.",
            "Part. hija", "ON DELETE"], filas


def tabla_nm():
    return (["Entidad A", "Entidad B", "Entidad asociativa", "Lectura de negocio"],
            [[a, b, c, d] for a, b, c, d in RELACIONES_NM])


def tabla_especializaciones():
    filas = []
    for e in ESPECIALIZACIONES:
        filas.append([e["supertipo"], ", ".join(e["subtipos"]), e["discriminador"],
                      e["restriccion"], e["nota"]])
    return ["Supertipo", "Subtipos", "Discriminador", "Restricción", "Descripción"], filas


def tabla_checks():
    filas = []
    for nombre in sorted(ENTIDADES):
        for chk in ENTIDADES[nombre]["checks"]:
            filas.append([nombre, chk])
    return ["Entidad", "Restricción CHECK"], filas


def tabla_decisiones():
    return (["#", "Decisión", "Definición adoptada", "Justificación"],
            [[str(i), t, d, j] for i, (t, d, j) in enumerate(C.DECISIONES, start=1)])


def tabla_trazabilidad():
    return (["Módulo", "Funcionalidad del diseño", "Entidades que la soportan"],
            [list(f) for f in C.TRAZABILIDAD])


def tabla_checklist():
    return (["#", "Variable / indicador de logro", "Cumple", "Evidencia en este documento"],
            [list(f) for f in C.CHECKLIST])


def tabla_pendientes():
    return (["Pendiente por confirmar", "Tratamiento en el modelo conceptual"],
            [list(f) for f in C.PENDIENTES])


def tabla_anexos():
    return ["Archivo", "Contenido"], [list(f) for f in C.ANEXOS]


TABLAS = {
    "modulos": tabla_modulos, "auditoria": tabla_auditoria, "relaciones": tabla_relaciones,
    "nm": tabla_nm, "especializaciones": tabla_especializaciones, "checks": tabla_checks,
    "decisiones": tabla_decisiones, "trazabilidad": tabla_trazabilidad,
    "checklist": tabla_checklist, "pendientes": tabla_pendientes, "anexos": tabla_anexos,
}

# Ancho de columna en centímetros para el documento .docx.
# El área útil de la página es de 16,1 cm (carta con márgenes de 3,0 y 2,5 cm).
ANCHOS = {
    "modulos": [3.3, 1.5, 11.0],
    "auditoria": [2.5, 3.2, 1.1, 1.3, 7.7],
    "relaciones": [1.0, 3.1, 3.3, 3.2, 1.7, 1.4, 2.1],
    "nm": [2.7, 2.7, 3.2, 7.2],
    "especializaciones": [2.9, 3.3, 2.6, 2.4, 4.6],
    "checks": [3.9, 11.9],
    "decisiones": [0.7, 3.5, 5.4, 6.2],
    "trazabilidad": [3.1, 5.3, 7.4],
    "checklist": [0.7, 6.1, 1.5, 7.5],
    "pendientes": [6.9, 8.9],
    "anexos": [5.5, 10.3],
}


# ==========================================================================
# Verificaciones de coherencia del informe
# ==========================================================================
def verificar_contenido():
    errores = []
    usadas = set()
    for _, _, ents in C.TRAZABILIDAD:
        for e in ents.split(","):
            usadas.add(e.strip())
    faltan = set(ENTIDADES) - usadas
    sobran = usadas - set(ENTIDADES)
    if faltan:
        errores.append("Entidades sin funcionalidad asociada: %s" % ", ".join(sorted(faltan)))
    if sobran:
        errores.append("Trazabilidad con entidades inexistentes: %s" % ", ".join(sorted(sobran)))
    if len(C.TRAZABILIDAD) != 42:
        errores.append("La matriz de trazabilidad tiene %d filas y el informe afirma 42."
                       % len(C.TRAZABILIDAD))
    for clave, _, _ in MODULOS:
        if clave not in C.MODULO_DESC:
            errores.append("Falta la descripción del módulo %s" % clave)
    if errores:
        print("CONTENIDO INCONSISTENTE:")
        for e in errores:
            print("  - " + e)
        sys.exit(1)
    print("Verificación del contenido: OK (%d entidades trazadas, %d funcionalidades)"
          % (len(usadas), len(C.TRAZABILIDAD)))


# ==========================================================================
# Informe en formato .docx
# ==========================================================================
def _sombrear(celda, color):
    tc = celda._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:fill"), color)
    tc.append(shd)


def _campo_toc(parrafo):
    run = parrafo.add_run()
    fld = OxmlElement("w:fldChar")
    fld.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = 'TOC \\o "1-2" \\h \\z \\u'
    sep = OxmlElement("w:fldChar")
    sep.set(qn("w:fldCharType"), "separate")
    texto = OxmlElement("w:t")
    texto.text = "Actualice este campo en Word con clic derecho > Actualizar campos (F9)."
    fin = OxmlElement("w:fldChar")
    fin.set(qn("w:fldCharType"), "end")
    for el in (fld, instr, sep, texto, fin):
        run._r.append(el)


def _pie_de_figura(doc, texto):
    p = doc.add_paragraph(texto)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.runs[0]
    r.italic = True
    r.font.size = Pt(9)
    return p


def _repetir_encabezado(fila):
    tr = fila._tr.get_or_add_trPr()
    th = OxmlElement("w:tblHeader")
    th.set(qn("w:val"), "true")
    tr.append(th)


def _agregar_tabla(doc, encabezados, filas, ancho_col=None):
    tabla = doc.add_table(rows=1, cols=len(encabezados))
    tabla.style = "Table Grid"
    tabla.alignment = WD_TABLE_ALIGNMENT.CENTER
    tabla.autofit = False
    # Diseño de tabla fijo: Word respeta los anchos declarados en lugar de
    # recalcularlos según el contenido.
    layout = OxmlElement("w:tblLayout")
    layout.set(qn("w:type"), "fixed")
    tabla._tbl.tblPr.append(layout)
    for i, texto in enumerate(encabezados):
        celda = tabla.rows[0].cells[i]
        celda.text = ""
        run = celda.paragraphs[0].add_run(texto)
        run.bold = True
        run.font.size = Pt(8.5)
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        _sombrear(celda, "1F4E79")
    _repetir_encabezado(tabla.rows[0])
    for fila in filas:
        celdas = tabla.add_row().cells
        for i, valor in enumerate(fila):
            celdas[i].text = ""
            run = celdas[i].paragraphs[0].add_run(str(valor))
            run.font.size = Pt(8.5)
    if ancho_col:
        for fila in tabla.rows:
            for i, ancho in enumerate(ancho_col):
                fila.cells[i].width = Cm(ancho)
    for fila in tabla.rows:
        for celda in fila.cells:
            for p in celda.paragraphs:
                p.paragraph_format.space_after = Pt(2)
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    return tabla


def _portada(doc):
    for _ in range(2):
        doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("SERVICIO NACIONAL DE APRENDIZAJE - SENA")
    r.bold = True
    r.font.size = Pt(14)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(C.CENTRO)
    r.font.size = Pt(12)
    doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("MODELO CONCEPTUAL DE LA BASE DE DATOS\n%s" % C.SISTEMA.upper())
    r.bold = True
    r.font.size = Pt(18)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Evidencia GA3-220501096-AA1-EV01")
    r.font.size = Pt(13)
    doc.add_paragraph()

    datos = [
        ("Programa de formación", "%s - Código %s" % (C.PROGRAMA, C.CODIGO_PROGRAMA)),
        ("Ficha", C.FICHA),
        ("Proyecto formativo", C.PROYECTO),
        ("Fase del proyecto", C.FASE),
        ("Competencia", C.COMPETENCIA),
        ("Resultado de aprendizaje", C.RAP),
        ("Actividad de aprendizaje", C.ACTIVIDAD),
        ("Evidencia de producto", C.EVIDENCIA),
        ("Aprendiz", C.APRENDIZ),
        ("Instructor", C.INSTRUCTOR),
        ("Ciudad y fecha", "%s, %s" % (C.CIUDAD, C.FECHA)),
    ]
    tabla = doc.add_table(rows=0, cols=2)
    tabla.style = "Table Grid"
    for etiqueta, valor in datos:
        celdas = tabla.add_row().cells
        celdas[0].text = ""
        run = celdas[0].paragraphs[0].add_run(etiqueta)
        run.bold = True
        run.font.size = Pt(10)
        _sombrear(celdas[0], "DEEAF6")
        celdas[1].text = ""
        celdas[1].paragraphs[0].add_run(valor).font.size = Pt(10)
        celdas[0].width = Cm(5.5)
        celdas[1].width = Cm(11.0)
    doc.add_page_break()

    # Se usa un párrafo con formato y no un encabezado, para que el título de la
    # tabla de contenido no aparezca dentro de la propia tabla de contenido.
    p = doc.add_paragraph()
    r = p.add_run("Tabla de contenido")
    r.bold = True
    r.font.size = Pt(16)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x79)
    _campo_toc(doc.add_paragraph())
    doc.add_page_break()


def generar_docx():
    doc = Document()
    estilo = doc.styles["Normal"]
    estilo.font.name = "Arial"
    estilo.font.size = Pt(11)
    estilo.paragraph_format.space_after = Pt(6)
    estilo.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    for seccion in doc.sections:
        seccion.top_margin = Cm(2.5)
        seccion.bottom_margin = Cm(2.5)
        seccion.left_margin = Cm(3.0)
        seccion.right_margin = Cm(2.5)

    _portada(doc)

    figura = 0
    for bloque in C.BLOQUES:
        tipo = bloque[0]
        if tipo in ("h1", "h2", "h3"):
            doc.add_heading(bloque[1].format(ficha=C.FICHA), level=int(tipo[1]))
        elif tipo == "p":
            doc.add_paragraph(bloque[1].format(ficha=C.FICHA))
        elif tipo == "ul":
            for item in bloque[1]:
                doc.add_paragraph(item, style="List Bullet")
        elif tipo == "ol":
            for item in bloque[1]:
                doc.add_paragraph(item, style="List Number")
        elif tipo == "img":
            figura += 1
            doc.add_picture(os.path.join(DIR_IMG, bloque[1]), width=Inches(6.3))
            doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
            _pie_de_figura(doc, bloque[2])
        elif tipo == "tabla":
            encabezados, filas = TABLAS[bloque[1]]()
            p = doc.add_paragraph()
            r = p.add_run(bloque[2])
            r.bold = True
            r.font.size = Pt(9)
            _agregar_tabla(doc, encabezados, filas, ANCHOS.get(bloque[1]))
            doc.add_paragraph()
        elif tipo == "modulos_detalle":
            for i, (clave, nombre, _) in enumerate(MODULOS, start=1):
                doc.add_heading("7.%d Módulo %s" % (i, nombre), level=2)
                doc.add_paragraph(C.MODULO_DESC[clave])
                figura += 1
                archivo = "eer_%02d_%s.png" % (i, clave)
                doc.add_picture(os.path.join(DIR_IMG, archivo), width=Inches(6.3))
                doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
                _pie_de_figura(doc, "Figura %d. Diagrama EER del módulo %s." % (figura, nombre))

    ruta = os.path.join(BASE, "GA3-220501096-AA1-EV01_Modelo_Conceptual_BD.docx")
    doc.save(ruta)
    print("Generado:", ruta)


# ==========================================================================
# Versión en Markdown
# ==========================================================================
def _tabla_md(encabezados, filas):
    L = ["| " + " | ".join(encabezados) + " |",
         "|" + "|".join(["---"] * len(encabezados)) + "|"]
    for fila in filas:
        L.append("| " + " | ".join(str(v).replace("|", "\\|") for v in fila) + " |")
    return L


def generar_markdown():
    L = ["# Modelo conceptual de la base de datos - %s" % C.SISTEMA,
         "",
         "**Evidencia GA3-220501096-AA1-EV01**",
         "",
         "| Campo | Valor |",
         "|---|---|",
         "| Programa de formación | %s - Código %s |" % (C.PROGRAMA, C.CODIGO_PROGRAMA),
         "| Ficha | %s |" % C.FICHA,
         "| Proyecto formativo | %s |" % C.PROYECTO,
         "| Fase | %s |" % C.FASE,
         "| Competencia | %s |" % C.COMPETENCIA,
         "| Resultado de aprendizaje | %s |" % C.RAP,
         "| Actividad | %s |" % C.ACTIVIDAD,
         "| Evidencia | %s |" % C.EVIDENCIA,
         "| Aprendiz | %s |" % C.APRENDIZ,
         "| Instructor | %s |" % C.INSTRUCTOR,
         "| Ciudad y fecha | %s, %s |" % (C.CIUDAD, C.FECHA),
         ""]
    figura = 0
    for bloque in C.BLOQUES:
        tipo = bloque[0]
        if tipo in ("h1", "h2", "h3"):
            L.append("")
            L.append("#" * (int(tipo[1]) + 1) + " " + bloque[1].format(ficha=C.FICHA))
            L.append("")
        elif tipo == "p":
            L.append(bloque[1].format(ficha=C.FICHA))
            L.append("")
        elif tipo == "ul":
            L.extend("- " + i for i in bloque[1])
            L.append("")
        elif tipo == "ol":
            L.extend("%d. %s" % (n, i) for n, i in enumerate(bloque[1], start=1))
            L.append("")
        elif tipo == "img":
            figura += 1
            L.append("![%s](../02-diagrama-eer/img/%s)" % (bloque[2], bloque[1]))
            L.append("")
            L.append("*%s*" % bloque[2])
            L.append("")
        elif tipo == "tabla":
            encabezados, filas = TABLAS[bloque[1]]()
            L.append("**%s**" % bloque[2])
            L.append("")
            L.extend(_tabla_md(encabezados, filas))
            L.append("")
        elif tipo == "modulos_detalle":
            for i, (clave, nombre, _) in enumerate(MODULOS, start=1):
                L.append("")
                L.append("### 7.%d Módulo %s" % (i, nombre))
                L.append("")
                L.append(C.MODULO_DESC[clave])
                L.append("")
                figura += 1
                archivo = "eer_%02d_%s.png" % (i, clave)
                L.append("![Diagrama EER del módulo %s](../02-diagrama-eer/img/%s)" % (nombre, archivo))
                L.append("")
                L.append("*Figura %d. Diagrama EER del módulo %s.*" % (figura, nombre))
                L.append("")
    ruta = os.path.join(DIR_DOCS, "01_modelo_conceptual.md")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(L))
    print("Generado:", ruta)

    # Trazabilidad y validación en archivo aparte
    T = ["# Trazabilidad y validación del modelo conceptual",
         "",
         "Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO %s." % C.FICHA,
         "",
         "## 1. Matriz de trazabilidad: funcionalidad del diseño - entidades del modelo",
         "",
         "Las %d funcionalidades definidas en `02_posibles_funcionalidades_sistema.md` están "
         "soportadas por las %d entidades del modelo conceptual." % (len(C.TRAZABILIDAD),
                                                                    len(ENTIDADES)),
         ""]
    encabezados, filas = tabla_trazabilidad()
    T.extend(_tabla_md(encabezados, filas))
    T.append("")
    T.append("## 2. Autoevaluación con el instrumento Instrumento_EV01_ModeloConceptualBD")
    T.append("")
    encabezados, filas = tabla_checklist()
    T.extend(_tabla_md(encabezados, filas))
    T.append("")
    T.append("## 3. Decisiones de diseño")
    T.append("")
    encabezados, filas = tabla_decisiones()
    T.extend(_tabla_md(encabezados, filas))
    T.append("")
    T.append("## 4. Pendientes por confirmar")
    T.append("")
    encabezados, filas = tabla_pendientes()
    T.extend(_tabla_md(encabezados, filas))
    T.append("")
    ruta = os.path.join(DIR_DOCS, "04_trazabilidad_y_validacion.md")
    with open(ruta, "w", encoding="utf-8") as f:
        f.write("\n".join(T))
    print("Generado:", ruta)


if __name__ == "__main__":
    verificar_contenido()
    generar_docx()
    generar_markdown()
