# Flujo canónico — Workbench

> Qué hace el sistema, en lenguaje de negocio. **Sin tecnología**: nada de frameworks, tablas, endpoints ni pantallas concretas. Sale de `insumos/` de la carpeta del plan; lo que no esté ahí va a "Preguntas abiertas", no se inventa.
> Fecha: 2026-10-11 · Actualizado: 2026-10-12 (aplica las respuestas de Sebastián) · 2026-10-12 (aplica la tercera ronda de respuestas) · 2026-10-12 (aplica la cuarta ronda de respuestas) · 2026-11-24 (E11 · Pizarra)
> Base: `insumos/01-requerimientos.md` (mensaje de Sebastián y respuestas del 2026-10-11 y del 2026-10-12). Plane y TaskView son referencias, no requisitos.
> Nombre: **Workbench**.

## 1. Resumen y objetivo
Sebastián gestiona tareas, proyectos y documentación con herramientas de terceros. Al crecer, el plan gratuito le queda chico y no quiere comprar licencias; además, no todas las herramientas permiten tener una wiki o documentación asociada a cada proyecto.

Workbench es un sistema web propio para gestionar, dentro de Workspaces, Clientes, Proyectos, Tareas y documentación (wiki con archivos). Los usuarios se crean por consola; cada uno es el Super admin de su cuenta y desde ahí crea sus Workspaces, sus Proyectos y los usuarios que trabajan en ellos.

El MVP funciona si:
- un Super admin creado por consola crea sus Workspaces, Clientes, Proyectos, admins, miembros y usuarios cliente, agrega usuarios existentes a los Proyectos y puede desactivar o eliminar usuarios;
- cada admin y miembro ve solo los Proyectos a los que fue asignado; el admin hace todo dentro de sus Proyectos y puede crear miembros para ellos; el miembro ve todas las Tareas de sus Proyectos, edita solo las que tiene asignadas y crea Tareas nuevas;
- cada Proyecto gestiona Tareas con estados y prioridades configurables, etiquetas, subtareas de un nivel, varios asignados, adjuntos, comentarios, historial y fechas;
- las Tareas se ven en kanban, listado y calendario, con filtros que se recuerdan por usuario;
- el Workspace y cada Proyecto tienen su wiki, con subpáginas, historial de cambios, archivos dentro de las Páginas y etiquetas de wiki por Workspace;
- todo lo borrado va a la papelera y se puede restaurar;
- el usuario cliente puede acceder, pero por ahora no ve nada.

## 2. Actores
| Actor | Qué necesita lograr |
|---|---|
| Super admin | Usuario creado por consola; dueño de su cuenta. Único que crea Workspaces (N), Clientes y Proyectos; crea admins, miembros y usuarios cliente; único que agrega usuarios existentes a un Proyecto; puede desactivar el acceso o eliminar usuarios (de forma lógica); es el único que accede a la documentación del Workspace |
| Admin | Ve solo los Proyectos a los que fue asignado y hace todas las acciones dentro de ellos; no crea Proyectos; puede crear miembros nuevos con acceso a sus Proyectos (no agrega usuarios existentes); accede a toda la documentación de sus Proyectos |
| Miembro | Ve solo los Proyectos a los que fue asignado y todas sus Tareas; hace todas las acciones dentro de las Tareas que tiene asignadas y solo edita esas; crea Tareas nuevas y las asigna a sí mismo o al equipo; accede a toda la documentación de sus Proyectos |
| Usuario cliente | Usuario con nombre, correo y password, distinto de admin y miembro; pertenece a uno o más Clientes. Por ahora puede acceder, pero sin ver nada |

Cada usuario ve solo los Workspaces que creó o a los que fue invitado. Solo el Super admin crea Clientes y Workspaces.

## 3. Alcance
- **Entra en v1 (MVP):**
  - creación de usuarios por consola, sin registro abierto; cada usuario creado así es Super admin de su cuenta;
  - Workspaces creados solo por el Super admin (N por cuenta); cada usuario ve solo los que creó o a los que fue invitado;
  - roles dentro del Workspace: admin, miembro y cliente; el Super admin crea los tres; el admin también puede crear miembros nuevos con acceso a sus Proyectos;
  - desactivar el acceso y eliminar usuarios, ambos de forma lógica;
  - Clientes dentro del Workspace, creados solo por el Super admin, con nombre, descripción, logo y color; cero o muchos Proyectos por Cliente;
  - Proyectos creados por el Super admin, con nombre, descripción e icono, con o sin Cliente, con su equipo (usuarios asignados; solo el Super admin agrega usuarios existentes) y marca de visibilidad para el Cliente (registrada, sin efecto visible por ahora);
  - estados y prioridades configurables por Proyecto, con valores por defecto, y con el valor inicial de una Tarea nueva configurable; etiquetas de tarea por Proyecto, sin etiquetas por defecto;
  - Tareas con nombre, descripción, adjuntos, comentarios, historial de cambios, fecha de inicio y término, varios asignados, etiquetas, estado (obligatorio al crearla; si se borra su estado, pasa al estado inicial configurado en su Proyecto), prioridad y subtareas de un nivel;
  - vistas kanban, listado y calendario, con filtros que se recuerdan por usuario;
  - wiki a nivel de Workspace (solo Super admin) y de Proyecto (admin y miembro), con subpáginas, historial de cambios, archivos dentro de las Páginas y etiquetas de wiki por Workspace; marca de qué documentación ve el Cliente (registrada, sin efecto visible por ahora);
  - papelera para todo lo que se borra, con restauración y sin poder vaciarla por ahora;
  - acceso del usuario cliente, sin ver nada por ahora;
  - interfaz solo en español, con los estados por defecto traducidos.
- **No entra (explícito):**
  - registro abierto;
  - qué ve y qué hace el usuario cliente (diferido: P-04, P-05, P-06, P-14);
  - archivado;
  - vaciar la papelera;
  - biblioteca de archivos fuera de las Páginas;
  - notificaciones por correo;
  - menciones;
  - vistas distintas de kanban, listado y calendario (la Pizarra del Workspace entra después del MVP, en E11);
  - más de un nivel de subtareas;
  - asignar Tareas a usuarios cliente;
  - versiones de Páginas de la wiki (solo historial de cambios);
  - interfaz en otro idioma;
  - cobros, planes o licencias.

## 4. Glosario
> Un término = un significado. Se usan igual en todos los documentos (y, traducidos al inglés, en el código).

