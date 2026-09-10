-- Verification manuelle de l'isolation stricte (point 04) pour les
-- nouvelles tables de l'assistant IA (assistant_conversations,
-- assistant_messages), sur le meme principe que rls_test.sql : role non-
-- superuser (RLS est ignoree par un superuser/proprietaire de table).

CREATE ROLE ekklesia_app LOGIN PASSWORD 'test_only';
GRANT ALL ON ALL TABLES IN SCHEMA public TO ekklesia_app;

INSERT INTO ministries (id, name, short_code) VALUES
  ('11111111-1111-1111-1111-111111111111', 'RCV Benin', 'RCV-BJ'),
  ('22222222-2222-2222-2222-222222222222', 'Grace Assembly', 'GA-CI');

INSERT INTO users (id, name, email, password) VALUES
  ('aaaaaaaa-0000-0000-0000-000000000001', 'Pasteur A', 'a@rcv.test', 'x'),
  ('aaaaaaaa-0000-0000-0000-000000000002', 'Pasteur B', 'b@ga.test', 'x');

INSERT INTO assistant_conversations (id, ministry_id, user_id, created_at, updated_at) VALUES
  ('c1111111-0000-0000-0000-000000000000', '11111111-1111-1111-1111-111111111111', 'aaaaaaaa-0000-0000-0000-000000000001', now(), now()),
  ('c2222222-0000-0000-0000-000000000000', '22222222-2222-2222-2222-222222222222', 'aaaaaaaa-0000-0000-0000-000000000002', now(), now());

INSERT INTO assistant_messages (id, ministry_id, conversation_id, role, content, created_at, updated_at) VALUES
  ('d1111111-0000-0000-0000-000000000000', '11111111-1111-1111-1111-111111111111', 'c1111111-0000-0000-0000-000000000000', 'user', 'Comment créer une église ?', now(), now()),
  ('d2222222-0000-0000-0000-000000000000', '22222222-2222-2222-2222-222222222222', 'c2222222-0000-0000-0000-000000000000', 'user', 'Message du ministere concurrent', now(), now());

\echo '--- Sans app.current_ministry_id fixe : doit voir 0 ligne (fail closed) ---'
SET ROLE ekklesia_app;
SELECT count(*) AS conversations_visibles_sans_contexte FROM assistant_conversations;
SELECT count(*) AS messages_visibles_sans_contexte FROM assistant_messages;
RESET ROLE;

\echo '--- Avec le contexte du ministere RCV Benin : doit voir UNIQUEMENT sa conversation et son message ---'
SET ROLE ekklesia_app;
SELECT set_config('app.current_ministry_id', '11111111-1111-1111-1111-111111111111', false);
SELECT count(*) AS conversations_visibles, array_agg(user_id) AS users FROM assistant_conversations;
SELECT count(*) AS messages_visibles, array_agg(content) AS contenus FROM assistant_messages;

\echo '--- Tentative de lecture directe d une conversation du ministere concurrent par id exact : doit renvoyer 0 ligne ---'
SELECT count(*) FROM assistant_conversations WHERE id = 'c2222222-0000-0000-0000-000000000000';
SELECT count(*) FROM assistant_messages WHERE id = 'd2222222-0000-0000-0000-000000000000';

\echo '--- Tentative d ecriture croisee (INSERT avec ministry_id du concurrent) : doit etre rejetee par WITH CHECK ---'
INSERT INTO assistant_conversations (id, ministry_id, user_id, created_at, updated_at)
VALUES ('c9999999-0000-0000-0000-000000000000', '22222222-2222-2222-2222-222222222222', 'aaaaaaaa-0000-0000-0000-000000000001', now(), now());

RESET ROLE;
