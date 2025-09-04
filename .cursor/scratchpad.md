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
- [x] Validar modelo de datos y plan SQL
- [x] Decidir modo de auth → Password + "Recordarme" (expiración 30 días)
- [x] Definir escala de estado de ánimo → 5 niveles (emojis doodle provisionales)
- [ ] Confirmar catálogo inicial de 36 tareas o recibir listado definitivo
- [ ] Confirmar entorno → PHP 8.3 (MySQL y Argon2 por confirmar)
- [ ] Alinear microcopy y tono (tratamiento de "tú" confirmado)
- [x] Acordar lógica de rotación/evitar repetición de tareas → evitar últimas N=5
- [ ] Plan de deploy en cPanel (dominio provisional: behappier.wthefox.com)
 - [x] Repo inicializado y primer push en GitHub
 - [x] Scaffolding básico: `index.php`, `register.php`, `home.php`, `task.php`, `history.php`, `logout.php`, `includes/*`, `partials/head.php`, `assets/styles.css`, `assets/app.js`
 - [ ] API endpoints JSON (si se desea en v1): `/api/tasks.php`, `/api/save.php`, `/api/history.php`

## Current Status / Progress Tracking
- Planner creado. Pendiente feedback de diseño y decisiones clave.
- UI: Integrado Iconoir; FAB Historial; icono logout con SVG inline; retirados atajos duplicados; fondo actualizado a `Fondo-behappier.jpg`; util `.desenfocado` lista (pendiente targets por pantalla).
- UI/Branding: Header con brandline (logo blanco + texto "Behappier").
- Personalización: Registro ahora captura `nombre` y backend guarda `users.nombre`; `home.php` saluda usando `nombre` (fallback a "Behappier").
- Datos: `data/schema.sql` actualizado para incluir `users.nombre` en instalaciones nuevas.
- UI/Home: `home.php` separa `hero` y `chooser` (botones apilados full‑width) con `.desenfocado` y animación escalonada.
- UI/Task: `task.php` adaptado al estilo glass `.desenfocado` (tarjeta principal, artículo de tarea y formulario post‑timer) para coherencia con Home.
- UI/Account: creada `account.php` (info: nombre, email, miembro desde). Incluye cambio de contraseña con CSRF y validaciones (actual correcta, nueva=confirmar, min 8). Invalida tokens `auth_tokens` y limpia cookie de "Recordarme" tras cambio. Incluye botón "Cerrar sesión".
- Navegación: en `partials/head.php` el icono de logout se ha sustituido por un icono de usuario que enlaza a `account.php`. Botones Inicio (abajo izq.) e Historial (abajo der.) mantienen estilo glass.
 - UX/Sonido: Añadido sonido al finalizar el temporizador (`assets/sfx/timer-end.mp3`). Preload y "primado" en el primer Start para evitar bloqueos de autoplay. Implementado en `assets/app.js` dentro de `finish()`.
 - UX/Timer: Botón "Reiniciar" cambiado a "Terminar" (`data-action="finish"`). Al pulsarlo, lleva el contador a 0 y dispara el mismo flujo de fin con sonido. Implementado en `task.php` y `assets/app.js`.
 - Datos/Import: Usuario confirmó columnas añadidas; BD aún con datos de muestra. Executor generará `data/tasks_import.sql` con `scripts/import_tasks_from_json.php` y pedirá ejecutar el SQL en MySQL. Luego validaremos conteos y encoding; después UI para `category` y `steps`.
 - UI/History (Executor): Rediseñado `history.php` a Variante A (rows con separadores). Aplicado `.desenfocado` al contenedor, eliminado estilos inline, creado badges (`.badge-duration`) para duración y chips (`.chip`) para fecha. Ajustes responsive y fix de overflow horizontal en móvil mediante `overflow-x:hidden`, wrapping y `min-width:0` en hijos flex. Estilos en `assets/styles.css`.

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

### Implementación MVP actual
- Autenticación con sesión y "Recordarme" (tabla `auth_tokens`).
- Páginas: login/registro (`index.php`, `register.php`), `home.php` (selector de duración), `task.php` (temporizador y guardado), `history.php` (últimas entradas).
- Temporizador en `assets/app.js`. Estilos base y textura en `assets/styles.css`.

## Planner Analysis: Post-Task Modal Redesign

### Contexto actual
El modal actual tras completar una tarea muestra:
- Título y pasos del ejercicio
- Pregunta "¿Cómo te sientes ahora?" (escala 1-5)
- Campo de nota opcional

### Análisis desde perspectiva psicológica y UX

**Problemas identificados:**
1. **Desconexión temporal**: Mostrar los pasos ya completados no aporta valor reflexivo
2. **Escala numérica abstracta**: Los números 1-5 carecen de contexto emocional significativo
3. **Momento inadecuado**: El usuario acaba de salir de un estado contemplativo y se le pide análisis cognitivo inmediato

**Principios budistas aplicables:**
- **Mindfulness del momento presente**: Capturar la experiencia inmediata sin análisis
- **No-juicio**: Evitar categorización rígida de estados internos
- **Impermanencia**: Reconocer que el estado actual es transitorio
- **Simplicidad**: Menos opciones, más presencia