| Término | Definición | Sinónimos a evitar |
|---|---|---|
| Cuenta | Ámbito de un Super admin: sus Workspaces y los usuarios que se crean en ellos | tenant, organización |
| Super admin | Usuario creado por consola, dueño de su cuenta: único que crea Workspaces, Clientes y Proyectos; crea admins, miembros y usuarios cliente; único que agrega usuarios existentes a un Proyecto; puede desactivar o eliminar usuarios; único con acceso a la documentación del Workspace | root, dueño |
| Workspace | Entidad superior donde vive el trabajo: Clientes, Proyectos, Tareas y documentación. Lo crea solo el Super admin; tiene usuarios con rol admin, miembro o cliente | espacio, organización |
| Rol | Papel de un usuario dentro de un Workspace: admin, miembro o cliente | perfil, permiso |
| Admin | Usuario con rol admin: ve solo sus Proyectos asignados, hace todas las acciones dentro de ellos y puede crear miembros nuevos con acceso a ellos; no crea Proyectos ni agrega usuarios existentes | administrador (suelto) |
| Miembro | Usuario con rol miembro: ve solo sus Proyectos asignados y todas sus Tareas, hace todas las acciones dentro de sus Tareas asignadas (solo edita esas) y crea Tareas nuevas | usuario normal |
| Equipo del Proyecto | Usuarios (admins y miembros) asignados a un Proyecto; solo el Super admin agrega usuarios existentes | integrantes |
| Usuario cliente | Usuario con rol cliente, con nombre, correo y password; distinto de admin y miembro; pertenece a uno o más Clientes; por ahora accede sin ver nada | invitado, externo |
| Usuario desactivado | Usuario cuyo acceso fue bloqueado por el Super admin; bloqueo lógico | — |
| Usuario eliminado | Usuario borrado por el Super admin; borrado lógico | — |
| Cliente | Entidad superior dentro del Workspace (Workspace > Cliente > Proyecto), con nombre, descripción, logo y color; lo crea solo el Super admin; tiene cero o muchos Proyectos y sus usuarios cliente | empresa, cuenta |
| Proyecto | Unidad de trabajo con nombre, descripción e icono, creada por el Super admin; puede o no estar relacionada con un Cliente; tiene equipo, Tareas, estados, prioridades, etiquetas y wiki propia | iniciativa |
| Visible para el cliente | Marca de un Proyecto o de una documentación para el Cliente. Se registra, pero por ahora no tiene efecto: el usuario cliente no ve nada | público, compartido |
| Tarea | Trabajo dentro de un Proyecto: nombre, descripción, adjuntos, comentarios, historial de cambios, fecha de inicio y término, asignados, etiquetas, estado, prioridad y subtareas | ticket, issue, ítem |
| Subtarea | Tarea hija de otra Tarea, con los mismos datos; un solo nivel | checklist |
| Estado | Etapa de avance de una Tarea, configurable por Proyecto. Por defecto, en español: Backlog, Por hacer, En curso, Hecho, Cancelado. Es obligatorio al crear una Tarea; si se borra su estado, pasa al estado inicial configurado en su Proyecto; una Tarea nunca queda sin estado | columna, fase |
| Prioridad | Nivel de urgencia de una Tarea, configurable por Proyecto. Por defecto: ninguna, baja, media, alta, urgente | importancia |
| Valor inicial | Estado y prioridad que toma una Tarea nueva; se configura en cada Proyecto | valor por omisión |
| Etiqueta de tarea | Marca personalizable por Proyecto para clasificar Tareas; no hay etiquetas por defecto | tag, categoría |
| Etiqueta de wiki | Marca por Workspace para clasificar contenido de la wiki; distinta de las etiquetas de tarea | tag, categoría |
| Asignado | Admin o miembro asociado a una Tarea; una Tarea puede tener varios; nunca un usuario cliente | responsable, dueño |
| Comentario | Mensaje sobre una Tarea | nota |
| Adjunto | Archivo asociado a una Tarea | documento |
| Historial de cambios | Registro de cada cambio de una Tarea o de una Página | log, bitácora |
| Vista | Forma de ver las Tareas de un Proyecto: kanban, listado o calendario | tablero (como sinónimo general) |
| Pizarra | Vista del Workspace, compartida: filas de Proyectos y columnas de días, con notas cortas de lo que hay que hacer | tablero (como sinónimo de kanban), pizarra física |
| Nota de pizarra | Apunte corto de un Proyecto en un día: planificada o hecha; puede apuntar a una Tarea de ese Proyecto | comentario, tarea |
| Prioridad del Proyecto | Urgencia del Proyecto, comparable con la de otros Proyectos (ninguna, baja, media, alta, urgente). Distinta de la prioridad de sus Tareas | prioridad de tarea |
| Proyecto en pausa | Proyecto detenido de forma reversible. Sigue en la Pizarra, tachado y rayado. No es archivarlo ni enviarlo a la papelera | archivado |
| Día trabajado | Día en el que el Proyecto tiene al menos una nota hecha. Vacío o solo con notas planificadas, no está trabajado | — |
| Filtro | Criterio para acotar las Tareas de una Vista; se recuerda por usuario | búsqueda |
| Wiki | Conjunto de Páginas de documentación de un Workspace o de un Proyecto | base de conocimiento |
| Página | Contenido de la wiki, con subpáginas, historial de cambios, archivos y etiquetas de wiki | documento, nota |
| Archivo | Archivo subido dentro de una Página de la wiki; no hay biblioteca aparte | adjunto (fuera de Tareas) |
| Papelera | Lugar donde queda todo lo que se borra; se puede restaurar desde ahí; por ahora no se puede vaciar | basurero |

Estados relevantes:
- **Usuario:** activo ⇄ desactivado; eliminado (lógico).
- **Tarea:** siempre en uno de los estados configurados en su Proyecto (si se borra el suyo, pasa al estado inicial); por defecto Backlog → Por hacer → En curso → Hecho | Cancelado (el orden es el que se muestra; las transiciones permitidas no están definidas).
- **Elemento borrado:** activo → en papelera → restaurado (activo).

## 5. Etapas y flujo principal
> Etapas en orden de construcción. Cada etapa agrupa su parte del flujo (F) y cita las alternativas (A) y reglas (RN) que le tocan. «Terminado cuando» describe la intención, no tareas.

