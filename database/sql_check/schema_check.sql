-- Traduction SQL brute des migrations Fondations, utilisee UNIQUEMENT pour
-- verifier localement (via psql) que le schema est valide : extension
-- ltree, types, contraintes, index, policies RLS. Les migrations PHP dans
-- database/migrations/ restent la source de verite pour Laravel ; ce
-- fichier n'est pas execute par l'application.

CREATE EXTENSION IF NOT EXISTS ltree;
CREATE EXTENSION IF NOT EXISTS "pgcrypto"; -- gen_random_uuid()

CREATE TABLE ministries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name varchar(255) NOT NULL,
  short_code varchar(255) NOT NULL UNIQUE,
  status varchar(255) NOT NULL DEFAULT 'active',
  created_at timestamptz,
  updated_at timestamptz
);

CREATE TABLE users (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL UNIQUE,
  phone varchar(255),
  email_verified_at timestamptz,
  password varchar(255) NOT NULL,
  status varchar(255) NOT NULL DEFAULT 'active',
  remember_token varchar(100),
  created_at timestamptz,
  updated_at timestamptz
);

CREATE TABLE org_units (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  ministry_id uuid NOT NULL REFERENCES ministries(id),
  parent_id uuid REFERENCES org_units(id),
  level_rank smallint NOT NULL,
  level_label varchar(255) NOT NULL,
  name varchar(255) NOT NULL,
  code varchar(255) NOT NULL,
  metadata jsonb NOT NULL DEFAULT '{}',
  status varchar(255) NOT NULL DEFAULT 'active',
  created_at timestamptz,
  updated_at timestamptz,
  path ltree,
  UNIQUE (parent_id, code)
);
CREATE INDEX org_units_path_gist_idx ON org_units USING GIST (path);
CREATE INDEX org_units_path_btree_idx ON org_units USING BTREE (path);
CREATE INDEX org_units_ministry_rank_idx ON org_units (ministry_id, level_rank);

CREATE TABLE org_unit_history (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  ministry_id uuid NOT NULL REFERENCES ministries(id),
  org_unit_id uuid NOT NULL REFERENCES org_units(id),
  valid_from date NOT NULL,
  valid_to date,
  name varchar(255) NOT NULL,
  level_rank smallint NOT NULL,
  level_label varchar(255) NOT NULL,
  parent_id uuid REFERENCES org_units(id),
  transformation_type varchar(20) NOT NULL CHECK (transformation_type IN
    ('creation','promotion','rattachement','renommage','scission','fusion','fermeture')),
  requested_by uuid REFERENCES users(id),
  approved_by uuid REFERENCES users(id),
  reason text,
  created_at timestamptz NOT NULL DEFAULT now(),
  path ltree
);
CREATE INDEX org_unit_history_path_gist_idx ON org_unit_history USING GIST (path);
CREATE INDEX org_unit_history_lookup_idx ON org_unit_history (org_unit_id, valid_from);
CREATE UNIQUE INDEX org_unit_history_one_current_idx ON org_unit_history (org_unit_id) WHERE valid_to IS NULL;

CREATE TABLE roles (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code varchar(255) NOT NULL UNIQUE,
  label varchar(255) NOT NULL,
  is_deputy boolean NOT NULL DEFAULT false,
  default_permissions jsonb NOT NULL DEFAULT '{}',
  can_manage_users boolean NOT NULL DEFAULT false,
  created_at timestamptz,
  updated_at timestamptz
);

CREATE TABLE affectations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  ministry_id uuid NOT NULL REFERENCES ministries(id),
  user_id uuid NOT NULL REFERENCES users(id),
  org_unit_id uuid NOT NULL REFERENCES org_units(id),
  role_id uuid NOT NULL REFERENCES roles(id),
  status varchar(255) NOT NULL DEFAULT 'active',
  started_at date NOT NULL,
  ended_at date,
  assigned_by uuid REFERENCES users(id),
  revoked_by uuid REFERENCES users(id),
  revocation_reason text,
  created_at timestamptz,
  updated_at timestamptz
);
CREATE INDEX affectations_org_unit_status_idx ON affectations (org_unit_id, status);
CREATE INDEX affectations_user_status_idx ON affectations (user_id, status);
CREATE UNIQUE INDEX affectations_one_active_role_idx ON affectations (user_id, org_unit_id, role_id) WHERE status = 'active';

