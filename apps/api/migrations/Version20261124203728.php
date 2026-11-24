<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * H1 Identity: users and roles, device tokens, activation and recovery links, SMS log, audit log.
 * Tables generated from the mappings; CHECK constraints and triggers below are the last line of defence
 * described in formularios-05 (sección 10) and modelo-datos-01 (sección 5). Doctrine does not model them,
 * so they never show up as schema drift.
 */
final class Version20261124203728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Identity: app_user, app_user_role, access_token, activation_token, sms_message, audit_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE access_token (id UUID NOT NULL, last_used_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, token_hash VARCHAR(64) NOT NULL, profile VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_access_token_user_revoked ON access_token (user_id, revoked_at)');
        $this->addSql('CREATE UNIQUE INDEX uniq_access_token_hash ON access_token (token_hash)');
        $this->addSql('CREATE INDEX IDX_B6A2DD68A76ED395 ON access_token (user_id)');
        $this->addSql('CREATE TABLE activation_token (id UUID NOT NULL, expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, token_hash VARCHAR(64) NOT NULL, type VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_activation_token_hash ON activation_token (token_hash)');
        $this->addSql('CREATE INDEX IDX_B1B4826BA76ED395 ON activation_token (user_id)');
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, full_name VARCHAR(150) NOT NULL, mobile_phone VARCHAR(20) NOT NULL, email VARCHAR(254) DEFAULT NULL, operator_number VARCHAR(20) DEFAULT NULL, password_hash VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, status_reason VARCHAR(255) DEFAULT NULL, last_login_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, failed_login_attempts INT DEFAULT 0 NOT NULL, locked_until TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, recent_lockouts INT DEFAULT 0 NOT NULL, last_lockout_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, created_by UUID DEFAULT NULL, updated_by UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_mobile_phone ON app_user (mobile_phone)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_email ON app_user (email)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_operator_number ON app_user (operator_number)');
        $this->addSql('CREATE TABLE app_user_role (role VARCHAR(20) NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (user_id, role))');
        $this->addSql('CREATE INDEX IDX_85879996A76ED395 ON app_user_role (user_id)');
        $this->addSql('CREATE TABLE audit_log (id UUID NOT NULL, action VARCHAR(64) NOT NULL, occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, result VARCHAR(10) NOT NULL, actor_id UUID DEFAULT NULL, entity_type VARCHAR(64) DEFAULT NULL, entity_id UUID DEFAULT NULL, data_before JSONB DEFAULT NULL, data_after JSONB DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_audit_log_entity ON audit_log (entity_type, entity_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_audit_log_actor ON audit_log (actor_id, occurred_at)');
        $this->addSql('CREATE TABLE sms_message (id UUID NOT NULL, phone VARCHAR(20) NOT NULL, status VARCHAR(10) NOT NULL, driver VARCHAR(20) DEFAULT NULL, external_id VARCHAR(64) DEFAULT NULL, error VARCHAR(255) DEFAULT NULL, sent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, type VARCHAR(20) NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, recipient_user_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_sms_message_created_at ON sms_message (created_at)');
        $this->addSql('CREATE INDEX IDX_46A7FBA5B15EFB97 ON sms_message (recipient_user_id)');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE activation_token ADD CONSTRAINT FK_B1B4826BA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE app_user_role ADD CONSTRAINT FK_85879996A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE sms_message ADD CONSTRAINT FK_46A7FBA5B15EFB97 FOREIGN KEY (recipient_user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE');

        // ---- Enumerations, mirrored from the PHP enums ----
        $this->addSql("ALTER TABLE app_user ADD CONSTRAINT chk_app_user_status CHECK (status IN ('pending_activation', 'active', 'blocked', 'deactivated'))");
        $this->addSql("ALTER TABLE app_user ADD CONSTRAINT chk_app_user_mobile_phone CHECK (mobile_phone ~ '^\\+[1-9][0-9]{7,14}$')");
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT chk_app_user_email_lower CHECK (email = lower(email))');
        $this->addSql("ALTER TABLE app_user ADD CONSTRAINT chk_app_user_password_when_active CHECK (status = 'pending_activation' OR password_hash IS NOT NULL)");
        $this->addSql("ALTER TABLE app_user_role ADD CONSTRAINT chk_app_user_role_role CHECK (role IN ('ROLE_ADMIN', 'ROLE_OFICINA', 'ROLE_OPERARIO', 'ROLE_CONSULTA'))");
        $this->addSql("ALTER TABLE access_token ADD CONSTRAINT chk_access_token_profile CHECK (profile IN ('desktop', 'mobile'))");
        $this->addSql("ALTER TABLE activation_token ADD CONSTRAINT chk_activation_token_type CHECK (type IN ('activation', 'password_reset'))");
        $this->addSql("ALTER TABLE sms_message ADD CONSTRAINT chk_sms_message_type CHECK (type IN ('activation', 'password_reset'))");
        $this->addSql("ALTER TABLE sms_message ADD CONSTRAINT chk_sms_message_status CHECK (status IN ('queued', 'sent', 'failed'))");
        $this->addSql("ALTER TABLE audit_log ADD CONSTRAINT chk_audit_log_result CHECK (result IN ('success', 'denied'))");

        // ---- At least one active administrator (formularios-05, regla 3.3.4) ----
        // Deferred to commit, so a transaction may swap administrators. A table with no users at all is allowed:
        // that is a fresh installation, or fixtures being purged.
        $this->addSql(<<<'SQL'
            CREATE FUNCTION assert_active_admin_remains() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF EXISTS (SELECT 1 FROM app_user) AND NOT EXISTS (
                    SELECT 1 FROM app_user u JOIN app_user_role r ON r.user_id = u.id
                    WHERE u.status = 'active' AND r.role = 'ROLE_ADMIN'
                ) THEN
                    RAISE EXCEPTION 'There must always be at least one active administrator'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NULL;
            END
            $$
            SQL);
        $this->addSql('CREATE CONSTRAINT TRIGGER trg_app_user_active_admin AFTER UPDATE OF status OR DELETE ON app_user DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_active_admin_remains()');
        $this->addSql('CREATE CONSTRAINT TRIGGER trg_app_user_role_active_admin AFTER UPDATE OR DELETE ON app_user_role DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION assert_active_admin_remains()');

        // ---- The audit trail is insert-only ----
        $this->addSql(<<<'SQL'
            CREATE FUNCTION reject_audit_log_change() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'audit_log is append-only' USING ERRCODE = 'insufficient_privilege';
            END
            $$
            SQL);
        $this->addSql('CREATE TRIGGER trg_audit_log_append_only BEFORE UPDATE OR DELETE ON audit_log FOR EACH ROW EXECUTE FUNCTION reject_audit_log_change()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER trg_audit_log_append_only ON audit_log');
        $this->addSql('DROP FUNCTION reject_audit_log_change()');
        $this->addSql('DROP TRIGGER trg_app_user_role_active_admin ON app_user_role');
        $this->addSql('DROP TRIGGER trg_app_user_active_admin ON app_user');
        $this->addSql('DROP FUNCTION assert_active_admin_remains()');
        $this->addSql('ALTER TABLE access_token DROP CONSTRAINT FK_B6A2DD68A76ED395');
        $this->addSql('ALTER TABLE activation_token DROP CONSTRAINT FK_B1B4826BA76ED395');
        $this->addSql('ALTER TABLE app_user_role DROP CONSTRAINT FK_85879996A76ED395');
        $this->addSql('ALTER TABLE sms_message DROP CONSTRAINT FK_46A7FBA5B15EFB97');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE activation_token');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE app_user_role');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE sms_message');
    }
}