| Etapa | Nombre | Cubre | Depende de |
|---|---|---|---|
| E1 | Super admin y Workspaces | F-01, F-02 | — |
| E2 | Clientes | F-03 | E1 |
| E3 | Usuarios y roles del Workspace | F-04 | E1, E2 |
| E4 | Proyectos, equipo, estados, prioridades y etiquetas | F-05 | E2, E3 |
| E5 | Tareas | F-06 | E4 |
| E6 | Adjuntos, comentarios e historial | F-07 | E5 |
| E7 | Vistas y filtros | F-08 | E5 |
| E8 | Wiki y archivos | F-09 | E1, E4 |
| E9 | Papelera | F-10 | E2, E4, E5, E8 |
| E10 | Acceso del usuario cliente | F-11 | E3 |
| E11 | Pizarra | F-12 | E4, E5, E7, E10 |

### E1 · Super admin y Workspaces
- **Objetivo:** que los usuarios se creen por consola, sin registro abierto, y que cada uno, como Super admin de su cuenta, cree sus Workspaces.
- **Depende de:** ninguna.
- **Cubre:** F-01, F-02 · A-01, A-02, A-20 · RN-01, RN-02, RN-03, RN-14, RN-20, RN-25.
- **Terminado cuando:** nadie puede registrarse por su cuenta; un Super admin creado por consola entra, crea varios Workspaces y ve solo los suyos y aquellos a los que fue invitado; la interfaz está en español.

#### F-01 · Crear un Super admin por consola y entrar
- **Actor y situación:** quien administra el sistema crea el usuario por consola.
- **Acción y resultado esperado:** el usuario creado por consola queda como Super admin de su cuenta e inicia sesión. No existe registro abierto.
- **Criterios de aceptación:**
  - [ ] Dado alguien sin usuario, cuando intenta registrarse, entonces no existe esa opción.
  - [ ] Dado un usuario creado por consola, cuando entra, entonces es Super admin de su cuenta.

#### F-02 · Crear Workspaces
- **Actor y situación:** Super admin.
- **Acción y resultado esperado:** crea uno o más Workspaces (N por cuenta). Solo el Super admin crea Workspaces. Cada usuario ve solo los Workspaces que creó o a los que fue invitado.
- **Criterios de aceptación:**
  - [ ] Dado un Super admin, cuando crea varios Workspaces, entonces ve todos los suyos y trabaja en uno a la vez.
  - [ ] Dado un usuario, cuando intenta abrir un Workspace que no creó ni al que fue invitado, entonces no se permite.
  - [ ] Dado un admin, un miembro o un usuario cliente, cuando intenta crear un Workspace, entonces no se permite.

### E2 · Clientes
- **Objetivo:** que dentro de un Workspace el Super admin cree Clientes con nombre, descripción, logo y color.
- **Depende de:** E1.
- **Cubre:** F-03 · A-20 · RN-02, RN-12, RN-25.
- **Terminado cuando:** cada Cliente queda con nombre, descripción, logo y color, listo para relacionarle Proyectos y usuarios cliente.

#### F-03 · Crear un Cliente
- **Actor y situación:** Super admin (solo él crea Clientes).
- **Acción y resultado esperado:** crea un Cliente con nombre, descripción, logo y color. Un Cliente puede tener cero o muchos Proyectos. El correo y el password son del usuario cliente, no del Cliente (E3).
- **Criterios de aceptación:**
  - [ ] Dado un Workspace, cuando se crea un Cliente con nombre, descripción, logo y color, entonces queda disponible para relacionarle Proyectos y usuarios cliente.
  - [ ] Dado un Cliente sin Proyectos, entonces es válido.
  - [ ] Dado un admin, un miembro o un usuario cliente, cuando intenta crear un Cliente, entonces no se permite.
- **Notas:** el color viene del mensaje original («logo, color e información»); la corrección del 2026-10-12 no lo menciona y se mantiene.

### E3 · Usuarios y roles del Workspace
- **Objetivo:** que el Super admin cree los usuarios de sus Workspaces con su rol (admin, miembro o cliente) y pueda desactivarlos o eliminarlos de forma lógica.
- **Depende de:** E1 y E2 (los usuarios cliente se asocian a Clientes).
- **Cubre:** F-04 · A-09, A-10, A-14 · RN-04, RN-14, RN-15.
- **Terminado cuando:** el Super admin crea admins, miembros y usuarios cliente (estos con nombre, correo y password y asociados a uno o más Clientes); un usuario desactivado o eliminado no entra, y sus datos no se borran.

#### F-04 · Crear, desactivar y eliminar usuarios
- **Actor y situación:** Super admin en uno de sus Workspaces.
- **Acción y resultado esperado:** crea usuarios con rol admin, miembro o cliente; al usuario cliente le registra nombre, correo y password y lo asocia a uno o más Clientes. Puede desactivar el acceso de un usuario o eliminarlo; el bloqueo y el borrado son lógicos. El admin también puede crear miembros, con acceso a sus Proyectos (E4).
- **Criterios de aceptación:**
  - [ ] Dado el Super admin, cuando crea un usuario con un rol, entonces ese usuario entra al Workspace con ese rol.
  - [ ] Dado un usuario cliente, entonces tiene nombre, correo y password, y puede quedar asociado a más de un Cliente.
  - [ ] Dado un miembro o un usuario cliente, cuando intenta crear usuarios, entonces no se permite; un admin solo puede crear miembros.
  - [ ] Dado un usuario desactivado o eliminado, cuando intenta entrar, entonces no se permite, y su información sigue registrada.

### E4 · Proyectos, equipo, estados, prioridades y etiquetas
- **Objetivo:** que el Super admin cree Proyectos, con o sin Cliente, con su equipo, estados y prioridades configurables y etiquetas propias, y que cada admin y miembro vea solo sus Proyectos.
- **Depende de:** E2 (relación con un Cliente) y E3 (usuarios del Workspace).
- **Cubre:** F-05 · A-04, A-05, A-13, A-15, A-16, A-21, A-23 · RN-02, RN-05, RN-06, RN-13, RN-16, RN-21, RN-22, RN-26, RN-27.
- **Terminado cuando:** cada Proyecto nace con los estados y prioridades por defecto y sin etiquetas, con su valor inicial configurable; los cambios aplican solo a ese Proyecto; admins y miembros ven solo los Proyectos a los que fueron asignados, el admin puede crear miembros nuevos con acceso a sus Proyectos y solo el Super admin agrega usuarios existentes a un Proyecto.

