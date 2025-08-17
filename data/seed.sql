-- behappier — seed.sql (MVP)
-- Nota: el catálogo de 36 tareas lo aportarás tú. Este seed es INTENCIONALMENTE mínimo
-- para validar la app; puedes reemplazarlo más adelante.

INSERT INTO tasks (duration, title, guidance, active) VALUES
  -- 1 minuto (ejemplos provisionales)
  (1, 'Respira 4-4-4', 'Inhala 4s, retén 4s, exhala 4s. Repite tres veces.', 1),
  (1, 'Estira cuello', 'Movimientos lentos: derecha, izquierda, adelante y atrás sin forzar.', 1),
  (1, 'Un vaso de agua', 'Sirve un vaso y bébelo despacio, nota temperatura y sabor.', 1),
  -- 5 minutos (ejemplos provisionales)
  (5, 'Paseo corto', 'Asómate a la ventana o sal a la puerta 5′. Observa colores y sonidos.', 1),
  (5, 'Ordena tu mesa', 'Retira lo innecesario y limpia una pequeña zona.', 1),
  (5, 'Escribe 3 cosas', 'Anota tres líneas sobre cómo estás y qué necesitas.', 1),
  -- 10–15 minutos (bucket 10, ejemplos provisionales)
  (10, 'Camina sin prisa', 'Camina 10–15′ sin móvil, siente pies y respiración.', 1),
  (10, 'Lectura breve', 'Lee 5–10 páginas con calma. Subraya una idea que te guste.', 1),
  (10, 'Espacio en casa', 'Elige una zona pequeña y renuévala: dobla, tira o coloca.', 1);