CREATE TABLE invitations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  ministry_id uuid NOT NULL REFERENCES ministries(id),
  org_unit_id uuid NOT NULL REFERENCES org_units(id),
  role_id uuid NOT NULL REFERENCES roles(id),
  email varchar(255),
  token_hash varchar(255) NOT NULL UNIQUE,
  status varchar(255) NOT NULL DEFAULT 'pending',
  invited_by uuid NOT NULL REFERENCES users(id),
  expires_at timestamptz NOT NULL,
  accepted_by uuid REFERENCES users(id),
  accepted_at timestamptz,
  created_at timestamptz,
  updated_at timestamptz
);
CREATE INDEX invitations_org_unit_status_idx ON invitations (org_unit_id, status);

CREATE TABLE attachment_codes (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  ministry_id uuid NOT NULL REFERENCES ministries(id),
  issuing_org_unit_id uuid NOT NULL REFERENCES org_units(id),
  target_level_rank smallint NOT NULL,
  code_hash varchar(255) NOT NULL UNIQUE,
  status varchar(255) NOT NULL DEFAULT 'pending',
  issued_by uuid NOT NULL REFERENCES users(id),
  expires_at timestamptz NOT NULL,
  used_by uuid REFERENCES users(id),
  used_at timestamptz,
  created_org_unit_id uuid REFERENCES org_units(id),
  created_at timestamptz,
  updated_at timestamptz
);
CREATE INDEX attachment_codes_issuing_status_idx ON attachment_codes (issuing_org_unit_id, status);

CREATE TABLE jobs (
  id bigserial PRIMARY KEY,
  queue varchar(255) NOT NULL,
  payload text NOT NULL,
  attempts smallint NOT NULL,
  reserved_at int,
  available_at int NOT NULL,
  created_at int NOT NULL
);
CREATE INDEX jobs_queue_idx ON jobs (queue);

CREATE TABLE job_batches (
  id varchar(255) PRIMARY KEY,
  name varchar(255) NOT NULL,
  total_jobs int NOT NULL,
  pending_jobs int NOT NULL,
  failed_jobs int NOT NULL,
  failed_job_ids text NOT NULL,
  options text,
  cancelled_at int,
  created_at int NOT NULL,
  finished_at int
);

CREATE TABLE failed_jobs (
  id bigserial PRIMARY KEY,
  uuid varchar(255) NOT NULL UNIQUE,
  connection text NOT NULL,
  queue text NOT NULL,
  payload text NOT NULL,
  exception text NOT NULL,
  failed_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE cache (
  key varchar(255) PRIMARY KEY,
  value text NOT NULL,
  expiration int NOT NULL
);

CREATE TABLE cache_locks (
  key varchar(255) PRIMARY KEY,
  owner varchar(255) NOT NULL,
  expiration int NOT NULL
);

-- RLS (point 04) sur les tables propres a un ministere.
DO $$
DECLARE
  t text;
BEGIN
  FOREACH t IN ARRAY ARRAY['org_units','org_unit_history','affectations','invitations','attachment_codes']
  LOOP
    EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY', t);
    EXECUTE format('ALTER TABLE %I FORCE ROW LEVEL SECURITY', t);
    EXECUTE format(
      'CREATE POLICY %I ON %I USING (ministry_id = current_setting(''app.current_ministry_id'', true)::uuid) '
      || 'WITH CHECK (ministry_id = current_setting(''app.current_ministry_id'', true)::uuid)',
      t || '_tenant_isolation', t
    );
  END LOOP;
END $$;