#### F-05 · Crear y configurar un Proyecto
- **Actor y situación:** Super admin; admin en sus Proyectos.
- **Acción y resultado esperado:** el Super admin crea un Proyecto con nombre, descripción e icono; lo relaciona o no con un Cliente; marca si es visible para el Cliente (la marca se registra, pero por ahora no tiene efecto); asigna su equipo; solo el Super admin agrega usuarios ya existentes a un Proyecto. El Proyecto recibe los estados por defecto (Backlog, Por hacer, En curso, Hecho, Cancelado) y las prioridades por defecto (ninguna, baja, media, alta, urgente); no hay etiquetas por defecto. Estados, prioridades y etiquetas se configuran por Proyecto, y en el Proyecto se configura el estado y la prioridad que toma una Tarea nueva. Si se borra un estado, las Tareas a las que fue aplicado pasan al estado inicial configurado en el Proyecto. El estado inicial no se puede borrar hasta elegir otro como inicial. El admin hace todas las acciones dentro de sus Proyectos, pero no crea Proyectos; puede crear miembros nuevos con acceso a los Proyectos a los que él tiene acceso, pero no agregar usuarios ya existentes a un Proyecto.
- **Criterios de aceptación:**
  - [ ] Dado un Proyecto nuevo, entonces queda con nombre, descripción e icono, los estados y prioridades por defecto y sin etiquetas.
  - [ ] Dado un Proyecto, cuando se agrega, cambia o quita un estado, una prioridad o una etiqueta, o se cambia el valor inicial, entonces el cambio aplica solo a ese Proyecto.
  - [ ] Dado un estado borrado, entonces las Tareas a las que fue aplicado pasan al estado inicial configurado en el Proyecto.
  - [ ] Dado el estado inicial del Proyecto, cuando se intenta borrar sin haber elegido otro como inicial, entonces no se permite.
  - [ ] Dado un admin, cuando intenta crear un Proyecto, entonces no se permite.
  - [ ] Dado un admin, cuando crea un miembro, entonces el miembro queda con acceso solo a Proyectos a los que el admin tiene acceso.
  - [ ] Dado un admin o un miembro, cuando intenta agregar a un Proyecto un usuario ya existente, entonces no se permite.
  - [ ] Dado un admin o un miembro, entonces ve solo los Proyectos a los que fue asignado.
  - [ ] Dado un Proyecto sin Cliente, entonces es válido.

### E5 · Tareas
- **Objetivo:** que cada Proyecto tenga Tareas con sus datos, estado, prioridad, etiquetas, subtareas de un nivel y varios asignados, según el rol de cada usuario.
- **Depende de:** E4.
- **Cubre:** F-06 · A-06, A-11, A-12, A-17, A-22 · RN-07, RN-17, RN-18, RN-23.
- **Terminado cuando:** una Tarea se crea con el valor inicial de su Proyecto, con estado obligatorio, se edita, cambia de estado, se etiqueta, se prioriza, se divide en subtareas de un nivel y se asigna a uno o más admins o miembros; el miembro ve todas las Tareas de sus Proyectos, edita solo las asignadas y puede crear Tareas nuevas.

#### F-06 · Crear y gestionar Tareas
- **Actor y situación:** admin o miembro en un Proyecto asignado (también el Super admin).
- **Acción y resultado esperado:** crea una Tarea con nombre, descripción, fecha de inicio y término; la Tarea toma el estado y la prioridad iniciales configurados en el Proyecto; el estado es obligatorio al crearla. Le asigna uno o más usuarios (nunca un usuario cliente); le pone estado, prioridad y etiquetas del Proyecto; le agrega subtareas de un solo nivel, con los mismos datos que una Tarea. El admin hace todas las acciones en cualquier Tarea de sus Proyectos; el miembro hace todas las acciones dentro de las Tareas que tiene asignadas y crea Tareas nuevas, asignándolas a sí mismo o al equipo; ve todas las Tareas de sus Proyectos, pero solo edita las que tiene asignadas.
- **Criterios de aceptación:**
  - [ ] Dado un Proyecto, cuando se crea una Tarea, entonces toma el estado y la prioridad iniciales configurados en el Proyecto.
  - [ ] Dada una Tarea, cuando se le asignan varios usuarios, etiquetas, prioridad o subtareas, entonces quedan registrados.
  - [ ] Dada una Tarea, cuando se intenta asignar a un usuario cliente, entonces no se permite.
  - [ ] Dada una subtarea, entonces tiene los mismos datos que una Tarea y no admite subtareas propias.
  - [ ] Dada una Tarea nueva, cuando se intenta crear sin estado, entonces no se permite.
  - [ ] Dada una Tarea, cuando cambia de estado, entonces usa solo los estados de su Proyecto y nunca queda sin estado.
  - [ ] Dado un miembro, cuando crea una Tarea, entonces puede asignarla a sí mismo o al equipo del Proyecto.
  - [ ] Dado un miembro, entonces ve todas las Tareas de sus Proyectos.
  - [ ] Dado un miembro, cuando intenta modificar una Tarea que no tiene asignada, entonces no se permite.

### E6 · Adjuntos, comentarios e historial
- **Objetivo:** que cada Tarea reúna sus archivos, su conversación y la historia de sus cambios.
- **Depende de:** E5.
- **Cubre:** F-07 · RN-08, RN-10 (comentarios en hilos de un nivel, con menciones y avisos en la bandeja de la app; sin correo).
- **Terminado cuando:** en cada Tarea se pueden adjuntar archivos y comentar, y todo cambio queda en su historial.

#### F-07 · Adjuntar, comentar y revisar el historial
- **Actor y situación:** usuario con permiso sobre la Tarea (admin del Proyecto o miembro asignado; el miembro ve también las Tareas que no tiene asignadas). Comentar y responder lo puede cualquiera que ve la Tarea, aunque no la tenga asignada (2026-11-26).
- **Acción y resultado esperado:** adjunta archivos, comenta y revisa el historial de cambios de la Tarea.
- **Criterios de aceptación:**
  - [ ] Dada una Tarea, cuando se adjunta un archivo o se comenta, entonces queda en la Tarea.
  - [ ] Dado un comentario, cuando se responde, entonces la respuesta queda en el hilo de su comentario raíz (un solo nivel).
  - [ ] Dado un comentario que menciona a alguien que puede ver la Tarea o Página, entonces esa persona recibe un aviso en su bandeja.
  - [ ] Dado un comentario nuevo, entonces reciben aviso quienes siguen la Tarea o Página (creador, asignados, quienes comentaron o fueron mencionados), salvo quien dejó de seguirla y salvo el autor.
  - [ ] Dado un cambio en una Tarea, entonces queda en su historial de cambios.

### E7 · Vistas y filtros
- **Objetivo:** que las Tareas de un Proyecto se vean en kanban, listado y calendario, con filtros que se recuerdan por usuario.
- **Depende de:** E5.
- **Cubre:** F-08 · RN-11.
- **Terminado cuando:** las mismas Tareas de un Proyecto se ven en las tres vistas, se pueden filtrar y cada usuario encuentra sus filtros activos al volver.

