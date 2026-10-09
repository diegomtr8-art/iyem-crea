# D.14 · Documentos requeridos vs. Arts. 10, 18 y 26

**Fuente de los artículos:** Reglas de Operación, Diario Oficial 31/10/2025 (`public/formatos/RO.pdf`).
**Fuente del sistema:** `DocumentoSolicitud::tiposRequeridos()`, ejecutado por combinación.
**Estado:** diagnóstico, sin cambios de código. Las filas con ✖ o «confirmar» esperan respuesta de Diego.

Leyenda: ✔ coincide · ✖ diferencia · PF = persona física · PM = persona moral.

---

## 1. Artesanal · Art. 10 (solo persona física)

| Documento | Art. 10 | Sistema | Resultado |
|---|---|---|---|
| Acta de nacimiento | b) | Pide | ✔ |
| Acta de matrimonio | No la lista | Pide si es casado | ✖ pide de más |
| Comprobante de domicilio del solicitante **y** del negocio | c) | Pide uno solo | ✖ falta el del negocio |
| INE (frente y reverso) | d) | Pide | ✔ |
| Propiedad o posesión del negocio | h) | Pide | ✔ |
| 2 cotizaciones | i) | Pide 2 | ✔ |
| Carta de no ser empleado público | j) | Pide | ✔ |
| Constancia de repatriados (solo si falta acta o INE) | k) | No existe | Confirmar |
| CURP, foto del negocio, constancia de artesano | No las lista | Pide | Confirmar |

## 2. Emprendedores · Art. 18

| Documento | Art. 18 | Sistema | Resultado |
|---|---|---|---|
| Constancia de situación fiscal (PF y PM) | a.2 / b.2 | Pide | ✔ |
| Acta de nacimiento (solo PF) | a.3 | PF y PM | PF ✔ · PM ✖ pide de más |
| Acta de matrimonio, si es casado (solo PF) | a.4 | PF y PM | PF ✔ · PM ✖ pide de más |
| Comprobante de domicilio del solicitante **y** del negocio | a.5 / b.3 | Pide uno solo | ✖ falta el del negocio |
| INE del solicitante (PF) o del representante legal (PM) | a.6 / b.4 | PF: INE · PM: INE personal **y** `id_rep_legal` | PF ✔ · PM ✖ INE personal sobra |
| Propiedad o posesión del negocio | a.10 / b.7 | Pide | ✔ |
| 3 cotizaciones | a.11 / b.9 | Pide 3 | ✔ |
| Factura del bien (si la garantía es prendaria) | a.12 / b.11 | Pide | ✔ |
| Buró de crédito (máx. 180 días) | a.13 / b.12 | Pide | ✔ |
| Carta de no ser empleado público | a.14 / b.13 | Pide | ✔ |
| Acta constitutiva y poderes (PM) | b.8 (un solo documento) | `acta_constitutiva` y `poder_rep_legal` por separado | Confirmar |
| Balance general y estado de resultados (PM) | b.6 | Pide | ✔ |
| Constancia de repatriados (solo si falta acta o INE) | a.15 | No existe | Confirmar |
| CURP y foto del negocio | No las lista | Pide | Confirmar |

## 3. Sustentable · Art. 26

| Documento | Art. 26 | Sistema | Resultado |
|---|---|---|---|
| Constancia de situación fiscal (PF y PM), con 1 año de antigüedad y máx. 30 días | a.2 / b.2 | **No la pide** | ✖ falta |
| Acta de nacimiento (solo PF) | a.3 | PF y PM | PF ✔ · PM ✖ pide de más |
| Acta de matrimonio, si es casado (solo PF) | a.4 | PF y PM | PF ✔ · PM ✖ pide de más |
| Comprobante de domicilio del solicitante **y** del negocio | a.5 / b.3 | Pide uno solo | ✖ falta el del negocio |
| INE del solicitante (PF) o del representante legal (PM) | a.6 / b.4 | PF: INE · PM: INE personal **y** `id_rep_legal` | PF ✔ · PM ✖ INE personal sobra |
| Plan de trabajo sostenible | a.8 / b.6 | Pide | ✔ |
| Propiedad o posesión del negocio | a.11 / b.8 | Pide | ✔ |
| 3 cotizaciones | a.12 / b.10 | Pide 3 | ✔ |
| Factura del bien (si la garantía es prendaria) | a.13 / b.12 | Pide | ✔ |
| Escritura del inmueble (**solo** si la garantía es hipotecaria) | a.14 / b.13 | Pide si el monto es ≥ $200 mil, **sea cual sea la garantía**; con hipotecaria pide además `doc_propiedad_inmueble` | ✖ mal condicionada y duplicada |
| Buró de crédito (máx. 180 días) | a.15 / b.14 | Pide | ✔ |
| Opinión de cumplimiento SAT (32-D) | a.16 / b.15 | Pide | ✔ |
| Carta de no ser empleado público | a.17 / b.16 | Pide | ✔ |
| Acta constitutiva y poderes (PM) | b.9 (un solo documento) | Por separado | Confirmar |
| Balance general y estado de resultados (PM) | b.7 | Pide | ✔ |
| Constancia de repatriados (solo si falta acta o INE) | a.18 | No existe | Confirmar |
| CURP y foto del negocio | No las lista | Pide | Confirmar |

