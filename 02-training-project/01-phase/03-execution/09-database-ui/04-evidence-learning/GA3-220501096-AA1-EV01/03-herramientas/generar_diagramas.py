# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01
Genera las imágenes del modelo conceptual a partir de modelo_datos.py y
modelo_relaciones.py:

  02-diagrama-eer/img/eer_00_mapa_modulos.png   Mapa de módulos y sus relaciones
  02-diagrama-eer/img/eer_0N_<modulo>.png       Diagrama EER de cada módulo

Las imágenes se incrustan en el informe .docx y sirven como vista previa de la
evidencia; el diagrama de referencia se construye en la herramienta CASE con
el script modelo_conceptual_hotel.sql.

Uso:  python generar_diagramas.py
"""
import os
import sys

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyArrowPatch, Rectangle

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from modelo_datos import ENTIDADES, MODULOS
from modelo_relaciones import RELACIONES

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DIR_IMG = os.path.join(BASE, "02-diagrama-eer", "img")

ANCHO = 4.6          # ancho de la caja de entidad
ALTO_TITULO = 0.62   # alto del encabezado
ALTO_FILA = 0.34     # alto de cada atributo
SEP_COL = 6.0        # separación horizontal entre columnas
SEP_FILA = 1.0       # separación vertical entre filas

COLOR_MODULO = {k: c for k, _, c in MODULOS}
NOMBRE_MODULO = {k: n for k, n, _ in MODULOS}


def alto_caja(ent):
    return ALTO_TITULO + ALTO_FILA * (len(ent["attrs"]) + 1)  # +1 por el id


def marca(attr):
    if "FK" in attr[3] and "UK" in attr[3]:
        return "FK,U"
    if "FK" in attr[3]:
        return "FK"
    if "UK" in attr[3]:
        return "U"
    return ""


def dibujar_entidad(ax, ent, x, y_top, color):
    """Dibuja la caja de una entidad con su encabezado y sus atributos."""
    h = alto_caja(ent)
    ax.add_patch(Rectangle((x, y_top - h), ANCHO, h, facecolor="white",
                           edgecolor=color, linewidth=1.4, zorder=2))
    ax.add_patch(Rectangle((x, y_top - ALTO_TITULO), ANCHO, ALTO_TITULO,
                           facecolor=color, edgecolor=color, linewidth=1.4, zorder=3))
    etiqueta = ent["nombre"]
    if ent["tipo"] == "asociativa":
        etiqueta += "  (asociativa)"
    elif ent["tipo"] == "subtipo":
        etiqueta += "  (subtipo)"
    elif ent["tipo"] == "debil":
        etiqueta += "  (dependiente)"
    ax.text(x + ANCHO / 2, y_top - ALTO_TITULO / 2, etiqueta, ha="center", va="center",
            fontsize=8.2, fontweight="bold", color="white", zorder=4)

    filas = [("PK", "id", "BIGINT")] + [(marca(a), a[0], a[1] + ("" if a[2] else " *"))
                                        for a in ent["attrs"]]
    yy = y_top - ALTO_TITULO
    for m, nombre, tipo in filas:
        yy -= ALTO_FILA
        ax.text(x + 0.12, yy + ALTO_FILA / 2, m, ha="left", va="center",
                fontsize=6.0, color="#b02020", fontweight="bold", zorder=4)
        ax.text(x + 0.78, yy + ALTO_FILA / 2, nombre, ha="left", va="center",
                fontsize=6.6, color="#111111", zorder=4)
        ax.text(x + ANCHO - 0.12, yy + ALTO_FILA / 2, tipo, ha="right", va="center",
                fontsize=5.8, color="#555555", zorder=4)
    return (x, y_top - h, x + ANCHO, y_top)   # x0, y0, x1, y1


def borde(caja, hacia):
    """Punto del borde de la caja en la dirección del punto 'hacia'."""
    x0, y0, x1, y1 = caja
    cx, cy = (x0 + x1) / 2.0, (y0 + y1) / 2.0
    dx, dy = hacia[0] - cx, hacia[1] - cy
    if dx == 0 and dy == 0:
        return cx, cy
    escalas = []
    if dx != 0:
        escalas.append(abs((x1 - x0) / 2.0 / dx))
    if dy != 0:
        escalas.append(abs((y1 - y0) / 2.0 / dy))
    t = min(escalas)
    return cx + dx * t, cy + dy * t


def dibujar_relacion(ax, caja_hijo, caja_padre, r, color="#444444"):
    ch = ((caja_hijo[0] + caja_hijo[2]) / 2.0, (caja_hijo[1] + caja_hijo[3]) / 2.0)
    cp = ((caja_padre[0] + caja_padre[2]) / 2.0, (caja_padre[1] + caja_padre[3]) / 2.0)
    p1 = borde(caja_padre, ch)
    p2 = borde(caja_hijo, cp)
    estilo = (0, (5, 3)) if r["opcional"] else "solid"
    ax.add_patch(FancyArrowPatch(p1, p2, arrowstyle="-", linewidth=1.1,
                                 color=color, linestyle=estilo, zorder=1,
                                 shrinkA=0, shrinkB=0))
    lado_hijo = "1" if r["card"] == "1:1" else "N"
    for punto, otro, texto in ((p1, p2, "1"), (p2, p1, lado_hijo)):
        ux, uy = otro[0] - punto[0], otro[1] - punto[1]
        norma = (ux ** 2 + uy ** 2) ** 0.5 or 1.0
        tx = punto[0] + ux / norma * 0.42
        ty = punto[1] + uy / norma * 0.42
        ax.text(tx, ty, texto, fontsize=7.2, fontweight="bold", color=color,
                ha="center", va="center", zorder=5,
                bbox=dict(boxstyle="round,pad=0.12", facecolor="white",
                          edgecolor="none", alpha=0.85))


def diagrama_modulo(clave, indice):
    ents = [e for e in ENTIDADES.values() if e["modulo"] == clave]
    color = COLOR_MODULO[clave]
    columnas = sorted(set(e["pos"][0] for e in ents))
    filas = sorted(set(e["pos"][1] for e in ents))
    alto_fila_mapa = {}
    for f in filas:
        alto_fila_mapa[f] = max(alto_caja(e) for e in ents if e["pos"][1] == f)
    y_fila = {}
    y = 0.0
    for f in filas:
        y_fila[f] = y
        y -= (alto_fila_mapa[f] + SEP_FILA)

    ancho_total = (max(columnas) + 1) * SEP_COL
    alto_total = abs(y) + 2.2

    fig, ax = plt.subplots(figsize=(max(9.0, ancho_total * 0.9), max(5.5, alto_total * 0.72)))
    cajas = {}
    for e in ents:
        col, fil = e["pos"]
        cajas[e["nombre"]] = dibujar_entidad(ax, e, col * SEP_COL, y_fila[fil], color)

    propias = set(cajas)
    externas = []
    for r in RELACIONES:
        if r["hijo"] in propias and r["padre"] in propias:
            dibujar_relacion(ax, cajas[r["hijo"]], cajas[r["padre"]], r, color="#333333")
        elif r["hijo"] in propias or r["padre"] in propias:
            externas.append(r)

    nota_y = y - 0.4
    ax.text(0, nota_y + 0.55,
            "Notación: línea continua = participación obligatoria (FK NOT NULL); "
            "línea punteada = participación opcional (FK NULL).  "
            "* = atributo obligatorio.  PK/FK/U = llave primaria, foránea, única.",
            fontsize=6.8, color="#444444", ha="left", va="top")
    if externas:
        texto = "Relaciones con otros módulos: " + ";  ".join(
            "%s.%s → %s [%s]" % (
                r["hijo"], r["fk"], r["padre"],
                NOMBRE_MODULO[ENTIDADES[r["padre"] if r["hijo"] in propias else r["hijo"]]["modulo"]])
            for r in externas)
        ax.text(0, nota_y + 0.15, texto, fontsize=6.4, color="#444444",
                ha="left", va="top", wrap=True)

    ax.set_title("Modelo conceptual EER - Módulo %s\nSistema de hotelería - Ficha ADSO 3239137"
                 % NOMBRE_MODULO[clave], fontsize=11, fontweight="bold", color=color, loc="left")
    ax.set_xlim(-0.8, (max(columnas) + 1) * SEP_COL)
    ax.set_ylim(nota_y - 0.8, 1.0)
    ax.axis("off")
    fig.tight_layout()
    ruta = os.path.join(DIR_IMG, "eer_%02d_%s.png" % (indice, clave))
    fig.savefig(ruta, dpi=200, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    print("Generado:", ruta)


def diagrama_mapa_modulos():
    posiciones = {
        "parametrizacion": (0, 2), "distribucion": (1, 2), "seguridad": (2, 2),
        "prestacion": (1, 1), "inventario": (0, 1), "mantenimiento": (2, 1),
        "facturacion": (1, 0), "notificacion": (0, 0),
    }
    conteo = {}
    for e in ENTIDADES.values():
        conteo[e["modulo"]] = conteo.get(e["modulo"], 0) + 1

    enlaces = {}
    for r in RELACIONES:
        a = ENTIDADES[r["hijo"]]["modulo"]
        b = ENTIDADES[r["padre"]]["modulo"]
        if a != b:
            enlaces[(a, b)] = enlaces.get((a, b), 0) + 1

    ancho, alto = 4.2, 1.5
    fig, ax = plt.subplots(figsize=(13, 8))
    centros = {}
    for clave, (c, f) in posiciones.items():
        x, y = c * 5.6, f * 3.0
        centros[clave] = (x + ancho / 2, y + alto / 2)
        ax.add_patch(Rectangle((x, y), ancho, alto, facecolor=COLOR_MODULO[clave],
                               edgecolor="black", linewidth=1.0, alpha=0.92, zorder=3))
        ax.text(x + ancho / 2, y + alto * 0.62, NOMBRE_MODULO[clave], ha="center", va="center",
                fontsize=11, fontweight="bold", color="white", zorder=4)
        ax.text(x + ancho / 2, y + alto * 0.27, "%d entidades" % conteo[clave], ha="center",
                va="center", fontsize=9, color="white", zorder=4)

    rects = []
    for clave, (c, f) in posiciones.items():
        x, y = c * 5.6, f * 3.0
        rects.append((x - 0.25, y - 0.25, x + ancho + 0.25, y + alto + 0.25))

    def libre(px, py):
        return not any(x0 <= px <= x1 and y0 <= py <= y1 for x0, y0, x1, y1 in rects)

    for (a, b), n in enlaces.items():
        p1, p2 = centros[a], centros[b]
        ax.add_patch(FancyArrowPatch(p1, p2, arrowstyle="-|>", mutation_scale=12,
                                     linewidth=1.0 + 0.35 * n, color="#666666",
                                     connectionstyle="arc3,rad=0.12", zorder=2,
                                     shrinkA=42, shrinkB=42))
        # La etiqueta se ubica en el primer punto de la línea que no quede
        # encima de una caja de módulo.
        dx, dy = p2[0] - p1[0], p2[1] - p1[1]
        norma = (dx ** 2 + dy ** 2) ** 0.5 or 1.0
        px_, py_ = -dy / norma, dx / norma          # vector perpendicular
        posicion = None
        for t in (0.5, 0.4, 0.6, 0.3, 0.7, 0.25, 0.75):
            for desp in (0.35, -0.35, 0.7, -0.7):
                cx = p1[0] + dx * t + px_ * desp
                cy = p1[1] + dy * t + py_ * desp
                if libre(cx, cy):
                    posicion = (cx, cy)
                    break
            if posicion:
                break
        if posicion is None:
            posicion = (p1[0] + dx * 0.5, p1[1] + dy * 0.5)
        ax.text(posicion[0], posicion[1], str(n), fontsize=8, fontweight="bold", color="#333333",
                ha="center", va="center", zorder=5,
                bbox=dict(boxstyle="circle,pad=0.22", facecolor="white", edgecolor="#666666"))

    ax.set_title("Modelo conceptual del sistema de hotelería - mapa de módulos\n"
                 "%d entidades y %d relaciones. La flecha va del módulo que contiene la llave "
                 "foránea hacia el módulo referenciado; el número indica cuántas relaciones existen."
                 % (len(ENTIDADES), len(RELACIONES)),
                 fontsize=12, fontweight="bold", loc="left")
    ax.set_xlim(-0.8, 2 * 5.6 + ancho + 0.8)
    ax.set_ylim(-0.8, 2 * 3.0 + alto + 0.8)
    ax.axis("off")
    fig.tight_layout()
    ruta = os.path.join(DIR_IMG, "eer_00_mapa_modulos.png")
    fig.savefig(ruta, dpi=200, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    print("Generado:", ruta)


if __name__ == "__main__":
    if not os.path.isdir(DIR_IMG):
        os.makedirs(DIR_IMG)
    diagrama_mapa_modulos()
    for i, (clave, _, _) in enumerate(MODULOS, start=1):
        diagrama_modulo(clave, i)
