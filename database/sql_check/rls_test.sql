-- Verification manuelle de l'isolation stricte (point 04) et du chemin
-- materialise (point 02), avec deux ministeres distincts et un role non-superuser
-- (RLS est ignoree par un superuser/proprietaire de table, donc le test
-- doit tourner avec un role ordinaire pour etre probant).

CREATE ROLE ekklesia_app LOGIN PASSWORD 'test_only';
GRANT ALL ON ALL TABLES IN SCHEMA public TO ekklesia_app;

INSERT INTO ministries (id, name, short_code) VALUES
  ('11111111-1111-1111-1111-111111111111', 'RCV Benin', 'RCV-BJ'),
  ('22222222-2222-2222-2222-222222222222', 'Grace Assembly', 'GA-CI');

INSERT INTO users (id, name, email, password) VALUES
  ('aaaaaaaa-0000-0000-0000-000000000001', 'Pasteur A', 'a@rcv.test', 'x'),
  ('aaaaaaaa-0000-0000-0000-000000000002', 'Pasteur B', 'b@ga.test', 'x');

INSERT INTO org_units (id, ministry_id, parent_id, level_rank, level_label, name, code, path) VALUES
  ('b1111111-0000-0000-0000-000000000000', '11111111-1111-1111-1111-111111111111', NULL, 0, 'Ministere', 'RCV Benin', 'RACINE', 'rcv_bj'),
  ('b1111111-0000-0000-0000-000000000001', '11111111-1111-1111-1111-111111111111', 'b1111111-0000-0000-0000-000000000000', 5, 'Eglise locale', 'Eglise Grace Divine', 'EGLISE-1', 'rcv_bj.eglise_1'),
  ('b2222222-0000-0000-0000-000000000000', '22222222-2222-2222-2222-222222222222', NULL, 0, 'Ministere', 'Grace Assembly', 'RACINE', 'ga_ci');

\echo '--- Sans app.current_ministry_id fixe : doit voir 0 ligne (fail closed) ---'
SET ROLE ekklesia_app;
SELECT count(*) AS visible_sans_contexte FROM org_units;
RESET ROLE;

\echo '--- Avec le contexte du ministere RCV Benin : doit voir 2 lignes (les siennes uniquement) ---'
SET ROLE ekklesia_app;
SELECT set_config('app.current_ministry_id', '11111111-1111-1111-1111-111111111111', false);
SELECT count(*) AS visible_rcv, array_agg(name ORDER BY name) AS noms FROM org_units;

\echo '--- Une eglise sous ce chemin (test ltree, point 02) : doit trouver Eglise Grace Divine ---'
SELECT name FROM org_units WHERE path <@ 'rcv_bj'::ltree AND level_rank = 5;

\echo '--- Tentative de lecture d une ligne du ministere concurrent malgre un id exact : doit renvoyer 0 ligne ---'
SELECT count(*) FROM org_units WHERE id = 'b2222222-0000-0000-0000-000000000000';

\echo '--- Tentative d ecriture croisee (INSERT avec ministry_id du concurrent) : doit etre rejetee par WITH CHECK ---'
INSERT INTO org_units (id, ministry_id, parent_id, level_rank, level_label, name, code, path)
VALUES ('c0000000-0000-0000-0000-000000000000', '22222222-2222-2222-2222-222222222222', NULL, 0, 'Ministere', 'Intrusion', 'HACK', 'hack');

RESET ROLE;
