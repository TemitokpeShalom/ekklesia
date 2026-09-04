-- Verification de l'historisation temporelle (point 02/13) : une
-- transformation (ici une promotion Cellule -> Eglise locale) doit fermer
-- la ligne d'historique en cours et en ouvrir une nouvelle, sans jamais
-- toucher a org_units.id (l'identite permanente).

SET ROLE ekklesia_app;
SELECT set_config('app.current_ministry_id', '11111111-1111-1111-1111-111111111111', false);

-- Etat initial : une cellule, avec sa premiere ligne d'historique (creation).
INSERT INTO org_units (id, ministry_id, parent_id, level_rank, level_label, name, code, path) VALUES
  ('d0000000-0000-0000-0000-000000000001', '11111111-1111-1111-1111-111111111111',
   'b1111111-0000-0000-0000-000000000001', 6, 'Cellule', 'Cellule Foi', 'CELLULE-1', 'rcv_bj.eglise_1.cellule_1');

INSERT INTO org_unit_history (ministry_id, org_unit_id, valid_from, valid_to, name, level_rank, level_label, parent_id, path, transformation_type, reason)
VALUES ('11111111-1111-1111-1111-111111111111', 'd0000000-0000-0000-0000-000000000001',
        '2026-01-01', NULL, 'Cellule Foi', 6, 'Cellule', 'b1111111-0000-0000-0000-000000000001',
        'rcv_bj.eglise_1.cellule_1', 'creation', 'Creation initiale');

\echo '--- Deuxieme ligne "encore en vigueur" pour le meme noeud : doit etre rejetee (org_unit_history_one_current_idx) ---'
INSERT INTO org_unit_history (ministry_id, org_unit_id, valid_from, valid_to, name, level_rank, level_label, parent_id, path, transformation_type, reason)
VALUES ('11111111-1111-1111-1111-111111111111', 'd0000000-0000-0000-0000-000000000001',
        '2026-02-01', NULL, 'Cellule Foi', 6, 'Cellule', 'b1111111-0000-0000-0000-000000000001',
        'rcv_bj.eglise_1.cellule_1', 'renommage', 'Ne doit pas passer');

\echo '--- Promotion correcte : fermer la ligne courante puis en ouvrir une nouvelle, en une transaction ---'
BEGIN;
  UPDATE org_unit_history SET valid_to = '2029-06-01'
    WHERE org_unit_id = 'd0000000-0000-0000-0000-000000000001' AND valid_to IS NULL;

  INSERT INTO org_unit_history (ministry_id, org_unit_id, valid_from, valid_to, name, level_rank, level_label, parent_id, path, transformation_type, requested_by, reason)
  VALUES ('11111111-1111-1111-1111-111111111111', 'd0000000-0000-0000-0000-000000000001',
          '2029-06-01', NULL, 'Eglise Foi', 5, 'Eglise locale', 'b1111111-0000-0000-0000-000000000000',
          'rcv_bj.eglise_foi', 'promotion', 'aaaaaaaa-0000-0000-0000-000000000001', 'Croissance de la cellule');

  -- org_units reflete l'etat courant : meme id, nouveau rang/parent/chemin.
  UPDATE org_units SET level_rank = 5, level_label = 'Eglise locale', name = 'Eglise Foi',
    parent_id = 'b1111111-0000-0000-0000-000000000000', path = 'rcv_bj.eglise_foi'
    WHERE id = 'd0000000-0000-0000-0000-000000000001';
COMMIT;

\echo '--- Identite permanente inchangee, etat courant mis a jour ---'
SELECT id, name, level_rank, path FROM org_units WHERE id = 'd0000000-0000-0000-0000-000000000001';

\echo '--- Historique complet du noeud : 2 lignes, dont une seule "encore en vigueur" ---'
SELECT valid_from, valid_to, name, level_rank, transformation_type
  FROM org_unit_history WHERE org_unit_id = 'd0000000-0000-0000-0000-000000000001'
  ORDER BY valid_from;

\echo '--- Reconstitution de l etat "au 2027-01-01" (avant la promotion) : doit renvoyer Cellule Foi / rang 6 ---'
SELECT name, level_rank, path FROM org_unit_history
  WHERE org_unit_id = 'd0000000-0000-0000-0000-000000000001'
    AND valid_from <= '2027-01-01' AND (valid_to IS NULL OR valid_to > '2027-01-01');

RESET ROLE;
