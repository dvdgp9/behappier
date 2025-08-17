-- behappier — seed.sql (MVP)
-- Nota: el catálogo de 36 tareas lo aportarás tú. Este seed es INTENCIONALMENTE mínimo
-- para validar la app; puedes reemplazarlo más adelante.

INSERT INTO tasks (duration, title, guidance, active) VALUES
  -- 1 minuto (ejemplos provisionales)
  (1, 'Respira 4-4-4', 'Inhala 4, retén 4, exhala 4.', 1),
  (1, 'Estira cuello', 'Lento: derecha, izquierda, adelante, atrás.', 1),
  (1, 'Un vaso de agua', 'Pausa y bebe con atención plena.', 1),
  -- 5 minutos (ejemplos provisionales)
  (5, 'Paseo corto', 'Asómate a la ventana o sal a la puerta 5′.', 1),
  (5, 'Ordena tu mesa', 'Deja a la vista solo lo esencial.', 1),
  (5, 'Escribe 3 cosas', 'Tres líneas: cómo estás ahora.', 1),
  -- 10–15 minutos (bucket 10, ejemplos provisionales)
  (10, 'Camina sin prisa', 'Sin móvil, sintiendo el paso.', 1),
  (10, 'Lectura breve', '5–10 páginas con calma.', 1),
  (10, 'Espacio en casa', 'Elige una zona pequeña y renuévala.', 1);
