# Auditoría del sistema CREA

En esta carpeta guardo el trabajo de revisión que haré sobre el sistema CREA del IYEM.

## Qué voy a hacer

Revisaré el sistema módulo por módulo: el portal ciudadano, las solicitudes de crédito, los pagos, la cobranza y los reportes. Me interesa comprobar tres cosas. Que el sistema cumpla con las Reglas de Operación del programa, que los datos personales de los solicitantes estén protegidos y que los flujos funcionen de principio a fin sin errores.

No parto de cero. En la raíz del proyecto ya existen auditorías anteriores (`AUDITORIA_PORTAL_CIUDADANO.md`, `CHANGELOG_AUDITORIA.md` y `PENDIENTES_AUTH.md`). Las usaré como punto de partida para confirmar qué se corrigió y qué sigue pendiente.

## Cómo registro los hallazgos

Cada hallazgo indica el archivo afectado, qué problema encontré y qué propongo para resolverlo. Los clasifico por prioridad, con la misma escala de las auditorías previas:

| Prioridad | Significado |
|-----------|-------------|
| P0 | Crítico. Bloquea un proceso o expone información sensible. |
| P1 | Alto. Incumple las Reglas de Operación o representa un riesgo de seguridad. |
| P2 | Medio. Afecta la experiencia de uso, pero no detiene el proceso. |
| P3 | Bajo. Mejoras menores o de presentación. |

Primero atenderé los P0 y los P1. Los demás quedarán documentados para resolverse después.