### Top 3 Propuestas de Rediseño

#### Opción 1: "Registro Sensorial Inmediato"
**Concepto**: Capturar la experiencia corporal y emocional del momento sin etiquetas cognitivas.

**Interfaz**:
- Pregunta: "¿Cómo sientes tu cuerpo ahora?"
- 3 iconos táctiles: Tenso 😤 / Neutro 😐 / Relajado 😌
- Pregunta: "¿Qué palabra describe este momento?"
- Campo libre de 1-3 palabras (ej: "calma", "claridad", "inquieto")
- Botón único: "Registrar y continuar"

**Ventajas**: Conecta con sensaciones corporales (base del mindfulness), evita juicios numéricos, permite expresión personal auténtica.

#### Opción 2: "Check-in Contemplativo"
**Concepto**: Micro-meditación de cierre que integra la experiencia sin forzar análisis.

**Interfaz**:
- Texto: "Toma una respiración profunda..."
- Animación sutil de respiración (3-4 segundos)
- Pregunta: "¿Qué se queda contigo de estos minutos?"
- 4 opciones visuales: Una semilla 🌱 (crecimiento) / Una gota 💧 (calma) / Una llama 🔥 (energía) / Una nube ☁️ (ligereza)
- Campo opcional: "Una palabra o frase" (máx 20 caracteres)

**Ventajas**: Mantiene el estado contemplativo, usa metáforas naturales, integra micro-ritual de cierre.

#### Opción 3: "Gratitud y Continuidad"
**Concepto**: Enfoque en apreciación y conexión con la intención de cuidado personal.

**Interfaz**:
- Texto: "Gracias por dedicarte estos minutos"
- Pregunta: "¿Por qué te alegras de haber hecho esta pausa?"
- 3 opciones: "Me siento más presente" / "He conectado conmigo" / "He cuidado mi bienestar"
- Pregunta de continuidad: "¿Cuándo volverás a cuidarte así?"
- 3 opciones temporales: "En unas horas" / "Mañana" / "Cuando lo necesite"

**Ventajas**: Refuerza comportamiento positivo, crea intención futura, evita auto-evaluación crítica.

### Decisión Final: Registro Diario de Estado Anímico

**Concepto aprobado**: Modal como registro diario con "¿Cómo te sientes hoy?"

**Características clave**:
- **Una entrada por día**: El modal captura el estado anímico diario, no por ejercicio
- **Persistencia inteligente**: Si ya hay registro del día, muestra la selección actual con opción de cambiar
- **Visión a largo plazo**: Datos para análisis temporal y gráficas futuras
- **Tono cercano y explicativo**: Inspirado en Opción 3 pero enfocado en seguimiento diario

**Implementación requerida**:
1. Nueva tabla `daily_moods` (user_id, date, mood, note, created_at, updated_at)
2. Modal actualizado con lógica de "ya registrado hoy" vs "primer registro"
3. Integración en task.php para mostrar/actualizar registro diario
4. Base para futuras vistas históricas y analytics

**Ventajas**:
- Reduce fricción (no pregunta en cada ejercicio)
- Crea hábito de auto-reflexión diaria
- Genera datos valiosos para insights personales
- Mantiene conexión emocional sin ser invasivo

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

 - Importación de tareas (acción requerida del usuario): Una vez generado `data/tasks_import.sql`, por favor ejecútalo en tu MySQL y comparte resultados de validación (conteos por duración y JSON_VALID en `steps`). Si hay errores/encoding raro, te preparo script de diagnóstico.

— Propuestas aprobadas —
- Iconografía: micro-set doodle propio (SVG) para MVP: reloj, play, stop, historial, shuffle, volver, y 5 caritas de ánimo → IMPLEMENTAR.
- Motion: 140ms; easing `cubic-bezier(.2,.8,.2,1)`; transición fade + translateY(8px); hover con leve elevación; focus ring 2px en acento → IMPLEMENTAR.
- Duración "10–15'": almacenar como bucket `10` y mostrar en UI "10–15'" → OK.
- "Recordarme": expiración 30 días; cookie segura + token persistente → OK.
- Repetición de tareas: evitar las últimas N=5 por duración → OK.

### Acciones pendientes inmediatas
- [USUARIO] Ejecutar ALTER TABLE para añadir `users.nombre` en la BD existente (ver snippet en chat) y luego probar registro/login + saludo personalizado.
- [USUARIO] QA rápido: registro/login, selector de duración, temporizador, guardado y revisión en `history.php`.
- [USUARIO] Probar "Recordarme" (cerrar navegador y volver a `home.php`).
- [USUARIO] Confirmar `DB_HOST` (si es distinto de `localhost`).
- [AMBOS] Ajustes visuales (tamaños, márgenes, tipografías) e iconos doodle.

## Lessons
- Para añadir una columna NOT NULL en una tabla existente: 1) ADD COLUMN como NULL; 2) Rellenar valores; 3) MODIFY a NOT NULL. Evita errores en BD con filas existentes.
