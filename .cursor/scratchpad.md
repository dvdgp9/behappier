# behappier — MVP Planning Scratchpad

## Background and Motivation
- Minimalista y "handmade" para entrenar presencia mediante micro-tareas (1′, 5′, 10–15′).
- Sincronización desde el inicio (multi-dispositivo vía login).
- Tono cálido, cercano y no invasivo. Diseño artesanal, nada "app moderna".

## Key Challenges and Analysis
- Diseño distintivo y coherente: tipografías manuscritas legibles, paleta suave, iconografía doodle, microinteracciones sutiles (120–160ms), texturas tipo papel.
- Autenticación y sincronización: sesiones seguras (cookies HttpOnly/SameSite), hashing robusto, CSRF para POST.
- Selección de tareas: simple por duración, evitar repetición inmediata, semilla de 36 tareas.
- Temporizador confiable: precisión y estados (pausa, reanudar opcional en v1?).
- Microcopy: voz consistente en español; accesibilidad y legibilidad.
- Hosting cPanel/PHP 8: sin build tools; organización simple de ficheros.
- Privacidad: datos personales mínimos; métricas opcionales fuera de v1.

## High-level Task Breakdown
1) Diseño del sistema (Design Kit)
- Definir tipografías (títulos/botones manuscrita, cuerpo sans), paleta extendida, espaciado, radio de bordes, sombras, iconografía doodle, texturas.
- Estados de UI (hover, foco, deshabilitado), animaciones (easing, duraciones), tamaños responsivos.
- Success: assets aprobados y disponibles en `/assets/` (fuentes, SVG/PNG, textura), tokens base en `assets/styles.css`.

2) Modelo de datos y SQL
- Tablas: `users`, `tasks`, `entries` (+ FK `user_id`), opcional `settings` por usuario.
- Seeds de 36 tareas con duración {1,5,10}.
- Success: SQL ejecutado por el usuario; `.env.php` con credenciales; prueba de conexión OK.

3) Autenticación y sesión
- Endpoints: `POST /api/register.php`, `POST /api/login.php`, `POST /api/logout.php`.
- Sesión PHP con cookie segura; rate-limit básico.
- Success: login/register operativos, redirecciones y guardado de sesión.

4) API de tareas y guardado
- `GET /api/tasks.php?duration=1|5|10` → JSON (id, título, guía).
- `POST /api/save.php` → guarda mood + nota + referencia de tarea.
- `GET /api/history.php?limit=10` → últimas entradas usuario.
- Success: endpoints responden con 200, validaciones y errores amigables.

5) Frontend y flujo principal
- `index.php` (login) → `home.php` (selector) → `task.php` (temporizador) → cierre (mood + nota) → `history.php`.
- Microinteracciones sutiles; responsive; accesible.
- Success: flujo completo probado en desktop y móvil.

6) Semillas de contenido y microcopys
- 36 tareas, tono cálido, simples; microcopys por pantalla.
- Success: contenido cargado y visible; no repite inmediatamente.

7) QA, Accesibilidad y rendimiento
- Contraste, foco visible, navegación por teclado, ARIA donde aplique.
- Success: checklist A11y básico completado; tiempos de carga mínimos.

8) Deploy y configuración
- Estructura de archivos según MVP; `.env.php`; logs de error; HTTPS.
- Success: app funcionando en cPanel.

## Project Status Board
- [ ] Aprobación de dirección visual y assets (tipos/paleta OK; icon set y motion aprobados → implementar)
- [ ] Validar modelo de datos y plan SQL
- [x] Decidir modo de auth → Password + "Recordarme" (expiración 30 días)
- [x] Definir escala de estado de ánimo → 5 niveles (emojis doodle provisionales)
- [ ] Confirmar catálogo inicial de 36 tareas o recibir listado definitivo
- [ ] Confirmar entorno → PHP 8.3 (MySQL y Argon2 por confirmar)
- [ ] Alinear microcopy y tono (tratamiento de "tú" confirmado)
- [x] Acordar lógica de rotación/evitar repetición de tareas → evitar últimas N=5
- [ ] Plan de deploy en cPanel (dominio provisional: behappier.wthefox.com)

