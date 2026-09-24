| Módulo | ¿Tabla? | ¿Modelo? | ¿Controlador? | ¿Vistas Vue? | ¿Tiene datos? | Recomendación | Observaciones | 
|:---:|:---:|:---:|:---:|:---:|:---:|:---:| :---|
| Interesados | Sí | Sí | Sí | Create, Edit, Index | No | Reactivar | Aunque está la tabla sin datos si es utilizado por acreditadoscontroller |
| Jurídico de cobranza | Sí | Sí | Sí | Index, Show | Si | Completar | Tanto en Juridico/index.vue como Juridico/show.vue marca error en las líneas con size ya que no se puede asignar un string a un número. <br>En Juridico/show.vue las líneas 45 y 263 marca este error: "Argumento de tipo número no se puede asignar a un parámetro de tipo: 'RouteParams<string> | undefined'".
| Presupuesto | Sí | Sí | Sí | Index | No | Reactivar | Aunque la tabla esté sin datos, se utiliza tanto en DesembolsoController como en SolicitudOperativoController |