## 4. Aval (garantía personal)

| Documento | Art. 10 · 18 · 26 (II) | Sistema | Resultado |
|---|---|---|---|
| Acta de nacimiento | a) | Pide | ✔ |
| Acta de matrimonio, si es casado | b) | Pide según el estado civil del aval | ✔ |
| Comprobante de domicilio | c) | Pide | ✔ |
| Identificación oficial | d) | Pide | ✔ |
| Declaración de bienes (anexo 4) | e) | Se genera en el ZIP de formatos | Confirmar que no se sube |
| Escritura, título o factura del bien del aval (solo Emprendedores y Sustentable) | f) | No existe | ✖ falta, confirmar |

## 5. Garantía × modalidad

| Combinación | Sistema | Artículo | Resultado |
|---|---|---|---|
| Hipotecaria en Artesanal o Emprendedores | Se acepta y pide `doc_propiedad_inmueble` | Solo existe en Sustentable | ✖ el front la bloquea, el modelo no |
| Hipotecaria en Sustentable con monto < $200 mil | Se acepta | Solo aplica con monto ≥ $200 mil | ✖ combinación inválida aceptada |
| Sustentable ≥ $200 mil con aval | Pide `escritura_hipotecaria` | No aplica, no hay hipoteca | ✖ pide de más |

## 6. Documentos posteriores a la aprobación

| Documento | Artículo | Sistema | Resultado |
|---|---|---|---|
| Fotografía de fachada | 10 l) · 18 a.16 / b.14 · 26 a.19 / b.17 | `foto_fachada` | ✔ |
| Enlace de Google Maps | ídem | `google_maps_negocio` | ✔ |
| Carátula de estado de cuenta | ídem | `cuenta_bancaria_caratula` | ✔ (el «últimos 3 meses» no viene en el artículo) |

**Visibilidad (comprobada en el servidor local):** con cualquier estatus distinto de `Aprobada` la subida responde 403. Con `Aprobada` pasa. Estos tipos nunca aparecen en Mi Expediente como requeridos.

## 7. Diferencias abiertas

| # | Diferencia | Acción |
|---|---|---|
| 1 | Falta el comprobante de domicilio del negocio | Confirmar con Diego: ¿se pide uno solo si es el mismo domicilio? |
| 2 | PM pide acta de nacimiento, acta de matrimonio e INE personal | Corregir: quitarlos en PM |
| 3 | Artesanal pide acta de matrimonio del solicitante | Confirmar con Diego |
| 4 | Sustentable: escritura atada al monto y duplicada | Corregir: atarla a la garantía hipotecaria |
| 5 | Sustentable no pide constancia de situación fiscal | Corregir |
| 6 | Falta escritura o factura del bien del aval (E y S) | Confirmar con Diego |
| 7 | Hipotecaria aceptada en A, E y en S < $200 mil | Confirmar con Diego |
| 8 | Extras: CURP, foto del negocio, constancia de artesano; poder separado del acta | Confirmar con Diego |
| 9 | Repatriados y excepciones por «innovación» o «sin operaciones» no modeladas | Confirmar con Diego (probable fuera de alcance) |

---

**Nota técnica:** la lista está duplicada en `DocumentoSolicitud::tiposRequeridos()` (backend) y en `tiposDocRequeridos` de `resources/js/Pages/portal/WizardCredito.vue` (frontend). Cualquier corrección debe aplicarse en los dos.