## Current Status / Progress Tracking
- Planner creado. Pendiente feedback de diseño y decisiones clave.

### Decisiones confirmadas
- Marca: wordmark por ahora; textura de fondo tipo papel: OK.
- Tipos: Patrick Hand (títulos/botones) + Inter (cuerpo) vía Google Fonts.
- Paleta: `#FFF8F1` fondo, `#4A3F35` tinta, acentos `#F0C6AA` `#C8E6D0` `#E8DFF5`.
- Componentes: radio 16px; sombras suaves.
- Microinteracciones: permitido que el sistema elija; se propondrá.
- Escala de ánimo: 5 niveles; assets definitivos por llegar (usaremos SVGs provisionales).
- Microcopy: trato de "tú".
- Auth: password clásico + "Recordarme".
- "Recordarme": expiración 30 días; token persistente rotatorio en BD.
- Entorno: PHP 8.3; deploy lo gestiona el usuario; dominio: behappier.wthefox.com.
- Repetición: evitar sugerir las últimas N=5 tareas por duración.

### Infra y configuración
- `.env.php` creado con credenciales (ignorado por git) y `.env.example.php` para el repo.
- `.gitignore` añadido para proteger secretos.
- Directorios de assets creados: `assets/brand/`, `assets/icons/`, `assets/textures/`, `assets/fonts/`.
- `data/schema.sql` y `data/seed.sql` listos para ejecutar.

## Executor's Feedback or Assistance Requests
- Diseños y decisiones requeridas del usuario:
  1. Marca: ¿wordmark/logotipo, favicon 32/180, textura de fondo tipo papel (archivo)?
  2. Tipografías: confirmar manuscrita (p.ej. Patrick Hand) y sans para cuerpo (p.ej. Inter/Atkinson/Work Sans). ¿Proveer archivos o Google Fonts?
  3. Paleta: confirmar los hex dados y si deseas mapa semántico (primary/accent/surface/ink). ¿Gradientes o sólido? ¿Sombras suaves y nivel?
  4. Componentes: botones (radio 16–20px, alturas), inputs, cards, estados de foco/hover; iconos estilo doodle (set preferido o dibujados propios).
  5. Microinteracciones: duraciones/easing favoritas; ¿transición entre pantallas? ¿Animación de temporizador?
  6. Escala de ánimo: nº de niveles (5 vs 7) y visual (emojis propios vs nativo). ¿Proveer assets SVG?
  7. Microcopy: aprueba/ajusta los textos de ejemplo y tono. ¿Tratamiento de "tú" o neutro?
  8. Catálogo de tareas: ¿proporcionas listado final o generamos propuesta para tu revisión?
  9. Auth: password clásico vs passcode de 6 dígitos; política "Recordarme" y expiración de sesión.
  10. Seguridad: ¿Argon2 disponible en el hosting? Si no, usaremos bcrypt.
  11. Entorno: versiones exactas de PHP y MySQL; ruta de deploy en cPanel; dominio/subdominio.

— Propuestas aprobadas —
- Iconografía: micro-set doodle propio (SVG) para MVP: reloj, play, stop, historial, shuffle, volver, y 5 caritas de ánimo → IMPLEMENTAR.
- Motion: 140ms; easing `cubic-bezier(.2,.8,.2,1)`; transición fade + translateY(8px); hover con leve elevación; focus ring 2px en acento → IMPLEMENTAR.
- Duración "10–15'": almacenar como bucket `10` y mostrar en UI "10–15'" → OK.
- "Recordarme": expiración 30 días; cookie segura + token persistente → OK.
- Repetición de tareas: evitar las últimas N=5 por duración → OK.

### Acciones pendientes inmediatas
- [USUARIO] Ejecutar `data/schema.sql` y luego `data/seed.sql` en la BD.
- [USUARIO] Confirmar `DB_HOST` correcto (por defecto `localhost`).
- [AMBOS] Crear repo GitHub y primer commit (sin `.env.php`).

## Lessons
- (vacío)