#### F-08 · Ver y filtrar las Tareas de un Proyecto
- **Actor y situación:** admin o miembro en un Proyecto asignado.
- **Acción y resultado esperado:** ve las Tareas en kanban (por estado), listado y calendario (por fechas), y las filtra; los filtros se recuerdan por usuario. Campos de filtro (propuesta, ajustable): estado, prioridad, asignado, etiqueta y rango de fechas.
- **Criterios de aceptación:**
  - [ ] Dado un Proyecto con Tareas, cuando el usuario cambia entre kanban, listado y calendario, entonces ve las mismas Tareas.
  - [ ] Dado un filtro aplicado, entonces cada vista muestra solo las Tareas que lo cumplen.
  - [ ] Dado un filtro aplicado por un usuario, cuando ese usuario vuelve, entonces el filtro sigue activo.

### E8 · Wiki y archivos
- **Objetivo:** que el Workspace y cada Proyecto tengan su wiki, con subpáginas, historial de cambios, archivos dentro de las Páginas y etiquetas de wiki por Workspace.
- **Depende de:** E1 (wiki del Workspace) y E4 (wiki del Proyecto y su equipo).
- **Cubre:** F-09 · A-07, A-18 · RN-05, RN-09, RN-24.
- **Terminado cuando:** el Super admin escribe en la wiki del Workspace y admins y miembros en la de sus Proyectos; se crean Páginas y subpáginas, cada cambio queda en su historial, los archivos se suben dentro de las Páginas, el contenido se etiqueta y la marca de qué documentación ve el Cliente queda registrada.

#### F-09 · Escribir la wiki y subir archivos
- **Actor y situación:** Super admin en la wiki del Workspace; admin o miembro en la wiki de un Proyecto asignado.
- **Acción y resultado esperado:** crea y edita Páginas y subpáginas; cada cambio queda en el historial de la Página (no hay versiones). Sube archivos dentro de las Páginas (no hay biblioteca aparte) y etiqueta el contenido con etiquetas de wiki, que son por Workspace y distintas de las de tareas. Marca qué documentación ve el Cliente; la marca se registra, pero por ahora no tiene efecto. Solo el Super admin accede a la documentación del Workspace; admins y miembros acceden a toda la documentación de sus Proyectos.
- **Criterios de aceptación:**
  - [ ] Dado un Workspace o un Proyecto, cuando se crea una Página o una subpágina, entonces queda en la wiki correspondiente.
  - [ ] Dado un cambio en una Página, entonces queda en su historial de cambios.
  - [ ] Dado un archivo, entonces se sube dentro de una Página.
  - [ ] Dada una Página, cuando se etiqueta, entonces se puede encontrar por esa etiqueta de wiki.
  - [ ] Dado un admin o un miembro, cuando intenta entrar a la documentación del Workspace, entonces no se permite.
  - [ ] Dada una documentación, cuando se marca visible para el Cliente, entonces la marca queda registrada.

### E9 · Papelera
- **Objetivo:** que todo lo que se borra vaya a la papelera, sin perderse.
- **Depende de:** E2, E4, E5 y E8 (los elementos que se borran).
- **Cubre:** F-10 · A-19 · RN-19.
- **Terminado cuando:** todo lo que se borra queda en la papelera, se puede restaurar desde ahí y la papelera no se puede vaciar.

#### F-10 · Enviar a la papelera y restaurar
- **Actor y situación:** usuario con permiso sobre el elemento.
- **Acción y resultado esperado:** borra un elemento y este va a la papelera; desde ahí lo puede restaurar. Por ahora la papelera no se puede vaciar. No hay archivado.
- **Criterios de aceptación:**
  - [ ] Dado un elemento borrado, entonces queda en la papelera.
  - [ ] Dado un elemento en la papelera, cuando se restaura, entonces vuelve a estar activo.
  - [ ] Dada la papelera, cuando se intenta vaciar, entonces no existe esa opción.

### E10 · Acceso del usuario cliente
- **Objetivo:** que el usuario cliente pueda acceder, sin ver nada por ahora.
- **Depende de:** E3.
- **Cubre:** F-11 · A-03, A-08 · RN-04, RN-05.
- **Terminado cuando:** un usuario cliente inicia sesión y no ve Proyectos, Tareas ni documentación, aunque estén marcados como visibles para su Cliente.

#### F-11 · Acceder como usuario cliente
- **Actor y situación:** usuario cliente creado por el Super admin.
- **Acción y resultado esperado:** inicia sesión con su correo y password y no ve nada. Las marcas de visibilidad de Proyectos y documentación quedan registradas para cuando se defina qué ve el cliente (diferido: P-04, P-05, P-06, P-14).
- **Criterios de aceptación:**
  - [ ] Dado un usuario cliente, cuando inicia sesión, entonces accede, pero no ve Proyectos, Tareas ni documentación.
  - [ ] Dado un usuario cliente, cuando intenta crear o editar algo, entonces no se permite.

### E11 · Pizarra
- **Objetivo:** que Sebastián vea, en un monitor vertical, qué hay que hacer cada día en sus Proyectos, en cuáles trabajó, la prioridad y lo vencido, en lugar de la pizarra física.
- **Depende de:** E4 (Proyectos), E5 (Tareas, para las subfilas y el vínculo opcional), E7 (la regla de Tarea abierta vencida) y E10 (el usuario cliente no entra).
- **Cubre:** F-12 · A-24, A-25, A-26, A-27 · RN-11, RN-21.
- **Terminado cuando:** la Pizarra muestra los Proyectos visibles en filas y los días de la semana en columnas; las notas son las mismas para quien ve el Proyecto; se distingue lo planificado, lo hecho, lo vencido, lo pausado y el día no trabajado; el usuario cliente no la ve.

