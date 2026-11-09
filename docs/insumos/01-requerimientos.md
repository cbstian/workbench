# Requerimientos de Sebastián — Workbench

> Copia literal del mensaje original (2026-10-11) y de sus respuestas.

## Mensaje original (2026-10-11)

Voy a crear mi propio sistema web para la gestión de tareas, proyectos y documentación.
He estado ocupando https://plane.so/ y me gusta mucho pero al ir creciendo el plan gratuito me queda chico y no quiero comprar licencias.
Otro proyecto que me gusta mucho es https://taskview.tech pero no tiene la posibilidad de incluir una wiki o documentación asociada a cada proyecto.

Lo que quiero es poder realizar lo siguiente:
Cada usuarios debe tener un workspace como entidad superior.
Crear clientes, como entidad superior. Cada cliente puede tener cero o muchos proyectos.
Poder incluir logo, color e información asociada al cliente.
Poder crear usuarios para los clientes y definir el tipo de acceso que deben tener.
Los usuarios de tipo cliente son distintos a los usuarios normales.
Crear proyectos definiendo nombre, descripción e icono.
Un proyecto puede o no estar relacionado a un cliente. Se debe poder definir si un proyecto es visible o no para el cliente y sus usuarios.
Cada proyecto puede tener tareas
Wiki o paginas de contenido.

las tareas deben poder tener
Nombre, descripción, archivos adjuntos, comentarios, historial de cambios, fecha de inicio y termino.
Poder asociar tareas a los usuarios.
Etiquetas personalizables para las tareas.
Estados personalizables, por defecto son: backlog, todo, in progress, done, cancelled

Los proyectos deben poder visualizar las tareas en formato kanban, listado, calendario, filtros etc.

Dentro del workspace se debe poder crear una wiki o paginas de documentación, subir archivos, etiquetar el contenido.

Ayudame a crear el plan o flujo canonio, a definir un nombre y cerrar cosas pendientes.

## Preguntas y respuestas (2026-10-11)

1. ¿Registro abierto o solo él y su equipo? → «Sin registro».
2. ¿Varios usuarios internos por workspace y un usuario en varios workspaces? → «Sí».
3. Niveles de acceso del usuario cliente → «Ver, comentar».
4. Wiki → «Wiki a nivel de workspace y proyectos, poder elegir qué documentación ve el cliente».
5. Estados y etiquetas → «por proyecto, definir por defecto al momento de crear, pero que permita modificarlos».
6. Fuera del MVP → «Incluir subtareas, prioridad» (se preguntó por subtareas, prioridad, notificaciones por correo y menciones; notificaciones por correo y menciones quedan fuera del MVP).
7. Nombre → «Workbench».

## Respuestas 2026-10-12

> Copia literal. Correspondencia de la numeración de Sebastián con las preguntas del flujo: 1=P-01, 2=P-02, 3=P-17, 4=P-03, 5=P-18, 6-9=P-05/P-04/P-06/P-14, 10=P-12, 11=P-08, 12=P-09, 13=P-07, 14=P-13, 15=P-10, 16=P-15, 17=P-11, 18=P-16, 19=P-19.

Usuarios y permisos
1. Por consola se crean los usuarios, con un comando artisan. Los workspace los van creando los mismos usuarios.
2. el usario creado por consola es el super_admin de su cuenta, puede crear N workspace. Dentro del workspace pueden haber admins, miembros y clientes, solo el super_admin puede crearlos todos.
3. El usuario solo ve los workspace que creo o a los cuales fue invitado.
4. un cliente si puede ser mas de un cliente
5. El super admin puede desactivar el acceso o eliminar usuarios. El borrado y el bloqueo es lógico.

Lo que ve el cliente
Por ahora no definamos nada del cliente, solo que pueda acceder pero sin ver nada.

Tareas
10. Si, pero no a un cliente
11. ninguna, baja, media, alta, urgente
12. 1 nivel de subtarea, mismos datos
13. Solo esas vistas por ahora, si, la idea es que los filtros queden activos
14. Si se borra un estado se borra de todas las tareas a las que fue aplicado, no hay etiquetas por defecto, solo estados y prioridad
15. si, papelera y archivado.

Wiki y datos
16. Si, subpaginas y solo historial de cambios.
17. No, etiquetas distintas.
18. nombre, correo, password, descripción, logo
19. Interfaz solo en español, todo el desarrollo en ingles

## Respuestas 2026-10-12 (2)

> Copia literal. Correspondencia: 1=P-20, 2=P-21, 3=P-22, 4=P-23, 5=P-24, 6=P-25, 7=P-26, 8=P-27, 9=P-28. La última línea responde sobre la fecha de término (alternativa A-06).

1. Quedan sin estado
2. solo ve los proyectos a los que fue asignado
3. me equivoque, nombre, correo, password son del usuario cliente, descripción y logo del Cliente
4. admin puede: hacer todas las acciones dentro de un proyecto. No puede crear proyectos y puede crear miembros con acceso a los proyectos que el tiene acceso.
el miembro puede: ver los proyectos asignados y todas las acciones dentro de las tareas asignadas, puede crear nuevas tareas y asignarlas a el o al equipo.
ambos pueden acceder a toda la documentación del proyecto, solo los super_admin acceden a la documentación del workspace.
5. simplificar, todo se va a la papelera (por ahora no se puede vaciar la papelera), no incluyas la opción de archivar
6. Los filtros se recuerdan por usuario. Los campos nose aun, propone un stack simple por ahora.
7. Si
8. Los archivos van dentro de las páginas, no hay biblioteca. Las etiquetas de la wiki con por workspace.
9. Si, prioridad y estado configurable, el usaurio configura en cual parte.

Fecha de termino no puede quedar antes que la de inicio

## Respuestas 2026-10-12 (3)

> Copia literal. Correspondencia: 1=P-23 (resto), 2=P-30, 3=P-31, 4=P-29, 5=P-32 (Backlog, Por hacer, En curso, Hecho, Cancelado), 6=P-33.

1. Solo super admin puede crear clientes y workspace.
2. solo el super admin
3. todas las tareas, solo puede editar las que fue asignado
4. Al momento de crearlas el estado es requerido
5. Si
6. Si

## Respuestas 2026-10-12 (4)

> Copia literal. Correspondencia: P-29 (resto: cómo se muestran en kanban las Tareas que quedan sin estado al borrarse el suyo; se propuso una columna «Sin estado»). Reemplaza la respuesta 1 de «Respuestas 2026-10-12 (2)» (P-20).

Definir un estado por defecto

## Respuestas 2026-10-12 (5)

> Copia literal. Correspondencia: P-34. Pregunta propuesta: «no se puede borrar el estado inicial del Proyecto hasta elegir otro como inicial».

ok
