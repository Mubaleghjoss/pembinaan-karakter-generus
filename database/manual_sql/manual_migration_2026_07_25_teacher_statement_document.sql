ALTER TABLE teacher_profiles
    ADD COLUMN signature_path VARCHAR(255) NULL AFTER constraints,
    ADD COLUMN document_token_hash VARCHAR(64) NULL AFTER signature_path,
    ADD UNIQUE INDEX teacher_profiles_document_token_hash_unique (document_token_hash);