#### F-12 · Revisar la semana en la Pizarra
- **Actor y situación:** Super admin, admin o miembro, frente a los Proyectos que puede ver.
- **Acción y resultado esperado:** ve una fila por Proyecto (con las Tareas raíz abiertas plegadas) y una columna por día de la semana visible, de lunes a viernes, pudiendo incluir el fin de semana. Se mueve a la semana anterior, a la de hoy y a la siguiente; la columna de hoy se destaca. En cada celda anota una o más notas cortas (por ejemplo «Update», «API», «Backup», «Reporte»), planificadas o ya hechas; una nota puede nacer hecha, para registrar trabajo que no estaba planeado, y puede apuntar a una Tarea o subtarea del mismo Proyecto. Quien ve el Proyecto ve las mismas notas. Quien trabaja en él (Super admin, admin y miembro del equipo) crea, edita, marca y elimina notas; se recuerda quién creó cada una. La prioridad del Proyecto (ninguna, baja, media, alta, urgente, con color) se compara entre Proyectos y no reemplaza las prioridades de sus Tareas. Un Proyecto puede tener fecha de término y puede pausarse: la pausa es reversible, la fila sigue visible, tachada y rayada, y no es archivarlo ni mandarlo a la papelera. El orden de las filas es del Workspace; ocultar un Proyecto es de cada persona. La semana y los filtros (ocultar pausados, solo vencidos, no trabajados hoy, búsqueda) se pueden compartir.
- **Criterios de aceptación:**
  - [ ] Dado un Proyecto, cuando se mira la Pizarra, entonces su fila muestra la prioridad, y si está vencido o en pausa.
  - [ ] Dado un día de un Proyecto, cuando se anota una nota planificada o ya hecha, entonces queda en esa celda para quien ve el Proyecto, y se sabe quién la creó.
  - [ ] Dada una nota, cuando se marca hecha, se edita, se vincula a una Tarea del mismo Proyecto o se elimina, entonces la Pizarra queda así para todos.
  - [ ] Dada una nota de un día anterior que sigue planificada, entonces está vencida. Una nota hecha no está vencida.
  - [ ] Dado un Proyecto con fecha de término anterior a hoy que no está en pausa, entonces el Proyecto está vencido. Si está en pausa, no.
  - [ ] Dado un Proyecto, entonces se muestra aparte cuántas Tareas abiertas tienen el término vencido.
  - [ ] Dado un día con al menos una nota hecha, entonces está trabajado. Si está vacío o solo tiene notas planificadas, no está trabajado y se nota en la celda.
  - [ ] Dado el filtro de no trabajados hoy, entonces quedan los Proyectos sin nota hecha hoy; los que están en pausa no entran.
  - [ ] Dado un Proyecto en pausa, entonces su fila sigue, tachada y rayada, y se puede reanudar.
  - [ ] Dado el orden de las filas, entonces es el mismo para quien ve la Pizarra. Ocultar un Proyecto solo lo quita de la Pizarra de quien lo ocultó.
  - [ ] Dado un usuario cliente, cuando intenta abrir la Pizarra, entonces no se permite.
  - [ ] «Hoy» es el día en America/Santiago.
- **Notas:** las subfilas son las Tareas raíz abiertas del Proyecto, plegadas por defecto. Una nota vinculada a una subtarea se muestra en la fila de su Tarea raíz. Las notas no van a la papelera: son apuntes del día. Reordenar con un filtro activo reordena entre las filas que se están viendo.

## 6. Alternativas y errores
| ID | Desde | Situación | Qué debe pasar |
|---|---|---|---|
| A-01 | F-01 | Alguien sin usuario intenta registrarse | No existe registro abierto |
| A-02 | F-02 | Usuario intenta abrir un Workspace que no creó ni al que fue invitado | No se permite |
| A-03 | F-11 | Usuario cliente intenta crear o editar algo | No se permite |
| A-04 | F-05 | Proyecto sin Cliente | Es válido |
| A-05 | F-05 | Proyecto con Cliente marcado como no visible | La marca queda registrada; por ahora el usuario cliente no ve nada de todos modos |
| A-06 | F-06 | Fecha de término anterior a la fecha de inicio | No se permite |
| A-07 | F-09 | Documentación no marcada como visible para el Cliente | La marca queda registrada; por ahora el usuario cliente no ve nada de todos modos |
| A-08 | F-11 | Usuario cliente intenta ver un Proyecto, incluso de su Cliente | Por ahora no ve nada |
| A-09 | F-04 | Usuario desactivado intenta entrar | No se permite; el bloqueo es lógico |
| A-10 | F-04 | Usuario eliminado intenta entrar | No se permite; el borrado es lógico |
| A-11 | F-06 | Crear una subtarea dentro de una subtarea | No se permite: un solo nivel |
| A-12 | F-06 | Asignar una Tarea a un usuario cliente | No se permite |
| A-13 | F-05 | Se borra un estado aplicado a Tareas | Las Tareas a las que fue aplicado pasan al estado inicial configurado en el Proyecto |
| A-14 | F-04 | Un miembro o usuario cliente intenta crear usuarios, o un admin intenta crear algo distinto de un miembro | No se permite |
| A-15 | F-05 | Un admin intenta crear un Proyecto | No se permite: solo el Super admin |
| A-16 | F-05 | Un admin o un miembro intenta abrir un Proyecto al que no fue asignado | No se permite |
| A-17 | F-06 | Un miembro intenta modificar una Tarea que no tiene asignada | No se permite |
| A-18 | F-09 | Un admin o un miembro intenta entrar a la documentación del Workspace | No se permite: solo el Super admin |
| A-19 | F-10 | Intentar vaciar la papelera | No existe esa opción por ahora |
| A-20 | F-02, F-03 | Un admin, miembro o usuario cliente intenta crear un Workspace o un Cliente | No se permite: solo el Super admin |
| A-21 | F-05 | Un admin o un miembro intenta agregar a un Proyecto un usuario ya existente | No se permite: solo el Super admin |
| A-22 | F-06 | Crear una Tarea sin estado | No se permite: el estado es obligatorio al crearla |
| A-23 | F-05 | Borrar el estado inicial del Proyecto | No se permite hasta elegir otro estado como inicial |
| A-24 | F-12 | Usuario cliente intenta abrir la Pizarra | No se permite |
| A-25 | F-12 | Nota apuntando a una Tarea de otro Proyecto | No se permite |
| A-26 | F-12 | Proyecto con fecha de término vencida, pero en pausa | El Proyecto no está vencido; la fila sigue tachada y rayada |
| A-27 | F-12 | Crear una nota directamente como hecha | Es válido: registra trabajo que no estaba planeado |

## 7. Reglas de negocio
- **RN-01** Sin registro abierto: los usuarios se crean por consola, y cada usuario creado así es Super admin de su cuenta.
- **RN-02** Jerarquía: Workspace > Cliente > Proyecto. Todo pertenece a un Workspace; un Cliente tiene cero o muchos Proyectos; un Proyecto puede o no estar relacionado con un Cliente.
- **RN-03** Un Workspace tiene varios usuarios y un usuario puede estar en varios Workspaces; cada usuario ve solo los Workspaces que creó o a los que fue invitado.
- **RN-04** Los usuarios cliente tienen nombre, correo y password, son distintos de admins y miembros y pueden pertenecer a más de un Cliente. Por ahora pueden acceder, pero sin ver nada.
- **RN-05** La visibilidad para el Cliente de Proyectos y documentación se marca y se registra, pero por ahora no tiene efecto visible.
- **RN-06** Estados, prioridades y etiquetas de tarea son por Proyecto y se pueden configurar. Estados por defecto, en español: Backlog, Por hacer, En curso, Hecho, Cancelado. No hay etiquetas por defecto. Si se borra un estado, las Tareas a las que fue aplicado pasan al estado inicial configurado en el Proyecto; una Tarea nunca queda sin estado.
- **RN-07** Una Tarea tiene nombre, descripción, adjuntos, comentarios, historial de cambios, fecha de inicio y término, asignados, etiquetas, estado, prioridad y subtareas. El estado es obligatorio al crear una Tarea y nunca queda vacío. La fecha de término no puede quedar antes que la de inicio.
- **RN-08** Todo cambio en una Tarea queda en su historial de cambios.
- **RN-09** Hay wiki a nivel de Workspace y de Proyecto, con subpáginas y solo historial de cambios (sin versiones); los archivos van dentro de las Páginas, sin biblioteca aparte; las etiquetas de wiki son por Workspace y distintas de las de tareas; se marca qué documentación ve el Cliente.
- **RN-10** Tareas y Páginas tienen comentarios con respuestas (hilos de un nivel) y menciones a quienes pueden verlas. Hay avisos en la app (campana y Bandeja) cuando alguien te menciona, responde en un hilo tuyo o comenta algo que sigues; se sigue automáticamente lo que creaste, lo que tienes asignado, lo que comentaste y donde te mencionaron, y se puede dejar de seguir. Las notificaciones por correo siguen fuera del MVP (revisado 2026-11-26).
- **RN-11** Las Tareas de un Proyecto se ven en kanban, listado y calendario, con filtros que se recuerdan por usuario (estado, prioridad, asignado, etiqueta y rango de fechas). La Pizarra es una vista compartida del Workspace, no una cuarta vista de Tareas: filas de Proyectos (con las Tareas raíz abiertas plegables) y columnas de días, de lunes a viernes y con el fin de semana opcional. Cada celda tiene notas cortas, planificadas o hechas; una nota puede crearse ya hecha y puede apuntar a una Tarea del mismo Proyecto, y se recuerda quién la creó. La prioridad del Proyecto (ninguna, baja, media, alta, urgente) se compara entre Proyectos y es distinta de la prioridad de sus Tareas. Un Proyecto está vencido si su fecha de término es anterior a hoy y no está en pausa; una nota está vencida si su día es anterior a hoy y sigue planificada; aparte se cuentan las Tareas abiertas con término vencido. Hoy es el día en America/Santiago. Pausar es reversible y no es archivar: la fila sigue, tachada y rayada. Un día está trabajado si tiene alguna nota hecha; vacío o solo planificado no está trabajado y se nota en la celda. «No trabajados hoy» deja los Proyectos sin nota hecha hoy y no incluye los pausados. El orden de las filas es del Workspace; ocultar un Proyecto es de cada persona. El usuario cliente no ve la Pizarra. La semana y los filtros se pueden compartir.
- **RN-12** Un Cliente tiene nombre, descripción, logo y color.
- **RN-13** Un Proyecto tiene nombre, descripción e icono, Cliente opcional, equipo y marca de visibilidad para el Cliente; solo el Super admin lo crea. Desde E11 tiene además prioridad de Proyecto, fecha de término opcional y pausa reversible.
- **RN-14** El Super admin crea N Workspaces, sus Clientes y sus Proyectos, crea admins, miembros y usuarios cliente, y puede desactivar el acceso o eliminar usuarios; el bloqueo y el borrado son lógicos.
- **RN-15** Roles dentro del Workspace: admin, miembro y cliente.
- **RN-16** Prioridades por defecto: ninguna, baja, media, alta, urgente; configurables por Proyecto.
- **RN-17** Subtareas de un solo nivel, con los mismos datos que una Tarea.
- **RN-18** Una Tarea puede tener varios asignados, pero nunca un usuario cliente.
- **RN-19** Todo lo que se borra va a la papelera y se puede restaurar desde ahí; por ahora no se puede vaciar. No hay archivado.
- **RN-20** Interfaz solo en español; el desarrollo, en inglés.
- **RN-21** Admin y miembro ven solo los Proyectos a los que fueron asignados. El admin hace todas las acciones dentro de sus Proyectos, no crea Proyectos y puede crear miembros nuevos con acceso a los Proyectos a los que él tiene acceso.
- **RN-22** El estado y la prioridad que toma una Tarea nueva se configuran en cada Proyecto.
- **RN-23** El miembro ve todas las Tareas de sus Proyectos, pero solo edita las que tiene asignadas, dentro de las cuales hace todas las acciones (comentar y responder puede hacerlo en todas las que ve, RN-10); puede crear Tareas nuevas, asignándolas a sí mismo o al equipo del Proyecto.
- **RN-24** Solo el Super admin accede a la documentación del Workspace; admins y miembros acceden a toda la documentación de sus Proyectos.
- **RN-25** Solo el Super admin crea Workspaces y Clientes.
- **RN-26** Solo el Super admin agrega usuarios ya existentes a un Proyecto; el admin solo puede crear miembros nuevos con acceso a sus Proyectos.
- **RN-27** El estado inicial de un Proyecto no se puede borrar hasta elegir otro como inicial.

## 8. Preguntas abiertas e hipótesis
Resueltas el 2026-10-12: P-01, P-02, P-03, P-07, P-08, P-09, P-10, P-11, P-12, P-13, P-15, P-16, P-17, P-18, P-19, P-20 (reemplazada por P-29), P-21, P-22, P-24, P-25, P-26, P-27 y P-28; P-23, P-29, P-30, P-31, P-32, P-33 y P-34. Ver Decisiones cerradas.

Diferidas (no se definen en el MVP; Sebastián: «Por ahora no definamos nada del cliente»):
- **P-04** Qué ve el usuario cliente de una Tarea (adjuntos, historial, comentarios internos).
- **P-05** Si el acceso del usuario cliente es fijo («ver, comentar») o se elige por usuario.
- **P-06** Si se pueden ocultar Tareas individuales al Cliente y si la visibilidad se elige por usuario.
- **P-14** Si la visibilidad de la documentación se elige por Página, sección o archivo, y si una Página del Workspace se puede mostrar a un Cliente específico.

Abiertas: ninguna por ahora.

### Decisiones cerradas (2026-10-11 y 2026-10-12, Sebastián)
- Registro: «Sin registro».
- Varios usuarios internos por Workspace y un usuario en varios Workspaces: «Sí».
- Acceso del usuario cliente: «Ver, comentar» (2026-10-11; queda diferido por la decisión del 2026-10-12 sobre lo que ve el cliente).
- Wiki: «Wiki a nivel de workspace y proyectos, poder elegir qué documentación ve el cliente».
- Estados y etiquetas: «por proyecto, definir por defecto al momento de crear, pero que permita modificarlos». Estados por defecto: backlog, todo, in progress, done, cancelled.
- Alcance del MVP: «Incluir subtareas, prioridad». Notificaciones por correo y menciones quedan fuera del MVP.
- Comentarios (2026-11-26): «las tareas y páginas deben soportar comentarios, respuestas y menciones», con alertas cuando alguien comenta algo relacionado contigo o te menciona. Se eligió bandeja en la app, seguidores automáticos, hilos de un nivel y texto plano con @menciones.
- Nombre: «Workbench».
- Jerarquía: «Cada usuarios debe tener un workspace como entidad superior»; clientes «como entidad superior» dentro del Workspace (Workspace > Cliente > Proyecto); «Un proyecto puede o no estar relacionado a un cliente».
- **(2026-10-12, P-01)** «Por consola se crean los usuarios, con un comando artisan. Los workspace los van creando los mismos usuarios.» En el flujo: los usuarios se crean por consola.
- **(2026-10-12, P-02; precisada por P-23)** «el usario creado por consola es el super_admin de su cuenta, puede crear N workspace. Dentro del workspace pueden haber admins, miembros y clientes, solo el super_admin puede crearlos todos.» Precisión posterior: el admin también puede crear miembros con acceso a sus Proyectos; agregar usuarios existentes a un Proyecto es solo del Super admin (P-30).
- **(2026-10-12, P-17)** «El usuario solo ve los workspace que creo o a los cuales fue invitado.»
- **(2026-10-12, P-03)** «un cliente si puede ser mas de un cliente»: un usuario cliente puede pertenecer a más de un Cliente.
- **(2026-10-12, P-18)** «El super admin puede desactivar el acceso o eliminar usuarios. El borrado y el bloqueo es lógico.»
- **(2026-10-12, P-04, P-05, P-06, P-14)** «Por ahora no definamos nada del cliente, solo que pueda acceder pero sin ver nada.» Las marcas de visibilidad de Proyectos y documentación se conservan registradas, sin efecto visible por ahora.
- **(2026-10-12, P-12)** «Si, pero no a un cliente»: una Tarea puede tener varios asignados, pero no un usuario cliente.
- **(2026-10-12, P-08)** Prioridad: «ninguna, baja, media, alta, urgente».
- **(2026-10-12, P-09)** «1 nivel de subtarea, mismos datos».
- **(2026-10-12, P-07)** «Solo esas vistas por ahora, si, la idea es que los filtros queden activos».
- **(2026-10-12, P-13)** «Si se borra un estado se borra de todas las tareas a las que fue aplicado, no hay etiquetas por defecto, solo estados y prioridad».
- **(2026-10-12, P-10; reemplazada por P-24)** «si, papelera y archivado.»
- **(2026-10-12, P-15)** «Si, subpaginas y solo historial de cambios.»
- **(2026-10-12, P-11)** «No, etiquetas distintas.»: las etiquetas de la wiki son distintas de las de tareas.
- **(2026-10-12, P-16; corregida por P-22)** Datos del Cliente: «nombre, correo, password, descripción, logo».
- **(2026-10-12, P-19)** «Interfaz solo en español, todo el desarrollo en ingles».
- **(2026-10-12, P-20; reemplazada por P-29)** «Quedan sin estado»: al borrar un estado, las Tareas a las que fue aplicado quedan sin estado.
- **(2026-10-12, P-21)** «solo ve los proyectos a los que fue asignado».
- **(2026-10-12, P-22)** «me equivoque, nombre, correo, password son del usuario cliente, descripción y logo del Cliente». El Cliente conserva su nombre y el color pedido en el mensaje original.
- **(2026-10-12, P-23)** «admin puede: hacer todas las acciones dentro de un proyecto. No puede crear proyectos y puede crear miembros con acceso a los proyectos que el tiene acceso. el miembro puede: ver los proyectos asignados y todas las acciones dentro de las tareas asignadas, puede crear nuevas tareas y asignarlas a el o al equipo. ambos pueden acceder a toda la documentación del proyecto, solo los super_admin acceden a la documentación del workspace.»
- **(2026-10-12, P-24)** «simplificar, todo se va a la papelera (por ahora no se puede vaciar la papelera), no incluyas la opción de archivar».
- **(2026-10-12, P-25)** «Los filtros se recuerdan por usuario. Los campos nose aun, propone un stack simple por ahora.» Propuesta, ajustable: estado, prioridad, asignado, etiqueta y rango de fechas.
- **(2026-10-12, P-26)** «Si»: los estados por defecto se muestran traducidos al español (nombres: P-32).
- **(2026-10-12, P-27)** «Los archivos van dentro de las páginas, no hay biblioteca. Las etiquetas de la wiki con por workspace.»
- **(2026-10-12, P-28)** «Si, prioridad y estado configurable, el usaurio configura en cual parte.»: estados y prioridades configurables por Proyecto, y el valor inicial de una Tarea nueva se configura en el Proyecto.
- **(2026-10-12, A-06)** «Fecha de termino no puede quedar antes que la de inicio».
- **(2026-10-12, P-23 resto)** «Solo super admin puede crear clientes y workspace.»
- **(2026-10-12, P-30)** «solo el super admin»: solo el Super admin agrega admins y miembros ya existentes a un Proyecto. Convive con P-23: el admin puede crear miembros nuevos con acceso a sus Proyectos.
- **(2026-10-12, P-31)** «todas las tareas, solo puede editar las que fue asignado».
- **(2026-10-12, P-29)** «Al momento de crearlas el estado es requerido». Y luego: «Definir un estado por defecto»: cuando se borra un estado, sus Tareas pasan al estado inicial configurado en el Proyecto (RN-22). Reemplaza P-20; no hay columna «Sin estado».
- **(2026-10-12, P-32)** «Si»: Backlog, Por hacer, En curso, Hecho, Cancelado.
- **(2026-10-12, P-33)** «Si»: lo que está en la papelera se puede restaurar.
- **(2026-10-12, P-34)** Propuesta «no se puede borrar el estado inicial del Proyecto hasta elegir otro como inicial»; respuesta: «ok».
